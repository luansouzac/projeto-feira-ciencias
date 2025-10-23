import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter();
  
  const userDataString = sessionStorage.getItem('user_data');
  const user = ref(userDataString ? JSON.parse(userDataString).user : null);
  const token = ref(userDataString ? JSON.parse(userDataString).token : null);

  const isAuthenticated = computed(() => !!token.value);
  const userName = computed(() => user.value?.nome || '');
  
  const userPhotoUrl = computed(() => {
    const backendUrl = import.meta.env.VITE_API_BASE_URL;
    if (user.value && user.value.photo) {
      return `${backendUrl}/storage/${user.value.photo}`;
    }
    return null;
  });

  /**
   * ✅ A LÓGICA CRUCIAL QUE ESTÁ EM FALTA
   * Esta computed property encontra o ID do tipo de utilizador,
   * quer ele venha na raiz do objeto ou aninhado.
   */
  const userTypeId = computed(() => {
    if (!user.value) {
      return null;
    }
    // 1. Tenta encontrar na raiz (ex: { "id_tipo_usuario": 2 })
    if (user.value.id_tipo_usuario) {
      return user.value.id_tipo_usuario;
    }
    // 2. Tenta encontrar aninhado (ex: { "tipo_usuario": { "id_tipo_usuario": 2 } })
    if (user.value.tipo_usuario && user.value.tipo_usuario.id_tipo_usuario) {
      return user.value.tipo_usuario.id_tipo_usuario;
    }
    return null;
  });
  
  function setUserData(authData) {
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

  // Certifique-se de que 'userTypeId' está a ser exportado no return
  return { 
    user, 
    token, 
    isAuthenticated, 
    userName, 
    userPhotoUrl,
    userTypeId, // ✅ EXPORTAR A NOVA PROPRIEDADE
    setUserData, 
    logout 
  };
});

