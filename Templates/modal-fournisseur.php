<!-- Fenêtre modale -->
<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div id="myModal" class="modal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <span class="close">&times;</span>

            <div class="div_saut_ligne" style="height:50px;"></div>

            <h2 id="modalTitle">Ajouter un Fournisseur</h2>

            <form id="form" name="form" enctype="multipart/form-data" method="post" action="<?php echo esc_url(home_url("fournisseurs")) ?>">
                <!-- Ajout du champ nonce caché dans la modal -->
                <?php wp_nonce_field('fournisseur_nonce_action', 'fournisseur_nonce'); ?>
                <input type="hidden" id="fournisseurId" name="fournisseurId" value="">
                <!-- Champ caché pour l'action (add ou update) -->
                <input type="hidden" id="actionField" name="action" value="add"> <!-- Par défaut sur "add" -->

                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse</label>
                    <input type="text" id="adresse" name="adresse" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="cp">Code Postal</label>
                    <input type="text" id="cp" name="cp" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="ville">Ville</label>
                    <input type="text" id="ville" name="ville" required class="form-control">
                </div>

                <div class="div_saut_ligne" style="height:20px;"></div>

                <div class="form-group">
                    <!-- Champ Select pour les pays -->
                    <label for="pays">Pays:</label>
                    <select id="pays" name="pays" required >
                        <?php

                        // Récupérer la liste des pays via WooCommerce
                        $countries = WC()->countries->get_countries();

                        // Définir le pays par défaut (FR pour la France)
                        $default_country = 'FR';

                        foreach ($countries as $country_code => $country_name) {
                            // Vérifier si le pays en cours est celui par défaut
                            $selected = ($country_code === $default_country) ? 'selected' : '';
                            echo '<option value="' . esc_attr($country_code) . '" ' . esc_attr($selected) . '>' . esc_html($country_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="div_saut_ligne" style="height:20px;"></div>

                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" required class="form-control"> 
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required class="form-control">
                </div>

                <div class="div_saut_ligne" style="height:20px;"></div>

                <div>
                    <button type="submit" id="submitButton">Ajouter</button>
                </div>

                <div class="div_saut_ligne" style="height:20px;"></div>
            </form>
        </div>
    </div>
</div>