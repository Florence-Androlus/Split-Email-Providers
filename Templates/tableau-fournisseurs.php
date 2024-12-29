<!-- Tab panes -->
<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="add">		
        <div class="div_conteneur_parent">
            <div class="div_conteneur_page"  >
                <div class="div_int_page">			
                    <div class="div_h1" >
                        <h1>Tableau des fournisseurs</h1>
                    </div>

                    <div class="div_saut_ligne" style="height:50px;"></div>	

                    <div style="width:100%;height:auto;text-align:center;">                              
                        <div style="display:inline-block;" id="conteneur">
                            <div class="centre">
                                <div class="titre_centre">
                                    <!-- Ajouter les boutons d'actions au-dessus du tableau -->
                                    <div class="d-flex justify-content-center mb-3">
                                        <div>
                                            <button id="openModalBtn" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                                                Ajouter
                                            </button>
                                        </div>
                                        <div>
                                            <button id="import" class="btn btn-secondary form-group tooltip-wrapper" data-bs-toggle="tooltip" title="Vous devez passer à la version PRO.">Importer</button>
                                        </div>
                                        <div>
                                            <button id="import" class="btn btn-secondary form-group tooltip-wrapper" data-bs-toggle="tooltip" title="Vous devez passer à la version PRO.">Exporter</button>
                                        </div>
                                    </div>
                                </div>	
                            </div>

                            <div class="div_saut_ligne" style="height:50px;"></div>

                            <div class="centre">

                            <?php
                                if ($message): 
                            ?>
                                <div id="auto-dismiss" class="alert <?php echo esc_attr($message_type === 'error' ? 'alert-danger' : 'alert-success'); ?>" style="display: inline-block;">
                                    <?php echo esc_html($message); ?>
                                </div>
                            <?php endif; ?>
                            </div>

                            <div class="div_saut_ligne" style="height:50px;"></div>

                            <!-- Ajoutez filtre alphabetique -->
                            <div id="alphabet-filter" style="margin-bottom: 20px;"></div>

                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Nom</th>
                                            <th scope="col">Adresse</th>
                                            <th scope="col">CP</th>
                                            <th scope="col">Ville</th>
                                            <th scope="col">Pays</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Téléphone</th>
                                            <th scope="col">Voir</th>
                                            <th scope="col">Modifier</th>
                                            <th scope="col">Supprimer</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php
                                        global $wpdb;
                                        // Récupérer les données de la table 'fournisseurs'
                                        $fournisseurs = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", FAND_FOURNISSEURS_TABLE));

                                        // Vérifier si des résultats ont été trouvés
                                        if (!empty($fournisseurs)) {

                                            foreach ($fournisseurs as $index => $fournisseur) {

                                                echo "<tr>";
                                                echo "<th scope='row'>" . esc_html($index + 1) . "</th>"; // Index + 1 pour numéroter les lignes
                                                echo "<td>" . esc_html($fournisseur->nom) . "</td>";
                                                echo "<td>" . esc_html($fournisseur->adresse) . "</td>";
                                                echo "<td>" . esc_html($fournisseur->cp) . "</td>";
                                                echo "<td>" . esc_html(stripslashes($fournisseur->ville)) . "</td>";
                                                echo "<td>" . esc_html($fournisseur->pays) . "</td>";
                                                echo "<td>" . esc_html($fournisseur->email) . "</td>";
                                                echo "<td>" . esc_html($fournisseur->telephone) . "</td>";
                                                echo "<td>";

                                                // afficher le bouton voir fiche fournisseur
                                                echo '<button type="button" class="openFicheBtn btn btn-primary" title="Voir" 
                                                        data-id="'. esc_attr($fournisseur->id) .'"
                                                        data-nom="'. esc_attr($fournisseur->nom) .'"
                                                        data-adresse="'. esc_attr($fournisseur->adresse) .'"
                                                        data-cp="'. esc_attr($fournisseur->cp) .'"
                                                        data-ville="'. esc_attr($fournisseur->ville) .'"
                                                        data-pays="'. esc_attr($fournisseur->pays) .'"
                                                        data-email="'. esc_attr($fournisseur->email) .'"
                                                        data-telephone="'. esc_attr($fournisseur->telephone) .'">
                                                        <i class="fas fa-eye"></i>
                                                    </button>';

                                                echo "</td>";
                                                echo "<td>";
                                                    echo '<button type="button" class="btn btn-warning" title="Modifier" 
                                                    data-id="'. esc_attr($fournisseur->id) .'"
                                                    data-nom="'. esc_attr($fournisseur->nom) .'"
                                                    data-adresse="'. esc_attr($fournisseur->adresse) .'"
                                                    data-cp="'. esc_attr($fournisseur->cp) .'"
                                                    data-ville="'. esc_attr($fournisseur->ville) .'"
                                                    data-pays="'. esc_attr($fournisseur->pays) .'"
                                                    data-email="'. esc_attr($fournisseur->email) .'"
                                                    data-telephone="'. esc_attr($fournisseur->telephone) .'">
                                                    <i class="fas fa-pencil-alt"></i>
                                                    </button>';
                                                echo "</td>";
                                                echo "<td>";
                                                    ?>
                                                    <form id="form" name="form" enctype="multipart/form-data" method="post" action="<?php echo esc_url(home_url("fournisseurs")) ?>">
                                                    <?php
                                                        wp_nonce_field('delete_fournisseur_action', 'delete_fournisseur_nonce'); 
                                                        echo "<input type='hidden' name='fournisseur_id' value='" . esc_attr($fournisseur->id) . "' />"
                                                    ?>
                                                    <button type='submit' name='action' value='delete' class='btn btn-danger' title='Supprimer'>
                                                    <i class='fas fa-times'></i>
                                                    </button>
                                                    </form>
                                                </td>
                                            </tr>
                                                <?php
                                            }
                                        } else {
                                            echo "<tr><td colspan='10'>Aucun fournisseur trouvé</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            <div class="div_saut_ligne" style="height:50px;"></div>
                        </div>
                    </div>								
                </div>
            </div>	
        </div>
    </div>
</div>

