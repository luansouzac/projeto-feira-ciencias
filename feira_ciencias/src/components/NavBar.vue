<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/authStore';

const authStore = useAuthStore();

// Controle da gaveta de navegação. 
// Definida como 'true' por padrão para que a barra lateral apareça ao carregar em telas desktop.
const drawer = ref(true); 

// MAPEAMENTO DE PERFIS (Mantido, pois é usado para filtrar os links)
// 1: Administrador
// 2: Aluno
// 3: Orientador
// 4: Avaliador

// Lista completa de todos os links de navegação possíveis (Seu código existente)
const allNavLinks = [
  { 
    title: 'Home', 
    to: '/home', 
    icon: 'mdi-view-dashboard-outline' 
  },
  {
    title: 'Submeter Projetos',
    to: '/projetos',
    icon: 'mdi-folder-plus-outline',
    meta: { requiredTypeId: [1, 2] } 
  },
  {
    title: 'Banco de Projetos',
    to: '/banco-projetos',
    icon: 'mdi-database-outline',
  },
  {
    title: 'Meus Projetos Orientados',
    to: '/projetos/orientados',
    icon: 'mdi-human-male-board-outline', // Ícone alterado para ser mais específico
    meta: { requiredTypeId: [1, 3] } // Corrigido para incluir Orientador (ID 3)
  },
  {
    title: 'Aprovações de projetos',
    to: '/avaliacoes',
    icon: 'mdi-clipboard-check-outline',
    meta: { requiredTypeId: [1, 4] }
  },
  {
    title: 'Minhas Avaliações',
    to: '/minhas-avaliacoes',
    icon: 'mdi-clipboard-clock-outline',
    meta: { requiredTypeId: [1, 3, 4] } 
  },
  {
    title: 'Resultados de Avaliações',
    to: '/ranking-projetos',
    icon: 'mdi-trophy-outline', // Ícone alterado para Ranking/Resultado
    meta: { requiredTypeId: [1] } // Se for visível a todos os perfis, remova a meta.
  },
  {
    title: 'Gerenciar Avaliacoes',
    to: '/admin/avaliacoes',
    icon: 'mdi-clipboard-edit-outline',
    meta: { requiredTypeId: [1] } 
  },
  {
    title: 'Eventos',
    to: '/eventos',
    icon: 'mdi-calendar-star-outline',
    meta: { requiredTypeId: [1, 4] } 
  },
  {
    title: 'Gerenciar Usuarios',
    to: '/admin/usuarios',
    icon: 'mdi-account-group-outline',
    meta: { requiredTypeId: [1] }
  }
];

// REMOVIDA A VARIÁVEL permanentDrawer, pois não é mais necessária

// Filtra os links de navegação baseado no tipo de usuário logado (Mantido)
const visibleNavLinks = computed(() => {
  return allNavLinks.filter(link => {
    if (link.meta && link.meta.requiredTypeId) {
      return link.meta.requiredTypeId.includes(authStore.user?.id_tipo_usuario);
    }
    return true;
  });
});

// Gera as iniciais do nome do usuário para o avatar (Mantido)
const userInitials = computed(() => {
  if (!authStore.userName) return '';
  const names = authStore.userName.split(" ");
  if (names.length > 1) {
    return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
  }
  return names[0].substring(0, 2).toUpperCase();
});

// Função de logout (Mantida)
function logout() {
  authStore.logout();
}
</script>

<template>
  <div>
    <v-app-bar
      app
      color="green-darken-4"
      flat
      border
    >
      <v-app-bar-nav-icon
        @click="drawer = !drawer"
      ></v-app-bar-nav-icon>

      <v-toolbar-title class="font-weight-bold text-white">
        Projetaí
      </v-toolbar-title>

      <v-spacer></v-spacer>
      
      <v-menu offset-y>
        <template v-slot:activator="{ props }">
          <v-btn v-bind="props" text class="pa-20 text-none">
            <v-avatar color="white" size="36" class="mr-2">
              <v-img
                v-if="authStore.userPhotoUrl"
                :src="authStore.userPhotoUrl"
                alt="Foto do usuário"
                cover
              ></v-img>
              <span v-else class="text-green-darken-4 font-weight-bold">{{ userInitials }}</span>
            </v-avatar>
            
            <span class="d-none d-sm-flex text-capitalize text-white">{{ authStore.userName }}</span>
            <v-icon class="d-none d-sm-flex ml-1">mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        
        <v-list density="compact">
          <v-list-item link to="/profile">
            <template v-slot:prepend>
              <v-icon>mdi-account-circle-outline</v-icon>
            </template>
            <v-list-item-title>Meu Perfil</v-list-item-title>
          </v-list-item>
          <v-divider></v-divider>
          <v-list-item link @click="logout">
            <template v-slot:prepend>
              <v-icon color="error">mdi-logout</v-icon>
            </template>
            <v-list-item-title class="text-error">Sair</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-navigation-drawer 
      v-model="drawer" 
      app
      permanent
    >
      <v-list nav>
        <v-list-item
          v-for="link in visibleNavLinks"
          :key="link.title"
          :to="link.to"
          :prepend-icon="link.icon"
          :title="link.title"
          link
        ></v-list-item>
      </v-list>
    </v-navigation-drawer>
  </div>
</template>