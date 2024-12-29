<?php

/**
* @package split-email-providers
* @version 1.6
*/

/* Plugin Name:        Split Email Providers 28 12 24 renvoyé
* Description:         Gestion des envois d'emails aux fournisseurs
* Version:             1.0.0
* Requires at least:   6.7
* Requires PHP:        8.0
* Author:              Fan-Develop
* Author URI:          https://fan-develop.fr
* License:             GPL v2 or later
* License URI:         https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:         split-email-providers
*/

namespace fand;

defined( 'ABSPATH' ) || exit;

// Include WooCommerce functions
include_once(ABSPATH.'wp-admin/includes/plugin.php');

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    wp_die('Le plugin WooCommerce n\'est pas actif.'); // Utiliser wp_die pour afficher un message d'erreur
}



// Utilisation de l'autoload PSR-4 de Composer
require __DIR__ . '/vendor/autoload.php';

// Définir les constantes
define('FAND_MAIN_FILE', __FILE__);
define('FAND_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FAND_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Défini la table des fournisseurs
global $wpdb;
define('FAND_FOURNISSEURS_TABLE', $wpdb->prefix . 'fand_fournisseurs');

// Défini l'attribut fournisseurs
define('FAND_FOURNISSEURS_ATTRIBUT', 'pa_fournisseur');

/* If this file is called directly, abort. */
if (!defined('WPINC')) {
    die;
}

// Inclure le fichier principal du plugin
require_once FAND_PLUGIN_DIR . 'plugin.php';

// Initialiser la page de paramètres
$fand = new FANDSettingsPage;
$fand->init();

