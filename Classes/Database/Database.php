<?php

namespace fand\Classes\Database;
use fand\Classes\FAND_Attribut;

if ( ! defined( 'ABSPATH' ) ) exit;

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
            email VARCHAR(100),
            UNIQUE KEY unique_email (email)
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
            note_personnelle TEXT,
            UNIQUE KEY unique_link (commercant_id, fournisseur_id)
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
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        // Supprimer la liaison dans ta table 3
        $wpdb->delete(FAND_COMMERCANTS_TERMS, [
            'term_id' => $term_id
        ]);
        
        // Note : On ne touche pas à la Table 1 (Fournisseurs) ici, 
        // car on veut garder les coordonnées SQL même si l'attribut produit est supprimé.
    }

    /* ok en free mais pas en market
    static function add_fournisseur($data) {
        global $wpdb;
        $user_id = get_current_user_id() ?: 1;
        $email = sanitize_email($data['email'] ?? '');
        $nom   = sanitize_text_field($data['nom'] ?? '');

        if (empty($nom)) return ['message' => 'Nom vide', 'message_type' => 'error'];

        // 1. Vérifier si l'email existe déjà
        $f_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . FAND_FOURNISSEURS_TABLE . " WHERE email = %s",
            $email
        ));

        if ($f_id) {
            return [
                'message'      => __('A provider with this email already exists.', 'split-email-providers'),
                'message_type' => 'error'
            ];
        }

        // 2. Créer le fournisseur
        $wpdb->insert(FAND_FOURNISSEURS_TABLE, ['nom' => $nom, 'email' => $email]);
        $f_id = $wpdb->insert_id;

        // 3. Table 2 : Coordonnées
        $wpdb->insert(FAND_COMMERCANTS_FOURNISSEURS_TABLE, [
            'commercant_id'    => $user_id,
            'fournisseur_id'   => $f_id,
            'adresse'          => sanitize_text_field($data['adresse'] ?? ''),
            'cp'               => sanitize_text_field($data['cp'] ?? ''),
            'ville'            => sanitize_text_field($data['ville'] ?? ''),
            'pays'             => sanitize_text_field($data['pays'] ?? ''),
            'telephone'        => sanitize_text_field($data['telephone'] ?? ''),
            'note_personnelle' => sanitize_textarea_field($data['note_personnelle'] ?? '')
        ]);

        // 4. Table 3 : Liaison Terms
        $term_slug = 'fournisseur-' . $f_id;
        $term_id   = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom, $user_id, $term_slug);

        return [
            'message'      => __('Provider added successfully!', 'split-email-providers'),
            'message_type' => 'success'
        ];
    }*/
    static function add_fournisseur($data) {
        global $wpdb;
        $user_id = get_current_user_id() ?: 1;
        $email = sanitize_email($data['email'] ?? '');
        $nom   = sanitize_text_field($data['nom'] ?? '');

        if (empty($nom)) return ['message' => 'Nom vide', 'message_type' => 'error'];

        // 1. Vérifier si l'email existe globalement
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $f_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM %i WHERE email = %s",
            FAND_FOURNISSEURS_TABLE,
            $email
        ));

        if ($f_id) {
            // Email existe — vérifier si ce commerçant l'a déjà
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $already_linked = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM %i WHERE commercant_id = %d AND fournisseur_id = %d",
                FAND_COMMERCANTS_FOURNISSEURS_TABLE,
                $user_id, $f_id
            ));

            if ($already_linked) {
                return [
                    'message'      => __('A provider with this email already exists.', 'split-email-providers'),
                    'message_type' => 'error'
                ];
            }
            // Fournisseur global existe mais pas lié à ce commerçant → on lie
        } else {
            // N'existe pas globalement → on crée
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert(FAND_FOURNISSEURS_TABLE, ['nom' => $nom, 'email' => $email]);
            $f_id = $wpdb->insert_id;
        }

        // 2. Table 2 : Coordonnées spécifiques à ce commerçant
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(FAND_COMMERCANTS_FOURNISSEURS_TABLE, [
            'commercant_id'    => $user_id,
            'fournisseur_id'   => $f_id,
            'adresse'          => sanitize_text_field($data['adresse'] ?? ''),
            'cp'               => sanitize_text_field($data['cp'] ?? ''),
            'ville'            => sanitize_text_field($data['ville'] ?? ''),
            'pays'             => sanitize_text_field($data['pays'] ?? ''),
            'telephone'        => sanitize_text_field($data['telephone'] ?? ''),
            'note_personnelle' => sanitize_textarea_field($data['note_personnelle'] ?? '')
        ]);

        // 3. Table 3 : Liaison Terms
        $term_slug = 'fournisseur-' . $f_id;
        $term_id   = FAND_Attribut::add_term_attribut(FAND_FOURNISSEURS_ATTRIBUT, $nom, $user_id, $term_slug);
        
        wp_cache_delete('fand_fournisseurs_' . $user_id, 'fand');
        return [
            'message'      => __('Provider added successfully!', 'split-email-providers'),
            'message_type' => 'success'
        ];
    }
    
    static public function update_fournisseur($data) {
        global $wpdb;

        $fournisseur_id = isset($data['id']) ? intval($data['id']) : 0;
        $commercant_id  = get_current_user_id() ?: 1;
        $nom_nouveau    = sanitize_text_field($data['nom'] ?? '');

        if ($fournisseur_id === 0 || empty($nom_nouveau)) {
            return ['message' => __('Invalid data.', 'split-email-providers'), 'message_type' => 'error'];
        }

        // 1. Mise à jour des tables SQL (Identité et Coordonnées)
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            FAND_FOURNISSEURS_TABLE,
            ['nom' => $nom_nouveau, 'email' => sanitize_email($data['email'] ?? '')],
            ['id' => $fournisseur_id]
        );
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            FAND_COMMERCANTS_FOURNISSEURS_TABLE,
            [
                'adresse'          => sanitize_text_field($data['adresse'] ?? ''),
                'cp'               => sanitize_text_field($data['cp'] ?? ''),
                'ville'            => sanitize_text_field($data['ville'] ?? ''),
                'pays'             => sanitize_text_field($data['pays'] ?? ''),
                'telephone'        => sanitize_text_field($data['telephone'] ?? ''),
                'note_personnelle' => sanitize_textarea_field($data['note_personnelle'] ?? '')
            ],
            ['commercant_id' => $commercant_id, 'fournisseur_id' => $fournisseur_id]
        );

        // 2. Slug unique basé sur l'ID SQL (évite les conflits de casse ou noms similaires)
        $term_slug = 'fournisseur-' . $fournisseur_id;

        // 3. Chercher le terme par slug (fiable) puis mettre à jour ou recréer
        $term_obj = get_term_by('slug', $term_slug, FAND_FOURNISSEURS_ATTRIBUT);

        if ($term_obj) {
            // Le terme existe : on met à jour le nom affiché mais on garde le slug fixe
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            wp_update_term($term_obj->term_id, FAND_FOURNISSEURS_ATTRIBUT, [
                'name' => $nom_nouveau,
                'slug' => $term_slug
            ]);
            $term_id = $term_obj->term_id;
        } else {
            // Terme introuvable par slug : on le recrée
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $term_id = FAND_Attribut::add_term_attribut(
                FAND_FOURNISSEURS_ATTRIBUT,
                $nom_nouveau,
                $commercant_id,
                $term_slug
            );
        }

        // 4. Sécurité finale : s'assurer que la liaison Table 3 est à jour
        if (!empty($term_id)) {
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
            'message'      => __('Provider updated successfully!', 'split-email-providers'),
            'message_type' => 'success'
        ];
    }
    
    static function get_all_fournisseurs() {
        global $wpdb;
        $user_id   = get_current_user_id();
        $cache_key = 'fand_fournisseurs_' . $user_id;
        $cached    = wp_cache_get($cache_key, 'fand');

        if ($cached !== false) {
            return $cached;
        }
        
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT f.id, f.nom, f.email,
                r.adresse, r.cp, r.ville, r.pays, r.telephone, r.note_personnelle
            FROM %i f
            INNER JOIN %i r ON f.id = r.fournisseur_id
            WHERE r.commercant_id = %d
        ", FAND_FOURNISSEURS_TABLE, FAND_COMMERCANTS_FOURNISSEURS_TABLE, $user_id));

        wp_cache_set($cache_key, $results, 'fand', 300);
        return $results;
    }

    static function delete_fournisseur($POST) {
        global $wpdb;
        $user_id   = get_current_user_id();
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
            "SELECT nom FROM %i WHERE id = %d",
            FAND_FOURNISSEURS_TABLE,
            $fournisseur_id
        ));

        // 3. Supprimer les coordonnées spécifiques du commerçant (Table 2)
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(FAND_COMMERCANTS_FOURNISSEURS_TABLE, [
            'commercant_id'  => $commercant_id,
            'fournisseur_id' => $fournisseur_id
        ]);

        // 4. Gérer le Term et la liaison (Table 3)
        if ($fournisseur_nom) {
            $term = get_term_by('name', $fournisseur_nom, FAND_FOURNISSEURS_ATTRIBUT);
            
            if ($term) {
                // Supprimer la liaison Commerçant <-> Term (Table 3)
                // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->delete(FAND_COMMERCANTS_TERMS, [
                    'commercant_id' => $commercant_id,
                    'term_id'       => $term->term_id
                ]);

                // 5. NETTOYAGE GLOBAL : Si plus aucun commerçant n'utilise ce fournisseur
                // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $still_used = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM %i WHERE fournisseur_id = %d",
                    FAND_COMMERCANTS_FOURNISSEURS_TABLE,
                    $fournisseur_id
                ));

                if (intval($still_used) === 0) {
                    // A. Supprimer le fournisseur de la table globale (Table 1)
                    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->delete(FAND_FOURNISSEURS_TABLE, ['id' => $fournisseur_id]);
                    
                    // B. Supprimer le terme de la taxonomie WooCommerce (pa_fournisseur)
                    // Cela retire le terme de la liste des attributs produits
                    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    wp_delete_term($term->term_id, FAND_FOURNISSEURS_ATTRIBUT);
                    
                    // C. Optionnel : Nettoyer le cache des transiants de WooCommerce 
                    // pour forcer la mise à jour de la liste des attributs en admin
                    delete_transient('wc_attribute_taxonomies');
                }
            }
        }

        wp_cache_delete('fand_fournisseurs_' . $user_id, 'fand');
        return [
            'message' => __('Provider deleted successfully.', 'split-email-providers'),
            'message_type' => 'success'
        ];
    }
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}