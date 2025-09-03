<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../assets/plugins/axios.js' 
import { useNotificationStore } from '@/stores/notification' 

const router = useRouter()
const notificationStore = useNotificationStore()

// --- ESTADOS DA PÁGINA ---
const carregando = ref(true)
const avaliacoes = ref([])
const totalProjetosOrientados = ref(0)
let userId = null

// --- ESTADOS DO MODAL DE AVALIAÇÃO ---
const isModalAvaliacaoOpen = ref(false)
const isModalLoading = ref(false)
const projetoSendoAvaliado = ref(null)
const tipoAvaliacao = ref('') // 'aprovado', 'ressalva', 'reprovado'
const justificativa = ref('')

// --- MAPA DE STATUS (para os chips) ---
const statusMap = {
  1: { text: 'Submetido', color: 'blue' },
  2: { text: 'Aprovado', color: 'green' },
  3: { text: 'Reprovado', color: 'red' },
  4: { text: 'Ressalvas', color: 'orange' },
  5: { text: 'Em Desenvolvimento', color: 'teal' },
  6: { text: 'Concluído', color: 'purple' },
}

// --- CONFIGURAÇÃO DINÂMICA DO MODAL ---
const modalConfig = computed(() => {
  switch (tipoAvaliacao.value) {
    case 'aprovado':
      return { title: 'Aprovar Projeto', color: 'green-darken-2' }
    case 'ressalva':
      return { title: 'Reprovar com Ressalvas', color: 'orange-darken-2' }
    case 'reprovado':
      return { title: 'Reprovar Projeto', color: 'red-darken-2' }
    default:
      return { title: 'Confirmar Ação', color: 'grey' }
  }
})

// --- OBTENÇÃO DE DADOS ---
const buscarDadosPagina = async () => {
  carregando.value = true
  try {
    const userDataString = sessionStorage.getItem('user_data')
    if (!userDataString) {
      router.push({ name: 'login' })
      return
    }
    const userData = JSON.parse(userDataString)
    userId = userData.user.id_usuario

    // Unica chamada que retorna todos os projetos (para avaliar e os orientados)
    const response = await api.get(`/usuarios/${userId}/projetos/avaliacao`);
    const todosOsProjetos = response.data;

    // Filtra para avaliações pendentes (situação 1)
    avaliacoes.value = todosOsProjetos.filter((p) => p.id_situacao === 1);

    // Filtra para projetos orientados e aprovados (situação 2) e pega a contagem
    totalProjetosOrientados.value = todosOsProjetos.filter((p) => p.id_situacao === 2).length;

  } catch (err) {
    console.error('Erro ao buscar dados dos projetos:', err)
    notificationStore.showNotification({ message: 'Falha ao carregar dados da página.', type: 'error' })
  } finally {
    carregando.value = false
  }
}

onMounted(buscarDadosPagina)

// --- FUNÇÕES DE AÇÃO ---
function goToProjectDetails(id) {
  router.push(`/projetos/${id}`)
}

const openModal = (projeto, tipo) => {
  projetoSendoAvaliado.value = projeto
  tipoAvaliacao.value = tipo
  justificativa.value = ''
  isModalAvaliacaoOpen.value = true
}

const closeModal = () => {
  isModalAvaliacaoOpen.value = false
  isModalLoading.value = false
  setTimeout(() => {
    projetoSendoAvaliado.value = null
    tipoAvaliacao.value = ''
  }, 300)
}

const confirmarAvaliacao = async () => {
  if (
    (tipoAvaliacao.value === 'ressalva' || tipoAvaliacao.value === 'reprovado') &&
    !justificativa.value.trim()
  ) {
    notificationStore.showNotification({
      message: 'A justificativa é obrigatória para esta ação.',
      type: 'warning',
    })
    return
  }

  isModalLoading.value = true

  const situacaoMap = {
    aprovado: 2,
    reprovado: 3,
    ressalva: 4,
  }

  const payload = {
    id_situacao: situacaoMap[tipoAvaliacao.value],
    id_projeto: projetoSendoAvaliado.value.id_projeto,
    id_avaliador: userId,
    feedback: justificativa.value,
  }

  try {
    await api.post('projeto_avaliacoes', payload)

    avaliacoes.value = avaliacoes.value.filter(
      (p) => p.id_projeto !== projetoSendoAvaliado.value.id_projeto,
    )
    
    // ✅ Atualiza a contagem de orientados caso um projeto seja aprovado
    if (tipoAvaliacao.value === 'aprovado') {
        totalProjetosOrientados.value++;
    }

    notificationStore.showNotification({
      message: 'Projeto avaliado com sucesso!',
      type: 'success',
    })
  } catch (err) {
    console.error('Erro ao confirmar avaliação:', err)
    notificationStore.showNotification({
      message: 'Ocorreu um erro ao salvar a avaliação.',
      type: 'error',
    })
  } finally {
    closeModal()
  }
}

// --- FUNÇÕES DE NAVEGAÇÃO DO CARD DE ORIENTADOS ---
function goToOrientadosManagement() {
  router.push('/projetos/orientados/1'); // Rota para a nova tela de gerenciamento
}

function handleOrientadosCardClick() {
  if (totalProjetosOrientados.value > 0) {
    goToOrientadosManagement();
  } else {
    notificationStore.showNotification({
      message: 'Você não possui projetos aprovados para gerenciar no momento.',
      type: 'info'
    });
  }
}
</script>

<template>
  <v-container fluid>
    <v-row class="mb-8">
      <v-col cols="12" sm="6" md="4">
        <v-card color="green-darken-4" dark class="d-flex flex-column" height="100%">
          <v-card-text>
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-clipboard-list-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ avaliacoes.length }}</div>
                <div class="text-subtitle-1">Avaliações Pendentes</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="4">
        <v-card
          color="green-darken-4"
          class="d-flex flex-column text-white"
          :class="{ 'card-clicavel': totalProjetosOrientados > 0 }"
          height="100%"
          :hover="totalProjetosOrientados > 0"
          @click="handleOrientadosCardClick"
          variant="tonal"
        >
          <v-card-text class="flex-grow-1">
            <div class="d-flex align-center">
              <v-icon size="48" class="mr-4">mdi-school-outline</v-icon>
              <div>
                <div class="text-h4 font-weight-bold">{{ totalProjetosOrientados }}</div>
                <div class="text-subtitle-1">Projetos Orientados</div>
              </div>
              <v-spacer></v-spacer>
              <v-icon v-if="totalProjetosOrientados > 0" size="36" class="icon-arrow">mdi-arrow-right-circle-outline</v-icon>
            </div>
          </v-card-text>
          <template v-if="totalProjetosOrientados > 0">
            <v-divider></v-divider>
            <v-card-actions class="justify-center text-caption pa-1">
              <span class="opacity-75">Clique para gerenciar</span>
            </v-card-actions>
          </template>
        </v-card>
      </v-col>
    </v-row>

    <v-divider class="my-6"></v-divider>

    <v-row align="center" class="mb-4">
      <v-col cols="12">
        <h2 class="text-h5 font-weight-bold text-grey-darken-4">Projetos para Avaliação</h2>
        <p class="text-subtitle-2 text-grey-darken-1">
          Analise e forneça seu parecer sobre as propostas de projeto.
        </p>
      </v-col>
    </v-row>

    <v-row v-if="carregando">
      <v-col v-for="n in 3" :key="n" cols="12" sm="6" lg="4">
        <v-skeleton-loader type="card"></v-skeleton-loader>
      </v-col>
    </v-row>
    <v-row v-else-if="avaliacoes.length === 0">
      <v-col cols="12">
        <v-card flat border class="text-center pa-8">
          <v-icon size="60" class="mb-4 text-grey-lighten-1">mdi-check-all</v-icon>
          <p class="text-h6 text-grey-darken-2">Tudo certo por aqui!</p>
          <p class="text-grey-darken-1">Nenhuma avaliação pendente no momento.</p>
        </v-card>
      </v-col>
    </v-row>

    <v-row v-else>
      <v-col v-for="projeto in avaliacoes" :key="projeto.id_projeto" cols="12" sm="6" lg="4">
        <v-card class="d-flex flex-column" height="100%" hover variant="outlined">
          <v-card-item class="pb-2">
            <div class="d-flex justify-space-between align-start mb-1">
              <v-card-title class="text-wrap me-2">{{ projeto.titulo }}</v-card-title>
              <v-chip :color="statusMap[projeto.id_situacao]?.color || 'grey'" size="small" label>
                {{ statusMap[projeto.id_situacao]?.text || 'Pendente' }}
              </v-chip>
            </div>
            <v-card-subtitle class="d-flex align-center">
              <v-icon start size="small">mdi-calendar-star</v-icon>
              {{ projeto.eventos.nome }}
            </v-card-subtitle>
          </v-card-item>

          <v-card-text class="py-3">
            <p class="text-body-2 text-grey-darken-2 mb-4 text-truncate-3-lines">
              {{ projeto.problema }}
            </p>

            <v-row dense>
              <v-col cols="12" sm="6">
                <div class="d-flex align-center">
                  <v-icon start color="grey-darken-1">mdi-account-school-outline</v-icon>
                  <div>
                    <div class="text-caption text-grey-darken-1">Responsável</div>
                    <div class="text-body-2 font-weight-medium text-truncate">
                      {{ projeto.responsavel.nome }}
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
                      {{ projeto.orientador.nome }}
                    </div>
                  </div>
                </div>
              </v-col>
            </v-row>
          </v-card-text>

          <v-spacer></v-spacer>
          <v-divider></v-divider>

          <v-card-actions>
            <v-btn
              color="grey-darken-1"
              variant="text"
              @click="goToProjectDetails(projeto.id_projeto)"
              >Ver Detalhes</v-btn
            >
            <v-spacer></v-spacer>
            <v-menu offset-y>
              <template v-slot:activator="{ props }">
                <v-btn color="blue-darken-2" variant="tonal" v-bind="props">
                  Avaliar
                  <v-icon right dark>mdi-chevron-down</v-icon>
                </v-btn>
              </template>
              <v-list density="compact">
                <v-list-item @click="openModal(projeto, 'aprovado')">
                  <template v-slot:prepend>
                    <v-icon color="green">mdi-check-circle-outline</v-icon>
                  </template>
                  <v-list-item-title>Aprovar</v-list-item-title>
                </v-list-item>
                <v-list-item @click="openModal(projeto, 'ressalva')">
                  <template v-slot:prepend>
                    <v-icon color="orange">mdi-alert-circle-outline</v-icon>
                  </template>
                  <v-list-item-title>Reprovar com Ressalvas</v-list-item-title>
                </v-list-item>
                <v-list-item @click="openModal(projeto, 'reprovado')">
                  <template v-slot:prepend>
                    <v-icon color="red">mdi-close-circle-outline</v-icon>
                  </template>
                  <v-list-item-title>Reprovar</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="isModalAvaliacaoOpen" persistent max-width="500px">
      <v-card>
        <v-card-title :class="['text-h5', 'text-white', modalConfig.color]">
          {{ modalConfig.title }}
        </v-card-title>
        <v-card-text class="pt-4">
          Você está prestes a avaliar o projeto
          <strong>"{{ projetoSendoAvaliado?.titulo }}"</strong>.

          <v-textarea
            v-model="justificativa"
            :label="
              tipoAvaliacao === 'aprovado' ? 'Feedback (Opcional)' : 'Justificativa (Obrigatório)'
            "
            placeholder="Descreva seu parecer sobre o projeto..."
            rows="4"
            class="mt-4"
            variant="outlined"
            :required="tipoAvaliacao !== 'aprovado'"
            autofocus
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="closeModal">Cancelar</v-btn>
          <v-btn
            :color="modalConfig.color"
            variant="tonal"
            @click="confirmarAvaliacao"
            :loading="isModalLoading"
          >
            Confirmar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<style scoped>
.text-wrap {
  white-space: normal !important;
  word-break: break-word;
}
.text-truncate-3-lines {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-clicavel {
  cursor: pointer;
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card-clicavel:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
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

