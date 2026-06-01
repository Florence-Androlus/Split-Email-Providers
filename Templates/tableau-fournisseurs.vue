<template>
<!-- Tab panes -->
<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="add">		
        <div class="div_conteneur_parent">
            <div class="div_conteneur_page"  >
                <div class="div_int_page">			
                    <div class="div_h1" >
                        <h1> {{ translate('Providers table') }}</h1>
                    </div>
                    <div class="div_saut_ligne" style="height:20px;"></div>
                    <div class="wrapper-alerte-fixe">
                        <transition name="fade-vitesse">
                            <div v-if="alert"
                                :class="['alert', alert.message_type === 'error' ? 'alert-danger' : 'alert-success']"
                                role="alert">
                                {{ translate( alert.message ) }}
                            </div>
                        </transition>
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
                                                {{ translate('Add')}}
                                            </button>
                                        </div>
                                        <!--div-->
                                            <!--button id="import" class="btn btn-secondary form-group tooltip-wrapper" data-bs-toggle="tooltip"  :title="translate('You need to upgrade to the PRO version.')"> {{ translate('Import') }} </!--button-->
                                            <!--button 
                                                id="import" 
                                                :class="['btn', isProActive ? 'btn-primary' : 'btn-secondary']" 
                                                @click="triggerFileInput"  :disabled="!isProActive"
                                                data-bs-toggle="tooltip" 
                                                :title="!isProActive ? translate('You need to upgrade to the PRO version.') : ''"> 
                                                {{ translate('Import') }} 
                                            </!--button>
                                        </!--div>
                                        <input 
                                            type="file" 
                                            ref="fileInput" 
                                            style="display: none" 
                                            accept=".csv" 
                                            @change="handleFileUpload"
                                        >
                                        <div-->
                                            <!--button--  id="export" class="btn btn-secondary form-group tooltip-wrapper" data-bs-toggle="tooltip"  :title="translate('You need to upgrade to the PRO version.')"> {{ translate('Export') }} </!--button-->
                                            <!--button 
                                                id="export" 
                                                :class="['btn', isProActive ? 'btn-primary' : 'btn-secondary']" 
                                                @click="exportCSV"
                                                :disabled="!isProActive"
                                                data-bs-toggle="tooltip" 
                                                :title="!isProActive ? translate('You need to upgrade to the PRO version.') : ''"> 
                                                {{ translate('Export') }} 
                                            </!--button>
                                        </div-->    
                                        <div>
                                            <button 
                                                id="import"
                                                class="btn btn-secondary"
                                                @click="goToPro"
                                                data-bs-toggle="tooltip" 
                                                :title="translate('You need to upgrade to the PRO version.')"> 
                                                {{ translate('Import') }} 
                                            </button>
                                        </div>

                                        <div>
                                            <button 
                                                id="export"
                                                class="btn btn-secondary"
                                                @click="goToPro"
                                                data-bs-toggle="tooltip" 
                                                :title="translate('You need to upgrade to the PRO version.')"> 
                                                {{ translate('Export') }} 
                                            </button>
                                        </div>                                  
                                    </div>
                                </div>	
                            </div>

                            <div class="div_saut_ligne" style="height:50px;"></div>

                            <div class="alphabet-filter" style="margin-bottom: 15px; text-align: center;">
                                <button 
                                    v-for="letter in alphabet" 
                                    :key="letter"
                                    @click="filterByLetter(letter)"
                                    :class="['btn btn-sm', selectedLetter === letter ? 'btn-primary' : 'btn-outline-secondary']"
                                    style="margin: 2px;">
                                    {{ letter }}
                                </button>
                            </div>

                            <table class="table table-hover" v-if="fournisseurs.length > 0">
                              <thead>
                                  <tr>
                                      <th scope="col">{{ translate('Name')}}</th>
                                      <th scope="col">{{ translate('Address')}}</th>
                                      <th scope="col">{{ translate('Postcode')}}</th>
                                      <th scope="col">{{ translate('City')}}</th>
                                      <th scope="col">{{ translate('Country')}}</th>
                                      <th scope="col">{{ translate('Email')}}</th>
                                      <th scope="col">{{ translate('Phone')}}</th>
                                      <th scope="col">{{ translate('View')}}</th>
                                      <th scope="col">{{ translate('Edit')}}</th>
                                      <th scope="col">{{ translate('Delete')}}</th>
                                  </tr>
                              </thead>

                              <tbody>
                                <tr v-for="(fournisseur, index) in currentFournisseurs" :key="fournisseur.id || 'prov-' + index">
                                    <td>{{ fournisseur.nom }}</td>
                                    <td>{{ fournisseur.adresse }}</td>
                                    <td>{{ fournisseur.cp }}</td>
                                    <td>{{ fournisseur.ville }}</td>
                                    <td>{{ fournisseur.pays }}</td>
                                    <td>{{ fournisseur.email }}</td>
                                    <td>{{ fournisseur.telephone }}</td>
                                    <td>
                                        <button type="button" class="btn btn-primary" @click="openModal(fournisseur, 'view')">
                                        <i class="fas fa-eye"></i> {{ translate('View')}}
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning" @click="openModal(fournisseur, 'edit')">
                                        <i class="fas fa-pencil-alt"></i> {{ translate('Edit')}}
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger" @click="deleteFournisseurs(fournisseur)">
                                        <i class="fas fa-times"></i> {{ translate('Delete')}}
                                        </button>
                                    </td>
                                </tr>
                              </tbody>
                            </table>
                            <p v-else>{{ translate('Aucune fournisseurs disponible.')}}</p>

                            <div class="div_saut_ligne" style="height:50px;"></div>
                            <div class="pagination">
                              <button id="precedent" class="btn btn-primary" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">{{ translate('Previous') }}</button>
                              <span>{{ currentPage }} / {{ totalPages }}</span>
                              <button id="suivant" class="btn btn-primary" @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">{{ translate('Next') }}</button>
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
  </div>
</template>

<script>
import ModalFournisseur from './modal-fournisseur.vue';

export default {
    components: {
        ModalFournisseur
    },
    data() {
        return {
            userLang: FandData.locale || 'fr_FR',
            translations: window.FandData.translations.locale_data['split-email-providers'], 
            fournisseurs: [], // Tableau pour stocker les fournisseurs
            currentPage: 1, // Page actuelle
            itemsPerPage: 10, // Nombre d'éléments par page
            errorMessage: '', // Message d'erreur en cas de problème AJAX
            alert: null, // Objet pour les alertes dynamiques { message: '', message_type: '' }
            showModal: false, // Contrôle de la visibilité de la modal
            selectedFournisseur: null, // Fournisseur sélectionné pour modification ou vue
            modalMode: "add", // "view" ou "edit"
            // isProActive: window.FandData.licenceStatus,
            selectedLetter: 'All',
            alphabet: ['All','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'],
        };
    },
    computed: {
        // 1. On trie d'abord la liste complète
        sortedFournisseurs() {
            return [...this.fournisseurs].sort((a, b) => {
                const nomA = a.nom ? a.nom.toLowerCase() : '';
                const nomB = b.nom ? b.nom.toLowerCase() : '';
                return nomA.localeCompare(nomB);
            });
        },

        filteredFournisseurs() {
            if (this.selectedLetter === 'All') return this.sortedFournisseurs;
            return this.sortedFournisseurs.filter(f =>
                f.nom && f.nom.toUpperCase().startsWith(this.selectedLetter)
            );
        },

        totalPages() {
            return Math.ceil(this.filteredFournisseurs.length / this.itemsPerPage) || 1;
        },

        currentFournisseurs() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredFournisseurs.slice(start, start + this.itemsPerPage);
        }
    },
    created() {
        // Lorsque le composant est créé, on charge les fournisseurs
        this.fetchFournisseurs();
    },
    mounted() {
        // console.log(traductionsVue.tableau_fournisseurs); // Cela doit afficher "Providers table" si la langue est en anglais
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Écouter l'événement de refresh du plugin PRO
        document.addEventListener('fandsep_refresh_fournisseurs', () => {
            this.fetchFournisseurs();
        });
    },
    methods: {
        goToPro() {
            window.open('https://fan-develop.fr/addon-woocommerce-gestion-fournisseurs/', '_blank');
        },

        translate(key) {
            if (this.translations && this.translations[key]) {
                return this.translations[key][1];
            }
            return key;
        },

        filterByLetter(letter) {
            this.selectedLetter = letter;
            this.currentPage = 1;
        },

        // Fonction pour récupérer les fournisseurs via AJAX
        fetchFournisseurs() {
        fetch(ajax_url + '?action=fandsep_get_fournisseurs')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                        // Si data.data contient directement la liste
                        this.fournisseurs = Array.isArray(data.data) ? data.data : []; 
                        
                        // Si le message est dans data.data.message
                        if (data.data && data.data.message) {
                            this.showAlert(data.data.message, data.data.message_type);
                        }
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
                    this.fournisseurs = [];
            });
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

        // Fonction pour supprimer un fournisseur
        deleteFournisseurs(fournisseur) {
            const confirmDelete = confirm(this.translate('Are you sure you want to remove this provider?'));
            if (confirmDelete) {
                fetch(ajax_url + '?action=fandsep_delete_fournisseur&nonce=' + window.FandData.nonce, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
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

        saveFournisseur(updatedFournisseur,mode) {
        fetch(ajax_url + '?action=fandsep_save_fournisseur&nonce=' + window.FandData.nonce, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fournisseur: updatedFournisseur, mode: mode }),
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

        changePage(page) {
        if (page > 0 && page <= this.totalPages) {
            this.currentPage = page;
            }
        },

        /*exportCSV() {
            if (!this.isProActive) return;

            // Pour un export, on peut simplement ouvrir l'URL AJAX dans un nouvel onglet
            // car l'action AJAX va forcer le téléchargement du fichier CSV
            const exportUrl = ajax_url + '?action=sep_export_fournisseurs';
            window.location.href = exportUrl;
        },

        // 1. Déclenche le clic sur l'input caché
        triggerFileInput() {
            if (this.isProActive) {
                this.$refs.fileInput.click();
            }
        },

        // 2. Gère l'envoi du fichier une fois sélectionné
        handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('action', 'import_fournisseurs');
            formData.append('csv_file', file);
            
            // ICI : On ajoute le nonce qui manque !
            // On utilise la clé 'import_fournisseurs_nonce' car c'est ce que ton PHP cherche
            formData.append('import_fournisseurs_nonce', window.FandData.import_nonce); 

            fetch(ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Le message est dans data.data.message
                    this.showAlert(data.data.message, 'success');
                    this.fetchFournisseurs(); 
                } else {
                    // Si data.success est false, l'erreur est ici
                    this.showAlert(data.data.message || 'Erreur lors de l\'import', 'error');
                }
                this.$refs.fileInput.value = '';
            })
            .catch(error => {
                console.error('Erreur:', error);
                this.showAlert('Erreur lors de la connexion au serveur', 'error');
            });
        },*/
    }
}
</script>

<style scoped>
button {
    border: none;
    color: #fff;  /* ← force tout en blanc */
    font-weight: bold;
}

.alphabet-filter {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 4px;
    margin-bottom: 15px;
}

.alphabet-filter button {
    min-width: 32px;
    padding: 2px 6px;
    font-size: 12px;
    color: #6c757d;        /* ← couleur sombre pour outline */
    font-weight: normal;
    border: 1px solid #6c757d;
    flex-shrink: 0;
}

.alphabet-filter button.btn-primary {
    color: #fff;           /* ← blanc uniquement quand actif */
    border-color: #0d6efd;
}

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
  padding: 10px;
  font-weight: bold;
  border-bottom: 2px solid #ddd;
}

/* Cellules */
td {
  padding: 12px 8px;
  border-bottom: 1px solid #ddd;
  text-align: left;
  vertical-align: middle;
  white-space: normal; 
  word-break: break-all; /* Force la coupure pour ne pas déborder sur les boutons */
  /* Alignement des colonnes avec du texte long */
  max-width: 200px; /* Ajustez selon vos besoins */
  overflow: hidden;
  text-overflow: ellipsis;
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
  position: relative; /* Force le bouton à être "au-dessus" */
  z-index: 999 !important;
  pointer-events: auto !important;
  cursor: pointer !important;
  border: none;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  color: #fff;
  font-weight: bold;
}

button.btn:hover {
    filter: brightness(0.9);
    outline: 2px solid blue; /* Si tu vois du bleu au survol, c'est que le clic est possible */
}


.error-message {
  color: red;
  font-weight: bold;
}

/* Le wrapper doit occuper toute la largeur pour que text-align fonctionne */
.wrapper-alerte-fixe {
    width: 100%;
    text-align: center; /* Centre l'alerte qui est en inline-block */
    height: 40px;      /* Réserve l'espace pour éviter que le tableau saute */
    margin-bottom: 15px; 
    display: flex;
    justify-content: center;
    align-items: center;
}

.alert {
    /* Supprime le display: inline-block si tu utilises le flex ci-dessus */
    display: block; 
    min-width: 300px; /* Optionnel : pour que l'alerte ait une belle taille */
    padding: 10px 20px;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 1000;
}

/* Animation fluide sans saut */
.fade-vitesse-enter-active, .fade-vitesse-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-vitesse-enter, .fade-vitesse-leave-to {
  opacity: 0;
    transform: scale(0.95); /* Effet de zoom léger plutôt que de déplacement */
}

.alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
.alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
</style>