import Api from './api';

export interface Courier {
  id?: number;
  name: string;
  code: string;
  is_active: boolean;
  sort_order: number;
  created_at?: string;
  updated_at?: string;
}

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string>;
}

/**
 * 快递种类服务类
 */
class CourierService {
  /**
   * 获取所有快递种类
   * @param options 查询选项
   * @returns Promise
   */
  static async getAll(options: {
    status?: 'active' | 'inactive' | 'all';
    sort?: string;
    order?: 'ASC' | 'DESC';
    search?: string;
  } = {}): Promise<ApiResponse<Courier[]>> {
    try {
      const response = await Api.get('/couriers', options);
      return response.data;
    } catch (error) {
      console.error('获取快递种类列表失败:', error);
      return {
        success: false,
        message: '获取快递种类列表失败，请稍后再试'
      };
    }
  }

  /**
   * 获取单个快递种类
   * @param id 快递种类ID
   * @returns Promise
   */
  static async getById(id: number): Promise<ApiResponse<Courier>> {
    try {
      const response = await Api.get(`/couriers/${id}`);
      return response.data;
    } catch (error) {
      console.error(`获取快递种类 ID:${id} 失败:`, error);
      return {
        success: false,
        message: '获取快递种类详情失败，请稍后再试'
      };
    }
  }

  /**
   * 创建快递种类
   * @param data 快递种类数据
   * @returns Promise
   */
  static async create(data: Omit<Courier, 'id' | 'created_at' | 'updated_at'>): Promise<ApiResponse<Courier>> {
    try {
      const response = await Api.post('/couriers', data);
      return response.data;
    } catch (error) {
      console.error('创建快递种类失败:', error);
      return {
        success: false,
        message: '创建快递种类失败，请检查输入并稍后再试'
      };
    }
  }

  /**
   * 更新快递种类
   * @param id 快递种类ID
   * @param data 快递种类数据
   * @returns Promise
   */
  static async update(id: number, data: Partial<Courier>): Promise<ApiResponse<Courier>> {
    try {
      const response = await Api.put(`/couriers/${id}`, data);
      return response.data;
    } catch (error) {
      console.error(`更新快递种类 ID:${id} 失败:`, error);
      return {
        success: false,
        message: '更新快递种类失败，请检查输入并稍后再试'
      };
    }
  }

  /**
   * 删除快递种类
   * @param id 快递种类ID
   * @returns Promise
   */
  static async delete(id: number): Promise<ApiResponse> {
    try {
      const response = await Api.delete(`/couriers/${id}`);
      return response.data;
    } catch (error) {
      console.error(`删除快递种类 ID:${id} 失败:`, error);
      return {
        success: false,
        message: '删除快递种类失败，请稍后再试'
      };
    }
  }

  /**
   * 重新排序快递种类
   * @param order 排序数据
   * @returns Promise
   */
  static async reorder(order: { id: number; sort_order: number }[]): Promise<ApiResponse> {
    try {
      const response = await Api.post('/couriers/reorder', { order });
      return response.data;
    } catch (error) {
      console.error('重新排序快递种类失败:', error);
      return {
        success: false,
        message: '重新排序失败，请稍后再试'
      };
    }
  }

  /**
   * 切换快递种类状态
   * @param id 快递种类ID
   * @param isActive 是否激活
   * @returns Promise
   */
  static async toggleStatus(id: number, isActive: boolean): Promise<ApiResponse<Courier>> {
    return this.update(id, { is_active: isActive });
  }
}

export default CourierService; 