import { api } from './axios';

export const uploadApi = {
  images: (files: File[]) => {
    const form = new FormData();
    files.forEach((f) => form.append('files', f));
    return api.post<{ urls: string[] }>('/upload/images', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};
