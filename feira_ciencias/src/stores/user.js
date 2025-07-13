import { defineStore } from 'pinia'
import apiClient from '../assets/plugins/axios'

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
        console.log("Ação fetchUser: A tentar obter dados de /api/user");
        const response = await apiClient.get('/api/usuarios');
        this.userData = response.data;
        localStorage.setItem('userData', JSON.stringify(response.data));
        console.log("Ação fetchUser: Utilizador obtido com sucesso:", response.data);
      } catch (error) {
        console.error("Ação fetchUser: Não foi possível obter o utilizador.", error);
        this.clearData();
      }
    },

    async handleLogin(credentials) {
      console.log("Ação handleLogin: Iniciada.");
      this.clearData();

      try {
        console.log("Ação handleLogin: Passo 1 - A obter o cookie CSRF de /sanctum/csrf-cookie...");
        await apiClient.get('/sanctum/csrf-cookie');
        console.log("Ação handleLogin: Passo 1 - Cookie CSRF obtido com sucesso.");

        console.log("Ação handleLogin: Passo 2 - A enviar pedido de login para /login...");
        const response = await apiClient.post('/login', credentials);
        console.log("Ação handleLogin: Passo 2 - Resposta de login recebida.");
        
        const token = response.data.access_token;

        if (token) {
          console.log("Ação handleLogin: Token encontrado. A configurar o estado.");
          this.authToken = token;
          localStorage.setItem('authToken', token);

          apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`;

          await this.fetchUser();
        } else {
          console.error("Ação handleLogin: A resposta de login não continha um access_token.");
          throw new Error('Token de acesso não recebido.');
        }
      } catch (error) {
        console.error("Ação handleLogin: Ocorreu um erro durante o processo de login.", error);
        // Lança o erro novamente para que o componente de login possa apanhá-lo e mostrar a mensagem.
        throw error;
      }
    },

    async handleLogout() {
      try {
        await apiClient.post('/logout');
      } catch (error) {
        console.error("Erro no logout do servidor, mas a limpar localmente.", error);
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
        console.log("Ação clearData: Dados do utilizador e token limpos.");
    }
  },
})
