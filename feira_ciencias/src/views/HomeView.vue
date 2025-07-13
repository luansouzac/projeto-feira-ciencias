<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const carregando = ref(false) 
const erro = ref(null)

const user = localStorage.getItem('user')

const nomeUsuario = ref(user ? JSON.parse(user).nome : '')

const todosProjetos = ref([
    { id_projeto: 1, titulo: 'Sistema de Irrigação Automatizado', problema: 'Alto consumo de água na agricultura familiar da região.', id_situacao: 1 },
    { id_projeto: 2, titulo: 'Plataforma de Gestão de TCCs', problema: 'Dificuldade no acompanhamento e versionamento dos trabalhos de conclusão.', id_situacao: 2 },
    { id_projeto: 3, titulo: 'Análise de Sentimentos em Redes Sociais', problema: 'Compreender a percepção pública sobre a instituição durante eventos.', id_situacao: 2 },
    { id_projeto: 4, titulo: 'Protótipo de Baixo Custo para IoT', problema: 'Falta de hardware acessível para ensino de Internet das Coisas.', id_situacao: 3 },
]);
const filtroStatus = ref('Todos')

const opcoesStatus = [
  { title: 'Todos', value: 'Todos' },
  { title: 'Em Elaboração', value: 1 },
  { title: 'Aprovado', value: 2 },
  { title: 'Reprovado', value: 3 },
]

const statusMap = {
  1: { text: 'Em Elaboração', color: 'orange-darken-2' },
  2: { text: 'Aprovado', color: 'green-darken-2' },
  3: { text: 'Reprovado', color: 'red-darken-2' },
}


onMounted(() => {
  console.log('Componente montado com dados de exemplo.');
})

const projetosFiltrados = computed(() => {
  if (filtroStatus.value === 'Todos' || !filtroStatus.value) {
    return todosProjetos.value
  }
  return todosProjetos.value.filter(p => p.id_situacao === filtroStatus.value)
})

const totalProjetos = computed(() => todosProjetos.value.length)
const projetosAprovados = computed(() => todosProjetos.value.filter(p => p.id_situacao === 2).length)

const goToProjectDetails = (id) => {
  router.push(`/projetos/${id}`)
}

const criarNovoProjeto = () => {
  router.push('/projetos/novo')
}
</script>

<template>
  <v-container fluid>
    <v-row class="mb-6">
      <v-col cols="12">
        <h1 class="text-h4 font-weight-bold text-grey-darken-4">
          Painel Inicial
        </h1>
        <p class="text-subtitle-1 text-grey-darken-1">
          Bem-vindo de volta, {{ nomeUsuario }}!
        </p>
      </v-col>
    </v-row>

    <v-row class="mb-8">
      <v-col cols="12" sm="6" md="4">
        <v-card color="green-darken-4" dark class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-plus-box-multiple</v-icon>
              <div>
                <div class="text-h5 font-weight-bold">Novo Projeto</div>
                <div class="text-subtitle-1">Inicie uma nova proposta</div>
              </div>
            </div>
          </v-card-text>
          <v-spacer></v-spacer>
          <v-card-actions>
            <v-btn
              variant="outlined"
              block
              @click="criarNovoProjeto"
            >
              Criar agora
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4">
        <v-card variant="tonal" color="grey-darken-1" class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-folder-account-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-grey-darken-4">{{ totalProjetos }}</div>
                <div class="text-subtitle-2 text-grey-darken-2">Projetos Registrados</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4">
        <v-card variant="tonal" color="green-darken-2" class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-check-decagram-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-green-darken-4">{{ projetosAprovados }}</div>
                <div class="text-subtitle-2 text-green-darken-3">Projetos Aprovados</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-divider class="my-6"></v-divider>

    <v-row align="center" class="mb-4">
      <v-col cols="12" md="6">
        <h2 class="text-h5 font-weight-bold text-grey-darken-4">Meus Projetos</h2>
        <p class="text-subtitle-2 text-grey-darken-1">Gerencie e acompanhe o andamento de suas propostas.</p>
      </v-col>
      <v-col cols="12" md="6" class="d-flex justify-md-end">
        <v-select
          v-model="filtroStatus"
          :items="opcoesStatus"
          label="Filtrar por Status"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 280px;"
        ></v-select>
      </v-col>
    </v-row>
    
    <v-row>
      <v-col v-if="projetosFiltrados.length === 0" cols="12">
        <v-card flat border class="text-center pa-8">
          <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-folder-search-outline</v-icon>
          <p class="text-grey-darken-1">Nenhum projeto encontrado com os filtros selecionados.</p>
        </v-card>
      </v-col>

      <v-col
        v-for="projeto in projetosFiltrados"
        :key="projeto.id_projeto"
        cols="12" sm="6" lg="4"
      >
        <v-card class="d-flex flex-column" height="100%" hover variant="outlined">
          <v-card-item>
            <div>
              <div class="d-flex justify-space-between align-center mb-2">
                <v-card-title class="text-wrap py-0">
                  {{ projeto.titulo }}
                </v-card-title>
                <v-chip
                  :color="statusMap[projeto.id_situacao]?.color || 'grey'"
                  size="small"
                  label
                >
                  {{ statusMap[projeto.id_situacao]?.text || 'Desconhecido' }}
                </v-chip>
              </div>
            </div>
          </v-card-item>

          <v-card-text class="py-2">
            <p class="text-body-2 text-grey-darken-2">
              {{ projeto.problema }}
            </p>
          </v-card-text>
          
          <v-spacer></v-spacer>

          <v-divider></v-divider>
          <v-card-actions>
            <v-btn
              color="green-darken-3"
              variant="tonal"
              @click="goToProjectDetails(projeto.id_projeto)"
            >
              Ver Detalhes
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn icon="mdi-pencil" variant="text" size="small"></v-btn>
            <v-btn icon="mdi-delete" variant="text" color="grey" size="small"></v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

  </v-container>
</template>

<style scoped>
.v-card-title.text-wrap {
  white-space: normal;
  line-height: 1.3em;
  font-weight: 500;
}
.v-card-text {
  min-height: 60px; /* Garante alinhamento visual dos cards */
}
</style>