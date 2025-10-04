<script setup>
import { ref, computed, defineProps } from 'vue'
import { useFileUtils } from '@/composables/useFileUtils'

const { getFullStorageUrl, isImage } = useFileUtils()

const props = defineProps({
  feedbacks: { type: Array, required: true },
})

// --- NOVA LÓGICA DE FILTRO (APENAS POR NOME) ---
const authorSearchQuery = ref('')

const filteredFeedbacks = computed(() => {
  let items = [...props.feedbacks]

  // 1. Filtra por nome do autor (se houver texto na busca)
  if (authorSearchQuery.value && authorSearchQuery.value.trim() !== '') {
    const searchTerm = authorSearchQuery.value.toLowerCase().trim()
    items = items.filter(fb => 
      fb.author && fb.author.name && fb.author.name.toLowerCase().includes(searchTerm)
    )
  }
  
  // 2. Ordena o resultado final por data
  return items.sort((a, b) => new Date(b.date) - new Date(a.date))
})

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
}

const getInitials = (name) => {
  if (!name) return '?'
  const names = name.split(' ')
  if (names.length > 1) return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase()
  return name.substring(0, 2).toUpperCase()
}
</script>

<template>
  <v-card-text class="pa-4 pa-md-6">
    <div v-if="feedbacks.length > 0" class="mb-6">
      <v-text-field
        v-model="authorSearchQuery"
        label="Buscar por autor"
        placeholder="Digite o nome do autor..."
        variant="outlined"
        prepend-inner-icon="mdi-magnify"
        density="compact"
        clearable
        hide-details
      ></v-text-field>
    </div>

    <div v-if="feedbacks.length === 0" class="text-center pa-8 text-grey-darken-1">
      <v-icon size="48" class="mb-4">mdi-comment-processing-outline</v-icon>
      <p>Nenhum evento foi registrado para este projeto ainda.</p>
    </div>
    <div v-else-if="filteredFeedbacks.length === 0" class="text-center pa-8 text-grey-darken-1">
      <v-icon size="48" class="mb-4">mdi-account-search-outline</v-icon>
      <p>Nenhum evento encontrado para o autor "{{ authorSearchQuery }}".</p>
    </div>

    <v-expansion-panels v-else variant="inset">
        <v-expansion-panel
          v-for="fb in filteredFeedbacks"
          :key="fb.id"
          elevation="2"
          class="mb-2 fade-list-item"
        >
          <v-expansion-panel-title class="py-3">
            <div class="d-flex align-center w-100">
              <v-avatar size="32" class="mr-4">
                <v-img v-if="fb.author.photo" :src="getFullStorageUrl(fb.author.photo)" :alt="fb.author.name"></v-img>
                <span v-else class="text-caption font-weight-bold">{{ getInitials(fb.author.name) }}</span>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="font-weight-bold">{{ fb.title }}</div>
                <div class="text-caption text-grey">Por: {{ fb.author.name }}</div>
              </div>
              <v-spacer></v-spacer>
              <span class="text-caption text-grey ml-4">{{ formatDateTime(fb.date) }}</span>
            </div>
          </v-expansion-panel-title>
          <transition name="fade-slow">
          <v-expansion-panel-text class="bg-grey-lighten-5 pt-4">
            <blockquote v-if="fb.feedbackText" class="text-body-1 font-italic text-medium-emphasis">
              "{{ fb.feedbackText }}"
            </blockquote>


            <div v-if="fb.arquivo" class="mt-4">
  <p class="text-caption font-weight-bold mb-2 text-grey-darken-1">ARQUIVO ANEXADO:</p>
      <v-img
      v-if="isImage(fb.arquivo)"
      :src="getFullStorageUrl(fb.arquivo)"
      max-height="200"
      aspect-ratio="16/9"
      cover
      class="rounded border mb-2 cursor-pointer"
      @click="() => window.open(getFullStorageUrl(fb.arquivo), '_blank')"
    >
      <v-tooltip activator="parent" location="center">Clique para ampliar</v-tooltip>
    </v-img>
    
    <v-btn
      :href="getFullStorageUrl(fb.arquivo)"
      target="_blank"
      prepend-icon="mdi-download-circle-outline"
      :color="fb.color"
      variant="tonal"
      size="small"
    >
      {{ isImage(fb.arquivo) ? 'Baixar Imagem' : 'Baixar Anexo' }}
    </v-btn>
</div>
          </v-expansion-panel-text>
          </transition>
        </v-expansion-panel>
      
    </v-expansion-panels>
  </v-card-text>
</template>

<style scoped>
.fade-slow-enter-active,
.fade-slow-leave-active {
  transition: opacity 0.4s ease;
}
.fade-slow-enter-from,
.fade-slow-leave-to {
  opacity: 0;
}
</style>