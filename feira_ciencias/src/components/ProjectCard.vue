<script setup>
import { computed } from 'vue'

// O componente ainda recebe o objeto 'projeto' como sua principal fonte de dados.
const props = defineProps({
  projeto: {
    type: Object,
    required: true,
  }
})

// O único evento que o card emite por padrão é 'ver-detalhes'.
// Outras ações (editar, excluir, avaliar) serão inseridas via slots.
const emit = defineEmits(['ver-detalhes'])

// O mapa de status continua aqui para o componente ser autossuficiente.
const statusMap = {
  1: { text: 'Em Elaboração', color: 'orange-darken-2' },
  2: { text: 'Aprovado', color: 'green-darken-2' },
  3: { text: 'Reprovado', color: 'red-darken-2' },
  4: { text: 'Reprovado com Ressalvas', color: 'orange-darken-2' },
}

const statusInfo = computed(() => {
  return statusMap[props.projeto.id_situacao] || { text: 'Pendente', color: 'grey' }
})

// Função para manter a emissão do evento de detalhes limpa.
function emitVerDetalhes() {
  emit('ver-detalhes', props.projeto.id_projeto)
}
</script>

<template>
  <v-card class="d-flex flex-column" height="100%" hover variant="outlined">
    <v-img
      height="180"
      :src="projeto.imagem_url || 'https://cdn.vuetifyjs.com/images/cards/dunes.jpg'"
      cover
      class="text-white"
    >
      <v-toolbar color="rgba(0, 0, 0, 0.3)" theme="dark">
        <v-toolbar-title class="text-body-2 text-truncate">
          {{ projeto.eventos?.nome || 'Evento não definido' }}
        </v-toolbar-title>
      </v-toolbar>
    </v-img>

    <v-card-item class="pt-4 pb-2">
      <div class="d-flex justify-space-between align-start mb-2">
        <v-card-title class="text-wrap me-2 pa-0">{{ projeto.titulo }}</v-card-title>
        <v-chip :color="statusInfo.color" size="small" label>{{ statusInfo.text }}</v-chip>
      </div>
    </v-card-item>

    <v-card-text class="py-2">
       <p class="text-body-2 text-grey-darken-2 mb-4 text-truncate-3-lines">
        {{ projeto.problema || 'Descrição não fornecida.' }}
      </p>

      <v-row dense>
        <v-col cols="12" sm="6">
          <div class="d-flex align-center">
            <v-icon start color="grey-darken-1">mdi-account-school-outline</v-icon>
            <div>
              <div class="text-caption text-grey-darken-1">Responsável</div>
              <div class="text-body-2 font-weight-medium text-truncate">
                {{ projeto.responsavel?.nome || 'Não informado' }}
              </div>
            </div>
          </div>
        </v-col>
        <v-col cols="12" sm="6">
          <div class="d-flex align-center">
            <v-icon start color="grey-darken-1">mdi-account-tie-outline</v-icon>
            <div>
              <div class="text-caption text-grey-darken-1">Orientador</div>
              <div class="text-body-2 font-weight-medium text-truncate">
                {{ projeto.orientador?.nome || 'Não informado' }}
              </div>
            </div>
          </div>
        </v-col>
      </v-row>
    </v-card-text>

    <v-spacer></v-spacer>
    <v-divider></v-divider>

    <v-card-actions class="pa-3">
      <v-btn
        variant="text"
        color="grey-darken-2"
        @click="emitVerDetalhes"
      >
        Ver Detalhes
      </v-btn>
      <v-spacer></v-spacer>

      <slot name="actions"></slot>
    </v-card-actions>
  </v-card>
</template>

<style scoped>
.v-card-title.text-wrap {
  white-space: normal;
  line-height: 1.3em;
  font-weight: 500;
}

/* Limita o texto da descrição a 3 linhas e adiciona "..." no final */
.text-truncate-3-lines {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  min-height: 60px; /* 3 linhas * 20px de altura de linha aproximada */
}
</style>