<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'

import DashboardCard from '@/components/DashboardCard.vue'

const router = useRouter();
const notificationStore = useNotificationStore();

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

onMounted(() => {
  if (!userId) {
    erro.value = "Usuário não encontrado. Por favor, faça o login novamente.";
    carregando.value = false;
    router.push('/login')
    return;
  }
})

// Atalhos principais
const atalhos = [
  {
    title: "Meus Projetos",
    subtitle: "Acesse seus projetos",
    icon: "mdi-folder",
    color: "primary",
    to: "/projetos"
  },
  {
    title: "Eventos",
    subtitle: "Veja os eventos",
    icon: "mdi-calendar",
    color: "secondary",
    to: "/eventos"
  },
  {
    title: "Avaliações",
    subtitle: "Confira suas avaliações",
    icon: "mdi-star-check",
    color: "success",
    to: "/avaliacoes"
  },
  {
    title: "Projetos Aprovados",
    subtitle: "Veja os aprovados",
    icon: "mdi-check-decagram",
    color: "warning",
    to: "/aprovados"
  }
]
</script>

<template>
  <v-container class="fill-height d-flex flex-column justify-center align-center" fluid>
    
    <!-- BLOCO DO TEXTO -->
    <div class="text-center mb-10">
      <h1 class="text-h4 font-weight-bold text-grey-darken-4">
        Bem-vindo de volta, {{ nomeUsuario }}!
      </h1>
      <p class="text-subtitle-1 text-grey-darken-1 mt-2">
        Seja bem-vindo ao nosso sistema de gestão de projetos!
      </p>
    </div>

    <!-- BLOCO DOS CARDS -->
    <!-- <v-row justify="center" align="center" class="w-100">
      <v-col
        v-for="(item, index) in atalhos"
        :key="index"
        cols="12"
        sm="6"
        md="3"
        class="d-flex justify-center"
      >
        <DashboardCard
          :title="item.title"
          :subtitle="item.subtitle"
          :icon="item.icon"
          :color="item.color"
          :to="item.to"
         
        />
      </v-col>
    </v-row> -->
  </v-container>
</template>

<style scoped>
.v-container {
  min-height: 80vh; 
}

.text-center{
  padding-top: 80px;
}
</style>
