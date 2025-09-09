<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../assets/plugins/axios.js';
import { useNotificationStore } from '@/stores/notification';

const route = useRoute();
const router = useRouter();
const notificationStore = useNotificationStore();

// --- ESTADOS DO COMPONENTE ---
const projeto = ref(null);
const evento = ref(null); // Novo estado para guardar os dados do evento
const membros = ref([]);
const carregando = ref(true);
const erro = ref(null);
const activeTab = ref('geral');
const isSaving = ref(false); // Estado de carregamento para o botão de salvar

// --- DADOS MOCADOS (para simulação de membros) ---
const mockMembros = [
  { id_usuario: 101, nome: 'Ana Clara Souza', email: 'ana.souza@email.com', data_inscricao: '2025-08-15' },
  { id_usuario: 102, nome: 'Bruno Carvalho', email: 'bruno.c@email.com', data_inscricao: '2025-08-16' },
  { id_usuario: 103, nome: 'Juliana Ferreira', email: 'juliana.f@email.com', data_inscricao: '2025-08-18' },
];

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  const projetoId = route.params.id;
  carregando.value = true;
  erro.value = null;
  try {
    // 1. Busca os detalhes do projeto
    const projetoResponse = await api.get(`/projetos/${projetoId}`);
    projeto.value = projetoResponse.data;

    // 2. Se o projeto tem um evento associado, busca os detalhes do evento
    if (projeto.value && projeto.value.id_evento) {
        const eventoResponse = await api.get(`/eventos/${projeto.value.id_evento}`);
        evento.value = eventoResponse.data;
    }

    // TODO: Substituir pela chamada real da API para buscar membros da equipe
    // Ex: const membrosResponse = await api.get(`/projetos/${projetoId}/membros`);
    // membros.value = membrosResponse.data;
    membros.value = mockMembros;

  } catch (err) {
    console.error("Erro ao buscar dados do projeto:", err);
    erro.value = "Não foi possível carregar os detalhes do projeto.";
  } finally {
    carregando.value = false;
  }
});


// --- CONFIGURAÇÕES E COMPUTEDS ---
const statusMap = {
  1: { text: 'Em Análise', color: 'orange-darken-2' },
  2: { text: 'Aprovado', color: 'green-darken-2' },
  5: { text: 'Em Desenvolvimento', color: 'blue-darken-2' },
  6: { text: 'Concluído', color: 'purple-darken-2' },
};

const projetoStatus = computed(() => {
    if (!projeto.value) return {};
    return statusMap[projeto.value.id_situacao] || { text: 'Desconhecido', color: 'grey' };
});

const progressoInscricoes = computed(() => {
    // Usa o max_pessoas do evento como fonte da verdade
    if (!evento.value || !evento.value.max_pessoas) return 0;
    return (membros.value.length / evento.value.max_pessoas) * 100;
});

// --- FUNÇÕES DE INTERAÇÃO ---
const salvarAlteracoes = async () => {
    isSaving.value = true;
    notificationStore.showInfo('Salvando alterações...');
    try {
        const payload = {
            titulo: projeto.value.titulo,
            problema: projeto.value.problema,
            relevancia: projeto.value.relevancia,
            // O campo de inscrições abertas seria um PATCH em outra rota, talvez
            // Ex: await api.patch(`/projetos/${projeto.value.id_projeto}/status`, { inscricoes_abertas: projeto.value.inscricoes_abertas });
        };
        const response = await api.put(`/projetos/${projeto.value.id_projeto}`, payload);
        projeto.value = response.data; // Atualiza o estado local com os dados salvos
        notificationStore.showSuccess('Projeto atualizado com sucesso!');
    } catch (err) {
        console.error('Erro ao salvar o projeto:', err);
        notificationStore.showError('Não foi possível salvar as alterações.');
    } finally {
        isSaving.value = false;
    }
};

const arquivarProjeto = () => {
    console.log("Arquivando projeto...", projeto.value.id_projeto);
    // Lógica para a chamada de API de arquivamento
};

const removerMembro = (membro) => {
    console.log("Removendo membro...", membro);
    // Lógica para a chamada de API de remoção de membro
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('pt-BR');
};

</script>

<template>
  <v-container fluid>
    <v-btn variant="text" prepend-icon="mdi-arrow-left" @click="router.go(-1)" class="mb-6">
      Voltar ao Banco de Projetos
    </v-btn>

    <!-- ESTADO DE CARREGAMENTO -->
    <div v-if="carregando" class="text-center py-16">
        <v-progress-circular indeterminate color="green-darken-3" size="64"></v-progress-circular>
        <p class="mt-4 text-grey-darken-1">Carregando gerenciamento do projeto...</p>
    </div>

    <!-- ESTADO DE ERRO -->
    <v-alert v-else-if="erro" type="error" variant="tonal" prominent>{{ erro }}</v-alert>

    <!-- CONTEÚDO DA PÁGINA -->
    <div v-else-if="projeto">
        <!-- CABEÇALHO DO PROJETO -->
        <v-card theme="dark" class="mb-8 bg-green-darken-4">
          <v-card-item class="pa-4 pa-sm-6">
            <div class="d-flex flex-wrap justify-space-between align-center">
              <div>
                <p class="text-overline">Gerenciamento do Projeto</p>
                <h1 class="text-h4 font-weight-bold">{{ projeto.titulo }}</h1>
                <div class="d-flex align-center mt-2 opacity-75">
                  <v-icon size="small" start icon="mdi-account-tie"></v-icon>
                  <span class="text-subtitle-1">Orientador: {{ projeto.orientador?.nome || 'Você' }}</span>
                </div>
              </div>
              <v-chip :color="projetoStatus.color" variant="tonal" label>
                {{ projetoStatus.text }}
              </v-chip>
            </div>
          </v-card-item>
        </v-card>

        <!-- ABAS DE NAVEGAÇÃO -->
        <v-card>
            <v-tabs v-model="activeTab" bg-color="green-darken-3" color="white" grow>
                <v-tab value="geral"><v-icon start>mdi-information-outline</v-icon>Visão Geral</v-tab>
                <v-tab value="membros"><v-icon start>mdi-account-group-outline</v-icon>Membros Inscritos</v-tab>
                <v-tab value="configuracoes"><v-icon start>mdi-cog-outline</v-icon>Configurações</v-tab>
            </v-tabs>

            <v-window v-model="activeTab">
                <!-- ABA: VISÃO GERAL -->
                <v-window-item value="geral">
                    <v-card-text class="pa-4 pa-md-6">
                        <v-row>
                            <v-col cols="12" md="4">
                                <v-card variant="tonal" color="blue-grey">
                                    <v-card-text>
                                        <div class="text-h3 font-weight-bold">{{ membros.length }} / {{ evento?.max_pessoas || projeto.max_membros }}</div>
                                        <div class="text-subtitle-1">Inscrições</div>
                                        <v-progress-linear :model-value="progressoInscricoes" color="light-blue" height="6" rounded class="mt-2"></v-progress-linear>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="8">
                                <v-list lines="two" bg-color="transparent">
                                  <v-list-item prepend-icon="mdi-text-box-outline" title="Descrição do Projeto" :subtitle="projeto.problema"></v-list-item>
                                  <v-list-item prepend-icon="mdi-bullseye-arrow" title="Relevância" :subtitle="projeto.relevancia"></v-list-item>
                                </v-list>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-window-item>

                <!-- ABA: MEMBROS INSCRITOS -->
                <v-window-item value="membros">
                    <v-card-text class="pa-0">
                         <v-list lines="two">
                            <v-list-subheader>Alunos inscritos no projeto</v-list-subheader>
                             <div v-if="membros.length === 0" class="text-center text-grey py-8">
                                 Nenhum aluno inscrito até o momento.
                             </div>
                            <template v-else>
                                <v-list-item v-for="membro in membros" :key="membro.id_usuario" :title="membro.nome" :subtitle="membro.email">
                                    <template v-slot:prepend>
                                        <v-avatar color="green-darken-4">
                                            <span class="text-h6">{{ membro.nome.charAt(0) }}</span>
                                        </v-avatar>
                                    </template>
                                    <template v-slot:append>
                                        <div class="d-none d-sm-block text-caption text-grey-darken-1 mr-4">Inscrito em: {{ formatDate(membro.data_inscricao) }}</div>
                                        <v-btn color="red-lighten-1" variant="text" icon="mdi-account-remove-outline" @click="removerMembro(membro)">
                                            <v-icon></v-icon>
                                            <v-tooltip activator="parent" location="bottom">Remover Aluno</v-tooltip>
                                        </v-btn>
                                    </template>
                                </v-list-item>
                            </template>
                         </v-list>
                    </v-card-text>
                </v-window-item>

                <!-- ABA: CONFIGURAÇÕES -->
                <v-window-item value="configuracoes">
                    <v-card-text class="pa-4 pa-md-6">
                        <h3 class="text-h6 mb-6">Configurações Gerais</h3>
                        <v-text-field v-model="projeto.titulo" label="Título do Projeto" variant="outlined" class="mb-4"></v-text-field>
                        <v-textarea v-model="projeto.problema" label="Descrição do Problema" variant="outlined" rows="3" class="mb-4"></v-textarea>
                        
                        <v-text-field
                            :model-value="evento?.max_pessoas"
                            label="Número Máximo de Alunos"
                            variant="outlined"
                            class="mb-4"
                            style="max-width: 350px;"
                            disabled
                            readonly
                            :hint="`Limite definido pelo evento: ${evento?.nome}`"
                            persistent-hint
                        ></v-text-field>

                        <v-switch v-model="projeto.inscricoes_abertas" label="Permitir novas inscrições" color="green-darken-3" class="mt-4"></v-switch>
                        
                        <v-divider class="my-6"></v-divider>
                        
                        <div class="d-flex justify-end">
                            <v-btn size="large" color="green-darken-3" @click="salvarAlteracoes" :loading="isSaving">Salvar Alterações</v-btn>
                        </div>

                         <v-divider class="my-8"></v-divider>

                        <h3 class="text-h6 mb-4 text-red-darken-2">Zona de Perigo</h3>
                        <v-card variant="outlined" color="red">
                            <v-card-text class="d-flex justify-space-between align-center">
                                <div>
                                    <div class="font-weight-bold">Arquivar este projeto</div>
                                    <div class="text-caption">Esta ação é irreversível e irá remover o projeto da lista pública.</div>
                                </div>
                                <v-btn color="red-darken-2" variant="flat" @click="arquivarProjeto">Arquivar</v-btn>
                            </v-card-text>
                        </v-card>
                    </v-card-text>
                </v-window-item>
            </v-window>
        </v-card>
    </div>

  </v-container>
</template>

