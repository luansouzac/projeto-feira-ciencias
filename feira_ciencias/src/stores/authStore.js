// src/stores/authStore.js

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter();
  
  // 1. STATE: Os dados do usuário e o token, lidos do sessionStorage como valor inicial.
  const userDataString = sessionStorage.getItem('user_data');
  const user = ref(userDataString ? JSON.parse(userDataString).user : null);
  const token = ref(userDataString ? JSON.parse(userDataString).token : null);

  // 2. GETTERS: Informações computadas a partir do estado.
  const isAuthenticated = computed(() => !!token.value);
  const userName = computed(() => user.value?.nome || '');
  
  const userPhotoUrl = computed(() => {
    const backendUrl = import.meta.env.VITE_API_BASE_URL;
    if (user.value && user.value.photo) {
      return `${backendUrl}/storage/${user.value.photo}`;
    }
    return null; // Retorna null se não houver foto
  });

  // 3. ACTIONS: Funções para modificar o estado.
  function setUserData(authData) {
    // authData deve ter o formato { user: {...}, token: '...' }
    user.value = authData.user;
    token.value = authData.token;
    sessionStorage.setItem('user_data', JSON.stringify(authData));
  }

  function logout() {
    user.value = null;
    token.value = null;
    sessionStorage.removeItem('user_data');
    router.push('/login');
  }

  return { 
    user, 
    token, 
    isAuthenticated, 
    userName, 
    userPhotoUrl,
    setUserData, 
    logout 
  };
});