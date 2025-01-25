<?php

/**
* @package split-email-providers
* @version 1.0.1
*/

/* Plugin Name:        Split Email Providers
* Description:         Gestion des envois d'emails aux fournisseurs
* Version:             1.0.1
* Requires at least:   6.7
* Requires PHP:        8.0
* Author:              Fan-Develop
* Author URI:          https://split-email-providers.com/
* License:             GPL v2 or later
* License URI:         https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:         split-email-providers
* Domain Path:         /languages
*/

namespace fand;

defined( 'ABSPATH' ) || exit;

// Include WooCommerce functions
include_once(ABSPATH.'wp-admin/includes/plugin.php');

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    wp_die('Le plugin WooCommerce n\'est pas actif.'); // Utiliser wp_die pour afficher un message d'erreur
}


load_plugin_textdomain('split-email-providers', false, dirname(plugin_basename(__FILE__)) . '/languages');

// Utilisation de l'autoload PSR-4 de Composer
require __DIR__ . '/vendor/autoload.php';

// Définir les constantes
define('FAND_MAIN_FILE', __FILE__);
define('FAND_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FAND_PLUGIN_DIR', plugin_dir_path(__FILE__));
/**
 * Plugin version
 */
define('FAND_VERSION', '1.0.1');

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

