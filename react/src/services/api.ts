import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

// API基础URL
const API_BASE_URL = process.env.REACT_APP_API_URL || '/api';

// 创建axios实例
const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 10000 // 请求超时时间10秒
});

// 请求拦截器
apiClient.interceptors.request.use(
  (config) => {
    // 这里可以添加认证token等
    // const token = localStorage.getItem('token');
    // if (token) {
    //   config.headers['Authorization'] = `Bearer ${token}`;
    // }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// 响应拦截器
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // 处理错误响应
    if (error.response) {
      // 服务器返回了错误状态码
      console.error('API错误响应:', error.response.data);
      
      // 根据状态码处理不同的错误情况
      switch (error.response.status) {
        case 401:
          // 未授权，可能需要登录
          console.log('未授权，请登录');
          break;
        case 403:
          // 禁止访问
          console.log('没有权限执行此操作');
          break;
        case 404:
          // 资源不存在
          console.log('请求的资源不存在');
          break;
        case 500:
          // 服务器错误
          console.log('服务器错误，请稍后再试');
          break;
        default:
          console.log('发生错误，请稍后再试');
      }
    } else if (error.request) {
      // 请求已发送但没有收到响应
      console.error('无法连接到服务器:', error.request);
      console.log('无法连接到服务器，请检查网络连接');
    } else {
      // 设置请求时发生错误
      console.error('请求错误:', error.message);
      console.log('发送请求时出错');
    }
    
    return Promise.reject(error);
  }
);

// API类
class Api {
  /**
   * 发送GET请求
   * @param url 请求URL
   * @param params 查询参数
   * @param config 额外的配置
   * @returns Promise
   */
  static get<T = any>(url: string, params: any = {}, config: AxiosRequestConfig = {}): Promise<AxiosResponse<T>> {
    return apiClient.get(url, { params, ...config });
  }

  /**
   * 发送POST请求
   * @param url 请求URL
   * @param data 请求数据
   * @param config 额外的配置
   * @returns Promise
   */
  static post<T = any>(url: string, data: any = {}, config: AxiosRequestConfig = {}): Promise<AxiosResponse<T>> {
    return apiClient.post(url, data, config);
  }

  /**
   * 发送PUT请求
   * @param url 请求URL
   * @param data 请求数据
   * @param config 额外的配置
   * @returns Promise
   */
  static put<T = any>(url: string, data: any = {}, config: AxiosRequestConfig = {}): Promise<AxiosResponse<T>> {
    return apiClient.put(url, data, config);
  }

  /**
   * 发送DELETE请求
   * @param url 请求URL
   * @param config 额外的配置
   * @returns Promise
   */
  static delete<T = any>(url: string, config: AxiosRequestConfig = {}): Promise<AxiosResponse<T>> {
    return apiClient.delete(url, config);
  }
}

export default Api; 