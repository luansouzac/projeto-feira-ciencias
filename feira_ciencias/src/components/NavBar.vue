<script setup>
import { ref, computed } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

// Controle da gaveta de navegação mobile
const drawer = ref(false);

const nomeUsuario = ref("");

// Links de navegação (centralizados para fácil manutenção)
const navLinks = [
  { title: "Home", to: "/home", icon: "mdi-view-dashboard-outline" },
  { title: "Projetos", to: "/projetos", icon: "mdi-folder-account-outline" },
  { title: "Eventos", to: "/eventos", icon: "mdi-chart-bar" },
];

const userDataString = localStorage.getItem('user_data');
if (userDataString) {
  const userData = JSON.parse(userDataString);
  nomeUsuario.value = userData.user.nome;
}

const userInitials = computed(() => {
  if (!nomeUsuario.value) return '';

  const names = nomeUsuario.value.split(" ");
  if (names.length > 1) {
    return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
  }
  return names[0].substring(0, 2).toUpperCase();
});

const logout = () => {
  console.log("Executando logout...");
  localStorage.removeItem("user_data");
  router.push('/login');
};
</script>

<template>
  <div>
    <v-app-bar app color="green-darken-4" flat border>
      <v-app-bar-nav-icon
        class="d-md-none"
        @click="drawer = !drawer"
      ></v-app-bar-nav-icon>

      <v-toolbar-title class="font-weight-bold text-white">
        Submit.ai
      </v-toolbar-title>

      <v-spacer></v-spacer>

      <div class="d-none d-md-flex">
        <v-btn
          v-for="link in navLinks"
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
          <v-btn v-bind="props" text class="pa-20">
            <v-avatar color="white" size="36" class="mr-2">
              <span class="white--text text-h6">{{ userInitials }}</span>
            </v-avatar>
            <span class="d-none d-sm-flex text-capitalize text-white">{{
              nomeUsuario
            }}</span>
            <v-icon class="d-none d-sm-flex">mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item link>
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
          v-for="link in navLinks"
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
/* Adicione este novo bloco de estilo */
.user-menu-activator {
  transition: background-color 0.2s ease-in-out;
}

.user-menu-activator:hover {
  background-color: rgba(0, 0, 0, 0.05); /* Um cinza bem claro, padrão do Vuetify */
}
</style>