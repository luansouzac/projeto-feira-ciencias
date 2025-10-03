<script setup>
import { defineProps } from 'vue'
import { useFileUtils } from '@/composables/useFileUtils'

const { getFullStorageUrl, isImage } = useFileUtils()

const props = defineProps({
  feedbacks: { type: Array, required: true },
})

// Nova função para formatar data E hora de forma mais completa
const formatDateTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
  })
}
</script>

<template>
  <v-card-text class="pa-4 pa-md-6">
    <div v-if="feedbacks.length === 0" class="text-center pa-8 text-grey-darken-1">
      <v-icon size="48" class="mb-4">mdi-comment-processing-outline</v-icon>
      <p>Nenhum feedback foi registrado para este projeto ainda.</p>
    </div>
    
    <v-timeline v-else side="end" align="start" line-inset="8">
      <v-timeline-item
        v-for="fb in feedbacks"
        :key="fb.id"
        :dot-color="fb.color"
        :icon="fb.icon"
        size="small"
        fill-dot
      >
        <v-sheet border rounded="lg" class="pa-4">
          <div class="d-flex justify-space-between align-center flex-wrap">
            <span class="font-weight-bold">{{ fb.title }}</span>
            <span class="text-caption text-grey">{{ formatDateTime(fb.date) }}</span>
          </div>

          <v-divider class="my-3"></v-divider>

          <div>
            <blockquote class="text-body-1 font-italic text-medium-emphasis ml-2 mb-4">
              "{{ fb.feedbackText }}"
            </blockquote>

            <div v-if="fb.arquivo" class="mt-3">
              <v-img
                v-if="isImage(fb.arquivo)"
                :src="getFullStorageUrl(fb.arquivo)"
                max-height="200"
                aspect-ratio="16/9"
                cover
                class="rounded border mb-2"
              ></v-img>
              <v-btn
                :href="getFullStorageUrl(fb.arquivo)"
                target="_blank"
                prepend-icon="mdi-download-circle-outline"
                :color="fb.color"
                variant="tonal"
                size="small"
              >
                {{ isImage(fb.arquivo) ? 'Ver Imagem' : 'Ver Anexo' }}
              </v-btn>
            </div>
          </div>

          <div class="text-caption text-right mt-4">
            - {{ fb.author }}
          </div>
        </v-sheet>
      </v-timeline-item>
    </v-timeline>
  </v-card-text>
</template>