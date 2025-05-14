/*!
 * 礼花效果JS - 用于每日弹窗
 */

// 创建礼花效果函数
function createConfetti(container) {
  // 创建礼花容器
  const confettiContainer = document.createElement('div');
  confettiContainer.className = 'confetti-container';
  confettiContainer.style.cssText = 'position: absolute; left: 0; top: 0; width: 200px; height: 200px; pointer-events: none; z-index: 9999; overflow: hidden;';
  container.appendChild(confettiContainer);
  
  // 创建多彩礼花
  const colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4CAF50', '#8BC34A', '#FFEB3B', '#FFC107', '#FF9800', '#FF5722'];
  
  // 创建30个礼花元素
  for (let i = 0; i < 30; i++) {
    setTimeout(() => {
      const confetti = document.createElement('div');
      const color = colors[Math.floor(Math.random() * colors.length)];
      const size = Math.random() * 10 + 5; // 5-15px
      const angle = Math.random() * 360; // 随机角度
      const x = Math.random() * 100; // 随机水平位置
      const duration = Math.random() * 3 + 2; // 2-5秒动画时长
      
      confetti.className = 'confetti';
      confetti.style.cssText = `
        position: absolute;
        left: ${x}px;
        top: 0;
        width: ${size}px;
        height: ${size}px;
        background-color: ${color};
        opacity: 0.7;
        border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
        transform: rotate(${angle}deg);
        animation: confetti-fall ${duration}s ease-in-out forwards;
      `;
      
      confettiContainer.appendChild(confetti);
      
      // 动画结束后移除元素
      setTimeout(() => {
        confetti.remove();
      }, duration * 1000);
      
    }, i * 50); // 错开时间发射礼花
  }
  
  // 3秒后移除容器
  setTimeout(() => {
    confettiContainer.remove();
  }, 5000);
}

// 添加礼花动画样式
function addConfettiStyle() {
  const styleElement = document.createElement('style');
  styleElement.textContent = `
    @keyframes confetti-fall {
      0% {
        transform: translateY(0) rotate(0deg);
        opacity: 0.7;
      }
      25% {
        transform: translateY(30px) translateX(15px) rotate(90deg);
        opacity: 0.8;
      }
      50% {
        transform: translateY(60px) translateX(-15px) rotate(180deg);
        opacity: 0.9;
      }
      75% {
        transform: translateY(100px) translateX(15px) rotate(270deg);
        opacity: 0.7;
      }
      100% {
        transform: translateY(150px) translateX(0) rotate(360deg);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(styleElement);
}

// 初始化
function initConfetti() {
  addConfettiStyle();
}

// 导出
window.DailyPopupConfetti = {
  init: initConfetti,
  create: createConfetti
}; 