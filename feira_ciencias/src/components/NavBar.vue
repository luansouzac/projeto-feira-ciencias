<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/authStore'; // Importa a store de autenticação

const authStore = useAuthStore(); // Inicia a store

// Controle da gaveta de navegação para telas mobile
const drawer = ref(false);

// MAPEAMENTO DE PERFIS (Exemplo baseado nas suas rotas)
// 1: Administrador
// 2: Aluno
// 3: Orientador
// 4: Avaliador

// Lista completa de todos os links de navegação possíveis
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
    meta: { requiredTypeId: [1, 2] } // Visível para Admin e Aluno
  },
  {
    title: 'Banco de Projetos',
    to: '/banco-projetos',
    icon: 'mdi-database-outline',
    // Sem 'meta', visível para todos os usuários logados
  },
  {
    title: 'Meus Projetos Orientados',
    to: '/projetos/orientados',
    icon: 'mdi-human-male-board-outline',
    meta: { requiredTypeId: [1, 3] } // Visível para Admin e Orientador
  },
  {
    title: 'Aprovações de projetos',
    to: '/avaliacoes',
    icon: 'mdi-clipboard-check-outline',
    meta: { requiredTypeId: [1, 4] } // Visível para Admin e Avaliador
  },
  {
    title: 'Eventos',
    to: '/eventos',
    icon: 'mdi-calendar-star-outline',
    meta: { requiredTypeId: [1, 3, 4] } // Visível para Admin, Orientador e Avaliador
  },
];

// Filtra os links de navegação baseado no tipo de usuário logado
const visibleNavLinks = computed(() => {
  return allNavLinks.filter(link => {
    // Se o link tem uma restrição de perfil
    if (link.meta && link.meta.requiredTypeId) {
      // Retorna true se o ID do tipo de usuário estiver na lista de permissões do link
      return link.meta.requiredTypeId.includes(authStore.user?.id_tipo_usuario);
    }
    // Se não há restrição, o link é visível para todos
    return true;
  });
});

// Gera as iniciais do nome do usuário para o avatar
const userInitials = computed(() => {
  if (!authStore.userName) return '';
  const names = authStore.userName.split(" ");
  if (names.length > 1) {
    return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
  }
  return names[0].substring(0, 2).toUpperCase();
});

// Função de logout que chama a ação da store
function logout() {
  authStore.logout();
}
</script>

<template>
  <div>
    <v-app-bar app color="green-darken-4" flat border>
      <v-app-bar-nav-icon
        class="d-md-none"
        @click="drawer = !drawer"
      ></v-app-bar-nav-icon>

      <v-toolbar-title class="font-weight-bold text-white">
        Projetaí
      </v-toolbar-title>

      <div class="centralizar-menu d-none d-md-flex">
        <v-btn
          v-for="link in visibleNavLinks"
          :key="link.title"
          :to="link.to"
          variant="text"
          class="mx-1"
        >
          {{ link.title }}
        </v-btn>
      </div>

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

    <v-navigation-drawer v-model="drawer" temporary app>
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

<style scoped>
.centralizar-menu {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}
</style>