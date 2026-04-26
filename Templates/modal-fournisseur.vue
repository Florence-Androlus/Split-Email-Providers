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
            <p><strong>{{ translate('Name') }} : </strong> {{ fournisseurData.nom }}</p>
            <p><strong>{{ translate('Address') }} : </strong> {{ fournisseurData.adresse }}</p>
            <p><strong>{{ translate('Postal Code') }} : </strong> {{ fournisseurData.cp }}</p>
            <p><strong>{{ translate('City') }} : </strong> {{ fournisseurData.ville }}</p>
            <p><strong>{{ translate('Country') }} : </strong> {{ fournisseurData.pays }}</p>
            <p><strong>{{ translate('Email') }} : </strong> {{ fournisseurData.email }}</p>
            <p><strong>{{ translate('Phone') }} : </strong> {{ fournisseurData.telephone }}</p>
          </div>
          <div v-else>
            <!-- Formulaire de modification -->

            <input type="hidden" v-model="fournisseurData.id" class="form-control">
            <input type="text" v-model="fournisseurData.nom" class="form-control mt-2" :placeholder="translate('Name')" required>
            <input type="text" v-model="fournisseurData.adresse" class="form-control mt-2" :placeholder="translate('Address')">
            <input type="text" v-model="fournisseurData.cp" class="form-control mt-2" :placeholder="translate('Postal Code')">
            <input type="text" v-model="fournisseurData.ville" class="form-control mt-2" :placeholder="translate('City')">
            <div class="form-group mt-2" v-if="countries && Object.keys(countries).length > 0">
              <label for="country">{{ translate('Country') }} : </label>
              <select id="country" v-model="fournisseurData.pays" class="form-control">
                <option v-for="(name, code) in countries" :key="code" :value="code">
                  {{ name }}
                </option>
              </select>
            </div>
            <div v-else>
              <p>{{ translate('load country...') }}</p>
            </div>
            <input type="text" v-model="fournisseurData.email" class="form-control mt-2" :placeholder="translate('Email')" required>
            <input type="text" v-model="fournisseurData.telephone" class="form-control mt-2" :placeholder="translate('Phone')">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" v-if="mode !== 'view'" @click="saveChanges">{{ translate('Register') }}</button>
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
          return 'Change provider';
        case 'add':
          return 'Add a provider';
        default:
          return 'View provider';
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
        alert(`${this.translate('Please fill in the required fields')} : ${translatedFields.join(', ')}`);
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
