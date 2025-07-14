import { defineStore } from 'pinia';

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    message: '',
    type: 'success', 
    visible: false,
  }),

  actions: {
    
    show(message, type = 'success') {
      this.message = message;
      this.type = type;
      this.visible = true;

      setTimeout(() => {
        this.hide();
      }, 6000);
    },

    showError(message) {
      this.show(message, 'error');
    },
    showSuccess(message) {
      this.show(message, 'success'); 
    },
    hide() {
      this.visible = false;
    },
  },
});