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
            <input type="text" maxlength="50" @input="fournisseurData.nom = fournisseurData.nom.replace(/[0-9<>]/g, '')" v-model="fournisseurData.nom" class="form-control mt-2" :placeholder="translate('Name')" required>
            <input type="text" maxlength="100" @input="fournisseurData.adresse = fournisseurData.adresse.replace(/[<>]/g, '')" v-model="fournisseurData.adresse" class="form-control mt-2" :placeholder="translate('Address')">
            <input type="text" maxlength="12" @input="fournisseurData.cp = fournisseurData.cp.toUpperCase().replace(/[^A-Z0-9\s\-]/g, '')" v-model="fournisseurData.cp" class="form-control mt-2" :placeholder="translate('Postal Code')">
            <input type="text" maxlength="50" @input="fournisseurData.ville = fournisseurData.ville.replace(/[0-9<>]/g, '')" v-model="fournisseurData.ville" class="form-control mt-2" :placeholder="translate('City')">
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
            <input type="email" v-model="fournisseurData.email" class="form-control mt-2" :placeholder="translate('Email')" required>
            <input type="tel" @input="fournisseurData.telephone = fournisseurData.telephone.replace(/[^0-9\+\s\-\.]/g, '')" v-model="fournisseurData.telephone" class="form-control mt-2" :placeholder="translate('Phone')">
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
      fetch(ajax_url + '?action=fandsep_get_countries')
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
      // Fonction interne pour supprimer les caractères dangereux (<, >, ", ')
        const sanitize = (text) => {
          if (typeof text !== 'string') return text;
          return text.replace(/[<>:"']/g, ''); 
        };

        // On crée une copie propre des données
        const cleanData = {
          id: this.fournisseurData.id,
          nom: sanitize(this.fournisseurData.nom),
          adresse: sanitize(this.fournisseurData.adresse),
          ville: sanitize(this.fournisseurData.ville),
          cp: sanitize(this.fournisseurData.cp),
          email: this.fournisseurData.email, // Déjà validé par regex
          telephone: this.fournisseurData.telephone, // Déjà validé par regex
          pays: this.fournisseurData.pays
        };

        // On lance les validations de format qu'on a fait avant
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (cleanData.email && !emailRegex.test(cleanData.email)) {
          alert("Email invalide");
          return;
        }

      if (this.validateForm()) {
        this.$emit('save', cleanData, this.mode);
      }
    },
  },
};
</script>
