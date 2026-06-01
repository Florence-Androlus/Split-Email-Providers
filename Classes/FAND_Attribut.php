<?php

namespace fand\Classes;
if ( ! defined( 'ABSPATH' ) ) exit;

class FAND_Attribut {

    // Fonction pour ajouter un nouvel attribut de produit
    static public function add_new_taxo(){
        // Vérifier que WooCommerce est chargé
        /*if (!function_exists('wc_create_attribute')) {
            return;
        }*/

        if (!taxonomy_exists(FAND_FOURNISSEURS_ATTRIBUT)) {

            // Nom de l'attribut
            $attribut = array(
                'slug' => FAND_FOURNISSEURS_ATTRIBUT,
                'name' => __('Provider', 'split-email-providers'),
                'type' => 'select', // type de champ (select, radio, etc.)
                'order_by' => 'menu_order', // tri des termes
                'has_archives' => false,
            );

            // Ajout de l'attribut
            return wc_create_attribute($attribut);

        }
        else {
            // Mettre à jour le nom si la langue a changé
            $attribute = wc_get_attribute(wc_attribute_taxonomy_id_by_name('fournisseur'));
            if ($attribute && $attribute->name !== __('Provider', 'split-email-providers')) {
                wc_update_attribute($attribute->id, [
                    'name'         => __('Provider', 'split-email-providers'),
                    'slug'         => 'fournisseur',
                    'type'         => 'select',
                    'order_by'     => 'menu_order',
                    'has_archives' => false,
                ]);
            }
        }
    }

    // Fonction pour ajouter un terme à un attribut
    static function add_term_attribut($taxonomy, $term, $commercant_id = null, $term_slug = null) {
        
        global $wpdb;

        // Chercher par slug si fourni (plus fiable que le nom)
        if ($term_slug) {
            $term_check = get_term_by('slug', $term_slug, $taxonomy);
            $term_check = $term_check ? ['term_id' => $term_check->term_id] : null;
        } else {
            $term_check = term_exists($term, $taxonomy);
        }
        
        if (!$term_check) {
            $args = ['slug' => $term_slug ?? sanitize_title($term)];
            $insert_result = wp_insert_term($term, $taxonomy, $args);

            if (is_wp_error($insert_result)) {
                return null;
            }

            $term_id = $insert_result['term_id'];
        } else {
            $term_id = is_array($term_check) ? $term_check['term_id'] : $term_check;
        }

        if ($commercant_id !== null) {
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->replace(FAND_COMMERCANTS_TERMS, [
                'commercant_id' => (int)$commercant_id,
                'term_id'       => (int)$term_id,
                'date_created'  => current_time('mysql')
            ]);
        }

        return $term_id;
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    // Fonction pour mettre à jour un terme dans l'attribut
    static function update_term_attribut($slug, $term, $new_term_data) {

        // Vérifier si le terme existe
        $term_id = term_exists($term, sanitize_title($slug));

        // Si le terme existe, le mettre à jour
        if ($term_id) {

            // Les données à mettre à jour
            $args = [
                'name'        => isset($new_term_data['name']) ? $new_term_data['name'] : $term, // Nom du terme
                'slug'        => isset($new_term_data['slug']) ? sanitize_title($new_term_data['slug']) : '', // Slug
                'description' => isset($new_term_data['name']) ? $new_term_data['name'] : '', // Description
            ];

            // Mettre à jour le terme
            $result = wp_update_term($term_id['term_id'], sanitize_title($slug), $args);

            // Vérifier si la mise à jour a échoué
            if (is_wp_error($result)) {
                return $result; // Retourner l'erreur si la mise à jour échoue
            } else {
                return true; // Retourner vrai si la mise à jour a réussi
            }
        }

        $products = wc_get_products(array(
            'limit' => -1, // Récupérer tous les produits
            'attribute' => $slug, // Le slug de votre attribut
            'attribute_term' => $term_id, // L'ID du terme
        ));

        foreach ($products as $product) {
            $product->save(); // Sauvegarder le produit pour forcer une mise à jour
        }
    }

    // Fonction pour supprimer des termes de l'attribut
    static function delete_term_attribut($slug, $term) {
        $taxonomy = sanitize_title($slug);

        //error_log("Suppression terme dans taxonomie '$taxonomy' pour valeur : " . print_r($term, true));

        // Vérifier si le terme existe
        $term_id = term_exists($term, $taxonomy);

        if (!$term_id) {
            //error_log("Aucun terme trouvé pour '$term' dans taxonomie '$taxonomy'");
            return false;
        }

        //error_log("Terme trouvé : ID=" . $term_id['term_id'] . " (taxonomy=$taxonomy)");

        // Supprimer le terme de la taxonomie spécifiée
        $result = wp_delete_term($term_id['term_id'], $taxonomy);

        if (is_wp_error($result)) {
            //error_log("Erreur suppression terme ID=" . $term_id['term_id'] . " : " . $result->get_error_message());
            return $result; // Retourner l'erreur si la suppression échoue
        } else {
            //error_log("Terme ID=" . $term_id['term_id'] . " supprimé avec succès.");
            return true; // Retourner vrai si la suppression a réussi
        }
    }

}