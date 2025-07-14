import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();

const api = axios.create({

  baseURL: import.meta.env.VITE_API_URL,

  timeout: 20000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

api.interceptors.request.use(
  (config) => {
    const userDataString = localStorage.getItem('user_data');

    if (userDataString) {
      const userData = JSON.parse(userDataString);
      const token = userData.token;

      if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
      }
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);
api.interceptors.response.use(
  (response) => response,

  (error) => {
    const notificationStore = useNotificationStore();
    if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
      notificationStore.showError('O servidor demorou muito para responder. Por favor, tente novamente.');
    }
    else if (!error.response) {
      notificationStore.showError('Não foi possível conectar ao servidor. Verifique sua conexão com a internet.');
    }
    else if (error.response.status === 401) {
      notificationStore.showError('Sua sessão expirou. Por favor, faça login novamente.');
      router.push('/login');
    }
    else {
      notificationStore.showError('Ocorreu um erro inesperado.');
    }

    return Promise.reject(error);
  }
);

export default api;
