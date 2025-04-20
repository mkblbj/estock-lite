import React, { useEffect } from 'react';
import { observer } from 'mobx-react-lite';
import courierStore from '../../store/CourierStore';
import { Courier } from '../../services/courierService';

// 假设这是v0.dev生成的组件，根据实际情况替换
import CourierManager from './CourierManager'; // 从v0.dev生成的组件导入

/**
 * 快递种类管理容器组件
 * 负责连接状态管理与UI组件
 */
const CourierContainer: React.FC = observer(() => {
  // 在组件挂载时加载数据
  useEffect(() => {
    loadCouriers();
  }, []);

  // 加载快递种类列表
  const loadCouriers = async () => {
    await courierStore.loadCouriers();
  };

  // 创建快递种类
  const handleCreate = async (data: Omit<Courier, 'id' | 'created_at' | 'updated_at'>) => {
    return await courierStore.createCourier(data);
  };

  // 更新快递种类
  const handleUpdate = async (id: number, data: Partial<Courier>) => {
    return await courierStore.updateCourier(id, data);
  };

  // 删除快递种类
  const handleDelete = async (id: number) => {
    return await courierStore.deleteCourier(id);
  };

  // 切换快递种类状态
  const handleToggleStatus = async (id: number, isActive: boolean) => {
    return await courierStore.toggleCourierStatus(id, isActive);
  };

  // 重新排序快递种类
  const handleReorder = async (items: { id: number; sort_order: number }[]) => {
    return await courierStore.reorderCouriers(items);
  };

  // 弹窗操作
  const handleOpenModal = (mode: 'create' | 'edit', courier?: Courier) => {
    courierStore.openModal(mode, courier);
  };

  const handleCloseModal = () => {
    courierStore.closeModal();
  };

  // 组装props传递给UI组件
  const props = {
    couriers: courierStore.couriers,
    loading: courierStore.loading,
    error: courierStore.error,
    currentCourier: courierStore.currentCourier,
    formErrors: courierStore.formErrors,
    modalVisible: courierStore.modalVisible,
    modalMode: courierStore.modalMode,
    onOpenModal: handleOpenModal,
    onCloseModal: handleCloseModal,
    onCreate: handleCreate,
    onUpdate: handleUpdate,
    onDelete: handleDelete,
    onToggleStatus: handleToggleStatus,
    onReorder: handleReorder,
    onRefresh: loadCouriers
  };

  // 渲染UI组件
  return <CourierManager {...props} />;
});

export default CourierContainer; 