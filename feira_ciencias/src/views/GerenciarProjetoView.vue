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
const evento = ref(null);
const membros = ref([]);
const carregando = ref(true);
const erro = ref(null);
const activeTab = ref('geral');
const isSaving = ref(false);
const isDeleteDialogOpen = ref(false);

// --- LÓGICA DE BUSCA DE DADOS ---
onMounted(async () => {
  const projetoId = route.params.id;
  carregando.value = true;
  erro.value = null;
  try {
    // 1. Otimização: Busca os detalhes do projeto e os membros da equipe em paralelo
    const [projetoResponse, membrosResponse] = await Promise.all([
        api.get(`/projetos/${projetoId}`),
        api.get(`/membros_projeto/${projetoId}`) // Usando a rota dedicada para buscar os membros
    ]);

    // 2. Processa a resposta do projeto
    console.log("Resposta da API para o projeto:", projetoResponse.data);
    const dadosProjeto = projetoResponse.data.data || projetoResponse.data;
    if (!dadosProjeto || typeof dadosProjeto !== 'object') {
        throw new Error("A resposta da API não retornou um formato de projeto válido.");
    }
    projeto.value = dadosProjeto;

    // 3. Processa a resposta dos membros da equipe
    console.log("Resposta da API para os membros:", membrosResponse.data);
    // Trata a resposta de forma robusta, aceitando um array direto ou um objeto com uma propriedade 'data'
    const dadosMembros = membrosResponse.data.data || membrosResponse.data;
    // Garante que 'membros' seja sempre um array para evitar erros de renderização
    membros.value = Array.isArray(dadosMembros) ? dadosMembros : [];

    // 4. Com o projeto carregado, busca os dados do evento associado
    if (projeto.value.id_evento) {
        const eventoResponse = await api.get(`/eventos/${projeto.value.id_evento}`);
        evento.value = eventoResponse.data.data || eventoResponse.data;
    }

  } catch (err) {
    console.error("Erro detalhado ao buscar dados do projeto e da equipe:", err);
    erro.value = "Não foi possível carregar os detalhes do projeto. Verifique o console para mais informações.";
  } finally {
    carregando.value = false;
  }
});


// --- CONFIGURAções E COMPUTEDS ---
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
    if (!evento.value || !evento.value.max_pessoas) return 0;
    // Garante que o cálculo seja feito com o número de membros buscados pela nova rota
    return (membros.value.length / evento.value.max_pessoas) * 100;
});

// --- FUNções DE INTERAÇÃO ---
const salvarAlteracoes = async () => {
    isSaving.value = true;
    notificationStore.showInfo('Salvando alterações...');
    try {
        const payload = {
            titulo: projeto.value.titulo,
            problema: projeto.value.problema,
            relevancia: projeto.value.relevancia,
            // CORREÇÃO: Adicionando os campos obrigatórios exigidos pela API na atualização
            id_responsavel: projeto.value.id_responsavel,
            id_situacao: projeto.value.id_situacao,
            id_evento: projeto.value.id_evento,
            id_orientador: projeto.value.id_orientador,
        };
        const response = await api.put(`/projetos/${projeto.value.id_projeto}`, payload);
        projeto.value = response.data.data || response.data; // A resposta de um PUT/POST também pode estar aninhada
        notificationStore.showSuccess('Projeto atualizado com sucesso!');
    } catch (err) {
        console.error('Erro ao salvar o projeto:', err);
        // Melhora a exibição de erros de validação vindos do backend
        let errorMessage = 'Não foi possível salvar as alterações.';
        if (err.response?.data?.errors) {
            const firstErrorKey = Object.keys(err.response.data.errors)[0];
            errorMessage = err.response.data.errors[firstErrorKey][0];
        } else if (err.response?.data?.erro) {
            errorMessage = err.response.data.erro;
        }
        notificationStore.showError(errorMessage);
    } finally {
        isSaving.value = false;
    }
};

const deletarProjeto = async () => {
    notificationStore.showInfo(`Apagando projeto...`);
    try {
        await api.delete(`/projetos/${projeto.value.id_projeto}`);
        notificationStore.showSuccess('Projeto apagado com sucesso!');
        isDeleteDialogOpen.value = false;
        router.push('/banco-projetos'); // Redireciona para a lista de projetos
    } catch (err) {
        console.error("Erro ao apagar projeto:", err);
        notificationStore.showError(err.response?.data?.erro || 'Não foi possível apagar o projeto.');
        isDeleteDialogOpen.value = false;
    }
};

const removerMembro = async (membro) => {
    // CORREÇÃO: Acessa o nome do usuário dentro do objeto aninhado 'usuario'
    notificationStore.showInfo(`Removendo ${membro.usuario.nome}...`);
    try {
        await api.delete(`/projetos/${projeto.value.id_projeto}/membros/${membro.id_usuario}`);
        membros.value = membros.value.filter(m => m.id_usuario !== membro.id_usuario);
        notificationStore.showSuccess(`${membro.usuario.nome} foi removido com sucesso!`);
    } catch (err) {
        console.error('Erro ao remover membro:', err);
        notificationStore.showError(err.response?.data?.erro || 'Não foi possível remover o membro.');
    }
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
                  <span class="text-subtitle-1">Orientador: {{ projeto.orientador?.nome || 'Não definido' }}</span>
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
                                <v-list-item v-for="membro in membros" :key="membro.id_usuario" :title="membro.usuario.nome" :subtitle="membro.usuario.email">
                                    <template v-slot:prepend>
                                        <v-avatar color="green-darken-4">
                                            <span class="text-h6">{{ membro.usuario.nome.charAt(0) }}</span>
                                        </v-avatar>
                                    </template>
                                    <template v-slot:append>
                                        <div class="d-none d-sm-block text-caption text-grey-darken-1 mr-4">Inscrito em: {{ formatDate(membro.created_at) }}</div>
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
                        
                        <v-divider class="my-6"></v-divider>
                        
                        <div class="d-flex justify-end">
                            <v-btn size="large" color="green-darken-3" @click="salvarAlteracoes" :loading="isSaving">Salvar Alterações</v-btn>
                        </div>

                         <v-divider class="my-8"></v-divider>

                        <h3 class="text-h6 mb-4 text-red-darken-2">Zona de Perigo</h3>
                        <v-card variant="outlined" color="red">
                            <v-card-text class="d-flex justify-space-between align-center">
                                <div>
                                    <div class="font-weight-bold">Apagar este projeto</div>
                                    <div class="text-caption">Esta ação é permanente e removerá o projeto do sistema.</div>
                                </div>
                                <v-btn color="red-darken-2" variant="flat" @click="isDeleteDialogOpen = true">Apagar</v-btn>
                            </v-card-text>
                        </v-card>
                    </v-card-text>
                </v-window-item>
            </v-window>
        </v-card>
    </div>

    <!-- DIÁLOGO DE CONFIRMAÇÃO PARA APAGAR PROJETO -->
    <v-dialog v-model="isDeleteDialogOpen" persistent max-width="500px">
      <v-card>
        <v-card-title class="text-h5">
          <v-icon color="red" start>mdi-alert-circle-outline</v-icon>
          Confirmar Exclusão
        </v-card-title>
        <v-card-text>
          Você tem certeza que deseja apagar o projeto <strong>"{{ projeto?.titulo }}"</strong>?
          <br><br>
          Esta ação não pode ser desfeita.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="isDeleteDialogOpen = false">
            Cancelar
          </v-btn>
          <v-btn color="red-darken-1" variant="flat" @click="deletarProjeto">
            Apagar Projeto
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

