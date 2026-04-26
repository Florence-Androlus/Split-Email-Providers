<?php

namespace fand\Classes\Database;

use fand\Classes\FAND_Attribut;

class Database {

    static public function init() {

        // Ici on crée les tables SQL nécessaires à notre plugin
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        // Table 1 : Identité globale
        $sql1 = "CREATE TABLE " . FAND_FOURNISSEURS_TABLE . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            email VARCHAR(100)
        ) $charset_collate;";
        dbDelta($sql1);

        // Table 2 : Données spécifiques (Adresses/Tel) - CRITIQUE pour ton GET_ALL
        $sql2 = "CREATE TABLE " . FAND_COMMERCANTS_FOURNISSEURS_TABLE . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            commercant_id INT NOT NULL,
            fournisseur_id INT NOT NULL,
            adresse VARCHAR(255),
            cp VARCHAR(10),
            ville VARCHAR(100),
            pays VARCHAR(100),
            telephone VARCHAR(20),
            note_personnelle TEXT
        ) $charset_collate;";
        dbDelta($sql2);

        // Table 3 : Données spécifiques (Adresses/Tel) - CRITIQUE pour ton GET_ALL
        $sql3 = "CREATE TABLE " . FAND_COMMERCANTS_TERMS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            commercant_id BIGINT(20) UNSIGNED NOT NULL,
            term_id BIGINT(20) UNSIGNED NOT NULL,
            date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_link (commercant_id, term_id),
            KEY commercant_idx (commercant_id),
            KEY term_idx (term_id)
        ) $charset_collate;";
        dbDelta($sql3);

        // Sécurité : Nettoyage des métadonnées de la table 3 si un terme est supprimé
       // add_action('pre_delete_term', [__CLASS__, 'clean_term_metadata_on_delete'], 10, 2);
    }

    // Appelé à CHAQUE requête via plugins_loaded
    static public function register_hooks() {
        add_action('delete_term', [__CLASS__, 'clean_term_metadata_on_delete'], 5, 3);
    }

    /**
     * Nettoie la table de liaison quand un terme est supprimé manuellement dans WP
     */
    static public function clean_term_metadata_on_delete($term_id, $tt_id, $taxonomy) {
        
        global $wpdb;

        // On ne s'occupe que de notre taxonomie
        if ($taxonomy !== FAND_FOURNISSEURS_ATTRIBUT) {
            return;
        }

        // Supprimer la liaison dans ta table 3
        $wpdb->delete(FAND_COMMERCANTS_TERMS, [
            'term_id' => $term_id
        ]);
        
        // Note : On ne touche pas à la Table 1 (Fournisseurs) ici, 
        // car on veut garder les coordonnées SQL même si l'attribut produit est supprimé.
    }

    static function add_fournisseur($data) {
        global $wpdb;
        $user_id = get_current_user_id() ?: 1;
        $email = sanitize_email($data['email'] ?? '');
        $nom   = sanitize_text_field($data['nom'] ?? '');

        if (empty($nom)) return ['message' => 'Nom vide', 'message_type' => 'error'];

        // 1. Table 1 : Identité
        $f_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . FAND_FOURNISSEURS_TABLE . " WHERE email = %s OR nom = %s",
            $email, $nom
        ));

        if (!$f_id) {
            $wpdb->insert(FAND_FOURNISSEURS_TABLE, ['nom' => $nom, 'email' => $email]);
            $f_id = $wpdb->insert_id;
        }

        // 2. Table 2 : Coordonnées (On utilise l'ID fournisseur pour lier)
        $wpdb->replace(FAND_COMMERCANTS_FOURNISSEURS_TABLE, [
            'commercant_id'  => $user_id,
            'fournisseur_id' => $f_id,
            'adresse'        => sanitize_text_field($data['adresse'] ?? ''),
            'cp'             => sanitize_text_field($data['cp'] ?? ''),
            'ville'          => sanitize_text_field($data['ville'] ?? ''),
            'pays'           => sanitize_text_field($data['pays'] ?? ''),
            'telephone'      => sanitize_text_field($data['telephone'] ?? ''),
            'note_personnelle' => sanitize_textarea_field($data['note_personnelle'] ?? '')
        ]);

        // 3. Table 3 : Liaison Terms (C'est ici qu'on sécurise)
        // On crée/récupère le term_id via ta classe FAND_Attribut
        $term_id = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom, $user_id);

        return [
            'message' => __('Provider added successfully!', 'split-email-providers'), 
            'message_type' => 'success'
        ];
    }

    /*static function add_fournisseur($data) {
        global $wpdb;
        $user_id = get_current_user_id() ? get_current_user_id() : 1; // Fallback admin

        $email = sanitize_email($data['email'] ?? '');
        $nom   = sanitize_text_field($data['nom'] ?? '');

        // 1. Est-ce que le fournisseur existe globalement ?
        $f_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . FAND_FOURNISSEURS_TABLE . " WHERE email = %s OR nom = %s",
            $email, $nom
        ));

        if (!$f_id) {
            $wpdb->insert(FAND_FOURNISSEURS_TABLE, ['nom' => $nom, 'email' => $email]);
            $f_id = $wpdb->insert_id;
        }

        // 2. Créer ou Mettre à jour la liaison (Adresses)
        // C'est CA qui permet à get_all_fournisseurs de fonctionner après
        $exists_link = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . FAND_COMMERCANTS_FOURNISSEURS_TABLE . " WHERE commercant_id = %d AND fournisseur_id = %d",
            $user_id, $f_id
        ));

        $link_data = [
            'commercant_id'  => $user_id,
            'fournisseur_id' => $f_id,
            'adresse'        => sanitize_text_field($data['adresse'] ?? ''),
            'cp'             => sanitize_text_field($data['cp'] ?? ''),
            'ville'          => sanitize_text_field($data['ville'] ?? ''),
            'pays'           => sanitize_text_field($data['pays'] ?? ''),
            'telephone'      => sanitize_text_field($data['telephone'] ?? ''),
        ];

        if ($exists_link) {
            $wpdb->update(FAND_COMMERCANTS_FOURNISSEURS_TABLE, $link_data, ['id' => $exists_link]);
            $message = __('Link updated.', 'split-email-providers');
        } else {
            $wpdb->insert(FAND_COMMERCANTS_FOURNISSEURS_TABLE, $link_data);
            $message = __('Supplier added and linked.', 'split-email-providers');
        }

        // 3. Gestion des Attributs (Terms)
        FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom, (FAND_MARKET_ACTIVE ? $user_id : null));

        return ['message' => $message, 'message_type' => 'success'];
    }

    /*static public function update_fournisseur($data){
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
        if ($result !== false) {
            $message = __('Provider updated successfully!', 'split-email-providers');
            $message_type = 'success';

            // Logique de mise à jour du terme (Taxonomie)
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
            // Ici $result est strictement FALSE (erreur de requête SQL)
            $message = __('Error updating provider.', 'split-email-providers');
            $message_type = 'error'; // Pas besoin de __() ici, c'est une clé technique pour ton CSS
        }

        return ['message' => $message, 'message_type' => $message_type];
    }*/

    static public function update_fournisseur($data) {
        global $wpdb;

        $fournisseur_id = isset($data['id']) ? intval($data['id']) : 0;
        $commercant_id = get_current_user_id() ?: 1;
        $nom_nouveau = sanitize_text_field($data['nom'] ?? '');

        if ($fournisseur_id === 0 || empty($nom_nouveau)) {
            return ['message' => __('Invalid data.', 'split-email-providers'), 'message_type' => 'error'];
        }

        // 1. On récupère les infos actuelles en base
        $fournisseur_actuel = $wpdb->get_row($wpdb->prepare(
            "SELECT nom FROM " . FAND_FOURNISSEURS_TABLE . " WHERE id = %d",
            $fournisseur_id
        ));

        // 2. Mise à jour des tables SQL (Identité et Coordonnées)
        $wpdb->update(FAND_FOURNISSEURS_TABLE, ['nom' => $nom_nouveau, 'email' => sanitize_email($data['email'] ?? '')], ['id' => $fournisseur_id]);
        
        $wpdb->update(
            FAND_COMMERCANTS_FOURNISSEURS_TABLE,
            [
                'adresse'   => sanitize_text_field($data['adresse'] ?? ''),
                'cp'        => sanitize_text_field($data['cp'] ?? ''),
                'ville'     => sanitize_text_field($data['ville'] ?? ''),
                'pays'      => sanitize_text_field($data['pays'] ?? ''),
                'telephone' => sanitize_text_field($data['telephone'] ?? ''),
                'note_personnelle' => sanitize_textarea_field($data['note_personnelle'] ?? '')
            ],
            ['commercant_id' => $commercant_id, 'fournisseur_id' => $fournisseur_id]
        );

        // 3. VÉRIFICATION ET RÉPARATION DU TERM (Attribut WC)
        // On cherche d'abord le terme avec le nouveau nom
        $term = term_exists($nom_nouveau, FAND_FOURNISSEURS_ATTRIBUT);

        if (!$term && $fournisseur_actuel) {
            // Si le terme n'existe pas sous le nouveau nom, on cherche l'ancien (cas d'un renommage)
            $term = term_exists($fournisseur_actuel->nom, FAND_FOURNISSEURS_ATTRIBUT);
        }

        if ($term) {
            // Le terme existe : on le met à jour (nom + slug) au cas où
            $term_id = is_array($term) ? $term['term_id'] : $term;
            wp_update_term($term_id, FAND_FOURNISSEURS_ATTRIBUT, [
                'name' => $nom_nouveau,
                'slug' => sanitize_title($nom_nouveau)
            ]);
        } else {
            // LE TERME A ÉTÉ SUPPRIMÉ : on le recrée de zéro
            // Ta fonction add_term_attribut va créer le terme et remplir FAND_COMMERCANTS_TERMS
            $term_id = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom_nouveau, $commercant_id);
        }

        // 4. Sécurité finale pour la table de liaison (Table 3)
        // Si le term_id n'a pas été traité par add_term_attribut au dessus
        if (isset($term_id) && $term_id) {
            $wpdb->replace(
                FAND_COMMERCANTS_TERMS,
                [
                    'commercant_id' => $commercant_id,
                    'term_id'       => (int)$term_id,
                    'date_created'  => current_time('mysql')
                ]
            );
        }

        return [
            'message' => __('Provider updated successfully!', 'split-email-providers'),
            'message_type' => 'success'
        ];
    }

    /*static function get_all_fournisseurs() {
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

    }*/

    static function get_all_fournisseurs() {
        global $wpdb;

        $table_f = FAND_FOURNISSEURS_TABLE;
        $table_rel = FAND_COMMERCANTS_FOURNISSEURS_TABLE;
        $user_id = get_current_user_id();

        // On ne fait plus de distinction Free/Market pour la requête de base
        // car tes données d'adresses sont dépendantes du commercant_id quoi qu'il arrive.
        
        // INNER JOIN : On ne récupère que les fournisseurs qui ont une liaison
        // avec le commerçant actuel (WHERE r.commercant_id = %d)
        $query = $wpdb->prepare("
            SELECT f.id, f.nom, f.email,
                r.adresse, r.cp, r.ville, r.pays, r.telephone, r.note_personnelle
            FROM $table_f f
            INNER JOIN $table_rel r ON f.id = r.fournisseur_id
            WHERE r.commercant_id = %d
        ", $user_id);

        return $wpdb->get_results($query);
    }

    static function delete_fournisseur($POST) {
        global $wpdb;

        // 1. Récupération de l'ID
        $fournisseur_id = 0;
        if (isset($POST['fournisseur_id'])) {
            $fournisseur_id = intval($POST['fournisseur_id']);
        } elseif (isset($POST['fournisseur']['id'])) {
            $fournisseur_id = intval($POST['fournisseur']['id']);
        }

        if (!$fournisseur_id) {
            return [
                'message' => __('Invalid provider ID.', 'split-email-providers'), 
                'message_type' => 'error'
            ];
        }

        $commercant_id = get_current_user_id() ?: 1;

        // 2. Récupérer le nom pour trouver le Term AVANT suppression
        $fournisseur_nom = $wpdb->get_var($wpdb->prepare(
            "SELECT nom FROM " . FAND_FOURNISSEURS_TABLE . " WHERE id = %d", 
            $fournisseur_id
        ));

        // 3. Supprimer les coordonnées spécifiques du commerçant (Table 2)
        $wpdb->delete(FAND_COMMERCANTS_FOURNISSEURS_TABLE, [
            'commercant_id'  => $commercant_id,
            'fournisseur_id' => $fournisseur_id
        ]);

        // 4. Gérer le Term et la liaison (Table 3)
        if ($fournisseur_nom) {
            $term = get_term_by('name', $fournisseur_nom, FAND_FOURNISSEURS_ATTRIBUT);
            
            if ($term) {
                // Supprimer la liaison Commerçant <-> Term (Table 3)
                $wpdb->delete(FAND_COMMERCANTS_TERMS, [
                    'commercant_id' => $commercant_id,
                    'term_id'       => $term->term_id
                ]);

                // 5. NETTOYAGE GLOBAL : Si plus aucun commerçant n'utilise ce fournisseur
                $still_used = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM " . FAND_COMMERCANTS_FOURNISSEURS_TABLE . " WHERE fournisseur_id = %d",
                    $fournisseur_id
                ));

                if (intval($still_used) === 0) {
                    // A. Supprimer le fournisseur de la table globale (Table 1)
                    $wpdb->delete(FAND_FOURNISSEURS_TABLE, ['id' => $fournisseur_id]);
                    
                    // B. Supprimer le terme de la taxonomie WooCommerce (pa_fournisseur)
                    // Cela retire le terme de la liste des attributs produits
                    wp_delete_term($term->term_id, FAND_FOURNISSEURS_ATTRIBUT);
                    
                    // C. Optionnel : Nettoyer le cache des transiants de WooCommerce 
                    // pour forcer la mise à jour de la liste des attributs en admin
                    delete_transient('wc_attribute_taxonomies');
                }
            }
        }

        return [
            'message' => __('Provider deleted successfully.', 'split-email-providers'),
            'message_type' => 'success'
        ];
    }

    /*static function delete_fournisseur($POST){
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
            return ['message' => __('Invalid provider.', 'split-email-providers'), 'message_type' => __('error', 'split-email-providers')];
        }

        //error_log("Tentative suppression fournisseur ID: $fournisseur_id");

        if (!$fournisseur_id) {
            return ['message' => __('Invalid provider.', 'split-email-providers'), 'message_type' => __('error', 'split-email-providers')];
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

            
            return [__('Provider deleted successfully.', 'split-email-providers'),__('success', 'split-email-providers')];

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
                    $message = __('Error deleting term: ', 'split-email-providers') . $result->get_error_message();
                    $message_type = __('error', 'split-email-providers'); // Indicateur d'erreur
                } 
                else {
                    $message = __('Term deleted successfully.', 'split-email-providers');
                    $message_type = __('success', 'split-email-providers'); // Indicateur de succès
                }
            }

            // Supprimer le fournisseur de la base de données
            $deleted = $wpdb->delete(FAND_FOURNISSEURS_TABLE, array('id' => $fournisseur_id));

            if ($deleted) {
                $message = __('Provider deleted successfully.', 'split-email-providers');
                $message_type = __('success', 'split-email-providers'); // Indicateur de succès
            } 
            else {
                $message = __('Error deleting provider.', 'split-email-providers');
                $message_type = __('error', 'split-email-providers'); // Indicateur d'erreur
            }

            return ['message'=>$message,'message_type'=>$message_type];
        }
 
    }*/

}