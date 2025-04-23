import axios from 'axios';

const API_URL = '/api/shipping';

export const getShippingRecords = async (options = {}) => {
  try {
    // 构建查询参数
    const params = {};
    
    // 添加分页参数
    if (options.page) {
      params.page = options.page;
    }
    
    if (options.perPage) {
      params.perPage = options.perPage;
    }
    
    // 添加排序参数
    if (options.sortBy) {
      params.sortBy = options.sortBy;
    }
    
    if (options.sortOrder) {
      params.sortOrder = options.sortOrder;
    }
    
    // 添加筛选参数
    if (options.date) {
      params.date = options.date;
    }
    
    if (options.date_from) {
      params.date_from = options.date_from;
    }
    
    if (options.date_to) {
      params.date_to = options.date_to;
    }
    
    if (options.courier_id) {
      params.courier_id = options.courier_id;
    }
    
    if (options.courier_ids) {
      params.courier_ids = options.courier_ids.join(',');
    }
    
    if (options.min_quantity !== undefined) {
      params.min_quantity = options.min_quantity;
    }
    
    if (options.max_quantity !== undefined) {
      params.max_quantity = options.max_quantity;
    }
    
    if (options.notes_search) {
      params.notes_search = options.notes_search;
    }
    
    const response = await axios.get(API_URL, { params });
    return response.data;
  } catch (error) {
    console.error('获取发货记录列表失败:', error);
    throw error;
  }
};

export const getShippingRecordById = async (id) => {
  try {
    const response = await axios.get(`${API_URL}/${id}`);
    return response.data;
  } catch (error) {
    console.error(`获取发货记录(ID: ${id})详情失败:`, error);
    throw error;
  }
};

export const createShippingRecord = async (recordData) => {
  try {
    const response = await axios.post(API_URL, recordData);
    return response.data;
  } catch (error) {
    console.error('创建发货记录失败:', error);
    throw error;
  }
};

export const updateShippingRecord = async (id, recordData) => {
  try {
    const response = await axios.put(`${API_URL}/${id}`, recordData);
    return response.data;
  } catch (error) {
    console.error(`更新发货记录(ID: ${id})失败:`, error);
    throw error;
  }
};

export const deleteShippingRecord = async (id) => {
  try {
    const response = await axios.delete(`${API_URL}/${id}`);
    return response.data;
  } catch (error) {
    console.error(`删除发货记录(ID: ${id})失败:`, error);
    throw error;
  }
};

export const batchCreateShippingRecords = async (date, records) => {
  try {
    const response = await axios.post(`${API_URL}/batch`, {
      date,
      records
    });
    return response.data;
  } catch (error) {
    console.error('批量添加发货记录失败:', error);
    throw error;
  }
}; 