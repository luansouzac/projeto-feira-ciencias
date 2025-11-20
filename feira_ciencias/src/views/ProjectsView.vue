<script setup>
import { ref, computed, onMounted, watch } from 'vue' // ✅ 'watch' é crucial e já está aqui
import { useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js'
import { useNotificationStore } from '@/stores/notification'
import { useEventoStore } from '@/stores/eventoStore'
import { storeToRefs } from 'pinia'
import ProjectCard from '@/components/ProjectCard.vue'
import { useAuthStore } from '@/stores/authStore'
const authStore = useAuthStore()

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
const totalProjetosAprovados = ref(0)

// ✅ ESTADO PARA ORIENTADORES FILTRADOS POR EVENTO
const orientadoresDoEvento = ref([])
const orientadoresLoading = ref(false)

// --- ESTADO PARA O MODAL ---
const isModalOpen = ref(false)
const isModalLoading = ref(false)
const formRef = ref(null) // Referência para validação do formulário manual

const getInitialFormData = () => ({
  id_evento: null,
  titulo: '',
  problema: '',
  relevancia: '',
  max_pessoas: 5,
  id_orientador: null,
  id_coorientador: null,
})
const currentItem = ref(getInitialFormData())

// --- ESTADO PARA O MODAL DE EXCLUSÃO ---
const isDeleteModalOpen = ref(false)
const projectToDelete = ref(null)

const userId = authStore.user?.id_usuario
const userType = authStore.user?.id_tipo_usuario

// ✅ COMPUTED: Opções de Orientadores Filtradas
const orientadorItemsParaSelecao = computed(() => {
  return orientadoresDoEvento.value.map((orientador) => ({
    title: orientador.nome,
    value: orientador.id_usuario,
  }))
})

// ✅ Regras de validação do formulário (redefinidas para uso manual)
const rules = {
  required: (v) => !!v || 'Campo obrigatório',
  maxPessoas: (v) => v > 0 || 'Deve ser maior que zero',
  eventoValido: (v) => {
    if (!v) return 'É necessário selecionar um evento'
    const eventoSelecionado = eventos.value.find((e) => e.id_evento === v)
    if (!eventoSelecionado) return true
    const fimSubmissao = new Date(eventoSelecionado.fim_submissao)
    // Permite manter o evento se estiver editando o mesmo, mesmo que o prazo tenha passado
    if (currentItem.value.id_projeto && v === currentItem.value.original_id_evento) return true; 
    
    return new Date() <= fimSubmissao || 'O período de submissão para este evento já encerrou.'
  }
}
const tituloModal = computed(() => currentItem.value.id_projeto ? 'Editar Projeto' : 'Novo Projeto')


// ✅ FUNÇÃO PARA BUSCAR ORIENTADORES DO EVENTO
async function fetchOrientadoresByEvento(id_evento) {
  if (!id_evento) return []
  orientadoresLoading.value = true
  try {
    const { data } = await api.get(`/eventos/${id_evento}/orientadores`)
    // Mapeia para EXTRAIR o objeto do usuário (o 'orientador' dentro do vínculo)
    return data
      .map((vinculo) => vinculo.orientador)
      .filter((o) => o)
  } catch (error) {
    console.error(`Erro ao buscar orientadores para o evento ${id_evento}:`, error)
    notificationStore.showError('Não foi possível carregar os orientadores do evento.')
    return []
  } finally {
    orientadoresLoading.value = false
  }
}


async function fetchData() {
  if (!userId) {
    erro.value = 'Usuário não encontrado. Por favor, faça o login novamente.'
    carregando.value = false
    return
  }

  const fetchProjetosPromise = api.get(`/projetos?id_responsavel=${userId}&situacao_not=2`)
  const fetchEventosPromise = eventoStore.fetchEventos()
  const fetchProjetosInscritosPromise = api.get(`/usuarios/${userId}/projetos-inscritos`)

  try {
    const results = await Promise.allSettled([
      fetchProjetosPromise,
      fetchEventosPromise,
      fetchProjetosInscritosPromise,
    ])

    const [projetosResult, eventosResult, ProjetosInscritosResult] = results

    if (projetosResult.status === 'fulfilled') {
      todosProjetos.value = projetosResult.value.data.map(transformarProjeto)
    } else {
      console.error('Erro ao buscar projetos:', projetosResult.reason)
      todosProjetos.value = []
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
}


// --- MÉTODOS ---
onMounted(() => {
  fetchData()
})


// ✅ WATCH: Observa a mudança do Evento no formulário
watch(() => currentItem.value.id_evento, async (newId, oldId) => {
  if (newId !== oldId) {
    if (newId) {
      orientadoresDoEvento.value = await fetchOrientadoresByEvento(newId)

      const availableIds = orientadoresDoEvento.value.map(o => o.id_usuario);
      if (currentItem.value.id_orientador && !availableIds.includes(currentItem.value.id_orientador)) {
        currentItem.value.id_orientador = null;
      }
      if (currentItem.value.id_coorientador && !availableIds.includes(currentItem.value.id_coorientador)) {
        currentItem.value.id_coorientador = null;
      }

    } else {
      orientadoresDoEvento.value = []
      currentItem.value.id_orientador = null;
      currentItem.value.id_coorientador = null;
    }
  }
}, { immediate: false })


// --- MÉTODOS PARA MODAIS ---

const openCreateModal = () => {
  currentItem.value = getInitialFormData()
  orientadoresDoEvento.value = [] // Limpa a lista ao abrir
  isModalOpen.value = true
}

const openEditModal = (projeto) => {
  // ✅ CORREÇÃO: Adiciona a propriedade original para a validação de data
  currentItem.value = { ...projeto, original_id_evento: projeto.id_evento }
  
  // Carrega os orientadores do evento atual imediatamente
  if (projeto.id_evento) {
    orientadoresLoading.value = true;
    fetchOrientadoresByEvento(projeto.id_evento).then(data => {
      orientadoresDoEvento.value = data;
      orientadoresLoading.value = false;
    });
  }
  isModalOpen.value = true
}

const openDeleteModal = (projeto) => {
  projectToDelete.value = projeto
  isDeleteModalOpen.value = true
}

const handleSave = async () => {
  // Validação do formulário
  const { valid } = await formRef.value.validate()
  if (!valid) return

  isModalLoading.value = true;
  const formData = currentItem.value;
  const isCreating = !formData.id_projeto;
  let responseData; 

  try {
    if (isCreating) {
      const payload = { ...formData, id_responsavel: userId, id_situacao: 1 };
      const { data } = await api.post('/projetos', payload);
      responseData = data;

      await api.post('/equipes', { id_projeto: responseData.id_projeto });
      notificationStore.showSuccess('Projeto criado com sucesso!');
    } else {
      const payload = { ...formData, id_responsavel: userId, id_situacao: 1 };
      const { data } = await api.put(`/projetos/${formData.id_projeto}`, payload);
      responseData = data;

      notificationStore.showSuccess('Projeto alterado com sucesso!');
    }

    // Lógica para atualizar lista local (necessário para o ProjectCard)
    if (!responseData.equipe) responseData.equipe = { membro_equipe: [] };
    if (!responseData.eventos) responseData.eventos = eventos.value.find(e => e.id_evento === responseData.id_evento);

    const projetoProcessado = transformarProjeto(responseData);

    if (isCreating) {
      todosProjetos.value.unshift(projetoProcessado);
    } else {
      const index = todosProjetos.value.findIndex((p) => p.id === projetoProcessado.id_projeto);
      if (index !== -1) {
        todosProjetos.value[index] = projetoProcessado;
      }
    }

    isModalOpen.value = false;
    await fetchData(); // Recarrega os dados

  } catch (error) {
    console.error('Erro ao salvar o projeto:', error);
    notificationStore.showError('Ocorreu um erro ao salvar o projeto.');
  } finally {
    isModalLoading.value = false;
  }
};


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

// --- COMPUTED PROPERTIES AUXILIARES ---

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

// --- FUNÇÕES DE CARD E NAVEGAÇÃO ---
function goToProjectDetails(id) { router.push(`/projetos/${id}`) }
function goToApprovedProjects() { router.push('/projetos/inscritos') }
function handleApprovedCardClick() {
  if (totalProjetosAprovados.value > 0) goToApprovedProjects()
  else notificationStore.showNotification({ message: 'É necessário ter um projeto aprovado para acessar esta área.', type: 'info' })
}

const transformarProjeto = (apiProjeto) => {
  const inscritos = apiProjeto.equipe?.[0]?.membro_equipe?.length ?? 0
  const maxAlunos = apiProjeto.max_pessoas || apiProjeto.eventos?.max_pessoas || 5
  
  let statusParaCard;
  if (userType === 2) { 
    statusParaCard = inscritos >= maxAlunos ? 'Esgotado' : 'Vagas Abertas';
  } else { 
    const statusMapProfessor = { 1: 'Em Análise', 2: 'Aprovado', 3: 'Reprovado', 4: 'Com Ressalvas' };
    statusParaCard = statusMapProfessor[apiProjeto.id_situacao] || 'Pendente';
  }

  const alunoInscrito = apiProjeto.equipe?.[0]?.membro_equipe?.some((m) => m.id_usuario === userId) ?? false

  const eventoDoProjeto = eventos.value.find((e) => e.id_evento === apiProjeto.id_evento)
  let statusInscricao = 'INDISPONIVEL'
  let mensagemInscricao = 'Período de inscrição não definido para este evento.'

  if (eventoDoProjeto && eventoDoProjeto.inicio_inscricao && eventoDoProjeto.fim_inscricao) {
    const agora = new Date()
    const inicio = new Date(eventoDoProjeto.inicio_inscricao)
    const fim = new Date(eventoDoProjeto.fim_inscricao)
    fim.setHours(23, 59, 59, 999)

    if (agora < inicio) {
      statusInscricao = 'NAO_INICIADO'
      mensagemInscricao = `As inscrições abrem em: ${inicio.toLocaleDateString('pt-BR')}`
    } else if (agora > fim) {
      statusInscricao = 'ENCERRADO'
      mensagemInscricao = 'O período de inscrições para este projeto está encerrado.'
    } else {
      statusInscricao = 'ABERTO'
      mensagemInscricao = 'Inscrições abertas!'
    }
  }

  return {
    ...apiProjeto,
    id: apiProjeto.id_projeto,
    status: statusParaCard,
    inscritos,
    maxAlunos,
    alunoInscrito,
    statusInscricao,
    mensagemInscricao,
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
                  <v-btn variant="outlined" block @click="openCreateModal" :disabled="!existemEventosAbertos">
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
        <v-card variant="tonal" color="green-darken-2" class="d-flex flex-column"
          :class="{ 'card-clicavel': totalProjetosAprovados > 0 }" height="100%" :hover="totalProjetosAprovados > 0"
          @click="handleApprovedCardClick">
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
              <v-icon v-if="totalProjetosAprovados > 0" size="36"
                class="icon-arrow">mdi-arrow-right-circle-outline</v-icon>
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
        <v-select v-model="filtroStatus" :items="opcoesStatus" label="Filtrar por Status" variant="outlined"
          density="compact" hide-details clearable style="max-width: 280px"></v-select>
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
        <ProjectCard :projeto="projeto" contexto="gerenciamento" @ver-detalhes="goToProjectDetails">
          <template #actions>
            <v-btn icon="mdi-pencil" variant="text" size="small" @click="openEditModal(projeto)"></v-btn>
            <v-btn icon="mdi-delete" variant="text" color="grey" size="small" @click="openDeleteModal(projeto)"></v-btn>
          </template>
        </ProjectCard>
      </v-col>
    </v-row>

    <!-- ✅ MODAL MANUAL DE CRIAÇÃO/EDIÇÃO -->
    <v-dialog v-model="isModalOpen" max-width="700px" persistent>
        <v-card class="modal-card">
            <v-card-title class="bg-green-darken-3 text-white">
                <span class="text-h5">{{ tituloModal }}</span>
            </v-card-title>
            <v-card-text class="pt-6">
                <!-- Referência ao formulário para validação -->
                <v-form ref="formRef" @submit.prevent="handleSave">
                    <!-- Evento Associado -->
                    <v-select
                        v-model="currentItem.id_evento"
                        :items="eventItemsParaSelecao"
                        label="Evento Associado"
                        :rules="[rules.required, rules.eventoValido]"
                        variant="outlined"
                        class="mb-4"
                    ></v-select>

                    <v-text-field v-model="currentItem.titulo" label="Título do Projeto" :rules="[rules.required]" variant="outlined" class="mb-4"></v-text-field>
                    <v-textarea v-model="currentItem.problema" label="Problema a ser Resolvido" :rules="[rules.required]" variant="outlined" class="mb-4"></v-textarea>
                    <v-textarea v-model="currentItem.relevancia" label="Relevância do Projeto" :rules="[rules.required]" variant="outlined" class="mb-4"></v-textarea>

                    <v-text-field
                         v-model="currentItem.max_pessoas"
                         label="Nº Máximo de Participantes"
                         type="number"
                         :rules="[rules.required, rules.maxPessoas]"
                         variant="outlined"
                         class="mb-4"
                    ></v-text-field>

                    <v-select
                        v-model="currentItem.id_orientador"
                        :items="orientadorItemsParaSelecao"
                        label="Professor Orientador"
                        :rules="[rules.required]"
                        variant="outlined"
                        class="mb-4"
                        :loading="orientadoresLoading"
                        :disabled="!currentItem.id_evento || orientadoresLoading"
                        :hint="!currentItem.id_evento ? 'Selecione um evento primeiro.' : ''"
                        persistent-hint
                        no-data-text="Nenhum orientador vinculado a este evento."
                    ></v-select>

                    <v-select
                        v-model="currentItem.id_coorientador"
                        :items="orientadorItemsParaSelecao"
                        label="Professor Coorientador (Opcional)"
                        variant="outlined"
                        class="mb-4"
                        :loading="orientadoresLoading"
                        :disabled="!currentItem.id_evento || orientadoresLoading"
                        clearable
                        no-data-text="Nenhum orientador vinculado a este evento."
                    ></v-select>
                </v-form>
            </v-card-text>
            
            <!-- ✅ AÇÕES FLUIDAS COMPORTANDO-SE MELHOR EM TELAS PEQUENAS -->
            <v-card-actions class="pa-4 modal-actions">
                <v-btn class="btn-cancelar" color="grey-darken-1" variant="text" @click="isModalOpen = false">
                    Cancelar
                </v-btn>
                <v-btn class="btn-salvar" color="green-darken-2" variant="flat" @click="handleSave" :loading="isModalLoading">
                    Salvar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Modal de Exclusão (Mantido) -->
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
          <v-btn color="red-darken-2" variant="flat" @click="handleDelete" :loading="isModalLoading">
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

.modal-card {
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-card .v-card-text {
  flex: 1;
  overflow-y: auto;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-wrap: wrap;
}

.modal-actions .v-btn {
  min-width: 140px;
}

@media (max-width: 600px) {
  .modal-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .modal-actions .v-btn {
    width: 100%;
  }

  .modal-actions .btn-cancelar {
    order: 2;
  }

  .modal-actions .btn-salvar {
    order: 1;
  }
}
</style>