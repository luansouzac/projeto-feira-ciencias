import axios from 'axios';

const api = axios.create({

  baseURL: import.meta.env.VITE_API_URL,
  
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

export default api;
