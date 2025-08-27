<?php

namespace fand\Classes;

class FAND_Attribut {

    static public function add_nouvel_attribut($nom_attribut,$attribut_slug)
    {
        if (!taxonomy_exists($attribut_slug)) {

            // Nom de l'attribut
            $attribut = array(
                'slug' => $attribut_slug,
                'name' => $nom_attribut,
                'type' => 'select', // type de champ (select, radio, etc.)
                'order_by' => 'menu_order', // tri des termes
                'has_archives' => false,
            );

            // Ajout de l'attribut
            return wc_create_attribute($attribut);

        }
    }

    // Fonction pour ajouter des termes à l'attribut
    /*static function add_term_attribut($slug,$term) {

        // Vérifier si le terme existe déjà
        $term_id = term_exists($term, sanitize_title($slug));

        $args=[
            'description'=>$term,
        ];

        // Si le terme n'existe pas, l'ajouter
        if ($term_id==null) {
           return wp_insert_term($term, $slug,$args);
        }
    }*/
    static function add_term_attribut($taxonomy, $term, $commercant_id = null) {
        global $wpdb;
        error_log('taxonomy :' . $taxonomy);
        error_log('term :' . $term);
        error_log('commercant_id :' . $commercant_id);

        // Vérifier si le terme existe déjà
        $term_check = term_exists($term, $taxonomy);
        $args = ['description' => $term];

        if (!$term_check) {
            $insert_result = wp_insert_term($term, sanitize_title($taxonomy), $args);

            if (is_wp_error($insert_result)) {
                error_log('Erreur wp_insert_term : ' . $insert_result->get_error_message());
                return null; // ou false selon ta logique
            }

            $term_id = $insert_result['term_id'];
        } else {
            // $term_check peut être int ou array selon contexte
            $term_id = is_array($term_check) ? $term_check['term_id'] : $term_check;
        }

        error_log('term_id :' . $term_id);

        // Si WCFM est actif, on ajoute la relation commerçant <-> terme
        if (FAND_MARKET_ACTIVE && $commercant_id !== null) {
            $table_relation = FAND_COMMERCANTS_TERMS;
            error_log('table_relation :' . $table_relation);

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_relation WHERE commercant_id = %d AND term_id = %d",
                $commercant_id,
                $term_id
            ));

            if (!$exists) {
                $wpdb->insert($table_relation, [
                    'commercant_id' => $commercant_id,
                    'term_id'       => $term_id,
                ]);
            }
        }

        return $term_id;
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
        // Vérifier si le terme existe
        $term_id = term_exists($term, sanitize_title($slug));

        // Si le terme existe, le supprimer
        if ($term_id) {
            // Supprimer le terme de la taxonomie spécifiée
            $result = wp_delete_term($term_id['term_id'], sanitize_title($slug));

            // Vérifier si la suppression a échoué
            if (is_wp_error($result)) {
                return $result; // Retourner l'erreur si la suppression échoue
            } else {
                return true; // Retourner vrai si la suppression a réussi
            }
        } 
    }
}