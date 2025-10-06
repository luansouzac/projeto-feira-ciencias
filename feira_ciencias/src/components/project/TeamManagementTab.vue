<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  membros: {
    type: Array,
    required: true,
  },
  project: {
    type: Object,
    required: true,
  },
  canCreateTasks: {
    type: Boolean,
    default: false,
  },
  isTeamFull: {
    type: Boolean,
    default: false,
  },
  authUserId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['open-add-modal', 'remove-member'])
</script>

<template>
  <v-card-title class="d-flex justify-space-between align-center flex-wrap">
    <span>Membros da Equipe</span>
    <div class="d-flex align-center ga-4">
      <v-chip
        v-if="project.max_pessoas > 0"
        :color="isTeamFull ? 'red' : 'green-darken-1'"
        variant="tonal"
        label
      >
        <v-icon start>mdi-account-multiple</v-icon>
        Vagas: {{ membros.length }} / {{ project.max_pessoas }}
      </v-chip>
      <v-tooltip
        :text="isTeamFull ? 'A equipe atingiu o limite' : 'Adicionar novo membro'"
        location="top"
      >
        <template v-slot:activator="{ props: tooltipProps }">
          <div v-bind="tooltipProps">
            <v-btn
              v-if="canCreateTasks"
              :disabled="isTeamFull"
              color="green"
              variant="flat"
              @click="emit('open-add-modal')"
              prepend-icon="mdi-account-plus-outline"
            >
              Adicionar Membro
            </v-btn>
          </div>
        </template>
      </v-tooltip>
    </div>
  </v-card-title>

  <div v-if="membros.length === 0" class="text-center pa-8 text-grey-darken-1">
    <v-icon size="48" class="mb-4">mdi-account-group-outline</v-icon>
    <p>Nenhum membro registrado.</p>
  </div>

  <v-card-text v-else class="pa-0">
    <v-list lines="two">
      <v-list-item
        v-for="membro in membros"
        :key="membro.id_membro"
        :title="membro.usuario?.nome"
        :subtitle="membro.usuario?.email"
      >
        <template v-slot:prepend>
          <v-avatar color="green-darken-4">
            <span class="text-h6">{{ membro.usuario?.nome.charAt(0) }}</span>
          </v-avatar>
        </template>
        <template v-slot:append>
          <v-btn
            v-if="canCreateTasks && authUserId !== membro.id_usuario"
            icon="mdi-delete-outline"
            color="red-lighten-1"
            variant="text"
            size="small"
            @click="emit('remove-member', membro)"
          >
            <v-tooltip activator="parent" location="top">Remover Membro</v-tooltip>
          </v-btn>
        </template>
      </v-list-item>
    </v-list>
  </v-card-text>
</template>