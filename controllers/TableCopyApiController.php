<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TableCopyApiController extends BaseApiController
{
    /**
     * 复制表格行数据
     */
    public function CopyTableRow(Request $request, Response $response, array $args)
    {
        try {
            // 对于userobjects，使用MASTER_DATA_EDIT权限
            User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);
            
            $entity = $args['entity'];
            $objectId = $args['objectId'];
            
            if (!$this->IsValidExposedEntity($entity)) {
                return $this->GenericErrorResponse($response, 'Entity does not exist or is not exposed', 400);
            }
            
            if ($this->IsEntityWithNoEdit($entity)) {
                return $this->GenericErrorResponse($response, 'This entity does not support editing', 400);
            }
            
            if ($this->IsEntityWithEditRequiresAdmin($entity)) {
                User::checkPermission($request, User::PERMISSION_ADMIN);
            }
            
            // 特殊处理userobjects - 使用直接SQL查询避免ORM问题
            if ($entity === 'userobjects') {
                try {
                    $sql = "SELECT * FROM userobjects WHERE id = ?";
                    $pdo = $this->getDatabaseService()->GetDbConnectionRaw();
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$objectId]);
                    $originalData = $stmt->fetch(\PDO::FETCH_ASSOC);
                    
                    if (!$originalData) {
                        return $this->GenericErrorResponse($response, 'Original object not found', 404);
                    }
                } catch (\Exception $ex) {
                    return $this->GenericErrorResponse($response, 'Failed to fetch original data: ' . $ex->getMessage());
                }
            } else {
                // 获取原始数据
                $originalRow = $this->getDatabase()->{$entity}($objectId);
                if ($originalRow == null) {
                    return $this->GenericErrorResponse($response, 'Original object not found', 404);
                }
                
                // 准备复制数据
                try {
                    $originalData = $originalRow->toArray();
                } catch (\Exception $ex) {
                    return $this->GenericErrorResponse($response, 'Failed to convert original data to array: ' . $ex->getMessage());
                }
            }
            
            $copyData = $this->prepareCopyData($originalData, $entity);
            
            // 特殊处理userobjects - 它需要保留userentity_id
            if ($entity === 'userobjects') {
                $copyData['userentity_id'] = $originalData['userentity_id'];
            }
            
            // 调试日志
            error_log("TableCopyApi: Original data for {$entity} ID {$objectId}: " . json_encode($originalData));
            error_log("TableCopyApi: Copy data for {$entity}: " . json_encode($copyData));
            
            // 创建新记录
            if ($entity === 'userobjects') {
                // 对userobjects使用直接SQL插入
                try {
                    // 构建动态SQL插入语句
                    $fields = array_keys($copyData);
                    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
                    $fieldsList = implode(',', $fields);
                    
                    $sql = "INSERT INTO userobjects ({$fieldsList}) VALUES ({$placeholders})";
                    error_log("TableCopyApi: SQL for userobjects: " . $sql);
                    error_log("TableCopyApi: Values: " . json_encode(array_values($copyData)));
                    
                    $pdo = $this->getDatabaseService()->GetDbConnectionRaw();
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_values($copyData));
                    $newObjectId = $pdo->lastInsertId();
                } catch (\Exception $ex) {
                    return $this->GenericErrorResponse($response, 'Failed to create new userobject: ' . $ex->getMessage());
                }
            } else {
                $newRow = $this->getDatabase()->{$entity}()->createRow($copyData);
                $newRow->save();
                $newObjectId = $this->getDatabase()->lastInsertId();
            }
            
            // 复制用户字段（如果存在）
            $this->copyUserFields($entity, $objectId, $newObjectId);
            
            // 对于userobjects，确保用户字段复制成功
            if ($entity === 'userobjects') {
                error_log("TableCopyApi: Copying userfields for userobject {$objectId} -> {$newObjectId}");
            }
            
            return $this->ApiResponse($response, [
                'success' => true,
                'message' => 'Row copied successfully',
                'original_id' => $objectId,
                'new_id' => $newObjectId,
                'entity' => $entity
            ]);
            
        } catch (\Exception $ex) {
            return $this->GenericErrorResponse($response, $ex->getMessage());
        }
    }
    
    /**
     * 准备复制数据（移除不应该复制的字段）
     */
    private function prepareCopyData($originalData, $entity)
    {
        $excludeFields = [
            'id',
            'row_created_timestamp',
            'created_timestamp', 
            'last_updated',
            'row_updated_timestamp'
        ];
        
        $copyData = [];
        
        foreach ($originalData as $key => $value) {
            if (!in_array($key, $excludeFields)) {
                // 包含所有非排除字段，即使值为null、空字符串或0
                $copyData[$key] = $value;
            }
        }
        
        // 为某些实体添加特殊处理
        $copyData = $this->applyEntitySpecificCopyRules($copyData, $entity);
        
        return $copyData;
    }
    
    /**
     * 应用实体特定的复制规则
     */
    private function applyEntitySpecificCopyRules($copyData, $entity)
    {
        switch ($entity) {
            case 'products':
                // 产品复制时，在名称后添加"(副本)"
                if (isset($copyData['name'])) {
                    $copyData['name'] = $copyData['name'] . ' (副本)';
                }
                break;
                
            case 'tasks':
                // 任务复制时，重置状态为待处理
                $copyData['done'] = 0;
                $copyData['done_timestamp'] = null;
                break;
                
            case 'recipes':
                // 菜谱复制时，在名称后添加"(副本)"
                if (isset($copyData['name'])) {
                    $copyData['name'] = $copyData['name'] . ' (副本)';
                }
                break;
                
            case 'chores':
                // 家务复制时，重置跟踪状态
                $copyData['last_tracked_time'] = null;
                $copyData['next_estimated_execution_time'] = null;
                break;
                
            case 'batteries':
                // 电池复制时，在名称后添加"(副本)"
                if (isset($copyData['name'])) {
                    $copyData['name'] = $copyData['name'] . ' (副本)';
                }
                break;
                
            case 'userobjects':
                // 用户对象复制时，可能需要重置某些字段
                // 具体规则取决于对象类型
                break;
        }
        
        return $copyData;
    }
    
    /**
     * 复制用户字段
     */
    private function copyUserFields($entity, $originalId, $newId)
    {
        try {
            // 对于userobjects，需要使用特殊的实体名称来查找用户字段
            $userfieldEntity = $entity;
            if ($entity === 'userobjects') {
                // 使用SQL查询获取userentity信息
                try {
                    $sql = "SELECT ue.name FROM userobjects uo JOIN userentities ue ON uo.userentity_id = ue.id WHERE uo.id = ?";
                    $pdo = $this->getDatabaseService()->GetDbConnectionRaw();
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$originalId]);
                    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        $userfieldEntity = 'userentity-' . $result['name'];
                        error_log("TableCopyApi: Found userentity name: {$userfieldEntity} for userobject {$originalId}");
                    } else {
                        error_log("TableCopyApi: No userentity found for userobject {$originalId}");
                        return;
                    }
                } catch (\Exception $ex) {
                    error_log("Failed to get userentity name for userobject {$originalId}: " . $ex->getMessage());
                    return;
                }
            }
            
            // 使用直接SQL查询来避免LessQL join问题
            $sql = "SELECT uv.field_id, uv.value 
                    FROM userfield_values uv 
                    JOIN userfields uf ON uf.id = uv.field_id 
                    WHERE uf.entity = ? AND uv.object_id = ?";
            
            $pdo = $this->getDatabaseService()->GetDbConnectionRaw();
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userfieldEntity, $originalId]);
            $userfields = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $copiedCount = 0;
            foreach ($userfields as $userfield) {
                error_log("TableCopyApi: Copying userfield {$userfield['field_id']} with value: {$userfield['value']}");
                
                // 使用直接SQL插入来避免ORM问题
                $insertSql = "INSERT INTO userfield_values (field_id, object_id, value) VALUES (?, ?, ?)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([$userfield['field_id'], $newId, $userfield['value']]);
                $copiedCount++;
            }
            
            error_log("TableCopyApi: Copied {$copiedCount} userfields for {$entity} {$originalId} -> {$newId}");
            
        } catch (\Exception $ex) {
            // 用户字段复制失败不应该影响主要复制操作
            error_log("Failed to copy userfields for {$entity} {$originalId} -> {$newId}: " . $ex->getMessage());
        }
    }
    
    /**
     * 检查实体是否有效且可公开访问
     */
    private function IsValidExposedEntity($entity)
    {
        $validEntities = [
            'products', 'product_groups', 'quantity_units', 'locations', 'shopping_locations',
            'recipes', 'tasks', 'chores', 'users', 'equipment', 'userfields', 'userentities',
            'batteries', 'task_categories', 'meal_plan_sections', 'api_keys', 'userobjects'
        ];
        
        return in_array($entity, $validEntities);
    }
    
    /**
     * 检查实体是否不允许编辑
     */
    private function IsEntityWithNoEdit($entity)
    {
        $noEditEntities = ['stock_log', 'stock_current', 'batteries_log', 'chores_log'];
        return in_array($entity, $noEditEntities);
    }
    
    /**
     * 检查实体是否需要管理员权限才能编辑
     */
    private function IsEntityWithEditRequiresAdmin($entity)
    {
        $adminOnlyEntities = ['users', 'userfields', 'userentities'];
        return in_array($entity, $adminOnlyEntities);
    }
} 