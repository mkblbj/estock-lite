import React from 'react';
import Card from './Card';

// 递归渲染子组件
const renderChildren = (children) => {
  if (!children) return null;
  
  // 如果是字符串或普通元素
  if (typeof children === 'string' || typeof children === 'number' || React.isValidElement(children)) {
    return children;
  }
  
  // 如果是数组，对每一个项进行处理
  if (Array.isArray(children)) {
    return children.map((child, index) => {
      // 对每个子项递归调用renderChildren
      if (typeof child === 'object' && child.type) {
        const Component = Card[child.type];
        if (!Component) {
          console.error(`组件类型 ${child.type} 不存在`);
          return null;
        }
        return (
          <Component key={index} {...(child.props || {})}>
            {renderChildren(child.props?.children)}
          </Component>
        );
      }
      return renderChildren(child);
    });
  }
  
  return null;
};

const CardDemo = ({ children }) => {
  return (
    <Card className="w-full">
      {renderChildren(children)}
    </Card>
  );
};

export default CardDemo; 