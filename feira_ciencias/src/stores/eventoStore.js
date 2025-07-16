import { defineStore } from 'pinia';
import api from '@/assets/plugins/axios';

export const useEventoStore = defineStore('evento', {
  // `state` é onde você define os dados reativos do seu store.
  state: () => ({
    eventos: [], // Array para armazenar todos os eventos
    selectedEvento: null, // Para um evento específico selecionado/editado
    loading: false,      // Para gerenciar o estado de carregamento da API
    error: null,         // Para armazenar erros da API
  }),

  // `getters` são como propriedades computadas para seu store.
  getters: {
    getAllEventos: (state) => state.eventos,
    getActiveEventos: (state) => state.eventos.filter(event => event.ativo),
    getEventoById: (state) => (id) => state.eventos.find(event => event.id_evento === id),
    getSelectedEvento: (state) => state.selectedEvento,
    isLoadingEventos: (state) => state.loading,
    getEventoErrors: (state) => state.error,
    getTotal: (state) => state.length,
  },

  // `actions` são onde você define métodos para interagir com a API e alterar o estado.
  actions: {
    // Listando todos os eventos
    async fetchEventos() {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/eventos');
        this.eventos = response.data; 
        //console.log('Eventos carregados:', response.data);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao carregar eventos.';
        console.error('Erro ao buscar eventos:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    //Novo evento
    async createEvento(evento) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/eventos', {
          nome: evento.nome, //Atributos da entidade eventos
          ativo: evento.ativo==1 ? "1" : "0",
        });

        //Colocando o retorno da API no array de eventos
        this.eventos.push(response.data);
        console.log('Evento criado com sucesso:', response.data);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao criar evento.';
        console.error('Erro ao criar evento:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    // Ação para atualizar um evento existente via API
    async updateEvento(id_evento, updated) {
      this.loading = true;
      this.error = null;
      try {
        // Atualizando os dados na API
        const response = await api.put(`/eventos/${id_evento}`, {
          nome: updated.nome,
          ativo: updated.ativo==1 ? "1" : "0",
        });

        //Atualizando os dados na Array local
        const index = this.eventos.findIndex(event => event.id_evento === id_evento);
        if (index !== -1) {
          this.eventos[index] = { ...this.eventos[index], ...response.data };
        }
        //console.log('Evento atualizado com sucesso:', response.data);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao atualizar evento.';
        //console.error('Erro ao atualizar evento:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    async deleteEvento(id_evento) {
      this.loading = true;
      this.error = null;
      try {
        //Apagando na API
        await api.delete(`/eventos/${id_evento}`);

        //Remove o evento do array local
        this.eventos = this.eventos.filter(evento => evento.id_evento !== id_evento);
        //console.log(`Evento com ID ${id_evento} deletado com sucesso.`);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao deletar evento.';
        //console.error('Erro ao deletar evento:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    // Ação para selecionar um evento (útil para formulários de edição)
    selectEvento(id_evento) {
      this.selectedEvento = this.getEventById(id_evento);
    },

    // Ação para limpar o evento selecionado
    clearSelectedEvent() {
      this.selectedEvento = null;
    }
  }
});