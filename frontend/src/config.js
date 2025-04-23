/**
 * 应用配置文件
 */

// API基础URL
export const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || '';

// 每页默认显示数量
export const DEFAULT_PAGE_SIZE = 10;

// 分页选项
export const PAGE_SIZE_OPTIONS = ['10', '20', '50', '100'];

// 请求超时时间(毫秒)
export const REQUEST_TIMEOUT = 30000;

// 是否显示接口请求日志
export const SHOW_API_LOGS = process.env.NODE_ENV === 'development'; 