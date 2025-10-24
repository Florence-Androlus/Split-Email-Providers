<?php

namespace fand\Classes\Database;

use fand\Classes\FAND_Attribut;
use fandmarket\Classes\marketutils;
use const FAND_COMMERCANTS_FOURNISSEURS_TABLE;

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

            if (FAND_MARKET_ACTIVE) {
                marketutils::add_relation_commercant_fournisseur($fournisseur_id, $commercant_id, $data);

                $term_result = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom, $commercant_id);

                if (is_wp_error($term_result)) {
                    $message = "Fournisseur associé, mais erreur lors de l'association attribut.";
                    $message_type = 'error';
                } else {
                    $message = 'Fournisseur associé à votre compte.';
                    $message_type = 'success';
                }
            
            }
            else {
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

            if (FAND_MARKET_ACTIVE) {
                marketutils::add_relation_commercant_fournisseur($fournisseur_id, $commercant_id, $data);

                // Ajouter le terme
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

        $data = [
            'fournisseur_id' => isset($data['id']) ? intval($data['id']) : 0,
            'nom'            => isset($data['nom']) ? sanitize_text_field($data['nom']) : '',
            'adresse'        => isset($data['adresse']) ? stripslashes(sanitize_text_field($data['adresse'])) : '',
            'cp'             => isset($data['cp']) ? sanitize_text_field($data['cp']) : '',
            'ville'          => isset($data['ville']) ? stripslashes(sanitize_text_field($data['ville'])) : '',
            'pays'           => isset($data['pays']) ? sanitize_text_field($data['pays']) : '',
            'email'          => isset($data['email']) ? sanitize_email($data['email']) : '',
            'telephone'      => isset($data['telephone']) ? sanitize_text_field($data['telephone']) : ''
        ];

        extract($data);

        // Ancien nom (pour update du term)
        $ancien_fournisseur = $wpdb->get_row(
            $wpdb->prepare("SELECT nom FROM " . FAND_FOURNISSEURS_TABLE . " WHERE id = %d", $fournisseur_id)
        );
        $ancien_nom = $ancien_fournisseur ? $ancien_fournisseur->nom : '';

        if (FAND_MARKET_ACTIVE) {
            // maj infos globales
            $result = $wpdb->update(
                FAND_FOURNISSEURS_TABLE,
                [
                    'nom'   => $nom,
                    'email' => $email,
                ],
                ['id' => $fournisseur_id]
            );

            $commercant_id = get_current_user_id();

            $infos = [
                'adresse'   => $adresse,
                'cp'        => $cp,
                'ville'     => $ville,
                'pays'      => $pays,
                'telephone' => $telephone,
            ];

            marketutils::enregistrer_relation_commercant_fournisseur($fournisseur_id, $commercant_id, $infos);

            // maj infos globales
            /*$result = $wpdb->update(
                FAND_FOURNISSEURS_TABLE,
                [
                    'nom'   => $nom,
                    'email' => $email,
                ],
                ['id' => $fournisseur_id]
            );

            // maj relation vendeur/fournisseur
            $commercant_id = get_current_user_id();

            // Vérifie si la liaison existe déjà
            $liaison_exist = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM " . FAND_COMMERCANTS_FOURNISSEURS_TABLE . " 
                    WHERE fournisseur_id = %d AND commercant_id = %d",
                    $fournisseur_id,
                    $commercant_id
                )
            );

            if ($liaison_exist) {
                // UPDATE
                $result_liaison = $wpdb->update(
                    FAND_COMMERCANTS_FOURNISSEURS_TABLE,
                    [
                        'adresse'   => $adresse,
                        'cp'        => $cp,
                        'ville'     => $ville,
                        'pays'      => $pays,
                        'telephone' => $telephone,
                    ],
                    [
                        'fournisseur_id' => $fournisseur_id,
                        'commercant_id'  => $commercant_id,
                    ]
                );
            } else {
                // INSERT (première fois qu’on lie ce fournisseur à ce vendeur)
                $result_liaison = $wpdb->insert(
                    FAND_COMMERCANTS_FOURNISSEURS_TABLE,
                    [
                        'fournisseur_id' => $fournisseur_id,
                        'commercant_id'  => $commercant_id,
                        'adresse'        => $adresse,
                        'cp'             => $cp,
                        'ville'          => $ville,
                        'pays'           => $pays,
                        'telephone'      => $telephone,
                    ]
                );
            }*/

        } 
        else {
            // Mode simple → mise à jour directe
            $result = $wpdb->update(
                FAND_FOURNISSEURS_TABLE,
                [
                    'nom'       => $nom,
                    'adresse'   => $adresse,
                    'cp'        => $cp,
                    'ville'     => $ville,
                    'pays'      => $pays,
                    'email'     => $email,
                    'telephone' => $telephone,
                ],
                ['id' => $fournisseur_id]
            );
        }

        // Retour messages
        if ((isset($result) && $result !== false) || (isset($result_liaison) && $result_liaison !== false)) {
            $message = 'Fournisseur mis à jour avec succès !';
            $message_type = 'success';

            if ($ancien_nom && $ancien_nom !== $nom) {
                $term = get_term_by('name', $ancien_nom, FAND_FOURNISSEURS_ATTRIBUT); 
                if ($term) {
                    FAND_Attribut::update_term_attribut(
                        FAND_FOURNISSEURS_ATTRIBUT,
                        $ancien_nom,
                        [
                            'name' => $nom,
                            'slug' => sanitize_title($nom),
                        ]
                    );
                }
            }
        } else {
            $message = 'Erreur lors de la mise à jour du fournisseur.';
            $message_type = 'error';
        }

        return ['message'=>$message,'message_type'=>$message_type];
    }

    static function get_all_fournisseurs() {
        global $wpdb;

        $table_fournisseurs = FAND_FOURNISSEURS_TABLE;

        // Marketplace inactive
        if (!defined('FAND_MARKET_ACTIVE') || !FAND_MARKET_ACTIVE) {
            $results = $wpdb->get_results("SELECT * FROM $table_fournisseurs");
            return $results;
        }

        // Marketplace active
        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);
        $table_relations    = FAND_COMMERCANTS_FOURNISSEURS_TABLE;
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

        $results = $wpdb->get_results($query);
        return $results;

    }

    static function delete_fournisseur($POST){
        global $wpdb;

        $message = '';
        $message_type = '';

        // 1. Récupération ID fournisseur
        if (isset($POST['fournisseur'])) {
            $fournisseur = $POST['fournisseur'];
            $fournisseur_id = FAND_MARKET_ACTIVE
                ? (isset($fournisseur['fournisseur_id']) ? intval($fournisseur['fournisseur_id']) : 0)
                : (isset($fournisseur['id']) ? intval($fournisseur['id']) : 0);
            //error_log('Fournisseur reçu : ' . print_r($fournisseur, true));
        } elseif (isset($POST['fournisseur_id'])) {
            $fournisseur_id = intval($POST['fournisseur_id']);
            //error_log("Fournisseur reçu directement avec fournisseur_id = $fournisseur_id");
        } else {
            //error_log('Aucun fournisseur trouvé dans POST : ' . print_r($POST, true));
            return ['message' => 'Fournisseur invalide.', 'message_type' => 'error'];
        }

        //error_log("Tentative suppression fournisseur ID: $fournisseur_id");

        if (!$fournisseur_id) {
            return ['message' => 'Fournisseur invalide.', 'message_type' => 'error'];
        }

        if (FAND_MARKET_ACTIVE) {
            $commercant_id = get_current_user_id();
            //error_log("ℹ Commerçant courant : $commercant_id");
            // Récupérer nom avant suppression
            $fournisseur_data = $wpdb->get_row(
                $wpdb->prepare("SELECT id, nom FROM " . FAND_FOURNISSEURS_TABLE . " WHERE id=%d", $fournisseur_id),
                ARRAY_A
            );
            //error_log("ℹ Données fournisseur avant suppression : " . print_r($fournisseur_data, true));
            $fournisseur_nom = $fournisseur_data['nom'];

            // Récupérer le terme global du fournisseur
            $term = get_term_by('name', $fournisseur_nom, FAND_FOURNISSEURS_ATTRIBUT);

            if ($term) {
                $term_id = intval($term->term_id);
                //error_log("ℹ Term_id à supprimer pour ce commerçant : $term_id");

                // Supprimer la relation uniquement pour ce commerçant
                $deleted_term_rel = $wpdb->delete(
                    FAND_COMMERCANTS_TERMS,
                    [
                        'commercant_id' => $commercant_id,
                        'term_id'       => $term_id
                    ]
                );
                //error_log(" Suppression relation commercant=$commercant_id ↔ term_id=$term_id : " . ($deleted_term_rel ? "OK" : "ECHEC"));
            } else {
                //error_log("Aucun terme trouvé pour '$fournisseur_nom'");
            }

            // 2. Supprimer relation commercant ↔ fournisseur
            $deleted_rel = $wpdb->delete(
                FAND_COMMERCANTS_FOURNISSEURS_TABLE,
                [
                    'commercant_id'  => $commercant_id,
                    'fournisseur_id' => $fournisseur_id
                ]
            );
            //error_log(" Suppression relation commercant-fournisseur : " . ($deleted_rel ? "OK" : "ECHEC"));

            // 4. Vérifier relations restantes
            $relations = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM " . FAND_COMMERCANTS_FOURNISSEURS_TABLE . " WHERE fournisseur_id=%d",
                    $fournisseur_id
                )
            );
            //error_log(" Nombre de relations restantes pour ce fournisseur : $relations");

            if (!$relations) {
                // Récupérer nom avant suppression
                $fournisseur_data = $wpdb->get_row(
                    $wpdb->prepare("SELECT id, nom FROM " . FAND_FOURNISSEURS_TABLE . " WHERE id=%d", $fournisseur_id),
                    ARRAY_A
                );
               // error_log(" Données fournisseur avant suppression : " . print_r($fournisseur_data, true));

                if ($fournisseur_data) {
                    $fournisseur_nom = $fournisseur_data['nom'];

                    // Supprimer fournisseur global
                    $deleted_fourn = $wpdb->delete(FAND_FOURNISSEURS_TABLE, ['id' => $fournisseur_id]);
                    //error_log("Fournisseur global supprimé : " . ($deleted_fourn ? "OK" : "ECHEC"));

                    // Supprimer toutes les relations term restantes
                    $deleted_term_all = $wpdb->delete(
                        FAND_COMMERCANTS_TERMS,
                        ['term_id' => $fournisseur_id]
                    );
                    //error_log(" Relations restantes dans FAND_COMMERCANTS_TERMS supprimées : " . ($deleted_term_all ? "OK" : "ECHEC"));

                    // Supprimer terme global
                    $term = get_term_by('name', $fournisseur_nom, FAND_FOURNISSEURS_ATTRIBUT);
                    if ($term) {
                        $result_delete_term = wp_delete_term($term->term_id, FAND_FOURNISSEURS_ATTRIBUT);
                        //error_log(" Suppression terme global : " . ($result_delete_term && !is_wp_error($result_delete_term) ? "OK" : "ECHEC"));
                    } else {
                        //error_log(" Aucun terme global trouvé pour '$fournisseur_nom'");
                    }
                }
            }

            $alert = [
                'message' => 'Fournisseur supprimé avec succès.',
                'message_type' => 'success'
            ];
            return $alert;

        } else {
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
        }
 
    }

}