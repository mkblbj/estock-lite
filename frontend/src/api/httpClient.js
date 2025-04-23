import axios from 'axios';
import { API_BASE_URL, REQUEST_TIMEOUT, SHOW_API_LOGS } from '../config';

// 创建axios实例
const httpClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: REQUEST_TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// 请求拦截器
httpClient.interceptors.request.use(
  config => {
    // 从localStorage获取token
    const token = localStorage.getItem('token');
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    
    // 开发环境下打印请求日志
    if (SHOW_API_LOGS) {
      console.log(`API请求: ${config.method.toUpperCase()} ${config.url}`, config.data || config.params);
    }
    
    return config;
  },
  error => {
    console.error('请求错误:', error);
    return Promise.reject(error);
  }
);

// 响应拦截器
httpClient.interceptors.response.use(
  response => {
    // 开发环境下打印响应日志
    if (SHOW_API_LOGS) {
      console.log(`API响应: ${response.config.method.toUpperCase()} ${response.config.url}`, response.data);
    }
    
    return response.data;
  },
  error => {
    if (SHOW_API_LOGS) {
      console.error('响应错误:', error);
    }
    
    // 处理401错误(未授权)
    if (error.response && error.response.status === 401) {
      // 清除token和用户信息
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      
      // 重定向到登录页
      window.location.href = '/login';
    }
    
    // 处理后端返回的错误消息
    const errorMessage = 
      (error.response && error.response.data && error.response.data.message) ||
      error.message ||
      '服务器错误';
    
    return Promise.reject(errorMessage);
  }
);

export default httpClient; 