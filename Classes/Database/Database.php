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

    static function add_fournisseur($data)
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
    }

    static public function update_fournisseur($data)
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
    }

    static function get_all_fournisseurs(){
        global $wpdb;
        $fournisseurs = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", FAND_FOURNISSEURS_TABLE));
        return $fournisseurs;
    }

    static function delete_fournisseur($POST)
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
    }
}