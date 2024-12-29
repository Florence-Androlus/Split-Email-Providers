<!-- Fenêtre import -->
<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div id="importModal" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Importation de Fournisseurs</h2>
            <form id="form" enctype="multipart/form-data" method="post" action="<?php echo esc_url(home_url('import_csv')) ?>">
                <?php wp_nonce_field('import_fournisseurs_action', 'import_fournisseurs_nonce'); ?>                        
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit" id="submitButton">Importer</button>
            </form>
        </div>
    </div>
</div>