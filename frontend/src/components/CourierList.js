import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getCouriers, deleteCourier, toggleCourierStatus, reorderCouriers } from '../api/courierAPI';

const CourierList = () => {
  const [couriers, setCouriers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [statusFilter, setStatusFilter] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [isDragging, setIsDragging] = useState(false);
  const [draggedIndex, setDraggedIndex] = useState(null);

  // 加载快递公司数据
  const loadCouriers = async () => {
    try {
      setLoading(true);
      const response = await getCouriers({
        status: statusFilter !== 'all' ? statusFilter : undefined,
        search: searchTerm || undefined,
        sortBy: 'sort_order',
        sortOrder: 'ASC'
      });
      setCouriers(response.data || []);
      setLoading(false);
    } catch (err) {
      console.error('获取快递公司失败:', err);
      setError('加载快递公司失败，请稍后重试');
      setLoading(false);
    }
  };

  useEffect(() => {
    loadCouriers();
  }, [statusFilter, searchTerm]);

  // 删除快递公司
  const handleDelete = async (id, name) => {
    if (window.confirm(`确定要删除快递公司 "${name}" 吗？`)) {
      try {
        await deleteCourier(id);
        // 重新加载数据
        loadCouriers();
      } catch (err) {
        console.error('删除快递公司失败:', err);
        setError('删除失败，请稍后重试');
      }
    }
  };

  // 切换快递公司状态
  const handleToggleStatus = async (id, currentStatus) => {
    try {
      await toggleCourierStatus(id);
      // 重新加载数据
      loadCouriers();
    } catch (err) {
      console.error('切换状态失败:', err);
      setError('操作失败，请稍后重试');
    }
  };

  // 处理拖拽开始
  const handleDragStart = (e, index) => {
    setDraggedIndex(index);
    setIsDragging(true);
    e.dataTransfer.effectAllowed = 'move';
    // 设置鼠标样式
    e.target.style.cursor = 'grabbing';
  };

  // 处理拖拽结束
  const handleDragEnd = (e) => {
    setIsDragging(false);
    setDraggedIndex(null);
    // 恢复鼠标样式
    e.target.style.cursor = 'grab';
  };

  // 处理拖拽经过
  const handleDragOver = (e, index) => {
    e.preventDefault();
    if (draggedIndex === null) return;
    
    if (draggedIndex !== index) {
      // 创建新的数组并更新排序
      const newCouriers = [...couriers];
      const draggedItem = newCouriers[draggedIndex];
      
      // 移除拖拽的元素
      newCouriers.splice(draggedIndex, 1);
      // 在新位置插入
      newCouriers.splice(index, 0, draggedItem);
      
      // 更新状态和拖拽索引
      setCouriers(newCouriers);
      setDraggedIndex(index);
    }
  };

  // 保存排序
  const handleSaveOrder = async () => {
    try {
      // 准备要发送的数据
      const items = couriers.map((courier, index) => ({
        id: courier.id,
        sort_order: index + 1
      }));
      
      await reorderCouriers({ items });
      // 重新加载数据
      loadCouriers();
    } catch (err) {
      console.error('保存排序失败:', err);
      setError('保存排序失败，请稍后重试');
    }
  };

  if (loading && couriers.length === 0) {
    return <div className="loading">正在加载快递公司...</div>;
  }

  return (
    <div className="courier-list">
      <div className="list-header">
        <h2>快递公司管理</h2>
        <Link to="/couriers/new" className="btn-add">添加快递公司</Link>
      </div>
      
      <div className="filters">
        <div className="search-box">
          <input
            type="text"
            placeholder="搜索快递公司..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
        
        <div className="status-filter">
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="all">全部状态</option>
            <option value="active">仅显示启用</option>
            <option value="inactive">仅显示禁用</option>
          </select>
        </div>
      </div>
      
      {error && <div className="error-message">{error}</div>}
      
      {couriers.length === 0 ? (
        <div className="no-data">没有找到符合条件的快递公司</div>
      ) : (
        <>
          <div className="drag-hint">
            <span>拖拽公司名称可调整显示顺序 </span>
            <button 
              className="btn-save-order" 
              onClick={handleSaveOrder}
              disabled={loading}
            >
              保存排序
            </button>
          </div>
          
          <div className="courier-table-container">
            <table className="courier-table">
              <thead>
                <tr>
                  <th>排序</th>
                  <th>公司名称</th>
                  <th>代码</th>
                  <th>状态</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                {couriers.map((courier, index) => (
                  <tr 
                    key={courier.id}
                    className={draggedIndex === index ? 'dragging' : ''}
                  >
                    <td>{index + 1}</td>
                    <td
                      draggable
                      onDragStart={(e) => handleDragStart(e, index)}
                      onDragEnd={handleDragEnd}
                      onDragOver={(e) => handleDragOver(e, index)}
                      className="draggable-cell"
                    >
                      <span className="drag-handle">☰</span>
                      {courier.name}
                    </td>
                    <td>{courier.code}</td>
                    <td>
                      <span className={`status ${courier.is_active ? 'active' : 'inactive'}`}>
                        {courier.is_active ? '启用' : '禁用'}
                      </span>
                    </td>
                    <td>
                      <div className="action-buttons">
                        <Link to={`/couriers/edit/${courier.id}`} className="btn-edit">
                          编辑
                        </Link>
                        <button
                          className={`btn-toggle ${courier.is_active ? 'deactivate' : 'activate'}`}
                          onClick={() => handleToggleStatus(courier.id, courier.is_active)}
                        >
                          {courier.is_active ? '禁用' : '启用'}
                        </button>
                        <button
                          className="btn-delete"
                          onClick={() => handleDelete(courier.id, courier.name)}
                        >
                          删除
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}
    </div>
  );
};

export default CourierList; 