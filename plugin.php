<?php

namespace fand;

use fand\Classes\Router;
use fand\Classes\FAND_Attribut;
use fand\Classes\Database\Database;

class FANDSettingsPage {

    public function init() {
		// déclaration du hook d'activation du plugin
		register_activation_hook(FAND_MAIN_FILE, [$this,'onPluginActivation']);
		//Enregistrement du hook pour enqueuer les scripts seulement dans l'administration
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		// Register the settings page.
		add_action( 'admin_menu', [$this,'register_fournisseurs_menu' ] );
		// on ajoute nos URL custom
		add_action('init', [$this,'registerCustomRewrites']);
		// Ajouter l'action pour envoyer un email après le paiement complet de la commande
		add_action('woocommerce_payment_complete', [$this,'envoyer_email_fournisseur_apres_paiement']);
		add_action('woocommerce_order_status_processing', [$this,'envoyer_email_fournisseur_manuel']);

    }

	// Fonction d'activation du plugin
	static function onPluginActivation() {

		// Definit le nom de l'attribut
		$nom_attribut = 'fournisseur';
		// Ajouter l'attribut s'il n'existe pas encore
		FAND_Attribut::add_nouvel_attribut($nom_attribut,FAND_FOURNISSEURS_ATTRIBUT);
		Database::init();
	}

	// Fonction ajout des URL custom
    static function registerCustomRewrites()
    {
        Router::init();
    }

	public function register_fournisseurs_menu() {

		// Ajouter le menu principal "Fournisseurs"
		add_menu_page(
			'Fournisseurs', // Le titre de votre page de paramètres
			'Fournisseurs', // Le nom du menu
			'manage_options', // La capacité requise
			'fand-settings', // Le slug de la page
			array($this, 'render_tableau_fournisseurs_page'), // La fonction de rappel pour afficher le contenu de la page
			'dashicons-share', // L'icône à utiliser pour ce menu
			59 // La position dans l'ordre du menu où celui-ci doit apparaître
		);

	}
	
	/**
	 * Enqueue les scripts et styles nécessaires uniquement sur les pages administratives spécifiques
	 *
	 * @param string $hook_suffix Identifiant de la page actuelle
	 */
	public function enqueue_scripts($hook_suffix) {

		// Vérifie qu'on es bien sur les pages de paramètres de Split Email Providers
		if ($hook_suffix === 'toplevel_page_fand-settings') {

			$plugin_version = '1.0.0'; // Remplacez la version de plugin

			// Enqueue des styles CSS
			wp_enqueue_style('bootstrap6',FAND_PLUGIN_URL . 'assets/css/bootstrap.min.css',array(),$plugin_version);
			wp_enqueue_style('font-awesome',FAND_PLUGIN_URL . 'assets/css/all.min.css',array(),$plugin_version);
			wp_enqueue_style('custom-style',FAND_PLUGIN_URL . 'assets/css/style.css',array(),$plugin_version);

			// Enqueue des scripts JavaScript
			wp_enqueue_script('jquery'); // Charge jQuery en priorité
			wp_enqueue_script('custom-script',FAND_PLUGIN_URL . 'assets/js/script.js',array('jquery'),$plugin_version,true);
			wp_enqueue_script('bootstrap',FAND_PLUGIN_URL . 'assets/js/bootstrap.bundle.min.js',array('jquery'),$plugin_version,true);
		}
	}

	// Render the settings page.
	public function render_tableau_fournisseurs_page(){

		// Vérification du nonce
		if (isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'fournisseur_action')) {
			wp_die(esc_html__('Échec de la vérification de sécurité.', 'split-email-providers'));
		}

		$action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';

		// Vérifier si un message est passé dans l'URL
		$message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
		$message_type = isset($_GET['message_type']) ? sanitize_text_field(wp_unslash($_GET['message_type'])) : '';

		echo '<div style="margin-top:5em;">';	
			echo '<div style="display: none;">';
			echo '<a href="https://fan-develop.fr/Split-email-providers/" >Création de plugin custom php MySQL Javasript Gestion des envois d\'emails aux fournisseurs.</a>';
			echo '</div>';
			
			// Inclure le tableau des fournisseurs
			include FAND_PLUGIN_DIR . '/Templates/tableau-fournisseurs.php';

			// Inclure les modales
			include FAND_PLUGIN_DIR . '/Templates/modal-fournisseur.php';
			include FAND_PLUGIN_DIR . '/Templates/import-fournisseurs.php';

		echo'</div>';
	}

	// Fonction pour les paiements par chèque
	function envoyer_email_fournisseur_manuel($order_id) {

		$order = wc_get_order($order_id);

		// Liste des méthodes de paiement manuelles, y compris 'cheque'
		$payment_methods_manual = ['cheque', 'bacs', 'cod']; // Vous pouvez ajouter d'autres méthodes manuelles ici

		// Vérifier si la méthode de paiement appartient à la liste des méthodes manuelles
		if (in_array($order->get_payment_method(), $payment_methods_manual)) {
			$this->envoyer_email_fournisseur_apres_paiement($order_id);
		}
	}

	function envoyer_email_fournisseur_apres_paiement($order_id) {

		global $wpdb;

		// Récupère la commande
		$order = wc_get_order($order_id);

		if (!$order) {
			//error_log('Erreur : commande introuvable pour ID : ' . $order_id);
			return;
		}

		// Adresse email de l'administrateur
		$admin_email = get_option('admin_email');

		if (!$admin_email) {
			return;
		}

		// Récupération de l'adresse de livraison, ajout du pays
		$shipping_country = WC()->countries->countries[$order->get_shipping_country()]; 
		$shipping_address = $order->get_formatted_shipping_address() . ', ' . $shipping_country;

		// Récupérer l'adresse complète de la boutique, y compris le pays
		$shop_country = WC()->countries->countries[get_option('woocommerce_default_country')];
		$shop_address = get_option('woocommerce_store_address') . ', ' . get_option('woocommerce_store_city') . ', ' . get_option('woocommerce_store_postcode') . ', ' . $shop_country;
	
		// Récupérer le nom du site (shop name)
		$shop_name = get_bloginfo('name');

		// Récupérer le logo de la boutique
		$shop_logo_url = get_site_icon_url();

		// Tableau pour stocker les produits par fournisseur et leurs emails respectifs
		$produits_par_fournisseur = array();

		foreach ($order->get_items() as $item_id => $item) {
			$product_id = $item->get_product_id();
			$productcap = $item->get_variation_id() ? $item->get_variation_id() : $product_id;
			$product = wc_get_product($product_id);
	
			if (!$product) {
				continue;
			}
	
			// Récupérer le code GTIN/EAN
			$gtin = get_post_meta($productcap, '_global_unique_id', true);

			// Récupère les termes liés à l'attribut 'pa_fournisseur'
			$terms = get_the_terms($product_id, FAND_FOURNISSEURS_ATTRIBUT);

			if ($terms && !is_wp_error($terms)) {
				$fournisseur_term = $terms[0];
				$fournisseur_nom = $fournisseur_term->name;

				// Rechercher l'email du fournisseur dans la base de données
				$fournisseur_email_row = $wpdb->get_row($wpdb->prepare(
					"SELECT email FROM %i WHERE nom = %s",FAND_FOURNISSEURS_TABLE,
					$fournisseur_nom
				));

				if (!$fournisseur_email_row || empty($fournisseur_email_row->email)) {
					continue;
				}
	
				$fournisseur_email = $fournisseur_email_row->email;

				// Ajouter les produits dans un tableau associant fournisseur et e-mail
				if (!isset($produits_par_fournisseur[$fournisseur_email])) {
					$produits_par_fournisseur[$fournisseur_email] = array(
						'nom_fournisseur' => $fournisseur_nom,
						'produits' => array()
					);
				}

				$produits_par_fournisseur[$fournisseur_email]['produits'][] = array(
					'nom' => $item->get_name(),
					'quantite' => $item->get_quantity(),
					'gtin' => $gtin
				);
			} else {
				continue;
			}
		}
	
		if (empty($produits_par_fournisseur)) {
			return;
		}

		// Récupérer le nom de la boutique
		$shop_name = get_bloginfo('name');

		// Récupérer l'email de la boutique
		$shop_email = get_option('woocommerce_email_from_address');

		// Envoi des emails aux fournisseurs concernés
		foreach ($produits_par_fournisseur as $fournisseur_email => $data) {
			$nom_fournisseur = $data['nom_fournisseur'];
			$produits = $data['produits'];	
			$email_subject = 'Nouvelle commande pour vos produits';	
			// Inclure l'email body
			include FAND_PLUGIN_DIR . '/Templates/email-fournisseur.php';
			// Headers pour inclure l'admin en CC
			$headers = array('Content-Type: text/html; charset=UTF-8','From: ' . $shop_name . ' <' . $shop_email . '>','Cc: ' . $admin_email);

			// Envoi de l'email
			$mail_sent = wp_mail($fournisseur_email, $email_subject, $email_body, $headers);

		}
	}

}