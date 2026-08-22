<?php
/* Plugin Name:        Split Email Providers
* Description:         Managing email communications to Providers 
* Version:             1.1.7
* Requires at least:   6.8
* Tested up to:        7.1  
* Requires PHP:        8.2
* Requires Plugins:    woocommerce
* Author:              Fan-Develop
* Author URI:          https://split-email-providers.com/
* License:             GPL v2 or later
* License URI:         https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:         split-email-providers
* Domain Path:         /languages
*/

namespace fand;

defined('ABSPATH') || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Vérification si WooCommerce est actif, sans bloquer tout le site
function fand_check_woocommerce() {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>Le plugin <strong>WooCommerce</strong> doit être activé pour que <strong>Split Email Providers Pro</strong> fonctionne.</p></div>';
        });
        return false;
    }
    return true;
}
add_action('admin_init', __NAMESPACE__ . '\\fand_check_woocommerce');

// Chargement de l'autoloader Composer si disponible
// Vérifie si l'autoload de la version Free est déjà chargé
if (!class_exists('fand\\Classes\\Apifournisseur')) { 
    // Charger l'autoload du Pro uniquement si nécessaire
    require_once __DIR__ . '/vendor/autoload.php';
}

// Définition des constantes
define('FAND_VERSION', '1.1.7');
define('FAND_MAIN_FILE', __FILE__);
define('FAND_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FAND_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Vérification si la version MARKET est active
if (!defined('FAND_MARKET_ACTIVE')) {
    define('FAND_MARKET_ACTIVE', false);
}

// Défini la table des fournisseurs
global $wpdb;
define('FAND_FOURNISSEURS_TABLE', $wpdb->prefix . 'fand_fournisseurs');
// Table des adresses/relations (Celle où tu as tes données !)
define('FAND_COMMERCANTS_FOURNISSEURS_TABLE', $wpdb->prefix . 'fand_commercants_fournisseurs');
// Table des relations termes
define('FAND_COMMERCANTS_TERMS', $wpdb->prefix . 'fand_commercants_terms');
define('FAND_FOURNISSEURS_ATTRIBUT', 'pa_fournisseur');

// Sécurité : éviter l'accès direct
if (!defined('WPINC')) {
    die;
}

// Inclure le fichier principal du plugin
require_once FAND_PLUGIN_DIR . 'plugin.php';

// Vérifier si la classe existe avant de l’instancier
$fand = new FANDSettingsPage();
$fand->init();

