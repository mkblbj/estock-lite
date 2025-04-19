import React from 'react';
import { createRoot } from 'react-dom/client';
import './index.css';
import * as Components from './components';

// 调试信息
console.log('React脚本已加载');
console.log('可用组件:', Object.keys(Components));

// 全局React应用模式
const reactRoot = document.getElementById('react-root');
console.log('React根元素:', reactRoot);

if (reactRoot) {
  try {
    console.log('尝试渲染React根应用');
    const root = createRoot(reactRoot);
    root.render(
      <React.StrictMode>
        <div className="react-app">
          <h1>React应用已成功加载</h1>
          {/* 这里可以放置页面级组件 */}
        </div>
      </React.StrictMode>
    );
    console.log('React根应用渲染完成');
  } catch (error) {
    console.error('React根应用渲染失败:', error);
  }
}

// 组件嵌入模式
document.addEventListener('DOMContentLoaded', () => {
  console.log('DOM已加载完成，开始查找React组件容器');
  const containers = document.querySelectorAll('.react-component');
  console.log('找到React组件容器数量:', containers.length);
  
  containers.forEach(container => {
    const componentName = container.dataset.component;
    const propsJson = container.dataset.props || '{}';
    console.log('正在渲染组件:', componentName, '属性:', propsJson);
    
    let props = {};
    
    try {
      props = JSON.parse(propsJson);
    } catch (e) {
      console.error('无法解析组件属性:', e);
    }
    
    if (Components[componentName]) {
      try {
        const root = createRoot(container);
        root.render(
          React.createElement(Components[componentName], props)
        );
        console.log(`组件 ${componentName} 渲染成功`);
      } catch (error) {
        console.error(`组件 ${componentName} 渲染失败:`, error);
      }
    } else {
      console.error(`React组件 ${componentName} 未找到`);
    }
  });
}); 