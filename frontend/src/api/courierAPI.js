import axios from 'axios';
import { API_BASE_URL } from '../config';

const API_URL = `${API_BASE_URL}/api/couriers`;

/**
 * 获取快递公司列表
 * @param {Object} params - 查询参数
 * @param {string} params.status - 状态过滤 (active/inactive)
 * @param {string} params.search - 搜索关键词
 * @param {string} params.sortBy - 排序字段
 * @param {string} params.sortOrder - 排序方向 (ASC/DESC)
 * @returns {Promise<Object>} 响应数据
 */
export const getCouriers = async (params = {}) => {
  try {
    const response = await axios.get(API_URL, { params });
    return response.data;
  } catch (error) {
    console.error('获取快递公司失败', error);
    throw error;
  }
};

/**
 * 获取单个快递公司详情
 * @param {number} id - 快递公司ID
 * @returns {Promise<Object>} 响应数据
 */
export const getCourierById = async (id) => {
  try {
    const response = await axios.get(`${API_URL}/${id}`);
    return response.data;
  } catch (error) {
    console.error(`获取快递公司 ID:${id} 失败`, error);
    throw error;
  }
};

/**
 * 创建新的快递公司
 * @param {Object} courierData - 快递公司数据
 * @returns {Promise<Object>} 响应数据
 */
export const createCourier = async (courierData) => {
  try {
    const response = await axios.post(API_URL, courierData);
    return response.data;
  } catch (error) {
    console.error('创建快递公司失败', error);
    throw error;
  }
};

/**
 * 更新快递公司信息
 * @param {number} id - 快递公司ID
 * @param {Object} courierData - 更新的数据
 * @returns {Promise<Object>} 响应数据
 */
export const updateCourier = async (id, courierData) => {
  try {
    const response = await axios.put(`${API_URL}/${id}`, courierData);
    return response.data;
  } catch (error) {
    console.error(`更新快递公司 ID:${id} 失败`, error);
    throw error;
  }
};

/**
 * 删除快递公司
 * @param {number} id - 快递公司ID
 * @returns {Promise<Object>} 响应数据
 */
export const deleteCourier = async (id) => {
  try {
    const response = await axios.delete(`${API_URL}/${id}`);
    return response.data;
  } catch (error) {
    console.error(`删除快递公司 ID:${id} 失败`, error);
    throw error;
  }
};

/**
 * 切换快递公司状态（启用/禁用）
 * @param {number} id - 快递公司ID
 * @returns {Promise<Object>} 响应数据
 */
export const toggleCourierStatus = async (id) => {
  try {
    const response = await axios.patch(`${API_URL}/${id}/toggle-status`);
    return response.data;
  } catch (error) {
    console.error(`切换快递公司 ID:${id} 状态失败`, error);
    throw error;
  }
};

/**
 * 重新排序快递公司
 * @param {Object} data - 包含排序信息的对象
 * @param {Array} data.items - 排序项数组，每项包含id和sort_order
 * @returns {Promise<Object>} 响应数据
 */
export const reorderCouriers = async (data) => {
  try {
    const response = await axios.post(`${API_URL}/reorder`, data);
    return response.data;
  } catch (error) {
    console.error('重排快递公司顺序失败', error);
    throw error;
  }
}; 