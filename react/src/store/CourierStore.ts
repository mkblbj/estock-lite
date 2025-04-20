import { makeAutoObservable, runInAction } from 'mobx';
import CourierService, { Courier, ApiResponse } from '../services/courierService';

export interface CourierState {
  couriers: Courier[];
  loading: boolean;
  error: string | null;
  currentCourier: Courier | null;
  formErrors: Record<string, string>;
  modalVisible: boolean;
  modalMode: 'create' | 'edit';
}

class CourierStore {
  // 状态
  couriers: Courier[] = [];
  loading: boolean = false;
  error: string | null = null;
  currentCourier: Courier | null = null;
  formErrors: Record<string, string> = {};
  modalVisible: boolean = false;
  modalMode: 'create' | 'edit' = 'create';
  
  constructor() {
    makeAutoObservable(this);
  }
  
  // 设置加载状态
  setLoading(loading: boolean) {
    this.loading = loading;
  }
  
  // 设置错误信息
  setError(error: string | null) {
    this.error = error;
  }
  
  // 设置表单错误
  setFormErrors(errors: Record<string, string>) {
    this.formErrors = errors;
  }
  
  // 清除表单错误
  clearFormErrors() {
    this.formErrors = {};
  }
  
  // 打开模态框
  openModal(mode: 'create' | 'edit', courier?: Courier) {
    this.modalMode = mode;
    this.currentCourier = courier || null;
    this.modalVisible = true;
    this.clearFormErrors();
  }
  
  // 关闭模态框
  closeModal() {
    this.modalVisible = false;
    this.currentCourier = null;
    this.clearFormErrors();
  }
  
  // 加载快递种类列表
  async loadCouriers(options: any = {}) {
    this.setLoading(true);
    this.setError(null);
    
    try {
      const response = await CourierService.getAll(options);
      
      runInAction(() => {
        if (response.success && response.data) {
          this.couriers = response.data;
        } else {
          this.setError(response.message || '加载快递种类列表失败');
        }
        this.setLoading(false);
      });
    } catch (error) {
      runInAction(() => {
        this.setError('加载快递种类列表失败');
        this.setLoading(false);
      });
    }
  }
  
  // 获取单个快递种类
  async getCourier(id: number) {
    this.setLoading(true);
    this.setError(null);
    
    try {
      const response = await CourierService.getById(id);
      
      runInAction(() => {
        if (response.success && response.data) {
          this.currentCourier = response.data;
        } else {
          this.setError(response.message || '获取快递种类详情失败');
        }
        this.setLoading(false);
      });
    } catch (error) {
      runInAction(() => {
        this.setError('获取快递种类详情失败');
        this.setLoading(false);
      });
    }
  }
  
  // 创建快递种类
  async createCourier(data: Omit<Courier, 'id' | 'created_at' | 'updated_at'>) {
    this.setLoading(true);
    this.clearFormErrors();
    
    try {
      const response = await CourierService.create(data);
      
      runInAction(() => {
        if (response.success && response.data) {
          this.couriers.push(response.data);
          this.closeModal();
        } else if (response.errors) {
          this.setFormErrors(response.errors);
        } else {
          this.setError(response.message || '创建快递种类失败');
        }
        this.setLoading(false);
      });
      
      return response;
    } catch (error) {
      runInAction(() => {
        this.setError('创建快递种类失败');
        this.setLoading(false);
      });
      
      return {
        success: false,
        message: '创建快递种类失败'
      } as ApiResponse;
    }
  }
  
  // 更新快递种类
  async updateCourier(id: number, data: Partial<Courier>) {
    this.setLoading(true);
    this.clearFormErrors();
    
    try {
      const response = await CourierService.update(id, data);
      
      runInAction(() => {
        if (response.success && response.data) {
          const index = this.couriers.findIndex(c => c.id === id);
          if (index !== -1) {
            this.couriers[index] = response.data;
          }
          this.closeModal();
        } else if (response.errors) {
          this.setFormErrors(response.errors);
        } else {
          this.setError(response.message || '更新快递种类失败');
        }
        this.setLoading(false);
      });
      
      return response;
    } catch (error) {
      runInAction(() => {
        this.setError('更新快递种类失败');
        this.setLoading(false);
      });
      
      return {
        success: false,
        message: '更新快递种类失败'
      } as ApiResponse;
    }
  }
  
  // 删除快递种类
  async deleteCourier(id: number) {
    this.setLoading(true);
    this.setError(null);
    
    try {
      const response = await CourierService.delete(id);
      
      runInAction(() => {
        if (response.success) {
          this.couriers = this.couriers.filter(c => c.id !== id);
        } else {
          this.setError(response.message || '删除快递种类失败');
        }
        this.setLoading(false);
      });
      
      return response;
    } catch (error) {
      runInAction(() => {
        this.setError('删除快递种类失败');
        this.setLoading(false);
      });
      
      return {
        success: false,
        message: '删除快递种类失败'
      } as ApiResponse;
    }
  }
  
  // 切换快递种类状态
  async toggleCourierStatus(id: number, isActive: boolean) {
    try {
      const response = await CourierService.toggleStatus(id, isActive);
      
      runInAction(() => {
        if (response.success && response.data) {
          const index = this.couriers.findIndex(c => c.id === id);
          if (index !== -1) {
            this.couriers[index] = response.data;
          }
        }
      });
      
      return response;
    } catch (error) {
      return {
        success: false,
        message: '切换状态失败'
      } as ApiResponse;
    }
  }
  
  // 重新排序快递种类
  async reorderCouriers(items: { id: number; sort_order: number }[]) {
    try {
      const response = await CourierService.reorder(items);
      
      if (response.success) {
        // 重新加载列表以获取最新排序
        await this.loadCouriers();
      }
      
      return response;
    } catch (error) {
      return {
        success: false,
        message: '重新排序失败'
      } as ApiResponse;
    }
  }
}

// 创建单例实例
const courierStore = new CourierStore();
export default courierStore; 