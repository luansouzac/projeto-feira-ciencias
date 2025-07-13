// src/api/axios.js

import axios from 'axios';

// Cria uma instância do axios com configurações padrão
const apiClient = axios.create({
  // Usa a variável de ambiente que agora aponta para a raiz do servidor
  baseURL: import.meta.env.VITE_API_URL, 
  
  // Essencial para o Laravel Sanctum
  withCredentials: true, 
});

export default apiClient;
