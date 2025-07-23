/**
 * 通用表格复制功能处理器
 * 为所有表格添加复制行的功能
 */

console.log('table-copy-handler.js loaded at', new Date().toISOString());
class TableCopyHandler {
    constructor() {
        console.log('TableCopyHandler constructor called');
        this.initializeTables();
    }

    /**
     * 初始化所有表格的复制功能
     */
    initializeTables() {
        // 为所有DataTable添加复制按钮
        const tables = document.querySelectorAll('.table, .dataTable');
        console.log(`Found ${tables.length} tables:`, tables);
        
        if (tables.length === 0) {
            console.warn('No tables found with .table or .dataTable class');
            return;
        }
        
        tables.forEach((table, index) => {
            console.log(`Processing table ${index + 1}:`, table.id || 'no-id', table);
            this.addCopyButtonsToTable(table);
        });
    }

    /**
     * 为表格添加复制按钮
     * @param {HTMLElement} table 表格元素
     */
    addCopyButtonsToTable(table) {
        const tbody = table.querySelector('tbody');
        let rows = table.querySelectorAll('tbody tr');
        
        // 如果没有找到tbody中的行，尝试直接查找表格中的所有行
        if (rows.length === 0) {
            rows = table.querySelectorAll('tr');
            console.log(`No tbody rows found, trying all rows: ${rows.length}`);
        }
        
        console.log(`Found ${rows.length} rows in table:`, table.id || 'no-id');
        console.log('Table tbody classes:', tbody?.className || 'no tbody');
        console.log('Table tbody style:', tbody?.style?.display || 'no style');
        
        if (rows.length === 0) {
            console.warn('No rows found in table:', table);
            return;
        }
        
        // 过滤掉表头行
        const dataRows = Array.from(rows).filter(row => {
            // 跳过在thead中的行
            const isHeaderRow = row.closest('thead') !== null;
            // 跳过只包含th元素的行
            const hasOnlyHeaders = row.querySelectorAll('th').length > 0 && row.querySelectorAll('td').length === 0;
            return !isHeaderRow && !hasOnlyHeaders;
        });
        
        console.log(`Filtered to ${dataRows.length} data rows (excluding headers)`);
        
        if (dataRows.length === 0) {
            console.warn('No data rows found after filtering headers');
            return;
        }
        
        let addedButtons = 0;
        dataRows.forEach((row, index) => {
            const firstCell = row.querySelector('td');
            if (firstCell && !firstCell.querySelector('.copy-row-btn')) {
                console.log(`Adding copy button to row ${index + 1}`);
                this.addCopyButtonToRow(row, firstCell);
                addedButtons++;
            } else if (!firstCell) {
                console.warn(`Row ${index + 1} has no td elements:`, row);
            } else {
                console.log(`Row ${index + 1} already has copy button`);
            }
        });
        
        console.log(`Added ${addedButtons} copy buttons to table:`, table.id || 'no-id');
    }

    /**
     * 为单行添加复制按钮
     * @param {HTMLElement} row 表格行
     * @param {HTMLElement} firstCell 第一个单元格
     */
    addCopyButtonToRow(row, firstCell) {
        // 检查是否已经有复制按钮，避免重复添加
        if (firstCell.querySelector('.copy-row-btn')) {
            console.log('Copy button already exists in this cell, skipping...');
            return;
        }
        
        const copyButton = document.createElement('button');
        copyButton.className = 'btn btn-outline-success btn-sm copy-row-btn';
        copyButton.title = '复制此行';
        copyButton.innerHTML = '<i class="fa-solid fa-copy"></i>';
        copyButton.setAttribute('data-toggle', 'tooltip');
        
        // 获取行数据和ID
        const entityType = this.getEntityType(row.closest('table'));
        const objectId = this.getObjectId(row, entityType);
        
        if (!objectId) {
            copyButton.disabled = true;
            copyButton.title = '无法获取行ID，不能复制';
            copyButton.className = 'btn btn-outline-secondary btn-sm copy-row-btn';
        }
        
        copyButton.addEventListener('click', (e) => {
            console.log('Copy button clicked!', {
                objectId,
                entityType,
                row
            });
            e.preventDefault();
            e.stopPropagation();
            if (objectId) {
                this.handleCopyRow(objectId, entityType, row);
            } else {
                console.error('No objectId found, cannot copy row');
            }
        });

        // 将复制按钮添加到操作列的末尾，确保在其他按钮下方
        firstCell.appendChild(copyButton);
        
        // 确保操作列有足够的宽度和正确的样式
        if (!firstCell.style.minWidth) {
            firstCell.style.minWidth = '120px';
        }
    }

    /**
     * 获取对象ID
     * @param {HTMLElement} row 表格行
     * @param {string} entityType 实体类型
     * @returns {string|null} 对象ID
     */
    getObjectId(row, entityType) {
        console.log(`Getting object ID for entity type: ${entityType}`);
        
        // 尝试从不同的data属性获取ID
        const possibleIdKeys = [
            `${entityType.replace('_', '-')}-id`,
            `${entityType.replace('_', '')}-id`,
            'id',
            'object-id',
            'item-id'
        ];
        
        console.log('Available row data attributes:', Object.keys(row.dataset));
        
        for (const key of possibleIdKeys) {
            if (row.dataset[key]) {
                console.log(`Found ID ${row.dataset[key]} from data attribute: ${key}`);
                return row.dataset[key];
            }
        }
        
        // 尝试从编辑链接获取ID
        const editLinks = row.querySelectorAll('a[href]');
        for (const link of editLinks) {
            const href = link.getAttribute('href');
            // 匹配各种可能的URL格式
            const patterns = [
                new RegExp(`/${entityType.replace('_', '')}/(\\d+)(?:\\?|$)`),
                new RegExp(`/${entityType}/(\\d+)(?:\\?|$)`),
                new RegExp(`/product/(\\d+)(?:\\?|$)`), // 特殊处理产品
                new RegExp(`/task/(\\d+)(?:\\?|$)`),   // 特殊处理任务
                new RegExp(`/recipe/(\\d+)(?:\\?|$)`), // 特殊处理菜谱
                new RegExp(`/user/(\\d+)(?:\\?|$)`),   // 特殊处理用户
                new RegExp(`/(\\d+)(?:\\?|$)`)         // 通用数字匹配
            ];
            
            for (const pattern of patterns) {
                const matches = href.match(pattern);
                if (matches) {
                    console.log(`Found ID ${matches[1]} from link: ${href}`);
                    return matches[1];
                }
            }
        }
        
        // 尝试从任何包含数字的data属性
        for (const [key, value] of Object.entries(row.dataset)) {
            if (/id$/i.test(key) && /^\d+$/.test(value)) {
                return value;
            }
        }
        
        return null;
    }

    /**
     * 获取实体类型
     * @param {HTMLElement} table 表格元素
     * @returns {string} 实体类型
     */
    getEntityType(table) {
        const tableId = table.id;
        console.log(`Getting entity type for table ID: ${tableId}`);
        
        // 首先尝试精确匹配
        const exactEntityMap = {
            'products-table': 'products',
            'stock-overview-table': 'products',
            'stockentries-table': 'products',
            'shoppinglist-table': 'shopping_list',
            'tasks-table': 'tasks',
            'recipes-table': 'recipes',
            'users-table': 'users',
            'locations-table': 'locations',
            'productgroups-table': 'product_groups',
            'quantityunits-table': 'quantity_units',
            'chores-table': 'chores',
            'chores-overview-table': 'chores',
            'equipment-table': 'equipment',
            'batteries-table': 'batteries',
            'batteries-overview-table': 'batteries',
            'userentities-table': 'userentities',
            'userfields-table': 'userfields',
            'stock-journal-table': 'stock_log',
            'batteries-journal-table': 'batteries_log',
            'chores-journal-table': 'chores_log',
            'apikeys-table': 'api_keys',
            'shoppinglocations-table': 'shopping_locations',
            'taskcategories-table': 'task_categories',
            'mealplansections-table': 'meal_plan_sections'
        };
        
        // 精确匹配
        if (exactEntityMap[tableId]) {
            const entityType = exactEntityMap[tableId];
            console.log(`Exact match: '${tableId}' → '${entityType}'`);
            return entityType;
        }
        
        // 模式匹配（支持动态ID）
        const patternMatches = [
            { pattern: /^userobjects-table-\d+$/, entity: 'userobjects' },
            { pattern: /^products-table-\d+$/, entity: 'products' },
            { pattern: /^tasks-table-\d+$/, entity: 'tasks' },
            { pattern: /^recipes-table-\d+$/, entity: 'recipes' },
            { pattern: /^users-table-\d+$/, entity: 'users' },
            { pattern: /^chores-table-\d+$/, entity: 'chores' },
            { pattern: /^equipment-table-\d+$/, entity: 'equipment' },
            { pattern: /^batteries-table-\d+$/, entity: 'batteries' },
            { pattern: /^userentities-table-\d+$/, entity: 'userentities' },
            { pattern: /^userfields-table-\d+$/, entity: 'userfields' },
            { pattern: /userobjects-table/, entity: 'userobjects' }, // 通用匹配
            { pattern: /products-table/, entity: 'products' },
            { pattern: /tasks-table/, entity: 'tasks' },
            { pattern: /recipes-table/, entity: 'recipes' },
            { pattern: /users-table/, entity: 'users' },
            { pattern: /chores-table/, entity: 'chores' },
            { pattern: /equipment-table/, entity: 'equipment' },
            { pattern: /batteries-table/, entity: 'batteries' }
        ];
        
        for (const match of patternMatches) {
            if (match.pattern.test(tableId)) {
                console.log(`Pattern match: '${tableId}' matches ${match.pattern} → '${match.entity}'`);
                return match.entity;
            }
        }
        
        console.log(`No match found for table ID '${tableId}', returning 'unknown'`);
        return 'unknown';
    }

    /**
     * 处理复制行操作
     * @param {string} objectId 对象ID
     * @param {string} entityType 实体类型
     * @param {HTMLElement} originalRow 原始行元素
     */
    handleCopyRow(objectId, entityType, originalRow) {
        // 获取行的显示名称用于确认对话框
        const displayName = this.getRowDisplayName(originalRow);
        
        // 显示确认对话框
        bootbox.confirm({
            title: '确认复制',
            message: `确定要复制这行数据吗？<br><br><strong>项目：</strong>${displayName}<br><strong>类型：</strong>${entityType}<br><strong>ID：</strong>${objectId}`,
            buttons: {
                confirm: {
                    label: '确认复制',
                    className: 'btn-success'
                },
                cancel: {
                    label: '取消',
                    className: 'btn-secondary'
                }
            },
            callback: (result) => {
                if (result) {
                    this.performCopy(objectId, entityType, originalRow);
                }
            }
        });
    }
    
    /**
     * 获取行的显示名称
     * @param {HTMLElement} row 表格行
     * @returns {string} 显示名称
     */
    getRowDisplayName(row) {
        // 尝试从第二列获取名称（通常是名称列）
        const secondCell = row.querySelector('td:nth-child(2)');
        if (secondCell) {
            const text = secondCell.textContent.trim();
            if (text) {
                return text.length > 50 ? text.substring(0, 50) + '...' : text;
            }
        }
        
        return '未知项目';
    }

    /**
     * 执行复制操作
     * @param {string} objectId 对象ID
     * @param {string} entityType 实体类型
     * @param {HTMLElement} originalRow 原始行元素
     */
    async performCopy(objectId, entityType, originalRow) {
        try {
            // 显示加载提示
            toastr.info('正在复制数据...', '复制操作');
            
            // 调用API复制数据
            const response = await this.callCopyApi(entityType, objectId);
            
            // 检查响应是否表示成功（可能有不同的成功标识）
            const isSuccess = response.success === true || 
                             response.message === 'Row copied successfully' || 
                             (response.new_id && response.original_id);
            
            if (isSuccess) {
                toastr.success('数据复制成功！', '复制操作');
                
                // 延迟刷新表格，让用户看到成功消息
                setTimeout(() => {
                    this.refreshTable(originalRow.closest('table'));
                }, 1500);
            } else {
                throw new Error(response.message || '复制失败');
            }
            
        } catch (error) {
            console.error('复制操作失败:', error);
            toastr.error(`复制失败: ${error.message}`, '复制操作');
        }
    }

    /**
     * 调用复制API
     * @param {string} entityType 实体类型
     * @param {string} objectId 对象ID
     * @returns {Promise<Object>} API响应
     */
    async callCopyApi(entityType, objectId) {
        const apiUrl = `/api/table-copy/${entityType}/${objectId}`;
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error_message || `HTTP ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API Response:', result);
        return result;
    }

    /**
     * 刷新表格
     * @param {HTMLElement} table 表格元素
     */
    refreshTable(table) {
        console.log('Refreshing table:', table.id);
        
        // 检查是否是DataTable
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(table)) {
            const dataTable = $(table).DataTable();
            
            // 检查DataTable是否配置了Ajax数据源
            if (dataTable.ajax && dataTable.ajax.url && typeof dataTable.ajax.url === 'function') {
                console.log('Table has Ajax data source, reloading...');
                try {
                    dataTable.ajax.reload(null, false); // 保持当前页面
                } catch (error) {
                    console.error('Ajax reload failed:', error);
                    console.log('Falling back to page reload');
                    window.location.reload();
                }
            } else {
                console.log('Table has no Ajax data source, using page reload');
                window.location.reload();
            }
        } else {
            console.log('Not a DataTable, using page reload');
            window.location.reload();
        }
    }
}

// 全局变量存储处理器实例
window.tableCopyHandlerInstance = null;

// 初始化函数
function initTableCopyHandler() {
    console.log('Initializing TableCopyHandler...');
    try {
        if (window.tableCopyHandlerInstance) {
            console.log('TableCopyHandler already exists, reinitializing...');
        }
        window.tableCopyHandlerInstance = new TableCopyHandler();
        console.log('TableCopyHandler initialized successfully');
    } catch (error) {
        console.error('Failed to initialize TableCopyHandler:', error);
    }
}

// 检查表格是否可见并初始化
function checkAndInitialize() {
    const tables = document.querySelectorAll('.table, .dataTable');
    let visibleTables = 0;
    
    tables.forEach(table => {
        const tbody = table.querySelector('tbody');
        if (tbody && !tbody.classList.contains('d-none')) {
            visibleTables++;
        }
    });
    
    console.log(`Found ${visibleTables} visible tables out of ${tables.length} total tables`);
    
    if (visibleTables > 0) {
        initTableCopyHandler();
        return true;
    }
    return false;
}

// 多种初始化策略
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    // 立即检查
    if (checkAndInitialize()) {
        return;
    }
    
    // 使用MutationObserver监听DOM变化
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const target = mutation.target;
                if (target.tagName === 'TBODY' && target.classList.contains('d-none') === false) {
                    console.log('Table tbody became visible, initializing...');
                    setTimeout(() => {
                        checkAndInitialize();
                    }, 200);
                }
            }
        });
    });
    
    // 观察所有tbody元素
    const tbodies = document.querySelectorAll('tbody.d-none');
    console.log(`Setting up observers for ${tbodies.length} hidden tbody elements`);
    tbodies.forEach((tbody, index) => {
        console.log(`Observing tbody ${index + 1}:`, tbody.closest('table')?.id || 'no-table-id');
        observer.observe(tbody, { attributes: true, attributeFilter: ['class'] });
    });
    
    // 延迟初始化作为后备方案
    let attempts = 0;
    const maxAttempts = 10;
    const checkInterval = setInterval(() => {
        attempts++;
        console.log(`Delayed initialization attempt ${attempts}/${maxAttempts}...`);
        if (checkAndInitialize() || attempts >= maxAttempts) {
            clearInterval(checkInterval);
            if (attempts >= maxAttempts) {
                console.warn('Max initialization attempts reached');
            }
        }
    }, 1000);
});

// 页面完全加载后再次尝试
window.addEventListener('load', function() {
    console.log('Window Load event');
    setTimeout(() => {
        console.log('Window load delayed initialization...');
        checkAndInitialize();
    }, 1000);
});

// 如果jQuery可用，使用jQuery的ready事件
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        console.log('jQuery ready');
        setTimeout(() => {
            console.log('jQuery ready delayed initialization...');
            checkAndInitialize();
        }, 2000);
    });
}

// 如果有新的表格行动态添加，需要重新初始化
window.TableCopyHandler = TableCopyHandler;

// 提供全局方法供其他脚本调用
window.initTableCopyButtons = function() {
    console.log('Manual initialization requested');
    checkAndInitialize();
};

// 提供方法为特定表格添加复制按钮
window.addCopyButtonsToTable = function(tableId) {
    console.log('Adding copy buttons to specific table:', tableId);
    const table = document.getElementById(tableId);
    if (table && window.tableCopyHandlerInstance) {
        window.tableCopyHandlerInstance.addCopyButtonsToTable(table);
    } else if (table) {
        // 如果实例不存在，创建一个临时实例
        const tempHandler = new TableCopyHandler();
        tempHandler.addCopyButtonsToTable(table);
    } else {
        console.error('Table not found:', tableId);
    }
};

// 添加调试信息，显示页面加载时的表格状态
setTimeout(() => {
    console.log('=== Table Copy Handler Debug Info ===');
    const allTables = document.querySelectorAll('table');
    console.log(`Total tables found: ${allTables.length}`);
    
    allTables.forEach((table, index) => {
        const id = table.id || `table-${index}`;
        const tbody = table.querySelector('tbody');
        const isHidden = tbody?.classList.contains('d-none');
        const rowCount = table.querySelectorAll('tbody tr').length;
        
        console.log(`Table ${index + 1}: ID=${id}, Hidden=${isHidden}, Rows=${rowCount}`);
    });
    
    console.log('=== End Debug Info ===');
}, 2000); 