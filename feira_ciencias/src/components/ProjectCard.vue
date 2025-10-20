<script setup>
import { computed, defineProps, defineEmits } from 'vue'

const props = defineProps({
  projeto: { type: Object, required: true },
  contexto: {
    type: String,
    default: 'inscricao',
    validator: (value) => ['inscricao', 'gerenciamento'].includes(value),
  },
  inscrito: { type: Boolean, default: false },
})

// ✅ Evento 'ver-resultados' adicionado para funcionalidade completa
const emit = defineEmits(['ver-detalhes', 'ver-resultados'])

// ✅ LÓGICA SIMPLIFICADA: O statusInfo agora apenas mapeia o status
// que já foi calculado pelo componente pai.
const statusMap = {
  // Status de Inscrição (para alunos)
  'Vagas Abertas': { color: 'green', icon: 'mdi-account-check-outline' },
  'Esgotado': { color: 'red', icon: 'mdi-account-off-outline' },
  // Status de Gerenciamento (para professores/admins)
  'Em Análise': { color: 'orange', icon: 'mdi-file-search-outline' },
  'Aprovado': { color: 'green', icon: 'mdi-check-decagram' },
  'Reprovado': { color: 'red', icon: 'mdi-close-octagon' },
  'Com Ressalvas': { color: 'orange', icon: 'mdi-alert-circle-outline' },
  'Em Desenvolvimento': { color: 'blue', icon: 'mdi-progress-wrench' },
  'Concluído': { color: 'purple', icon: 'mdi-trophy-check' },
}

const statusInfo = computed(() => {
  // Retorna o objeto de cor/ícone com base no status que o projeto já tem
  return statusMap[props.projeto.status] || { text: 'Pendente', color: 'grey', icon: 'mdi-help-circle' }
})


// Função de data ajustada para formatar datas de submissão/evento
const formatDate = (dateString) => {
  if (!dateString) return 'Não definida'
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR', {
    timeZone: 'UTC',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function emitVerDetalhes() {
  emit('ver-detalhes', props.projeto.id)
}
function emitVerResultados() {
  emit('ver-resultados', props.projeto.id)
}
</script>

<template>
  <v-card
    class="d-flex flex-column"
    height="100%"
    hover
    :variant="inscrito ? 'tonal' : 'outlined'"
    :color="inscrito ? 'green-darken-3' : undefined"
  >
    <div
      v-if="inscrito"
      class="d-flex align-center pa-1 bg-green-darken-3 text-caption justify-center"
    >
      <v-icon start size="small">mdi-check-circle</v-icon>
      INSCRITO
    </div>

    <v-card-item class="pb-2">
      <div class="d-flex align-start justify-space-between ga-2 mb-2">
        <v-card-title class="text-wrap me-2 pa-0 text-body-1 font-weight-bold">{{
          projeto.titulo
        }}</v-card-title>
        <v-chip
          :color="statusInfo.color"
          :prepend-icon="statusInfo.icon"
          size="small"
          label
          variant="tonal"
        >
          {{ projeto.status }}
        </v-chip>
      </div>
      <v-chip
        v-if="projeto.eventos?.nome"
        color="grey"
        prepend-icon="mdi-calendar-star"
        size="x-small"
        label
      >
        {{ projeto.eventos.nome }}
      </v-chip>
    </v-card-item>

    <v-card-text class="py-3">
      <template v-if="contexto === 'inscricao'">
        <p class="text-body-2 text-medium-emphasis mb-4 text-truncate-3-lines">
          {{ projeto.problema || 'Descrição não fornecida.' }}
        </p>
        <v-divider></v-divider>
        <div class="mt-3">
          <div class="d-flex align-center text-caption mb-2">
            <v-icon start color="grey-darken-2">mdi-account-tie-outline</v-icon>
            Orientador:
            <span class="font-weight-bold ml-1">{{
              projeto.orientador?.nome || 'Não definido'
            }}</span>
          </div>
          <div class="d-flex justify-space-between align-center text-caption font-weight-medium">
            <span>INSCRIÇÕES</span>
            <!-- ✅ CORREÇÃO: Usando as propriedades 'inscritos' e 'maxAlunos' do projeto -->
            <span>{{ projeto.inscritos }} de {{ projeto.maxAlunos }} vagas</span>
          </div>
          <v-progress-linear
            :model-value="(projeto.inscritos / projeto.maxAlunos) * 100"
            :color="projeto.inscritos >= projeto.maxAlunos ? 'red' : 'green-darken-1'"
            height="6"
            rounded
            class="mt-1"
          ></v-progress-linear>
        </div>
      </template>

      <template v-if="contexto === 'gerenciamento'">
        <v-list density="compact" bg-color="transparent" lines="two">
          <v-list-item class="px-0" prepend-icon="mdi-account-school-outline">
            <v-list-item-title class="font-weight-medium">{{
              projeto.responsavel?.nome || 'Não definido'
            }}</v-list-item-title>
            <v-list-item-subtitle>Aluno Responsável</v-list-item-subtitle>
          </v-list-item>

          <v-list-item class="px-0" prepend-icon="mdi-account-tie-outline">
            <v-list-item-title class="font-weight-medium">{{
              projeto.orientador?.nome || 'Não definido'
            }}</v-list-item-title>
            <v-list-item-subtitle>Professor Orientador</v-list-item-subtitle>
          </v-list-item>

          <v-list-item
            v-if="projeto.coorientador"
            class="px-0"
            prepend-icon="mdi-account-tie-outline"
          >
            <v-list-item-title class="font-weight-medium">{{
              projeto.coorientador?.nome
            }}</v-list-item-title>
            <v-list-item-subtitle>Coorientador</v-list-item-subtitle>
          </v-list-item>

          <v-list-item class="px-0" prepend-icon="mdi-calendar-clock-outline">
            <v-list-item-title class="font-weight-medium">
              {{ formatDate(projeto.eventos?.inicio_submissao) }} -
              {{ formatDate(projeto.eventos?.fim_submissao) }}
            </v-list-item-title>
            <v-list-item-subtitle>Período de Submissão</v-list-item-subtitle>
          </v-list-item>
        </v-list>
      </template>
    </v-card-text>

    <v-spacer></v-spacer>
    <v-divider></v-divider>

    <v-card-actions class="pa-2">
      <slot name="actions-start">
        <v-btn variant="text" color="grey-darken-2" @click="emitVerDetalhes" size="small">
          Ver Detalhes
        </v-btn>
      </slot>
      <v-spacer></v-spacer>

      <!-- ✅ Botão de "Resultados" adicionado -->
      <v-btn
        v-if="contexto === 'gerenciamento' && projeto.id_situacao > 2"
        color="green-darken-3"
        variant="flat"
        size="small"
        @click="emitVerResultados"
      >
        Resultados
      </v-btn>

      <slot name="actions"></slot>
    </v-card-actions>
  </v-card>
</template>

<style scoped>
.v-card-title.text-wrap {
  line-height: 1.3em;
}
.text-truncate-3-lines {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  min-height: 60px;
}
</style>

