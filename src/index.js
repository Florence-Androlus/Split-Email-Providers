// Importation de Vue et du composant principal
import Vue from 'vue';
import App from '../Templates/tableau-fournisseurs.vue'; // ou ton composant principal

new Vue({
  render: h => h(App),
}).$mount('#app'); // remplace #app par l'ID de ton div
