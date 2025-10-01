<script setup>
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router'
import GlobalAlert from '@/components/GlobalAlert.vue';
import NavBar from '@/components/NavBar.vue';

const route = useRoute();

const showNavbar = computed(() => {
  const rotasSemNavbar = ['login', 'registrar', 'recuperar']; 
  return !rotasSemNavbar.includes(route.name);
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
/* PASSO 2: Limpar o CSS antigo que não é mais necessário. 
   O Vuetify já cuida disso.
   Você pode adicionar seus próprios estilos aqui depois, se precisar. */
</style>