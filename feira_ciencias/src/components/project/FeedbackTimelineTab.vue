<script setup>
import { defineProps } from 'vue'
const { getFullStorageUrl, isImage } = useFileUtils()

const props = defineProps({
  feedbacks: { type: Array, required: true },
})
</script>

<template>
  <v-card-text class="pa-4 pa-md-6">
    <div v-if="feedbacks.length === 0" class="text-center pa-8 text-grey-darken-1">
      <v-icon size="48" class="mb-4">mdi-comment-processing-outline</v-icon>
      <p>Nenhum feedback foi registrado para este projeto ainda.</p>
    </div>
    <v-timeline v-else side="end" align="start">
      <v-timeline-item
        v-for="fb in feedbacks"
        :key="fb.id"
        :dot-color="fb.color"
        :icon="fb.icon"
        size="small"
      >
        <div class="feedback-item">
          <div class="font-weight-bold">{{ fb.title }}</div>
          <p class="text-body-2 mt-2 font-italic">"{{ fb.feedbackText }}"</p>
          <div v-if="fb.arquivo" class="mt-3">
            <v-btn
              :href="getFullStorageUrl(fb.arquivo)"
              target="_blank"
              prepend-icon="mdi-download-circle-outline"
              color="purple-darken-1"
              variant="tonal"
              size="small"
            >
              Ver Anexo
            </v-btn>
          </div>
          <div class="text-caption opacity-75 mt-3">Por: {{ fb.author }}</div>
        </div>
      </v-timeline-item>
    </v-timeline>
  </v-card-text>
</template>

<style scoped>
/* Estilos que pertencem apenas a este componente */
.feedback-item {
  border-left: 3px solid #e0e0e0;
  padding-left: 16px;
}
</style>