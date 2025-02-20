<template>
    <div v-if="isVisible" class="modal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <button class="close" @click="closeModal">&times;</button>
          <h2>{{ translate('Importation de Fournisseurs') }}</h2>
          <form @submit.prevent="importChanges" enctype="multipart/form-data">
            <input 
              type="file" 
              name="csv_file" 
              accept=".csv" 
              required 
              @change="handleFileChange"
            />
            <button type="submit" class="btn btn-primary" :disabled="isLoading">
              <span v-if="isLoading">{{ translate('Importation...') }}</span>
              <span v-else>{{ translate('Importer') }}</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        showImportModal: false,
        selectedFile: null,
        isLoading: false,
      };
    },
    props: {
    nonce: {
      type: String,
      required: true,
    },
    translations: {
      type: Object,
      required: true,
    },
    isVisible: {
      type: Boolean,
      required: true,
    },
  },
    methods: {
      translate(key) {
        if (this.translations && this.translations[key]) {
          return this.translations[key][1];
        }
        return key; // Retourne la clé elle-même si la traduction manque
      },

      handleFileChange(event) {
        this.selectedFile = event.target.files[0];
      },

      /*async submitForm() {
        if (!this.selectedFile) {
          alert('Veuillez sélectionner un fichier CSV.');
          return;
        }

        this.isLoading = true;

        const formData = new FormData();
        formData.append('csv_file', this.selectedFile);
        formData.append('import_fournisseurs_nonce', this.getNonce());
        console.log('Valeur de root :', this.root); // Debug
        const actionUrl = this.getActionUrl();
        if (!actionUrl) {
            console.error('Erreur : `actionUrl` est invalide.');
            return;
        }
        try {
          const response = await       fetch(ajax_url + '?action=get_fournisseurs')
          .then(response => response.json())
          .then(data => {, {
            method: 'POST',
            body: formData,
          });

          const result = await response.json();
          console.log(result);

          if (response.ok && result.message_type === 'success') {
            alert(result.message);
            this.closeModal();
          } else {
            alert(result.message || 'Erreur lors de l\'importation.');
          }
        } catch (error) {
          console.error('Erreur réseau :', error);
          alert('Une erreur est survenue lors de l\'importation.');
        } finally {
          this.isLoading = false;
        }
      },*/
      importChanges() {
        if (!this.selectedFile) {
          alert('Veuillez sélectionner un fichier CSV.');
          return;
        }
        this.$emit('import', this.selectedFile);
      },

      getActionUrl() {
        return `${window.wpApiSettings.root}import_csv`;
      },

      getNonce() {
        return this.nonce;
      },

      closeModal() {
        this.$emit('close');
      },
    },
  };
  </script>
  
  <style scoped>
  .modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }
  .modal-dialog {
    background: white;
    padding: 20px;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
  }
  .close {
    background: none;
    border: none;
    font-size: 1.5rem;
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
  }
  </style>
  