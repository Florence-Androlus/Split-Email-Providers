<?php

namespace fand\Classes;

use fand\Classes\Database\Database;

class Import_fournisseurs {

    static public function import_fournisseurs_csv() {
        global $wpdb;
        $alert = [];

        // Vérifier les permissions de l'utilisateur
        if (!current_user_can('manage_options')) {
            return ['message' => 'Vous n\'avez pas la permission de réaliser cette action.', 'message_type' => 'error'];
        }

        // Vérifier le nonce
        if (!isset($_POST['import_fournisseurs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['import_fournisseurs_nonce'])), 'import_fournisseurs_action')) {
            wp_die(esc_html__('Vérification nonce échouée. Action non autorisée.', 'split-email-providers'));
        }

        // Vérifier si un fichier a été téléchargé
        if (isset($_FILES['csv_file']) && !empty($_FILES['csv_file']['tmp_name'])) {
            $file = $_FILES['csv_file']['tmp_name'];

            if (!file_exists($file)) {
                return ['message' => 'Le fichier temporaire est introuvable.', 'message_type' => 'error'];
            }
            // Utiliser WP_Filesystem pour ouvrir et lire le fichier
            require_once(ABSPATH . 'wp-admin/includes/file.php');

            WP_Filesystem();

            global $wp_filesystem;

            if ($wp_filesystem->exists($file)) {
                $file_contents = $wp_filesystem->get_contents($file);

                if ($file_contents !== false) {
                    $lines = explode("\n", $file_contents);
                    $headers = str_getcsv(array_shift($lines), ",");

                    // Nettoyer la première clé
                    $headers[0] = preg_replace('/\x{FEFF}/u', '', $headers[0]);

                    // Lire chaque ligne du fichier
                    foreach ($lines as $line) {
                        $data = str_getcsv($line, ",");

                        // Vérification du nombre de colonnes
                        if (count($data) == count($headers)) {
                            $fournisseur = array_combine($headers, $data);
                            $fournisseur = array_change_key_case($fournisseur, CASE_LOWER);

                            if ($fournisseur !== false) {
                                // Utilisation de clés normalisées
                                $data=[
                                'nom' => isset($fournisseur['nom']) ? sanitize_text_field($fournisseur['nom']) : '',
                                'adresse' => isset($fournisseur['adresse']) ? sanitize_text_field($fournisseur['adresse']) : '',
                                'cp' => isset($fournisseur['cp']) ? sanitize_text_field($fournisseur['cp']) : '',
                                'ville' => isset($fournisseur['ville']) ? stripslashes(sanitize_text_field($fournisseur['ville'])) : '',
                                'pays' => isset($fournisseur['pays']) ? sanitize_text_field($fournisseur['pays']) : '',
                                'email' => isset($fournisseur['email']) ? sanitize_email($fournisseur['email']) : '',
                                'telephone' => isset($fournisseur['téléphone']) ? sanitize_text_field($fournisseur['téléphone']) : ''
                                ];
                                extract($data);


                                // Vérifier si un fournisseur avec le même email existe déjà
                                $existing_fournisseur = $wpdb->get_row(
                                    $wpdb->prepare("SELECT * FROM %i WHERE email = %s",FAND_FOURNISSEURS_TABLE, $email)
                                );

                                if ($existing_fournisseur) {
                                    // Fournisseur existe - Mettre à jour les informations
                                    Database::update_fournisseur($data);
                                    /*$wpdb->update(
                                        FAND_FOURNISSEURS_TABLE,
                                        [
                                            'nom' => $nom,
                                            'adresse' => $adresse,
                                            'cp' => $cp,
                                            'ville' => $ville,
                                            'pays' => $pays,
                                            'telephone' => $telephone
                                        ],
                                        ['email' => $email]
                                    );*/
                                } else {
                                    // Fournisseur n'existe pas - Insérer un nouveau fournisseur
  
                                    Database::add_fournisseur($data);
                                    /*$wpdb->insert(
                                        FAND_FOURNISSEURS_TABLE,
                                        [
                                            'nom' => $nom,
                                            'adresse' => $adresse,
                                            'cp' => $cp,
                                            'ville' => $ville,
                                            'pays' => $pays,
                                            'email' => $email,
                                            'telephone' => $telephone
                                        ]
                                    );*/
                                }
                            }
                        } else {
                            continue; // Ligne ignorée (nombre de colonnes incorrect)
                        }
                    }
                    // Message de confirmation
                    $message = "Importation terminée avec succès.";
                    $message_type = 'success';
                } else {
                    $message = "Impossible de lire le contenu du fichier CSV.";
                    $message_type = 'error';
                }
            } else {
                $message = "Fichier introuvable ou inaccessible.";
                $message_type = 'error';
            }

            $alert = ['message' => $message, 'message_type' => $message_type];
            return $alert;
        }
    }
}
