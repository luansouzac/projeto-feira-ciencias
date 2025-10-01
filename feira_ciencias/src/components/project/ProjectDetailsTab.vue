<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
})

// Funções utilitárias (podem vir de um composable)
const getFullStorageUrl = (filePath) => {
  if (!filePath) return null
  return `${import.meta.env.VITE_API_BASE_URL}/storage/${filePath}`
}

const formatDate = (dateString) => {
  if (!dateString) return 'Não definida'
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const getInitials = (name) => {
  if (!name) return ''
  const names = name.split(' ')
  if (names.length > 1) {
    return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}
</script>

<template>
  <v-card-text class="pa-4 pa-md-6">
    <div class="mb-8">
      <p class="text-h5 font-weight-bold mb-1">Sobre o Projeto</p>
      <v-divider class="mb-4"></v-divider>
      <p class="font-weight-bold mb-1">O Problema a ser Resolvido</p>
      <p class="text-medium-emphasis mb-4">{{ project.problema }}</p>

      <p class="font-weight-bold mb-1">Relevância e Justificativa</p>
      <p class="text-medium-emphasis">{{ project.relevancia }}</p>
    </div>

    <v-row>
      <v-col cols="12" md="6">
        <p class="text-h5 font-weight-bold mb-1">Pessoas-Chave</p>
        <v-divider class="mb-2"></v-divider>
        <v-list lines="two" bg-color="transparent">
          <v-list-item v-if="project.responsavel" class="pa-0">
            <template v-slot:prepend>
              <v-avatar color="green-darken-3" class="mr-4">
                <v-img v-if="project.responsavel.photo" :src="getFullStorageUrl(project.responsavel.photo)" :alt="project.responsavel.nome"></v-img>
                <span v-else class="text-white font-weight-bold">{{ getInitials(project.responsavel.nome) }}</span>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-bold">{{ project.responsavel.nome }}</v-list-item-title>
            <v-list-item-subtitle>Responsável pelo Projeto</v-list-item-subtitle>
          </v-list-item>

          <v-divider v-if="project.responsavel && project.orientador" class="my-2"></v-divider>

          <v-list-item v-if="project.orientador" class="pa-0">
            <template v-slot:prepend>
              <v-avatar color="green-darken-3" class="mr-4">
                <v-img v-if="project.orientador.photo" :src="getFullStorageUrl(project.orientador.photo)" :alt="project.orientador.nome"></v-img>
                <span v-else class="text-white font-weight-bold">{{ getInitials(project.orientador.nome) }}</span>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-bold">{{ project.orientador.nome }}</v-list-item-title>
            <v-list-item-subtitle>Orientador</v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </v-col>

      <v-col cols="12" md="6">
        <p class="text-h5 font-weight-bold mb-1">Informações Gerais</p>
        <v-divider class="mb-2"></v-divider>
        <v-list bg-color="transparent">
          <v-list-item class="px-1">
            <template v-slot:prepend>
              <v-icon color="green-darken-2" class="mr-3">mdi-account-group-outline</v-icon>
            </template>
            <v-list-item-title>Tamanho Máximo da Equipe</v-list-item-title>
            <template v-slot:append>
              <v-chip color="green-darken-1" variant="tonal" size="small">{{ project.max_pessoas }} pessoas</v-chip>
            </template>
          </v-list-item>
          
          <v-divider class="my-2"></v-divider>

          <v-list-item class="px-1">
            <template v-slot:prepend>
              <v-icon color="green-darken-2" class="mr-3">mdi-calendar-plus</v-icon>
            </template>
            <v-list-item-title>Data de Criação</v-list-item-title>
            <v-list-item-subtitle>{{ formatDate(project.created_at || project.data_criacao) }}</v-list-item-subtitle>
          </v-list-item>

          <v-divider class="my-2"></v-divider>

          <v-list-item class="px-1">
            <template v-slot:prepend>
              <v-icon color="green-darken-2" class="mr-3">mdi-calendar-check</v-icon>
            </template>
            <v-list-item-title>Data de Aprovação</v-list-item-title>
            <v-list-item-subtitle>{{ formatDate(project.data_aprovacao) }}</v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </v-col>
    </v-row>
  </v-card-text>
</template>