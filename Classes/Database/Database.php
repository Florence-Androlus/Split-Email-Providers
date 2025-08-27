<?php

namespace fand\Classes\Database;

use fand\Classes\FAND_Attribut;

class Database {

    static public function init()
    {
        global $wpdb;

        // Préfixe des tables WordPress
        $table_name = FAND_FOURNISSEURS_TABLE;

        // Structure SQL pour créer la table
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            adresse VARCHAR(255),
            cp VARCHAR(10),
            ville VARCHAR(100),
            pays VARCHAR(100),
            email VARCHAR(100),
            telephone VARCHAR(20)
        )$charset_collate;";

        // Inclure le fichier qui contient la fonction dbDelta()
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Créer ou mettre à jour la table
        dbDelta($sql);
    }

    /*static function add_fournisseur($data)
    {    
        global $wpdb;
        $alert=[];

        // Récupérer les données du formulaire
        $data = [
            'fournisseur_id' => isset($data['fournisseurId']) ? intval($data['fournisseurId']) : 0,
            'nom' => isset($data['nom']) ? sanitize_text_field($data['nom']) : '',
            'adresse' => isset($data['adresse']) ? stripslashes(sanitize_text_field($data['adresse'])) : '',
            'cp' => isset($data['cp']) ? sanitize_text_field($data['cp']) : '',
            'ville' => isset($data['ville']) ? stripslashes(sanitize_text_field($data['ville'])) : '',
            'pays' => isset($data['pays']) ? sanitize_text_field($data['pays']) : '',
            'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'telephone' => isset($data['telephone']) ? sanitize_text_field($data['telephone']) : ''
        ];
        extract($data); // Pour rendre les variables disponibles séparément

        // Vérifier si le fournisseur existe déjà
        $existing_supplier = $wpdb->get_row($wpdb->prepare( "SELECT * FROM %i WHERE email = %s OR nom = %s",FAND_FOURNISSEURS_TABLE,  $email,  $nom ));

        if ($existing_supplier) {
            // Fournisseur existe déjà
            $message = 'Le fournisseur existe déjà.';
            $message_type = 'error'; // Indicateur d'erreur

        } 
        else {
            // Insérer dans la table des fournisseurs
            $result = $wpdb->insert(FAND_FOURNISSEURS_TABLE, array(
                'nom' => $nom,
                'adresse' => $adresse,
                'cp' => $cp,
                'ville' => $ville,
                'pays' => $pays,
                'email' => $email,
                'telephone' => $telephone,
            ));

            if ($result) {
                // Appel de la fonction pour ajouter le fournisseur comme term de l'attribut fournisseur
                $term_result = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom);

                if (is_wp_error($term_result)) {
                    $message = "Fournisseur ajouté, mais erreur lors de l'ajout du term.";
                    $message_type = 'error';
                } 
                else {
                    $message = 'Fournisseur ajouté avec succès !';
                    $message_type = 'success'; // Indicateur de succès
                }
            } 
            else {
                $message = "Erreur lors de l'ajout du fournisseur.";
                $message_type = 'error'; // Indicateur d'erreur
            }
        }
        $alert=['message'=>$message,'message_type'=>$message_type];
        return $alert;
    }*/
    static function add_fournisseur($data)
    {
        global $wpdb;
        $alert = [];
        $message = '';
        $message_type = '';

        // Récupérer les données du formulaire
        $data = [
            'fournisseur_id' => isset($data['fournisseurId']) ? intval($data['fournisseurId']) : 0,
            'nom'            => isset($data['nom']) ? sanitize_text_field($data['nom']) : '',
            'adresse'        => isset($data['adresse']) ? stripslashes(sanitize_text_field($data['adresse'])) : '',
            'cp'             => isset($data['cp']) ? sanitize_text_field($data['cp']) : '',
            'ville'          => isset($data['ville']) ? stripslashes(sanitize_text_field($data['ville'])) : '',
            'pays'           => isset($data['pays']) ? sanitize_text_field($data['pays']) : '',
            'email'          => isset($data['email']) ? sanitize_email($data['email']) : '',
            'telephone'      => isset($data['telephone']) ? sanitize_text_field($data['telephone']) : '',
        ];
        extract($data);

        $table_name = FAND_COMMERCANTS_FOURNISSEURS_TABLE;
        $commercant_id = get_current_user_id();

        // Vérifier si le fournisseur existe déjà
        $existing_supplier = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . FAND_FOURNISSEURS_TABLE . " WHERE email = %s OR nom = %s",
                $email,
                $nom
            )
        );

        if ($existing_supplier) {
            // Fournisseur existe, on utilise son ID
            $fournisseur_id = $existing_supplier->id;

            // Vérifie si la relation existe déjà
            $relation = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE commercant_id = %d AND fournisseur_id = %d",
                    $commercant_id,
                    $fournisseur_id
                )
            );

            if (!$relation) {
                // Création de la relation sans modifier le fournisseur
                $wpdb->insert(
                    $table_name,
                    [
                        'commercant_id'  => $commercant_id,
                        'fournisseur_id' => $fournisseur_id,
                        'adresse'        => $adresse,
                        'cp'             => $cp,
                        'ville'          => $ville,
                        'telephone'      => $telephone,
                        'pays'           => $pays,
                    ]
                );

                // 💡 Ajouter le terme pour cet attribut
                if (FAND_MARKET_ACTIVE) {
                    $term_result = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom, $commercant_id);
                } else {
                    $term_result = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom);
                }

                if (is_wp_error($term_result)) {
                    $message = "Fournisseur associé, mais erreur lors de l'association attribut.";
                    $message_type = 'error';
                } else {
                    $message = 'Fournisseur associé à votre compte.';
                    $message_type = 'success';
                }

            } else {
                $message = 'Ce fournisseur est déjà associé à votre compte.';
                $message_type = 'error';
            }
        }
        else {
            // Nouveau fournisseur : créer dans FAND_FOURNISSEURS_TABLE
            $wpdb->insert(FAND_FOURNISSEURS_TABLE, [
                'nom'      => $nom,
                'adresse'  => $adresse,
                'cp'       => $cp,
                'ville'    => $ville,
                'pays'     => $pays,
                'email'    => $email,
                'telephone'=> $telephone,
            ]);

            $fournisseur_id = $wpdb->insert_id;

            // Créer la relation commerçant <-> fournisseur
            $wpdb->insert($table_name, [
                'commercant_id'  => $commercant_id,
                'fournisseur_id' => $fournisseur_id,
                'adresse'        => $adresse,
                'cp'             => $cp,
                'ville'          => $ville,
                'telephone'      => $telephone,
                'pays'           => $pays,
            ]);

            // Ajouter le terme
            if (FAND_MARKET_ACTIVE) {
                $term_result = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom, $commercant_id);
            } else {
                $term_result = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom);
            }

            if (is_wp_error($term_result)) {
                $message = "Fournisseur créé, mais erreur lors de l'association attribut.";
                $message_type = 'error';
            } else {
                $message = 'Fournisseur créé et associé à votre compte.';
                $message_type = 'success';
            }
        }

        $alert=['message'=>$message,'message_type'=>$message_type];
        return $alert;
    }

    static public function update_fournisseur($data){
        global $wpdb;
        $alert=[];
        error_log(print_r($data, true));

        // Récupérer les données du formulaire
        $data= [
            'fournisseur_id' => isset($data['fournisseur_id']) ? intval($data['fournisseur_id']) : 0,
            'nom' => isset($data['nom']) ? sanitize_text_field($data['nom']) : '',
            'adresse' => isset($data['adresse']) ? stripslashes(sanitize_text_field($data['adresse'])) : '',
            'cp' => isset($data['cp']) ? sanitize_text_field($data['cp']) : '',
            'ville' => isset($data['ville']) ? stripslashes(sanitize_text_field($data['ville'])) : '',
            'pays' => isset($data['pays']) ? sanitize_text_field($data['pays']) : '',
            'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'telephone' => isset($data['telephone']) ? sanitize_text_field($data['telephone']) : ''
        ];

        extract($data); // Pour rendre les variables disponibles séparément

        // Récupérer l'ancien nom pour vérifier le changement
        $ancien_fournisseur = $wpdb->get_row($wpdb->prepare("SELECT nom FROM %i WHERE id = %d",FAND_FOURNISSEURS_TABLE,$fournisseur_id));
        $ancien_nom = '';
        if(isset($ancien_fournisseur->nom)){
        $ancien_nom = $ancien_fournisseur->nom;
        }

        error_log('fournisseur_id : '.$fournisseur_id);
        //error_log($ancien_nom);
        // Si MarketPlace actif
        if (FAND_MARKET_ACTIVE) {
            // Mettre à jour les infos globales (nom et email) dans la table fournisseurs
            $result = $wpdb->update(FAND_FOURNISSEURS_TABLE, [
                'nom'  => $nom,
                'email' => $email,
            ], ['id' => $fournisseur_id]);

            //recupere l'id commercant
            $commercant_id = get_current_user_id();
            // Puis mettre à jour ou insérer la liaison commerçant / fournisseur via REPLACE
            $result_liaison = $wpdb->update(FAND_COMMERCANTS_FOURNISSEURS_TABLE, [
                'adresse'        => $adresse,
                'cp'             => $cp,
                'ville'          => $ville,
                'pays'           => $pays,
                'telephone'      => $telephone,
            ],
            ['fournisseur_id' => $fournisseur_id,'commercant_id'  => $commercant_id,]);

        } 
        else {
            // Mettre à jour le fournisseur
            $result = $wpdb->update(FAND_FOURNISSEURS_TABLE, array(
                'nom' => $nom,
                'adresse' => $adresse,
                'cp' => $cp,
                'ville' => $ville,
                'pays' => $pays,
                'email' => $email,
                'telephone' => $telephone,
            ), array('id' => $fournisseur_id));
        }

        if (isset($result) && $result !== false) {
            $message = 'Fournisseur mis à jour avec succès !';
            $message_type = 'success';
            // Si le nom a changé, mettre à jour le term associé
            if ($ancien_nom !== $nom) {
                // Récupérer l'ID du term associé au fournisseur
                $term = get_term_by('name', $ancien_nom, FAND_FOURNISSEURS_ATTRIBUT); 

                if ($term) {
                    // Appeler la méthode statique de AttributManager pour mettre à jour le term
                    $result = FAND_Attribut::update_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $ancien_nom, [
                        'name' => $nom,
                        'slug' => sanitize_title($nom),
                    ]);
                }
            }
        } else {
            $message = 'Erreur lors de la mise à jour du fournisseur.';
            $message_type = 'error';
        }

        $alert=['message'=>$message,'message_type'=>$message_type];
        return $alert;
    }
    /*static public function update_fournisseur($data)
    {
        global $wpdb;
        $alert=[];

        // Récupérer les données du formulaire
        $data= [
            'fournisseur_id' => isset($data['id']) ? intval($data['id']) : 0,
            'nom' => isset($data['nom']) ? sanitize_text_field($data['nom']) : '',
            'adresse' => isset($data['adresse']) ? stripslashes(sanitize_text_field($data['adresse'])) : '',
            'cp' => isset($data['cp']) ? sanitize_text_field($data['cp']) : '',
            'ville' => isset($data['ville']) ? stripslashes(sanitize_text_field($data['ville'])) : '',
            'pays' => isset($data['pays']) ? sanitize_text_field($data['pays']) : '',
            'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'telephone' => isset($data['telephone']) ? sanitize_text_field($data['telephone']) : ''
        ];

        extract($data); // Pour rendre les variables disponibles séparément

        // Récupérer l'ancien nom pour vérifier le changement
        $ancien_fournisseur = $wpdb->get_row($wpdb->prepare("SELECT nom FROM %i WHERE id = %d",FAND_FOURNISSEURS_TABLE,$fournisseur_id));
        $ancien_nom = '';
        if(isset($ancien_fournisseur->nom)){
        $ancien_nom = $ancien_fournisseur->nom;
        }
        //error_log($ancien_nom);
        // Mettre à jour le fournisseur
        $result = $wpdb->update(FAND_FOURNISSEURS_TABLE, array(
            'nom' => $nom,
            'adresse' => $adresse,
            'cp' => $cp,
            'ville' => $ville,
            'pays' => $pays,
            'email' => $email,
            'telephone' => $telephone,
        ), array('id' => $fournisseur_id));

        if ($result !== false) {
            $message = 'Fournisseur mis à jour avec succès !';
            $message_type = 'success';
            // Si le nom a changé, mettre à jour le term associé
            if ($ancien_nom !== $nom) {
                // Récupérer l'ID du term associé au fournisseur
                $term = get_term_by('name', $ancien_nom, FAND_FOURNISSEURS_ATTRIBUT); 

                if ($term) {
                    // Appeler la méthode statique de AttributManager pour mettre à jour le term
                    $result = FAND_Attribut::update_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $ancien_nom, [
                        'name' => $nom,
                        'slug' => sanitize_title($nom),
                    ]);
                }
            }
        } else {
            $message = 'Erreur lors de la mise à jour du fournisseur.';
            $message_type = 'error';
        }

        $alert=['message'=>$message,'message_type'=>$message_type];
        return $alert;
    }*/

    static function get_all_fournisseurs() {
        global $wpdb;

        $table_relations    = FAND_COMMERCANTS_FOURNISSEURS_TABLE;
        $table_fournisseurs = FAND_FOURNISSEURS_TABLE;

        // Vérif marketplace
        if (!defined('FAND_MARKET_ACTIVE') || !FAND_MARKET_ACTIVE) {
            error_log("[FAND] Marketplace INACTIVE → récupération de TOUS les fournisseurs");
            $results = $wpdb->get_results("SELECT * FROM $table_fournisseurs");
            error_log("[FAND] Fournisseurs récupérés (count=" . count($results) . ")");
            return $results;
        }

        // Marketplace active
        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);
        error_log("[FAND] Marketplace ACTIVE → user_id=$user_id, roles=" . implode(',', (array)$user->roles));

        // User est vendeur → récupérer uniquement SES fournisseurs
        $query = $wpdb->prepare("
            SELECT f.*, 
                r.adresse   AS adresse,
                r.cp        AS cp,
                r.ville     AS ville,
                r.pays      AS pays,
                r.telephone AS telephone,
                r.note_personnelle
            FROM $table_fournisseurs f
            INNER JOIN $table_relations r 
                ON f.id = r.fournisseur_id
            WHERE r.commercant_id = %d
        ", $user_id);

        error_log("[FAND] Requête SQL (vendeur) : $query");

        $results = $wpdb->get_results($query);
        error_log("[FAND] Fournisseurs trouvés pour vendeur $user_id (count=" . count($results) . ")");
        return $results;

    }

    /*static function get_all_fournisseurs(){
        global $wpdb;
        $fournisseurs = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", FAND_FOURNISSEURS_TABLE));
        return $fournisseurs;
    }*/

    /*static function delete_fournisseur($POST)
    {
        global $wpdb;
        $alert=[];

        // Suppression d'un fournisseur
        $fournisseur_id = intval($POST['fournisseur_id']); // Récupérer l'ID du fournisseur à supprimer

        // Récupérer le fournisseur pour obtenir le nom du term
        $fournisseur = $wpdb->get_row($wpdb->prepare("SELECT nom FROM %i WHERE id = %d",FAND_FOURNISSEURS_TABLE, $fournisseur_id));

        if ($fournisseur) {
            // Supprimer le term associé
            $term_name = $fournisseur->nom; // Nom du term à supprimer

            // Appeler la fonction pour supprimer le term
            $result = FAND_Attribut::delete_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $term_name);
            if (is_wp_error($result)) {
                $message = 'Erreur lors de la suppression du term : ' . $result->get_error_message();
                $message_type = 'error'; // Indicateur d'erreur
            } 
            else {
                $message = 'Term supprimé avec succès.';
                $message_type = 'success'; // Indicateur de succès
            }
        }

        // Supprimer le fournisseur de la base de données
        $deleted = $wpdb->delete(FAND_FOURNISSEURS_TABLE, array('id' => $fournisseur_id));

        if ($deleted) {
            $message = 'Fournisseur supprimé avec succès.';
            $message_type = 'success'; // Indicateur de succès
        } 
        else {
            $message = 'Erreur lors de la suppression du fournisseur.';
            $message_type = 'error'; // Indicateur d'erreur
        }

        $alert=['message'=>$message,'message_type'=>$message_type];
        return $alert;
    }*/
    static function delete_fournisseur($POST){
        global $wpdb;
        $alert = [];
        $message = '';
        $message_type = '';

        $fournisseur = $POST['fournisseur'];

        $fournisseur_id = FAND_MARKET_ACTIVE
            ? (isset($fournisseur['fournisseur_id']) ? intval($fournisseur['fournisseur_id']) : 0)
            : (isset($fournisseur['id']) ? intval($fournisseur['id']) : 0);

        if (FAND_MARKET_ACTIVE) {
            error_log('FAND_MARKET_ACTIVE');

            // Récupérer l'ID du commerçant connecté
            $commercant_id = get_current_user_id();

            // Supprimer uniquement la relation commerçant-fournisseur
            $deleted = $wpdb->delete(
                FAND_COMMERCANTS_FOURNISSEURS_TABLE,
                [
                    'commercant_id' => $commercant_id,
                    'fournisseur_id' => $fournisseur_id
                ]
            );
        } else {
            // Supprimer complètement le fournisseur s’il n’y a pas de multi-vendeur
            $deleted = $wpdb->delete(
                FAND_FOURNISSEURS_TABLE,
                ['id' => $fournisseur_id]
            );
        }

        if ($deleted) {
            $message = 'Fournisseur supprimé avec succès.';
            $message_type = 'success';
        } else {
            $message = 'Erreur lors de la suppression du fournisseur.';
            $message_type = 'error';
        }

        $alert=['message'=>$message,'message_type'=>$message_type];
        return $alert;
}

}