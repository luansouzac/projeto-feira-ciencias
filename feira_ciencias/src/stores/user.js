// src/stores/user.js

import { defineStore } from 'pinia'
import apiClient from 'axios'

export const useUserStore = defineStore('user', {
  state: () => ({
    userData: JSON.parse(localStorage.getItem('userData')) || null,
    authToken: localStorage.getItem('authToken') || null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.authToken,
    userName: (state) => state.userData?.name || 'Visitante',
  },

  actions: {
    async fetchUser() {
      try {
        const response = await apiClient.get('/api/usuarios');
        this.userData = response.data;
        localStorage.setItem('userData', JSON.stringify(response.data));
      } catch (error) {
        console.error("Não foi possível buscar o usuário.", error);
        this.clearData();
      }
    },

    async handleLogin(credentials) {
    
      this.clearData();

      await apiClient.get('/sanctum/csrf-cookie');
      
      const response = await apiClient.post('/login', credentials);
    
      const token = response.data.access_token;

      if (token) {
        this.authToken = token;
        localStorage.setItem('authToken', token);

        apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`;

        await this.fetchUser();
      } else {
        throw new Error('Token de acesso não recebido.');
      }
    },

    async handleLogout() {
      try {
        await apiClient.post('/logout');
      } catch (error) {
        console.error("Erro no logout do servidor, mas limpando localmente.", error);
      } finally {
        this.clearData();
      }
    },
    clearData() {
        this.userData = null;
        this.authToken = null;
        localStorage.removeItem('userData');
        localStorage.removeItem('authToken');
        delete apiClient.defaults.headers.common['Authorization'];
    }
  },
})
