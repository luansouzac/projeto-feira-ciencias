import { defineStore } from 'pinia';
import api from '@/assets/plugins/axios';

export const useUsuarioStore = defineStore('usuario', {
  // `state` é onde você define os dados reativos do seu store.
  state: () => ({
    usuarios: [], // Array para armazenar os usuários buscado
    selectedUsuario: null, // Para um usuário específico selecionado/editado
    loading: false,      // Para gerenciar o estado de carregamento da API
    error: null,         // Para armazenar erros da API
  }),

  // `getters` são como propriedades computadas para seu store.
  getters: {
    getAllUsuarios: (state) => state.usuarios,
    getActiveUsuarios: (state) => state.usuarios.filter(event => event.ativo),
    getUsuarioById: (state) => (id) => state.usuarios.find(event => event.id_usuario === id),
    getSelectedUsuario: (state) => state.selectedUsuario,
    isLoadingUsuarios: (state) => state.loading,
    getUsuarioErrors: (state) => state.error,
    getTotal: (state) => state.length,
  },

  // `actions` são onde você define métodos para interagir com a API e alterar o estado.
  actions: {
    // Listando todos os usuarios
    async fetchUsuarios() {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/usuarios');
        this.usuarios = response.data; 
        console.log('Usuarios carregados:', response.data);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao carregar os usuarios.';
        console.error('Erro ao buscar usuarios:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    //Novo Usuário
    async createUsuario(user) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/usuarios', {
          nome: user.nome, //Atributos da entidade usuarios
          email: user.email,
          senha_hash: user.senha_hash,
          id_tipo_usuario: user.id_tipo_usuario,
          id_matricula:user.id_matricula,
          telefone:user.telefone,
          ano:user.ano,
          photo:user.photo
        });

        //Colocando o retorno da API no array de usuarios
        this.usuarios.push(response.data);
        console.log('Usuário criado com sucesso:', response.data);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao criar o usuário.';
        console.error('Erro ao criar o usuário:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    // Ação para atualizar um usuário existente via API
    async updateUsuario(id_usuario, updated) {
      this.loading = true;
      this.error = null;
      try {
        // Atualizando os dados na API
        const response = await api.put(`/usuarios/${id_usuario}`, {
          nome: updated.nome, //Atributos da entidade usuarios
          email: updated.email,
          senha_hash: updated.senha_hash,
          id_tipo_usuario: updated.id_tipo_usuario,
          id_matricula:updated.id_matricula,
          telefone:updated.telefone,
          ano:updated.ano,
          photo:updated.photo	
        });

        //Atualizando os dados na Array local
        const index = this.usuarios.findIndex(event => event.id_usuario === id_usuario);
        if (index !== -1) {
          this.usuarios[index] = { ...this.usuarios[index], ...response.data };
        }
        console.log('Usuário atualizado com sucesso:', response.data);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao atualizar o usuário.';
        console.error('Erro ao atualizar o usuário:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    async deleteUsuario(id_usuario) {
      this.loading = true;
      this.error = null;
      try {
        //Apagando na API
        await api.delete(`/usuarios/${id_usuario}`);

        //Remove o usuario do array local
        this.usuarios = this.usuarios.filter(event => event.id_usuario !== id_usuario);
        console.log(`usuario com ID ${id_usuario} deletado com sucesso.`);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao deletar o usuario.';
        console.error('Erro ao deletar o usuario:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    // Ação para selecionar um evento (útil para formulários de edição)
    selectUsuario(id_usuario) {
      this.selectedUsuario = this.getEventById(id_usuario);
    },

    // Ação para limpar o usuário selecionado
    clearSelectedUsuario() {
      this.selectedUsuario = null;
    }
  }
});