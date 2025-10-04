<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js'
import { useNotificationStore } from '@/stores/notification'
import CrudModal from '@/components/CrudModal.vue'
import { useEventoStore } from '@/stores/eventoStore'
import { storeToRefs } from 'pinia'
import ProjectCard from '@/components/ProjectCard.vue'

const router = useRouter()
const notificationStore = useNotificationStore()
const eventoStore = useEventoStore()
const { eventos } = storeToRefs(eventoStore)

// --- ESTADO DA PÁGINA ---
const carregando = ref(true)
const erro = ref(null)
const todosProjetos = ref([])
const filtroStatus = ref('Todos')
const nomeUsuario = ref('')
let userId = null
const avaliadores = ref([])
const totalProjetosAprovados = ref(0)

// --- ESTADO PARA O MODAL ---
const isModalOpen = ref(false)
const isModalLoading = ref(false)

const getInitialFormData = () => ({
  id_evento: null,
  titulo: '',
  problema: '',
  relevancia: '',
  id_orientador: null,
  id_coorientador: null,
})
const currentItem = ref(getInitialFormData())

// --- ESTADO PARA O MODAL DE EXCLUSÃO ---
const isDeleteModalOpen = ref(false)
const projectToDelete = ref(null)

const userDataString = sessionStorage.getItem('user_data')
if (userDataString) {
  const userData = JSON.parse(userDataString)
  nomeUsuario.value = userData.user.nome
  userId = userData.user.id_usuario
}

const modalConfig = computed(() => ({
  title: currentItem.value && currentItem.value.id_projeto ? 'Editar Projeto' : 'Novo Projeto',
  fields: [
    {
      key: 'id_projeto',
      label: 'Identificação do Projeto',
      type: 'id',
    },
    {
      key: 'id_evento',
      label: 'Evento Associado',
      type: 'select',
      items: eventItemsParaSelecao.value,
      rules: [
        (v) => !!v || 'É necessário selecionar um evento',
        (v) => {
          const eventoSelecionado = eventos.value.find((e) => e.id_evento === v)
          if (!eventoSelecionado) return true
          const fimSubmissao = new Date(eventoSelecionado.fim_submissao)
          return (
            new Date() <= fimSubmissao ||
            v === currentItem.value?.id_evento ||
            'O período de submissão para este evento já encerrou.'
          )
        },
      ],
    },
    {
      key: 'titulo',
      label: 'Título do Projeto',
      type: 'text',
      rules: [(v) => !!v || 'O título é obrigatório'],
    },
    {
      key: 'problema',
      label: 'Problema a ser Resolvido',
      type: 'textarea',
      rules: [(v) => !!v || 'A descrição do problema é obrigatória'],
    },
    {
      key: 'relevancia',
      label: 'Relevância do Projeto',
      type: 'textarea',
      rules: [(v) => !!v || 'A relevância é obrigatória'],
    },
    {
      key: 'max_pessoas',
      label: 'Nº Máximo de Participantes',
      type: 'text',
      rules: [(v) => !!v || 'O número máximo de participantes é obrigatório'],
    },
    {
      key: 'id_orientador',
      label: 'Professor orientador',
      type: 'select',
      items: avaliadores.value.map((avaliador) => ({
        title: avaliador.nome,
        value: avaliador.id_usuario,
      })),
      rules: [(v) => !!v || 'O Orientador é obrigatório'],
    },
    {
      key: 'id_coorientador',
      label: 'Professor Coorientador (Opcional)',
      type: 'select',
      items: avaliadores.value.map((avaliador) => ({
        title: avaliador.nome,
        value: avaliador.id_usuario,
      })),
    },
  ],
}))

const opcoesStatus = [
  { title: 'Todos', value: 'Todos' },
  { title: 'Em Elaboração', value: 1 },
  { title: 'Reprovado', value: 3 },
]
const statusMap = {
  1: { text: 'Em Elaboração', color: 'orange-darken-2' },
  3: { text: 'Reprovado', color: 'red-darken-2' },
  4: { text: 'Reprovado com Ressalvas', color: 'orange-darken-2' },
}

// --- MÉTODOS ---
onMounted(async () => {
  if (!userId) {
    erro.value = 'Usuário não encontrado. Por favor, faça o login novamente.'
    carregando.value = false
    return
  }

  const fetchProjetosPromise = api.get(`/projetos?id_responsavel=${userId}&situacao_not=2`)
  const fetchEventosPromise = eventoStore.fetchEventos()
  const fetchAvaliadoresPromise = api.get(`/usuarios?id_tipo_usuario=4`)
  const fetchProjetosInscritosPromise = api.get(`/usuarios/${userId}/projetos-inscritos`)

  try {
    const results = await Promise.allSettled([
      fetchProjetosPromise,
      fetchAvaliadoresPromise,
      fetchEventosPromise,
      fetchProjetosInscritosPromise,
    ])

    const [projetosResult, avaliadoresResult, eventosResult, ProjetosInscritosResult] = results

    if (projetosResult.status === 'fulfilled') {
      todosProjetos.value = projetosResult.value.data
    } else {
      console.error('Erro ao buscar projetos:', projetosResult.reason)
      todosProjetos.value = []
    }

    if (avaliadoresResult.status === 'fulfilled') {
      avaliadores.value = avaliadoresResult.value.data
    } else {
      console.error('Erro ao buscar avaliadores:', avaliadoresResult.reason)
    }
    if (ProjetosInscritosResult.status === 'fulfilled') {
      totalProjetosAprovados.value = ProjetosInscritosResult.value.data.length
    } else {
      console.error('Erro ao buscar contagem de aprovados:', ProjetosInscritosResult.reason)
    }

    if (eventosResult.status === 'rejected') {
      console.error('Erro ao buscar eventos:', eventosResult.reason)
    }
  } catch (geralError) {
    console.error('Ocorreu um erro inesperado:', geralError)
    erro.value = 'Não foi possível carregar os dados da página.'
  } finally {
    carregando.value = false
  }
})

// --- MÉTODOS PARA MODAIS ---

const openCreateModal = () => {
  // ✅ ALTERAÇÃO 4: Garante que o formulário está limpo ao abrir o modal de criação
  currentItem.value = getInitialFormData()
  isModalOpen.value = true
}

const openEditModal = (projeto) => {
  currentItem.value = { ...projeto } // Copia o objeto para evitar mutação direta
  isModalOpen.value = true
}

const openDeleteModal = (projeto) => {
  projectToDelete.value = projeto
  isDeleteModalOpen.value = true
}

const handleSave = async (formData) => {
  isModalLoading.value = true
  console.log(formData)
  // A lógica para adicionar o id_responsavel e id_situacao foi movida para dentro da verificação de "criação"
  const isCreating = !formData.id_projeto

  try {
    if (isCreating) {
      // Cria um novo payload para não modificar o formData diretamente
      const payload = {
        ...formData,
        id_responsavel: userId,
        id_situacao: 1,
      }
      const { data } = await api.post('/projetos', payload)
      todosProjetos.value.push(data)
      //Adicionar a equipe assim que o projeto for armazenado
      const { dataEquipe } = await api.post('/equipes', { id_projeto: data.id_projeto })
      notificationStore.showSuccess('Projeto criado com sucesso!')
    } else {
      const payload = {
        ...formData,
        id_responsavel: userId,
        id_situacao: 1,
      }
      const { data } = await api.put(`/projetos/${formData.id_projeto}`, payload)
      const index = todosProjetos.value.findIndex((p) => p.id_projeto === data.id_projeto)
      if (index !== -1) todosProjetos.value.splice(index, 1, data)
      notificationStore.showSuccess('Projeto alterado com sucesso!')
    }
    isModalOpen.value = false
  } catch (error) {
    console.error('Erro ao salvar o projeto:', error)
    notificationStore.showError('Ocorreu um erro ao salvar o projeto.')
  } finally {
    isModalLoading.value = false
  }
}

const handleDelete = async () => {
  if (!projectToDelete.value) return

  isModalLoading.value = true
  try {
    await api.delete(`/equipesProjeto/${projectToDelete.value.id_projeto}`)
    await api.delete(`/projetos/${projectToDelete.value.id_projeto}`)

    const index = todosProjetos.value.findIndex(
      (p) => p.id_projeto === projectToDelete.value.id_projeto,
    )
    if (index !== -1) {
      todosProjetos.value.splice(index, 1)
    }

    notificationStore.showSuccess('Projeto excluído com sucesso!')
    isDeleteModalOpen.value = false
    projectToDelete.value = null
  } catch (err) {
    console.error('Erro ao excluir o projeto:', err)
    notificationStore.showError('Ocorreu um erro ao excluir o projeto.')
  } finally {
    isModalLoading.value = false
  }
}

// --- COMPUTED PROPERTIES ---

const eventItemsParaSelecao = computed(() => {
  const agora = new Date()

  return eventos.value.map((evento) => {
    const fimSubmissao = new Date(evento.fim_submissao)
    const prazoEncerrado = agora > fimSubmissao

    const isCurrentItem = currentItem.value?.id_evento === evento.id_evento

    return {
      title: `${evento.nome} ${prazoEncerrado ? '(Submissões Encerradas)' : ''}`,
      value: evento.id_evento,
      disabled: prazoEncerrado && !isCurrentItem,
    }
  })
})

const existemEventosAbertos = computed(() => {
  const agora = new Date()
  return eventos.value.some((evento) => new Date(evento.fim_submissao) >= agora)
})

const projetosFiltrados = computed(() => {
  if (filtroStatus.value === 'Todos' || !filtroStatus.value) {
    return todosProjetos.value
  }
  return todosProjetos.value.filter((p) => p.id_situacao === filtroStatus.value)
})

const totalProjetos = computed(() => todosProjetos.value.length)

function goToProjectDetails(id) {
  router.push(`/projetos/${id}`)
}
function goToApprovedProjects() {
  router.push('/projetos/inscritos')
}

function handleApprovedCardClick() {
  if (totalProjetosAprovados.value > 0) {
    goToApprovedProjects()
  } else {
    notificationStore.showNotification({
      message: 'É necessário ter um projeto aprovado para acessar esta área.',
      type: 'info',
    })
  }
}
</script>

<template>
  <v-container fluid>
    <v-row class="mb-8">
      <!-- Card Novo Projeto -->
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
            <v-tooltip
              text="Não há eventos com período de submissão aberto no momento."
              location="top"
              :disabled="existemEventosAbertos"
            >
              <template v-slot:activator="{ props }">
                <div v-bind="props" class="d-block w-100">
                  <v-btn
                    variant="outlined"
                    block
                    @click="openCreateModal"
                    :disabled="!existemEventosAbertos"
                  >
                    Criar agora
                  </v-btn>
                </div>
              </template>
            </v-tooltip>
          </v-card-actions>
        </v-card>
      </v-col>

      <!-- Card Projetos Registrados -->
      <v-col cols="12" sm="6" md="4">
        <v-card variant="tonal" color="grey-darken-1" class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-folder-account-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-grey-darken-4">{{ totalProjetos }}</div>
                <div class="text-subtitle-2 text-grey-darken-2">Projetos Submetidos</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4">
        <v-card
          variant="tonal"
          color="green-darken-2"
          class="d-flex flex-column"
          :class="{ 'card-clicavel': totalProjetosAprovados > 0 }"
          height="100%"
          :hover="totalProjetosAprovados > 0"
          @click="handleApprovedCardClick"
        >
          <v-card-text class="flex-grow-1">
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-check-decagram-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold text-green-darken-4">
                  {{ totalProjetosAprovados }}
                </div>
                <div class="text-subtitle-2 text-green-darken-3">Projetos Inscritos</div>
              </div>
              <v-spacer></v-spacer>
              <v-icon v-if="totalProjetosAprovados > 0" size="36" class="icon-arrow"
                >mdi-arrow-right-circle-outline</v-icon
              >
            </div>
          </v-card-text>
          <template v-if="totalProjetosAprovados > 0">
            <v-divider></v-divider>
            <v-card-actions class="justify-center text-caption pa-1">
              <span class="opacity-75">Clique para visualizar</span>
            </v-card-actions>
          </template>
        </v-card>
      </v-col>
    </v-row>

    <v-divider class="my-6"></v-divider>
    <v-row align="center" class="mb-4">
      <v-col cols="12" md="6">
        <h2 class="text-h5 font-weight-bold text-grey-darken-4">Projetos submetidos</h2>
        <p class="text-subtitle-2 text-grey-darken-1">
          Aguarde a aprovação do professor em algum projeto submetido.
        </p>
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
          style="max-width: 280px"
        ></v-select>
      </v-col>
    </v-row>

    <v-row v-if="carregando">
      <v-col v-for="n in 3" :key="n" cols="12" sm="6" lg="4">
        <v-skeleton-loader type="image, article, actions"></v-skeleton-loader>
      </v-col>
    </v-row>
    <v-row v-else-if="projetosFiltrados.length === 0">
      <v-col cols="12">
        <v-card flat border class="text-center pa-8">
          <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-folder-search-outline</v-icon>
          <p class="text-grey-darken-1">Nenhum projeto encontrado.</p>
        </v-card>
      </v-col>
    </v-row>

    <v-row v-else>
      <v-col v-for="projeto in projetosFiltrados" :key="projeto.id_projeto" cols="12" sm="6" lg="4">
        <ProjectCard 
        :projeto="projeto"
        contexto="gerenciamento"
         @ver-detalhes="goToProjectDetails">
          <template #actions>
            <v-btn
              icon="mdi-pencil"
              variant="text"
              size="small"
              @click="openEditModal(projeto)"
            ></v-btn>
            <v-btn
              icon="mdi-delete"
              variant="text"
              color="grey"
              size="small"
              @click="openDeleteModal(projeto)"
            ></v-btn>
          </template>
        </ProjectCard>
      </v-col>
    </v-row>

    <CrudModal
      v-model="isModalOpen"
      :title="modalConfig.title"
      :fields="modalConfig.fields"
      :item="currentItem"
      :loading="isModalLoading"
      @save="handleSave"
    />

    <v-dialog v-model="isDeleteModalOpen" max-width="450">
      <v-card prepend-icon="mdi-alert-circle-outline" title="Confirmar Exclusão">
        <v-card-text>
          Você tem certeza que deseja excluir o projeto
          <strong>{{ projectToDelete?.titulo }}</strong
          >? Esta ação não pode ser desfeita.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="isDeleteModalOpen = false" :disabled="isModalLoading">Cancelar</v-btn>
          <v-btn
            color="red-darken-2"
            variant="flat"
            @click="handleDelete"
            :loading="isModalLoading"
          >
            Excluir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.v-card-title.text-wrap {
  white-space: normal;
  line-height: 1.3em;
  font-weight: 500;
}
.v-card-text {
  min-height: 60px;
}

.card-clicavel {
  cursor: pointer;
  transition:
    transform 0.2s ease-in-out,
    box-shadow 0.2s ease-in-out;
}

.card-clicavel:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.icon-arrow {
  transition: transform 0.3s ease;
  opacity: 0.7;
}

.card-clicavel:hover .icon-arrow {
  transform: translateX(5px);
  opacity: 1;
}

.opacity-75 {
  opacity: 0.75;
}
</style>
