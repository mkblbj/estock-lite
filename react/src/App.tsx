import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import CourierContainer from './components/courier/CourierContainer';

const App: React.FC = () => {
  return (
    <Router>
      <Routes>
        <Route path="/couriers" element={<CourierContainer />} />
        {/* 其他路由将在实现对应故事时添加 */}
        <Route path="/" element={<div>每日发货统计系统</div>} />
      </Routes>
    </Router>
  );
};

export default App; 