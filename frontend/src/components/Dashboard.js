import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getShippingRecords } from '../api/shippingAPI';
import { getCouriers } from '../api/courierAPI';

const Dashboard = () => {
  const [stats, setStats] = useState({
    totalShipping: 0,
    todayShipping: 0,
    totalCouriers: 0,
    activeCouriers: 0
  });
  
  const [recentRecords, setRecentRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        
        // 获取今天的日期
        const today = new Date().toISOString().slice(0, 10);
        
        // 获取所有快递公司
        const couriersResponse = await getCouriers();
        const couriers = couriersResponse.data || [];
        const activeCouriers = couriers.filter(c => c.is_active).length;
        
        // 获取今日发货记录
        const todayRecordsResponse = await getShippingRecords({
          date: today,
          sortBy: 'created_at',
          sortOrder: 'DESC'
        });
        const todayRecords = todayRecordsResponse.data?.records || [];
        const todayTotal = todayRecords.reduce((sum, record) => sum + record.quantity, 0);
        
        // 获取最近的发货记录
        const recentRecordsResponse = await getShippingRecords({
          page: 1,
          perPage: 5,
          sortBy: 'created_at',
          sortOrder: 'DESC'
        });
        
        const recentRecords = recentRecordsResponse.data?.records || [];
        const totalShipping = recentRecordsResponse.data?.pagination?.total || 0;
        
        setStats({
          totalShipping,
          todayShipping: todayTotal,
          totalCouriers: couriers.length,
          activeCouriers
        });
        
        setRecentRecords(recentRecords);
        setLoading(false);
      } catch (err) {
        console.error('获取数据失败:', err);
        setError('获取数据失败，请稍后重试');
        setLoading(false);
      }
    };
    
    fetchData();
  }, []);

  if (loading) {
    return <div className="loading">正在加载数据...</div>;
  }

  if (error) {
    return <div className="error">{error}</div>;
  }

  return (
    <div className="dashboard">
      <h2>欢迎使用快递管理系统</h2>
      
      <div className="stats-container">
        <div className="stat-card">
          <h3>快递公司</h3>
          <div className="stat-value">{stats.totalCouriers}</div>
          <div className="stat-detail">活跃: {stats.activeCouriers}</div>
          <Link to="/couriers" className="stat-link">管理快递公司</Link>
        </div>
        
        <div className="stat-card">
          <h3>发货记录</h3>
          <div className="stat-value">{stats.totalShipping}</div>
          <div className="stat-detail">今日: {stats.todayShipping}</div>
          <Link to="/shipping" className="stat-link">查看发货记录</Link>
        </div>
        
        <div className="stat-card">
          <h3>快速操作</h3>
          <div className="quick-links">
            <Link to="/shipping/new" className="quick-link">添加发货记录</Link>
            <Link to="/shipping/batch" className="quick-link">批量添加记录</Link>
            <Link to="/couriers/new" className="quick-link">添加快递公司</Link>
          </div>
        </div>
      </div>
      
      <div className="recent-records">
        <h3>最近发货记录</h3>
        {recentRecords.length > 0 ? (
          <table>
            <thead>
              <tr>
                <th>日期</th>
                <th>快递公司</th>
                <th>数量</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              {recentRecords.map(record => (
                <tr key={record.id}>
                  <td>{record.date}</td>
                  <td>{record.courier_name}</td>
                  <td>{record.quantity}</td>
                  <td>
                    <Link to={`/shipping/edit/${record.id}`}>编辑</Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : (
          <p>暂无发货记录</p>
        )}
        
        <div className="view-all">
          <Link to="/shipping">查看所有记录</Link>
        </div>
      </div>
    </div>
  );
};

export default Dashboard; 