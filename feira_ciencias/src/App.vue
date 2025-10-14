<script setup>
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router'
import GlobalAlert from '@/components/GlobalAlert.vue';
import NavBar from '@/components/NavBar.vue'; 

const route = useRoute();

const showNavbar = computed(() => {

  const rotasSemNavbarPorNome = ['login', 'registrar', 'recuperar']; 
  if (rotasSemNavbarPorNome.includes(route.name)) {
    return false;
  }

  if (route.meta.layout === 'public') {
    return false;
  }

  return true;
});
</script>

<template>
  <v-app>
    <NavBar v-if="showNavbar"/>

    <v-main>
      <GlobalAlert />
      <RouterView />
    </v-main>
  </v-app>
</template>

<style scoped>
/* Pode adicionar estilos globais para a v-main aqui se necessário, 
   por exemplo, uma cor de fundo. */
</style>
