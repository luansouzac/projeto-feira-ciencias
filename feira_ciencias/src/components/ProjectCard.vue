<script setup>
import { computed, defineProps, defineEmits } from 'vue'

const props = defineProps({
  projeto: { type: Object, required: true }
})

const emit = defineEmits(['ver-detalhes'])

const statusMap = {
  1: { text: 'Em Elaboração', color: 'orange', icon: 'mdi-pencil-ruler' },
  2: { text: 'Aprovado', color: 'green', icon: 'mdi-check-decagram' },
  3: { text: 'Reprovado', color: 'red', icon: 'mdi-close-octagon' },
  4: { text: 'Com Ressalvas', color: 'orange', icon: 'mdi-alert-circle-outline' },
}

const statusInfo = computed(() => {
  return statusMap[props.projeto.id_situacao] || { text: 'Pendente', color: 'grey', icon: 'mdi-help-circle' }
})

const memberCount = computed(() => {
  return props.projeto.equipe?.[0]?.membro_equipe?.length || 0;
})

const formatDate = (dateString) => {
  if (!dateString) return 'Data não definida';
  // Adiciona a correção de fuso horário para garantir que a data exibida seja a correta
  const date = new Date(dateString)
  const userTimezoneOffset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() + userTimezoneOffset).toLocaleDateString('pt-BR');
}

function emitVerDetalhes() {
  emit('ver-detalhes', props.projeto.id_projeto)
}
</script>

<template>
  <v-card class="d-flex flex-column" height="100%" hover variant="outlined">
    <v-card-item class="pb-2">
      <div class="d-flex align-start justify-space-between ga-2 mb-2">
        <v-card-title class="text-wrap me-2 pa-0 text-body-1 font-weight-bold">{{ projeto.titulo }}</v-card-title>
        <v-chip :color="statusInfo.color" :prepend-icon="statusInfo.icon" size="small" label variant="tonal">
          {{ statusInfo.text }}
        </v-chip>
      </div>
      <v-chip v-if="projeto.eventos?.nome" color="green" prepend-icon="mdi-calendar-star" size="x-small" label variant="tonal">
        {{ projeto.eventos.nome }}
      </v-chip>
    </v-card-item>

    <v-card-text class="py-3">
      <v-row dense>
        <v-col cols="12" sm="6">
          <div class="d-flex align-center text-caption">
            <v-icon start color="green-darken-2">mdi-account-tie-outline</v-icon>
            <span class="text-truncate">{{ projeto.orientador?.nome || 'Orientador não definido' }}</span>
          </div>
        </v-col>
        <v-col cols="12" sm="6">
          <div class="d-flex align-center text-caption">
            <v-icon start color="green-darken-2">mdi-calendar-start</v-icon>
            <span>{{ formatDate(projeto.eventos?.data_evento) }}</span>
          </div>
        </v-col>
      </v-row>

      <div class="mt-3">
        <div class="d-flex justify-space-between align-center text-caption font-weight-medium">
          <span>MEMBROS</span>
          <span>{{ memberCount }} de {{ projeto.max_pessoas }} vagas</span>
        </div>
        <v-progress-linear
            :model-value="(memberCount / projeto.max_pessoas) * 100"
            :color="memberCount >= projeto.max_pessoas ? 'red' : 'green-darken-1'"
            height="6"
            rounded
            class="mt-1"
          ></v-progress-linear>
      </div>
    </v-card-text>

    <v-spacer></v-spacer>
    <v-divider></v-divider>

    <v-card-actions class="pa-2">
      <slot name="actions"></slot>
      <v-spacer></v-spacer>
      <v-btn
        variant="text"
        color="green-darken-2"
        @click="emitVerDetalhes"
        size="small"
      >
        Ver detalhes
        <v-icon end>mdi-arrow-right</v-icon>
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<style scoped>
.v-card-title.text-wrap {
  white-space: normal;
  line-height: 1.3em;
}
/* Estilo opcional para garantir que o texto truncado não quebre o layout */
.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>