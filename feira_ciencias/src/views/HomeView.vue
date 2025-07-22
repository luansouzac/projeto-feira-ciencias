<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router' 
import { useNotificationStore } from '@/stores/notification'


const router = useRouter();
const notificationStore = useNotificationStore();

// --- ESTADO DA PÁGINA ---
const carregando = ref(true) 
const erro = ref(null)
const nomeUsuario = ref('')
let userId = null;

const userDataString = sessionStorage.getItem('user_data');
if (userDataString) {
  const userData = JSON.parse(userDataString);
  nomeUsuario.value = userData.user.nome;
  userId = userData.user.id_usuario; 
}else{
  router.push({ name: 'login' });
}

// métodos
onMounted(() => {
  if (!userId) {
    erro.value = "Usuário não encontrado. Por favor, faça o login novamente.";
    carregando.value = false;
    router.push('/login')
    return;
  }
})

</script>

<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" md="8" lg="6" class="text-center">
        <h1 class="text-h4 font-weight-bold text-grey-darken-4">Bem-vindo de volta, {{ nomeUsuario }}!</h1>
        <p class="text-subtitle-1 text-grey-darken-1 mt-2">Aqui ficarão as notificações do seu projeto</p>
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>
.v-container {
  min-height: 80vh; 
}
</style>