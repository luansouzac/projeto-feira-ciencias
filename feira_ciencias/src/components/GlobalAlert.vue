<template>
  <v-snackbar
    v-model="store.visible"
    :color="store.type"
    :timeout="-1" location="top right"
    multi-line
  >
    {{ store.message }}

    <template v-slot:actions>
      <v-btn
        icon="mdi-close"
        variant="text"
        @click="store.hide()"
      ></v-btn>
    </template>
  </v-snackbar>
</template>

<script setup>
import { useNotificationStore } from '@/stores/notification';

const store = useNotificationStore();
</script>