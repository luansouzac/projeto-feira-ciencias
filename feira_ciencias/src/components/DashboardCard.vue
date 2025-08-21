<template>
  <v-card 
    :to="to"  
    :color="color" 
    class="dashboard-card pa-4 text-center d-flex flex-column align-center" 
    rounded="xl" 
    elevation="5"
  >
    <v-icon size="64" class="mb-2">{{ icon }}</v-icon>
    <div class="text-h6 font-weight-bold">{{ title }}</div>
    <div class="text-subtitle-1">{{ subtitle }}</div>
  </v-card>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  title: String,
  subtitle: String,
  icon: String,
  color: String,
  to: String, 
});
</script>

<style scoped>
.dashboard-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  max-width: 350px;
}

.dashboard-card:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}
</style>