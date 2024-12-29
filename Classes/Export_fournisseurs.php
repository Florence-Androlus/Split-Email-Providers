<?php
namespace fand\Classes;

class Export_fournisseurs {

    static public function exporter_fournisseurs_csv() {
        if (!current_user_can('manage_options')) {
            return;
        }
    
        global $wpdb;
        $fournisseurs = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", FAND_FOURNISSEURS_TABLE));
    
        if (empty($fournisseurs)) {
            wp_die('Aucun fournisseur à exporter.');
        }
    
        // Définir les en-têtes pour le téléchargement du fichier CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=fournisseurs.csv');
        echo "\xEF\xBB\xBF"; // Ajouter le BOM pour UTF-8
    
        // Capturer la sortie du CSV dans une variable
        ob_start();

        // Utilisation de WP_Filesystem pour ouvrir un fichier de sortie
        $output = fopen('php://output', 'w');
    
        // Entêtes du fichier CSV
        fputcsv($output, ['Nom', 'Adresse', 'CP', 'Ville', 'Pays', 'Email', 'Téléphone']);
    
        // Contenu du fichier CSV
        foreach ($fournisseurs as $fournisseur) {
            fputcsv($output, [
                str_replace("'", "’", $fournisseur->nom),
                str_replace("'", "’", $fournisseur->adresse),
                $fournisseur->cp,
                str_replace("'", "’", $fournisseur->ville),
                str_replace("'", "’", $fournisseur->pays),
                $fournisseur->email,
                $fournisseur->telephone
            ]);
        }
    
        // Récupérer le contenu du buffer de sortie
        $csvContent = ob_get_clean();

        // Supprimer les guillemets autour des champs, sauf pour les champs qui en ont besoin (ex : contenant des virgules)
        $csvContent = preg_replace('/"([^,]+)"/', '$1', $csvContent);
        
        // Assurer que le CSV est correctement échappé avant l'affichage
        echo esc_html($csvContent);
    }
}
