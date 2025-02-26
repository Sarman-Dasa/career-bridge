// src/services/apiService.ts
import axiosIns from "@/plugins/axios";
import { AxiosRequestConfig, AxiosResponse } from "axios";
import { showToast } from "./toast";

// Define response and error types
interface ApiResponse<T> {
  data: T;
  status: number;
  message: string;
}

// Generic GET request
export const getRequest = async <T>(
  url: string,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> => {
  const response: AxiosResponse<T> = await axiosIns.get(url, config);
  return {
    data: response.data.data,
    status: response.status,
    message: response.statusText,
  };
};

// Generic POST request
export const postRequest = async <T>(
  url: string,
  payload: unknown,
  show_message = true,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> => {
  try {
    const response: AxiosResponse<T> = await axiosIns.post(
      url,
      payload,
      config
    );
    if (show_message) {
      showToast("success", response.data.message);
    }

    return {
      data: response.data.data,
      status: response.status,
      message: response.statusText,
    };
  } catch (error) {
    showError(error);
    console.log('error: ', error);
  }
};

// Generic PUT request
export const putRequest = async <T>(
  url: string,
  payload: unknown,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> => {
  const response: AxiosResponse<T> = await axiosIns.put(url, payload, config);
  return {
    data: response.data,
    status: response.status,
    message: response.statusText,
  };
};

// Generic DELETE request
export const deleteRequest = async <T>(
  url: string,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> => {
  try {
    const response: AxiosResponse<T> = await axiosIns.delete(url, config);
    return {
      data: response.data,
      status: response.status,
      message: response.statusText,
    };
  }catch (error) {
    showToast("error", error.message);
    console.log('error: ', error);
  }
};

function showError(e: any) {
  if (e.status === 401) {
    // this.logout()
  }
  if (!e.response || !e.response.data || !e.response.data.message) {
    return;
  }
  const error = e.response.data.message;
  showToast("error", error);
}
