<template>
  <div v-if="isVisible" class="modal" tabindex="-1" v-bind:style="{ display: isVisible ? 'block' : 'none' }">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ translate(modalTitle) }}</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body">
          <div v-if="fournisseurData && mode === 'view'">
            <p><strong>{{ translate('Nom') }} : </strong> {{ fournisseurData.nom }}</p>
            <p><strong>{{ translate('Adresse') }} : </strong> {{ fournisseurData.adresse }}</p>
            <p><strong>{{ translate('CP') }} : </strong> {{ fournisseurData.cp }}</p>
            <p><strong>{{ translate('Ville') }} : </strong> {{ fournisseurData.ville }}</p>
            <p><strong>{{ translate('Pays') }} : </strong> {{ fournisseurData.pays }}</p>
            <p><strong>{{ translate('Email') }} : </strong> {{ fournisseurData.email }}</p>
            <p><strong>{{ translate('Téléphone') }} : </strong> {{ fournisseurData.telephone }}</p>
          </div>
          <div v-else>
            <!-- Formulaire de modification -->

            <input type="hidden" v-model="fournisseurData.id" class="form-control">
            <input type="text" v-model="fournisseurData.nom" class="form-control mt-2" :placeholder="translate('Nom')" required>
            <input type="text" v-model="fournisseurData.adresse" class="form-control mt-2" :placeholder="translate('Adresse')">
            <input type="text" v-model="fournisseurData.cp" class="form-control mt-2" :placeholder="translate('CP')">
            <input type="text" v-model="fournisseurData.ville" class="form-control mt-2" :placeholder="translate('Ville')">
            <div class="form-group mt-2" v-if="countries && Object.keys(countries).length > 0">
              <label for="country">{{ translate('Pays') }} : </label>
              <select id="country" v-model="fournisseurData.pays" class="form-control">
                <option v-for="(name, code) in countries" :key="code" :value="code">
                  {{ name }}
                </option>
              </select>
            </div>
            <div v-else>
              <p>{{ translate('Chargement des pays...') }}</p>
            </div>
            <input type="text" v-model="fournisseurData.email" class="form-control mt-2" :placeholder="translate('Email')" required>
            <input type="text" v-model="fournisseurData.telephone" class="form-control mt-2" :placeholder="translate('Téléphone')">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" v-if="mode !== 'view'" @click="saveChanges">{{ translate('Enregistrer') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      countries: {}, // Liste des pays initialisée vide
    };
  },
  props: {
    translations: {
      type: Object,
      required: true,
    },
    fournisseurData: {
      type: Object,
      required: false,
      default: () => ({}),
    },
    isVisible: {
      type: Boolean,
      required: true,
    },
    mode: {
      type: String, // "view", "edit", ou "add"
      required: true,
    },
  },
  mounted() {
    this.fetchCountries();
  },
  computed: {
    modalTitle() {
      switch (this.mode) {
        case 'edit':
          return 'Modifier fournisseur';
        case 'add':
          return 'Ajouter un fournisseur';
        default:
          return 'Voir fournisseur';
      }
    },
  },

  methods: {
    translate(key) {
      if (this.translations && this.translations[key]) {
        return this.translations[key][1];
      }
      return key; // Retourne la clé elle-même si la traduction manque
    },

    fetchCountries() {
      fetch(ajax_url + '?action=get_countries')
        .then((response) => response.json())
        .then((data) => {
          if (data.success && data.data) {
            this.countries = data.data;
          } 
        })
        .catch((error) => {
          console.error('Erreur AJAX :', error.message);
        });
    },

    closeModal() {
      this.$emit('close');
    },

    validateForm() {
      const requiredFields = ['nom', 'email'];
      const missingFields = requiredFields.filter((field) => !this.fournisseurData[field]);
      if (missingFields.length > 0) {
        const translatedFields = missingFields.map((field) => this.translate(field));
        alert(`${this.translate('Veuillez remplir les champs obligatoires')} : ${translatedFields.join(', ')}`);
        return false;
      }
      return true;
    },

    saveChanges() {
      if (this.validateForm()) {
        this.$emit('save', this.fournisseurData, this.mode);
      }
    },
  },
};
</script>
