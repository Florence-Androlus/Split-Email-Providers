<?php

namespace fand\Classes;

use fand\Classes\Database\Database;
use fand\Classes\Export_fournisseurs;

class Router {

    static public function init()
    {
        // objectif :
        // @TODO: déplacer la déclaration de la rewrite rule dans l'activation du plugin
        // si l'URL courante est ajout, afficher le template readfile.php du thème
        // 2e argument : URL réelle correspondant à la "fausse URL" de l'argument 1 
        // 1. ajout de la réécriture = on permet à WP de reconnaître notre URL custom :
        add_rewrite_rule('fournisseurs', 'index.php?fand-page=fournisseurs', 'top');  
        add_rewrite_rule('export_csv', 'index.php?fand-page=export_csv', 'top'); 
        add_rewrite_rule('import_csv', 'index.php?fand-page=import_csv', 'top');

        // 2. on rafraîchit les réécritures au sein de WP
        flush_rewrite_rules();

        // 3. Autoriser notre query var (paramètre d'URL) custom dans WP
        add_filter('query_vars', function($query_vars) {

            $query_vars[] = 'fand-page'; // on rajoute notre propre query var en tant que query var autorisée

            // on return le tableau $query_vars
            return $query_vars;

        });

        // 4. Surcharger (ou pas !) le choix de template fait par WP
        // $template contient le chemin vers le fichier de template que WP comptait charger si on ne l'avait pas interrompu
        add_action( 'template_include', function( $template ) {
            
            // on vérifie si notre query var custom est présente et a une valeur qu'on connaît
            // pour lire une query var, on utilise get_query_var()
            if (get_query_var('fand-page') == 'fournisseurs') {

                // Vérification si une action est soumise via POST
                $action = isset($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : '';
            
                if ($action === 'add') {
                    // Vérification du nonce pour l'ajout
                    if (!isset($_POST['fournisseur_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fournisseur_nonce'])), 'fournisseur_nonce_action')) {
                        wp_die('Nonce non valide');
                    }

                    $alert = Database::add_fournisseur($_POST);
                }
                elseif ($action === 'update') {
                    // Vérification du nonce pour la mise à jour
                    if (!isset($_POST['fournisseur_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fournisseur_nonce'])), 'fournisseur_nonce_action')) {
                        wp_die(esc_html__('Nonce non valide pour la mise à jour du fournisseur.', 'split-email-providers'));
                    }
                    $alert = Database::update_fournisseur($_POST);
                }
                elseif ($action === 'delete') {
                    // Vérification du nonce pour la suppression
                    if (!isset($_POST['delete_fournisseur_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['delete_fournisseur_nonce'])), 'delete_fournisseur_action')) {
                        wp_die(esc_html__('Nonce non valide pour la suppression du fournisseur.', 'split-email-providers'));
                    }
                    $alert = Database::delete_fournisseur($_POST);
                }
            
                // Redirection avec le message
                $url = admin_url('admin.php?page=fand-settings&message=' . urlencode($alert['message']) . '&message_type=' . $alert['message_type']);
                wp_redirect($url);
                exit();
            }        
            elseif (get_query_var('fand-page') == 'export_csv') {

                // Vérification du nonce pour l'export CSV
                if (!isset($_POST['export_fournisseurs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['export_fournisseurs_nonce'])), 'export_fournisseurs_action')) {
                    wp_die(esc_html__('Vérification nonce échouée. Action non autorisée.', 'split-email-providers'));
                }
                $alert = Export_fournisseurs::exporter_fournisseurs_csv();
                exit();
            }
            else if (get_query_var('fand-page') == 'import_csv') {
                // Vérification du nonce pour l'import CSV
                if (!isset($_POST['import_fournisseurs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['import_fournisseurs_nonce'])), 'import_fournisseurs_action')) {
                    wp_die(esc_html__('Vérification nonce échouée. Action non autorisée.', 'split-email-providers'));
                }
                $alert= Import_fournisseurs::import_fournisseurs_csv();
                // Rediriger vers la page de paramètres avec le message et le type
                $url = admin_url('admin.php?page=fand-settings&message=' . urlencode($alert['message']) . '&message_type=' . $alert['message_type']);
                wp_redirect($url);
                exit(); // On empêche le reste du code de s'exécuter
            }
            else {
                // sinon, on laisse WP faire
                return $template;
            }
        } );
    }
}