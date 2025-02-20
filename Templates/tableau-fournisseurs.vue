<template>
<!-- Tab panes -->
<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="add">		
        <div class="div_conteneur_parent">
            <div class="div_conteneur_page"  >
                <div class="div_int_page">			
                    <div class="div_h1" >
                        <h1> {{ translate('Tableau des fournisseurs') }}</h1>
                    </div>

                    <div class="div_saut_ligne" style="height:20px;"></div>	

                    <div style="width:100%;height:auto;text-align:center;">                              
                        <div style="display:inline-block;" id="conteneur">
                            <div class="centre">
                                <div class="titre_centre">
                                    <!-- Ajouter les boutons d'actions au-dessus du tableau -->
                                    <div class="d-flex justify-content-center mb-3">
                                        <div>
                                          <button id="openModalBtn" class="btn btn-primary" @click="openModal(null, 'add')">
                                              {{ translate('Ajouter')}}
                                          </button>
                                        </div>
                                        <div>
                                          <span data-bs-toggle="tooltip" :title="!licenceStatus ? translate('Vous devez passer à la version PRO.') : ''">
                                            <button id="import" class="btn btn-secondary form-group tooltip-wrapper" :disabled="!licenceStatus" @click="openImportModal()" >
                                              {{ translate('Importer') }}
                                            </button>
                                          </span>
                                        </div>
                                        <div>
                                          <span data-bs-toggle="tooltip" :title="!licenceStatus ? translate('Vous devez passer à la version PRO.') : ''">
                                            <button id="export" class="btn btn-secondary form-group tooltip-wrapper" :disabled="!licenceStatus" @click="exportFournisseurs()" >
                                              {{ translate('Exporter') }}
                                            </button>
                                          </span>
                                        </div>                
                                    </div>
                                </div>	
                            </div>

                            <div class="div_saut_ligne" style="height:20px;"></div>

                            <div class="centre">
                                <div
                                    v-if="alert"
                                    :class="['alert', alert.message_type === 'error' ? 'alert-danger' : 'alert-success']"
                                    role="alert" style="margin-bottom: 1rem;">
                                    {{ translate( alert.message ) }}
                                </div>
                                <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
                            </div>

                            <div class="div_saut_ligne" style="height:20px;"></div>

                            <!-- Ajoutez filtre alphabetique -->
                            <div id="alphabet-filter">
                              <ul class="nav nav-tabs my-3">
                                <li class="nav-item">
                                  <a class="nav-link" :class="{ active: selectedLetter === 'all' }" @click.prevent="filterByLetter('all')">
                                    {{ translate('All') }}
                                  </a>
                                </li>
                                <li class="nav-item" v-for="letter in alphabet" :key="letter">
                                  <a class="nav-link" :class="{ active: selectedLetter === letter }" @click.prevent="filterByLetter(letter)">
                                    {{ letter }}
                                  </a>
                                </li>
                              </ul>
                            </div>

                            <table class="table table-hover" v-if="paginatedFournisseurs.length > 0">
                              <thead>
                                  <tr>
                                      <th scope="col">{{ translate('Nom')}}</th>
                                      <th scope="col">{{ translate('Adresse')}}</th>
                                      <th scope="col">{{ translate('CP')}}</th>
                                      <th scope="col">{{ translate('Ville')}}</th>
                                      <th scope="col">{{ translate('Pays')}}</th>
                                      <th scope="col">{{ translate('Email')}}</th>
                                      <th scope="col">{{ translate('Téléphone')}}</th>
                                      <th scope="col">{{ translate('Voir')}}</th>
                                      <th scope="col">{{ translate('Modifier')}}</th>
                                      <th scope="col">{{ translate('Supprimer')}}</th>
                                  </tr>
                              </thead>

                              <tbody>
                                <tr v-for="fournisseur in paginatedFournisseurs" :key="fournisseur.id">
                                    <td>{{ fournisseur.nom}}</td>
                                    <td>{{ fournisseur.adresse }}</td>
                                    <td>{{ fournisseur.cp }}</td>
                                    <td>{{ fournisseur.ville }}</td>
                                    <td>{{ fournisseur.pays }}</td>
                                    <td>{{ fournisseur.email }}</td>
                                    <td>{{ fournisseur.telephone }}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary" @click="openModal(fournisseur, 'view')">
                                        <i class="fas fa-eye"></i> {{ translate('Voir')}}
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning" @click="openModal(fournisseur, 'edit')">
                                        <i class="fas fa-pencil-alt"></i> {{ translate('Modifier')}}
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger" @click="deleteFournisseurs(fournisseur)">
                                        <i class="fas fa-times"></i> {{ translate('Supprimer')}}
                                        </button>
                                    </td>
                                </tr>
                              </tbody>
                            </table>
                            <p v-else>{{ translate('Aucun fournisseur trouvé pour cette lettre.')}}</p>

                            <div class="div_saut_ligne" style="height:20px;"></div>

                            <div class="pagination" v-if="filteredFournisseurs.length > itemsPerPage">
                              <button id="precedent" class="btn btn-primary" @click="currentPage--" :disabled="currentPage === 1">{{ translate('Précédent') }}</button>
                              <p>{{ translate('Page') }} {{ currentPage }} {{ translate('sur') }} {{ Math.ceil(filteredFournisseurs.length / itemsPerPage) }}</p>
                              <button id="suivant" class="btn btn-primary" @click="currentPage++" :disabled="currentPage >= Math.ceil(filteredFournisseurs.length / itemsPerPage)">
                                {{ translate('Suivant') }}
                              </button>
                            </div>
                        </div>
                    </div>								
                </div>
            </div>	
        </div>
    </div>
</div>
<!-- Modal Fournisseur -->
<ModalFournisseur
    :isVisible="showModal" 
    :fournisseurData="selectedFournisseur|| {}" 
    :mode="modalMode"
    :translations="translations"
    @close="closeModal" 
    @save="saveFournisseur"
/>
<!-- Import Fournisseurs -->
<ImportFournisseurs
    :isVisible="showImportModal" 
    :translations="translations"
    @close="closeImportModal" 
    @import="importFournisseur"
/>
</template>

<script>
import ModalFournisseur from './modal-fournisseur.vue';
import ImportFournisseurs from './import-fournisseurs.vue';

export default {
  components: {
    ImportFournisseurs,
    ModalFournisseur
  },
  props: {
    translations: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      fournisseurs: [], // Tableau pour stocker les fournisseurs
      selectedLetter: 'all', // Lettre actuellement sélectionnée pour le filtre
      alphabet: Array.from({ length: 26 }, (_, i) => String.fromCharCode(65 + i)), // Génère A-Z
      userLang: window.FandProData.locale || 'fr_FR',
      translations: window.FandProData.translations.locale_data.messages, // Accéder à la structure imbriquée
      currentPage: 1, // Page actuelle
      itemsPerPage: 10, // Nombre d'éléments par page
      errorMessage: '', // Message d'erreur en cas de problème AJAX
      alert: null, // Objet pour les alertes dynamiques { message: '', message_type: '' }
      showModal: false, // Contrôle de la visibilité de la modal
      showImportModal: false, // Contrôle de la visibilité de la modal
      selectedFournisseur: null, // Fournisseur sélectionné pour modification ou vue
      modalMode: "add", // "view" ou "edit"
      licenceStatus: window.FandProData.licenceStatus // Récupération du statut
    };
  },

  computed: {
    // Fournisseurs filtrés par lettre sélectionnée
    filteredFournisseurs() {
      return this.selectedLetter === 'all'
        ? this.fournisseurs
        : this.fournisseurs.filter((f) => f.nom.toUpperCase().startsWith(this.selectedLetter.toUpperCase()));
    },
    paginatedFournisseurs() {
      const startIndex = (this.currentPage - 1) * this.itemsPerPage;
      const endIndex = startIndex + this.itemsPerPage;
      return this.filteredFournisseurs.slice(startIndex, endIndex);
    },
  },
  created() {
    // Lorsque le composant est créé, on charge les fournisseurs
    this.fetchFournisseurs();
  },
  mounted() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
  },
  methods: {
    filterByLetter(letter) {
      this.selectedLetter = letter;
      this.currentPage = 1; // Réinitialiser la pagination
    },
    
    showAlert(message, type = 'success') {
        if (this.alertTimeout) {
            clearTimeout(this.alertTimeout);
        }
        this.alert = { message, message_type: type };
        this.alertTimeout = setTimeout(() => {
            const alertElement = document.querySelector('.alert');
            if (alertElement) alertElement.classList.add('alert-hidden');
            setTimeout(() => {
            this.alert = null;
            }, 500); // Durée de l'animation
        }, 4500);
    },

    translate(key) {
      if (this.translations && this.translations[key]) {
        return this.translations[key][1];
      }
      return key;
    },

    /*Section modal fournisseur*/
    openModal(fournisseur, mode) {
      this.selectedFournisseur = { ...fournisseur };
      const shortLang = this.userLang.split('_')[1];
      if (mode === 'add') {
        // Définir le pays par défaut ici, par exemple 'FR'
        this.selectedFournisseur.pays = shortLang || 'FR';
      }
      this.showModal = true;
      this.modalMode = mode;
    },

    closeModal() {
        this.showModal = false;
        this.selectedFournisseur = null;
    },

    // Fonction pour récupérer les fournisseurs via AJAX
    fetchFournisseurs() {
      //console.log(this.licenceStatus);
      fetch(ajax_url + '?action=get_fournisseurs')
          .then(response => response.json())
          .then(data => {
              // Si la récupération des fournisseurs est réussie
              if (data.success) {
                  this.fournisseurs = data.data; // Mettre à jour la liste des fournisseurs
              }
              // Vérifiez si un message et un type de message sont présents dans la réponse
              if (data.data.message && data.data.message_type) {
                  this.showAlert(data.data.message, data.data.message_type); // Afficher le message avec son type
              }
          })
          .catch(error => {
              console.error('Erreur AJAX:', error);
          });
    },

    saveFournisseur(updatedFournisseur,mode) {
      fetch(ajax_url + '?action=save_fournisseur', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({fournisseur:updatedFournisseur,mode:mode}), // Envoyer les données mises à jour
      })
      .then((response) => response.json())
      .then((data) => {
          if (data.success) {
            if (data.data.message && data.data.message_type) {
                this.showAlert(data.data.message, data.data.message_type); // Afficher le message avec son type
            }
            this.fetchFournisseurs(); // Rafraîchir la liste
            this.closeModal(); // Fermer la modal
          }
      })
      .catch((error) => {
          this.showAlert('Erreur AJAX : ' + error.message, 'error');
      });
    },

    // Fonction pour supprimer un fournisseur
    deleteFournisseurs(fournisseur) {
        const confirmDelete = confirm(this.translate('Êtes-vous sûr de vouloir supprimer ce fournisseur ?'));
        if (confirmDelete) {
            fetch(ajax_url + '?action=delete_fournisseur', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fournisseur_id: fournisseur.id }),
            })
            .then((response) => response.json())
            .then((data) => {
                // Vérification si la réponse est réussie
                if (data.success) {
                    // Rafraîchir les fournisseurs
                    this.fetchFournisseurs();
                    if (data.data.message && data.data.message_type) {
                  this.showAlert(data.data.message, data.data.message_type); // Afficher le message avec son type
              }
            }
          })
          .catch(error => {
              console.error('Erreur AJAX:', error);
          });
        }
    },

    /*Section modal import fournisseur*/

    openImportModal() {
      const shortLang = this.userLang.split('_')[1];
      this.showImportModal = true;
    },

    closeImportModal() {
        this.showImportModal = false;
    },

    importFournisseur(selectedFile) {
      if (!selectedFile) {
        this.showAlert(this.translate('Veuillez sélectionner un fichier CSV.'), 'error');
        return;
      }

      const formData = new FormData();
      formData.append('csv_file', selectedFile);
      formData.append('action', 'import_fournisseurs'); // Ajout de l'action pour WordPress AJAX
      formData.append('import_fournisseurs_nonce', 'import_fournisseurs_action'); // Utilisation d'un nonce pour sécuriser la requête

      this.showAlert(this.translate('Importation en cours...'), 'info');

      fetch(ajax_url, {
        method: 'POST',
        body: formData,
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (data.data.message && data.data.message_type) {
                this.showAlert(data.data.message, data.data.message_type); // Afficher le message avec son type
            }
            this.closeImportModal();
            this.fetchFournisseurs(); // Recharger la liste des fournisseurs après importation
          } else {
            this.showAlert(data.message || this.translate('Erreur lors de l\'importation.'), 'error');
          }
        })
        .catch(error => {
          console.error('Erreur lors de l\'importation :', error);
          this.showAlert(this.translate('Une erreur est survenue lors de l\'importation.'), 'error');
        });
    },

    exportFournisseurs(){
      window.location.href = ajax_url + '?action=export_fournisseurs';
    },
  }
}
</script>

<style scoped>
.pagination {
justify-content: center
}

.pagination p {
  margin: auto 0px !important;
}

#precedent,#suivant {
 margin: 0px 20px;
}

#conteneur{
  width: 90%;
}

/* Styles de base pour le tableau */
table {
  width: 100%;
  border-collapse: collapse;
  font-family: Arial, sans-serif;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-radius: 5px;
  overflow: hidden;
}

/* En-têtes du tableau */
th {
  background-color: #f7f7f7;
  color: #333;
  text-align: left;
  padding: 5px;
  font-weight: bold;
  border-bottom: 2px solid #ddd;
}

/* Cellules */
td {
  padding: 5px;
  border-bottom: 1px solid #ddd;
  text-align: left;
  vertical-align: middle;
}

/* Alternance des lignes */
tr:nth-child(even) {
  background-color: #f9f9f9;
}

/* Effet au survol */
tr:hover {
  background-color: #f1f1f1;
}

/* Boutons */
button {
  border: none;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  color: #fff;
  font-weight: bold;
}

/* Alignement des colonnes avec du texte long */
td {
  max-width: 200px; /* Ajustez selon vos besoins */
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.error-message {
  color: red;
  font-weight: bold;
}

.alert {
  padding: 15px;
  border-radius: 5px;
  margin-bottom: 1rem;
  opacity: 1;
  transition: opacity 0.5s ease-in-out;
}
.alert-success {
  background-color: #d4edda;
  color: #155724;
}
.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
}
.alert-hidden {
  opacity: 0;
  transition: opacity 0.5s ease-in-out;
  visibility: hidden;
}
</style>