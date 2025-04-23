import React from 'react';
import { BrowserRouter as Router, Routes, Route, Link } from 'react-router-dom';
import './App.css';

// 导入组件
import CourierList from './components/courier/CourierList';
import CourierForm from './components/courier/CourierForm';
import ShippingList from './components/shipping/ShippingList';
import ShippingForm from './components/shipping/ShippingForm';
import ShippingBatchForm from './components/shipping/ShippingBatchForm';
import Dashboard from './components/Dashboard';

function App() {
  return (
    <Router>
      <div className="app">
        <header className="app-header">
          <div className="container">
            <h1>快递管理系统</h1>
            <nav>
              <ul>
                <li><Link to="/">首页</Link></li>
                <li><Link to="/couriers">快递公司</Link></li>
                <li><Link to="/shipping">发货记录</Link></li>
              </ul>
            </nav>
          </div>
        </header>

        <main className="container">
          <Routes>
            <Route path="/" element={<Dashboard />} />
            
            <Route path="/couriers" element={<CourierList />} />
            <Route path="/couriers/new" element={<CourierForm />} />
            <Route path="/couriers/edit/:id" element={<CourierForm />} />
            
            <Route path="/shipping" element={<ShippingList />} />
            <Route path="/shipping/new" element={<ShippingForm />} />
            <Route path="/shipping/edit/:id" element={<ShippingForm />} />
            <Route path="/shipping/batch" element={<ShippingBatchForm />} />
          </Routes>
        </main>

        <footer className="app-footer">
          <div className="container">
            <p>&copy; {new Date().getFullYear()} 快递管理系统</p>
          </div>
        </footer>
      </div>
    </Router>
  );
}

export default App; 