/**
 * 响应式表格表头列宽拖拽调整
 * 支持Bootstrap 4表格
 */
(function() {
  // 初始化表格列宽调整功能
  function initResizableTables() {
    const tables = document.querySelectorAll('.table-resizable');
    
    tables.forEach(table => {
      const headers = table.querySelectorAll('thead th');
      const tableWidth = table.offsetWidth;
      
      // 添加调整手柄到每个表头
      headers.forEach(th => {
        // 处理表头内容，为其添加容器，便于处理溢出
        if (!th.querySelector('.th-inner')) {
          const content = th.innerHTML;
          const innerDiv = document.createElement('div');
          innerDiv.classList.add('th-inner');
          innerDiv.innerHTML = content;
          th.innerHTML = '';
          th.appendChild(innerDiv);
          
          // 检测标题长度，自动应用换行类
          if (innerDiv.textContent.length > 15) {
            th.classList.add('th-wrap');
          }
        }

        if (!th.querySelector('.resizer')) {
          const resizer = document.createElement('div');
          resizer.classList.add('resizer');
          th.appendChild(resizer);
          
          // 设置初始列宽
          if (!th.style.width) {
            // 根据内容自动设置初始宽度
            const contentWidth = Math.min(
              Math.max(th.offsetWidth, 80), // 最小80px
              Math.min(300, tableWidth / 4) // 最大300px或表格宽度的1/4
            );
            
            // 设置初始宽度为百分比
            th.style.width = (contentWidth / tableWidth * 100) + '%';
          }
          
          // 添加拖拽事件处理
          setupResizerEvents(th, resizer, table);
          
          // 双击自动调整列宽事件
          th.addEventListener('dblclick', function(e) {
            // 避免点击在调整手柄上
            if (e.target !== resizer) {
              autoAdjustColumnWidth(th, table);
            }
          });
        }
      });
    });
  }
  
  // 自动调整列宽到内容宽度
  function autoAdjustColumnWidth(th, table) {
    // 获取当前列索引
    const thIndex = Array.from(th.parentNode.children).indexOf(th);
    
    // 设置临时宽度为auto以便测量内容宽度
    th.style.width = 'auto';
    
    // 计算最大内容宽度（考虑表头的宽度）
    let maxWidth = Math.max(th.offsetWidth, 80); // 最小宽度80px
    const rows = table.querySelectorAll('tbody tr');
    
    // 检查此列中所有单元格，找出最宽的内容
    rows.forEach(row => {
      const cell = row.children[thIndex];
      if (cell) {
        // 临时克隆并测量内容宽度
        const clone = cell.cloneNode(true);
        clone.style.width = 'auto';
        clone.style.position = 'absolute';
        clone.style.visibility = 'hidden';
        clone.style.whiteSpace = 'nowrap';
        document.body.appendChild(clone);
        
        // 获取内容宽度并添加一些内边距
        const contentWidth = clone.offsetWidth + 30; // 额外的内边距
        if (contentWidth > maxWidth) {
          maxWidth = contentWidth;
        }
        
        document.body.removeChild(clone);
      }
    });
    
    // 限制最大宽度（防止单个列过宽导致表格过长）
    maxWidth = Math.min(maxWidth, 300); // 最大宽度300px
    
    // 设置新的列宽
    th.style.width = maxWidth + 'px';
    
    // 检查标题长度，决定是否应用换行类
    const titleLength = th.textContent.trim().length;
    if (titleLength > 15) {
      th.classList.add('th-wrap');
    } else {
      th.classList.remove('th-wrap');
    }
    
    // 保存列宽设置
    saveColumnWidths(table);
  }
  
  // 设置列宽调整手柄的事件
  function setupResizerEvents(th, resizer, table) {
    let startX, startWidth;
    
    function onMouseDown(e) {
      startX = e.pageX;
      startWidth = th.offsetWidth;
      th.classList.add('resizing');
      
      // 添加拖拽过程中的事件监听
      document.addEventListener('mousemove', onMouseMove);
      document.addEventListener('mouseup', onMouseUp);
      
      // 阻止表格内其他事件
      e.preventDefault();
      e.stopPropagation();
    }
    
    function onMouseMove(e) {
      if (th.classList.contains('resizing')) {
        const width = startWidth + (e.pageX - startX);
        if (width > 50) { // 最小宽度限制
          th.style.width = width + 'px';
          
          // 根据新宽度决定是否应该换行
          if (width < 120 && th.textContent.trim().length > 10) {
            th.classList.add('th-wrap');
          } else if (width >= 120) {
            th.classList.remove('th-wrap');
          }
        }
      }
    }
    
    function onMouseUp() {
      th.classList.remove('resizing');
      
      // 移除拖拽过程中的事件监听
      document.removeEventListener('mousemove', onMouseMove);
      document.removeEventListener('mouseup', onMouseUp);
      
      // 保存列宽设置到本地存储
      saveColumnWidths(table);
    }
    
    // 为调整手柄添加鼠标按下事件
    resizer.addEventListener('mousedown', onMouseDown);
  }
  
  // 保存列宽设置
  function saveColumnWidths(table) {
    if (!table.id) return; // 需要表格有ID才能保存设置
    
    const headers = table.querySelectorAll('thead th');
    const settings = [];
    
    headers.forEach(th => {
      settings.push({
        width: th.style.width,
        wrap: th.classList.contains('th-wrap')
      });
    });
    
    // 保存到本地存储
    if (window.localStorage) {
      localStorage.setItem('table_' + table.id + '_settings', JSON.stringify(settings));
    }
  }
  
  // 加载保存的列宽设置
  function loadColumnWidths() {
    const tables = document.querySelectorAll('.table-resizable[id]');
    
    tables.forEach(table => {
      if (window.localStorage) {
        const savedSettings = localStorage.getItem('table_' + table.id + '_settings');
        
        if (savedSettings) {
          const settings = JSON.parse(savedSettings);
          const headers = table.querySelectorAll('thead th');
          
          headers.forEach((th, index) => {
            if (settings[index]) {
              th.style.width = settings[index].width;
              
              if (settings[index].wrap) {
                th.classList.add('th-wrap');
              } else {
                th.classList.remove('th-wrap');
              }
            }
          });
        }
      }
    });
  }
  
  // 重置表格列宽
  function resetTableWidths(tableId) {
    if (window.localStorage) {
      localStorage.removeItem('table_' + tableId + '_settings');
    }
    
    const table = document.getElementById(tableId);
    if (table) {
      const headers = table.querySelectorAll('thead th');
      const tableWidth = table.offsetWidth;
      
      headers.forEach(th => {
        // 重置为默认宽度
        th.style.width = (th.offsetWidth / tableWidth * 100) + '%';
        
        // 检查文本长度，决定是否应用换行类
        const titleLength = th.textContent.trim().length;
        if (titleLength > 15) {
          th.classList.add('th-wrap');
        } else {
          th.classList.remove('th-wrap');
        }
      });
    }
  }
  
  // 为全局添加重置表格宽度的方法
  window.resetTableWidths = resetTableWidths;
  
  // 自动优化表格宽度
  function optimizeTableWidths(tableId) {
    const table = document.getElementById(tableId);
    if (table) {
      const headers = table.querySelectorAll('thead th');
      headers.forEach(th => {
        autoAdjustColumnWidth(th, table);
      });
    }
  }
  
  // 为全局添加优化表格宽度的方法
  window.optimizeTableWidths = optimizeTableWidths;
  
  // 为表格添加自动换行功能
  function addTextWrappingToTables() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
      const cells = table.querySelectorAll('tbody td');
      
      cells.forEach(cell => {
        // 跳过操作列或已处理的单元格
        if (cell.classList.contains('fit-content') || 
            cell.classList.contains('dtr-control') || 
            cell.children.length > 0 && 
            (cell.querySelector('.btn') || cell.querySelector('.dropdown'))) {
          return;
        }
        
        // 应用文本换行样式
        cell.classList.add('text-wrap');
        
        // 处理非常长的文本
        const text = cell.textContent.trim();
        if (text.length > 100) {
          handleLongContent(cell, text);
        }
      });
    });
  }
  
  // 处理单元格内的长内容
  function handleLongContent(cell, text) {
    // 清空单元格
    cell.innerHTML = '';
    
    // 创建可展开容器
    const container = document.createElement('div');
    container.classList.add('expandable-cell');
    container.textContent = text;
    
    // 创建展开/收起切换按钮
    const toggle = document.createElement('span');
    toggle.classList.add('expand-toggle');
    toggle.textContent = '更多...';
    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      container.classList.toggle('expanded');
      toggle.textContent = container.classList.contains('expanded') ? '收起' : '更多...';
    });
    
    // 将元素添加到单元格
    container.appendChild(toggle);
    cell.appendChild(container);
  }
  
  // 初始化处理
  document.addEventListener('DOMContentLoaded', function() {
    // 初始化可调整列宽的表格
    initResizableTables();
    
    // 加载保存的列宽设置
    loadColumnWidths();
    
    // 添加表格自动换行功能
    addTextWrappingToTables();
    
    // 公开全局方法
    window.resetTableWidths = resetTableWidths;
    window.optimizeTableWidths = optimizeTableWidths;
  });
  
  // 为AJAX加载的表格添加监听
  $(document).on('init.dt', function(e, settings) {
    // 在DataTables初始化后应用处理
    setTimeout(function() {
      const table = $(settings.nTable).closest('.table')[0];
      if (table) {
        addTextWrappingToTables();
      }
    }, 200);
  });
})(); 