<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/assets/plugins/axios.js'
import ProjectCard from '@/components/ProjectCard.vue'

const router = useRouter()

// --- ESTADOS DA PÁGINA ---
const projetos = ref([]) // Armazena a lista plana da API
const carregando = ref(true)
const erro = ref(null)
const filtroBusca = ref('')
const viewMode = ref('grid')
let userId = null

// --- OBTÉM DADOS DO USUÁRIO LOGADO ---
const userDataString = sessionStorage.getItem('user_data')
if (userDataString) {
  const userData = JSON.parse(userDataString)
  userId = userData.user.id_usuario
}

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  if (!userId) {
    erro.value = 'Usuário não autenticado.'
    carregando.value = false
    return
  }

  carregando.value = true
  try {
    // Chama o novo endpoint!
    const { data } = await api.get(`/usuarios/${userId}/projetos-inscritos`)
    projetos.value = data
  } catch (err) {
    console.error('Erro ao buscar projetos inscritos:', err)
    erro.value = 'Não foi possível carregar seus projetos.'
  } finally {
    carregando.value = false
  }
})

// --- DADOS PROCESSADOS E AGRUPADOS POR EVENTO ---
const projetosPorEvento = computed(() => {
  if (!projetos.value) return []

  // Agrupa os projetos por evento
  const grupos = projetos.value.reduce((acc, projeto) => {
    const eventoId = projeto.eventos?.id_evento || 'sem-evento'
    if (!acc[eventoId]) {
      acc[eventoId] = {
        evento: projeto.eventos || { nome: 'Projetos sem Evento Associado' },
        projetos: [],
      }
    }
    acc[eventoId].projetos.push(projeto)
    return acc
  }, {})

  // Converte o objeto de grupos em um array e aplica o filtro de busca
  return Object.values(grupos)
    .map((grupo) => {
      if (!filtroBusca.value) {
        return grupo // Retorna o grupo inteiro se não houver busca
      }
      // Filtra os projetos dentro de cada grupo
      const projetosFiltrados = grupo.projetos.filter((p) =>
        p.titulo.toLowerCase().includes(filtroBusca.value.toLowerCase()),
      )
      // Só retorna o grupo se ele tiver algum projeto após a filtragem
      return projetosFiltrados.length > 0 ? { ...grupo, projetos: projetosFiltrados } : null
    })
    .filter(Boolean) // Remove os grupos que ficaram nulos (vazios)
})

function getEventStatus(evento) {
  if (!evento || !evento.inicio_inscricao || !evento.fim_inscricao) {
    return { text: 'Status indefinido', color: 'grey', icon: 'mdi-help-circle-outline' }
  }
  const agora = new Date()
  const inicioInscricao = new Date(evento.inicio_inscricao)
  const fimInscricao = new Date(evento.fim_inscricao)
  fimInscricao.setHours(23, 59, 59, 999) // Garante que o dia final seja incluído
  const dataEvento = new Date(evento.data_evento)

  if (agora < inicioInscricao) {
    return { text: 'Inscrições em Breve', color: 'blue-grey', icon: 'mdi-clock-start' }
  } else if (agora >= inicioInscricao && agora <= fimInscricao) {
    return { text: 'Inscrições Abertas', color: 'green', icon: 'mdi-lock-open-variant-outline' }
  } else if (agora > fimInscricao && agora < dataEvento) {
    return { text: 'Inscrições Encerradas', color: 'orange', icon: 'mdi-lock-outline' }
  } else {
    return { text: 'Evento Finalizado', color: 'grey-darken-1', icon: 'mdi-check-circle-outline' }
  }
}

function goToProjectDetails(id) {
  router.push(`/projetos/${id}`)
}
</script>

<template>
  <v-container fluid>
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">
      Voltar
    </v-btn>
    
    <h1 class="text-h4 font-weight-bold text-grey-darken-4">Meus Projetos Inscritos</h1>
    <p class="text-subtitle-1 text-grey-darken-1 mb-6">
      Projetos nos quais você está participando, agrupados por evento.
    </p>
    
    <v-card class="mb-8 pa-4" variant="outlined">
      <v-row align="center" no-gutters>
        <v-col cols="12" md="8">
          <v-text-field
            v-model="filtroBusca"
            label="Buscar por título do projeto..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
            clearable
          ></v-text-field>
        </v-col>
        <v-col cols="12" md="4" class="d-flex justify-start justify-md-end mt-4 mt-md-0">
          <v-btn-toggle v-model="viewMode" mandatory density="compact" variant="outlined">
            <v-btn value="grid" aria-label="Visualização em Grade">
              <v-icon>mdi-view-grid-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Grade</v-tooltip>
            </v-btn>
            <v-btn value="list" aria-label="Visualização em Lista">
              <v-icon>mdi-view-list-outline</v-icon>
              <v-tooltip activator="parent" location="bottom">Lista</v-tooltip>
            </v-btn>
          </v-btn-toggle>
        </v-col>
      </v-row>
    </v-card>

    <v-row v-if="carregando">
      <v-col v-for="n in 6" :key="n" cols="12" sm="6" lg="4">
        <v-skeleton-loader type="image, article, actions"></v-skeleton-loader>
      </v-col>
    </v-row>

    <v-alert v-else-if="erro" type="error" border="start" variant="tonal">
      {{ erro }}
    </v-alert>

    <div v-else-if="projetosPorEvento.length === 0">
      <v-card flat border class="text-center pa-8">
        <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-folder-search-outline</v-icon>
        <p class="text-grey-darken-1">
          {{ filtroBusca ? 'Nenhum projeto encontrado para sua busca.' : 'Você ainda não está inscrito em nenhum projeto.' }}
        </p>
      </v-card>
    </div>
    
    <div v-else class="d-flex flex-column ga-8">
      <v-card 
        v-for="grupo in projetosPorEvento" 
        :key="grupo.evento.id_evento" 
        variant="outlined"
      >
        <v-card-title class="d-flex align-center justify-space-between flex-wrap">
          <span class="text-h6 font-weight-medium">{{ grupo.evento.nome }}</span>
          <v-chip
            :color="getEventStatus(grupo.evento).color"
            :prepend-icon="getEventStatus(grupo.evento).icon"
            size="small"
            label
          >
            {{ getEventStatus(grupo.evento).text }}
          </v-chip>
        </v-card-title>
        <v-card-subtitle>
          Data do Evento: {{ new Date(grupo.evento.data_evento).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) }}
        </v-card-subtitle>
        
        <v-divider class="mt-2"></v-divider>

        <v-card-text>
          <v-row v-if="viewMode === 'grid'">
            <v-col
              v-for="projeto in grupo.projetos"
              :key="projeto.id_projeto"
              cols="12"
              sm="6"
              lg="4"
            >
              <ProjectCard :projeto="projeto" @ver-detalhes="goToProjectDetails(projeto.id_projeto)" />
            </v-col>
          </v-row>

          <v-table v-else-if="viewMode === 'list'" hover>
            <thead>
              <tr>
                <th class="text-left">Projeto</th>
                <th class="text-left d-none d-sm-table-cell">Orientador</th>
                <th class="text-left d-none d-md-table-cell">Membros</th>
                <th class="text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="projeto in grupo.projetos"
                :key="`list-${projeto.id_projeto}`"
                @click="goToProjectDetails(projeto.id_projeto)"
                style="cursor: pointer;"
              >
                <td>
                  <div class="font-weight-bold">{{ projeto.titulo }}</div>
                  <div class="text-caption text-grey-darken-1">{{ projeto.area_conhecimento || 'Área não definida' }}</div>
                </td>
                <td class="d-none d-sm-table-cell">{{ projeto.orientador?.nome || 'Não definido' }}</td>
                <td class="d-none d-md-table-cell">
                  <v-icon size="small">mdi-account-group-outline</v-icon>
                  {{ projeto.equipe[0]?.membro_equipe?.length || 0 }}
                </td>
                <td class="text-right">
                  <v-btn color="grey-darken-2" variant="text" size="small">
                    Ver detalhes <v-icon end>mdi-arrow-right</v-icon>
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
      </v-card>
    </div>
  </v-container>
</template>
