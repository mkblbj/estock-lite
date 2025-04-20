import React from 'react';
import { Courier } from '../../services/courierService';

// 组件Props类型定义
interface CourierManagerProps {
  couriers: Courier[];
  loading: boolean;
  error: string | null;
  currentCourier: Courier | null;
  formErrors: Record<string, string>;
  modalVisible: boolean;
  modalMode: 'create' | 'edit';
  onOpenModal: (mode: 'create' | 'edit', courier?: Courier) => void;
  onCloseModal: () => void;
  onCreate: (data: Omit<Courier, 'id' | 'created_at' | 'updated_at'>) => Promise<any>;
  onUpdate: (id: number, data: Partial<Courier>) => Promise<any>;
  onDelete: (id: number) => Promise<any>;
  onToggleStatus: (id: number, isActive: boolean) => Promise<any>;
  onReorder: (items: { id: number; sort_order: number }[]) => Promise<any>;
  onRefresh: () => void;
}

/**
 * 快递种类管理组件
 * 注意：这是一个占位组件，实际UI组件将由v0.dev生成
 */
const CourierManager: React.FC<CourierManagerProps> = (props) => {
  const { 
    couriers, 
    loading, 
    error, 
    onOpenModal, 
    onRefresh 
  } = props;

  // 这只是一个占位组件，实际内容会由v0.dev生成的组件替代
  return (
    <div className="courier-manager">
      <div className="header">
        <h2>快递种类管理</h2>
        <button onClick={() => onOpenModal('create')}>添加快递种类</button>
        <button onClick={onRefresh}>刷新</button>
      </div>
      
      {loading && <div>加载中...</div>}
      {error && <div className="error">{error}</div>}
      
      <div className="courier-list">
        {couriers.length === 0 ? (
          <div>暂无快递种类数据</div>
        ) : (
          <table>
            <thead>
              <tr>
                <th>名称</th>
                <th>代码</th>
                <th>状态</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              {couriers.map(courier => (
                <tr key={courier.id}>
                  <td>{courier.name}</td>
                  <td>{courier.code}</td>
                  <td>{courier.is_active ? '激活' : '禁用'}</td>
                  <td>
                    <button onClick={() => onOpenModal('edit', courier)}>编辑</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
      
      <div className="footer">
        <p>注意：此组件仅为占位，实际UI将由v0.dev生成</p>
      </div>
    </div>
  );
};

export default CourierManager; 