<?php

namespace fand;

use fand\Classes\FAND_Attribut;
use fand\Classes\Database\Database;
//use fandmarket\FANDSettingsPageMarket;

defined('ABSPATH') || exit;

class FANDSettingsPage {

    public function init() {
		// déclaration du hook d'activation du plugin
		register_activation_hook(FAND_MAIN_FILE, [$this,'onPluginActivation']);
		
		//Enregistrement du hook pour enqueuer les scripts seulement dans l'administration
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

		// Register the settings page.
		add_action( 'admin_menu', [$this,'register_fournisseurs_menu' ], 20 );

		// Ajouter l'action pour envoyer un email après le paiement complet de la commande
		add_action('woocommerce_payment_complete', [$this,'envoyer_email_fournisseur_apres_paiement']);
		add_action('woocommerce_order_status_processing', [$this,'envoyer_email_fournisseur_manuel']);

		//requette AJAX pour récupérer les fournisseurs
		add_action('wp_ajax_fandsep_get_fournisseurs', [$this,'get_fournisseurs_callback']); // Pour les utilisateurs connectés
		add_action('wp_ajax_nopriv_fandsep_get_fournisseurs', [$this,'get_fournisseurs_callback']); // Pour les utilisateurs non connectés
        //requette AJAX pour supprimer un fournisseur
		add_action('wp_ajax_fandsep_delete_fournisseur', [$this,'delete_fournisseur_callback']);
		
		//requette AJAX pour récupérer la liste des pays
		add_action('wp_ajax_fandsep_get_countries', [$this,'get_countries_ajax']);
		add_action('wp_ajax_nopriv_fandsep_get_countries', [$this,'get_countries_ajax']);
		//requette AJAX pour sauvegarder un fournisseur
		add_action('wp_ajax_fandsep_save_fournisseur', [$this,'save_fournisseur_ajax']);

		// Fix pour l'erreur WooCommerce Subscriptions
        add_action('plugins_loaded', function() {
            if (class_exists('WC_Subscriptions')) {
                // Désactiver temporairement le cache problématique
                add_filter('woocommerce_subscriptions_object_data_cache_enabled', '__return_false');
            }
        }, 5);

		// Fix pour l'erreur "Translation loading too early" de WCFM Marketplace
		add_filter('doing_it_wrong_trigger_error', '__return_false');

		add_action('plugins_loaded', [Database::class, 'register_hooks']);
		add_action('init', [FAND_Attribut::class, 'add_new_taxo']);

    }

	// Fonction d'activation du plugin
	static function onPluginActivation() {
		// Ajouter l'attribut s'il n'existe pas encore
		FAND_Attribut::add_new_taxo();
		// Initialiser la base de données
		Database::init();
	}

	static function get_fournisseurs_callback() {
		// Récupérer toutes les fournisseurs via la fonction Database::get_all_fournisseurs()
        $fournisseurs = Database::get_all_fournisseurs();
		//error_log("Fournisseurs récupérés : " . print_r($fournisseurs, true));
        // Retourner les fournisseurs sous forme de JSON pour Vue.js
		wp_send_json_success($fournisseurs);
		// Terminer l'exécution du script
		wp_die();
    }

	static function delete_fournisseur_callback() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Unauthorized.', 'split-email-providers')]);
			wp_die();
		}

		// Vérification nonce via GET
		if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'sep_nonce')) {
			wp_send_json_error(['message' => __('Security check failed.', 'split-email-providers')]);
			wp_die();
		}

		$raw = file_get_contents('php://input');
		$data = json_decode($raw, true);
		
        if (isset($data['fournisseur_id'])) {
            // Effectuez la suppression du fournisseur en fonction de la clé
            $alert=Database::delete_fournisseur($data); // Fonction à définir selon votre base de données
            if ($alert) {
                    // Réponse de succès
                    wp_send_json_success($alert);
                } else {
                    // Réponse d'erreur
                    wp_send_json_error(array('message' =>__('Error during deletion.', 'split-email-providers') ));
                }
            } else {
                wp_send_json_error(array('message' => __('Key missing.', 'split-email-providers') ));
            }
    }

	// Fonction AJAX pour récupérer la liste des pays
	static function get_countries_ajax() {
		// Récupérer les pays via WooCommerce
		$countries = WC()->countries->get_countries();

		// Renvoyer la réponse JSON
		wp_send_json_success($countries);
	}

	// Fonction AJAX pour sauvegarder un fournisseur
	static function save_fournisseur_ajax() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Unauthorized.', 'split-email-providers')]);
			wp_die();
		}

		// Vérification nonce via GET
		if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'sep_nonce')) {
			wp_send_json_error(['message' => __('Security check failed.', 'split-email-providers')]);
			wp_die();
		}

		$raw = file_get_contents('php://input');
		$data = json_decode($raw, true);
		// error_log("Données reçues pour save_fournisseur_ajax : " . print_r($data, true));
        
		//error_log(print_r($data['fournisseur'], true));
		if (isset($data['fournisseur'])) {
			if ($data['mode']==='add'){
				// Effectuez l'ajout du fournisseur
				$alert=Database::add_fournisseur($data['fournisseur']);
			}
			else{
				// Effectuez la mise a jour du fournisseur en fonction de son id
				$alert=Database::update_fournisseur($data['fournisseur']);; 
			}
			if ($alert) {
                    // Réponse de succès
                    wp_send_json_success($alert);
                } else {
                    // Réponse d'erreur
                    wp_send_json_error(array('message' => __('Error adding or modifying provider.', 'split-email-providers') ));
                }
            } else {
                wp_send_json_error(array('message' => __('Key missing.', 'split-email-providers') ));
            }
		
	}

	public function register_fournisseurs_menu() {
	
		// Ajouter le menu principal "Fournisseurs"
		add_menu_page(
			__('Providers', 'split-email-providers'), // Le titre de votre page de paramètres
			__('Providers', 'split-email-providers'), // Le nom du menu
			'manage_options', // La capacité requise
			'fand-settings', // Le slug de la page
			[$this, 'render_tableau_fournisseurs_page'], // La fonction de rappel pour afficher le contenu de la page
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

			// Enqueue des styles CSS
			wp_enqueue_style('bootstrap',FAND_PLUGIN_URL . 'assets/css/bootstrap.min.css',array(),FAND_VERSION);
			wp_enqueue_style('font-awesome',FAND_PLUGIN_URL . 'assets/css/all.min.css',array(),FAND_VERSION);
			wp_enqueue_style('custom-fandsep-style',FAND_PLUGIN_URL . 'assets/css/style.css',array(),FAND_VERSION);

			// Enqueue des scripts JavaScript
			wp_enqueue_script('jquery'); // Charge jQuery en priorité
			wp_enqueue_script('custom-fandsep-script',FAND_PLUGIN_URL . 'assets/js/script.js',array('jquery'),FAND_VERSION,true);
			wp_enqueue_script('bootstrap',FAND_PLUGIN_URL . 'assets/js/bootstrap.bundle.min.js',array('jquery'),FAND_VERSION,true);
			wp_enqueue_script('vue-app', FAND_PLUGIN_URL . 'dist/tableauFournisseurs.js', array('jquery'), FAND_VERSION, true);
		}

		// Chemin vers le fichier de traduction en fonction de la langue
		$current_locale = get_user_locale();
		$translations = [
			'domain' => 'split-email-providers',
			'locale_data' => [
				'split-email-providers' => [
					'' => [
						'domain' => 'split-email-providers',
						'lang'   => $current_locale,
					]
				]
			]
		];

		// On ne cherche le fichier que si ce n'est pas de l'anglais pur
		if ($current_locale !== 'en_US') {
			$translations_file = plugin_dir_path(__FILE__) . 'languages/split-email-providers-' . $current_locale . '.json';
			if (file_exists($translations_file)) {
				$file_content = json_decode(file_get_contents($translations_file), true);
				if ($file_content) {
					$translations = $file_content;
				}
			}
		}

		/*$data_to_pass = [
			'ajax_url'      => admin_url('admin-ajax.php'),
			'locale'        => $current_locale,
			'translations'  => $translations, 
			'licenceStatus' => defined('FAND_PRO_IMPORT_EXPORT_ENABLED') && FAND_PRO_IMPORT_EXPORT_ENABLED,
			'import_nonce'  => wp_create_nonce('import_fournisseurs_action'),
			'nonce'        => wp_create_nonce('sep_nonce'),
		];*/

		$data_to_pass = [
			'ajax_url'     => admin_url('admin-ajax.php'),
			'locale'       => $current_locale,
			'translations' => $translations,
			'nonce'        => wp_create_nonce('sep_nonce'),
		];
	
		// Passer les données à Vue.js
		wp_localize_script('vue-app', 'FandData', $data_to_pass);
		// Ajouter la variable ajax_url dans le HTML
		echo "<script type='text/javascript'>
		var ajax_url = '" . esc_url(admin_url('admin-ajax.php')) . "';
		</script>";
	}

	// Render the settings page.
	public function render_tableau_fournisseurs_page(){

		echo '<div style="display: none;">';
			echo '<a href="https://fan-develop.fr/Split-email-providers/">Création de plugin custom php MySQL Javasript Gestion des envois d\'emails aux fournisseurs.</a>';
		echo '</div>';
		echo '<div class="div_saut_ligne" style="height:50px;"></div>';
		// Inclure le tableau des fournisseurs en vuejs
		echo '<div id="app">';
		echo '</div>';

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
		
		// Détection de la langue
		$current_locale = get_user_locale();
		$json_path = FAND_PLUGIN_DIR . 'languages/split-email-providers-' . $current_locale . '.json';

		if (file_exists($json_path)) {
			$content = json_decode(file_get_contents($json_path), true);
			$translations = $content['locale_data']['split-email-providers'] ?? [];

			// On ajoute un filtre dynamique pour intercepter les appels de traduction
			add_filter('gettext', function($translation, $text, $domain) use ($translations) {
				if ($domain === 'split-email-providers' && isset($translations[$text])) {
					// Dans ton JSON, la traduction est à l'index [1]
					return is_array($translations[$text]) ? $translations[$text][1] : $translations[$text];
				}
				return $translation;
			}, 10, 3);
		}

		// Options addon - géré par le plugin PRO via hook
		$show_price_column = apply_filters('fandsep_show_price_column', 0);
		$send_shop_address = apply_filters('fandsep_send_shop_address', 0);
	
		// Récupération commande
		$order = wc_get_order($order_id);
		if (!$order) {
			//error_log("[SplitEmail] Commande introuvable pour ID $order_id");
			return;
		}

		// Email admin
		$admin_email = get_option('admin_email');
		if (!$admin_email) {
			//error_log("[SplitEmail] Aucun email admin configuré");
			return;
		}

		// Pays de livraison
		$shipping_country = WC()->countries->countries[$order->get_shipping_country()] ?? '';
		$shipping_address = $order->get_formatted_shipping_address() . ', ' . $shipping_country;

		// Nom site + logo global Woo
		$shop_name     = get_bloginfo('name');
		$shop_logo_url = get_site_icon_url();
		$shop_email    = get_option('woocommerce_email_from_address');

		// Tableau regroupement
		$groupes = [];

		foreach ($order->get_items() as $item_id => $item) {
			$product_id   = $item->get_product_id();
			$productcap   = $item->get_variation_id() ? $item->get_variation_id() : $product_id;
			$product      = wc_get_product($product_id);

			if (!$product) {
				//error_log("[SplitEmail] Produit $product_id introuvable");
				continue;
			}

			// GTIN
			$gtin  = get_post_meta($productcap, '_global_unique_id', true);
			$price = $product->get_price();

			// Mode marketplace activé
			if (defined('FAND_MARKET_ACTIVE') && FAND_MARKET_ACTIVE && class_exists('fandmarket\FANDSettingsPageMarket')) {
				$info = \fandmarket\FANDSettingsPageMarket::fand_get_vendor_and_supplier_info($product_id);

				if (!$info || empty($info['fournisseur'])) {
					continue;
				}

				$vendor      = $info['vendeur'];
				$fournisseur = $info['fournisseur'];

				$key = $vendor['id'] . '-' . $fournisseur['id'];
				if (!isset($groupes[$key])) {
					$groupes[$key] = [
						'vendeur'     => $vendor,
						'fournisseur' => $fournisseur,
						'produits'    => []
					];
				}

				$groupes[$key]['produits'][] = [
					'nom'      => $item->get_name(),
					'quantite' => $item->get_quantity(),
					'gtin'     => $gtin,
					'price'    => $price
				];

				//error_log("[SplitEmail] Ajout produit {$item->get_name()} ({$item->get_quantity()}) au couple Vendor={$vendor['nom']} / Fournisseur={$fournisseur['nom']}");

			} else {
				// === Mode FREE (sans marketplace) ===
				$terms = get_the_terms($product_id, FAND_FOURNISSEURS_ATTRIBUT);
				if ($terms && !is_wp_error($terms)) {
					$fournisseur_term = $terms[0];
					$fournisseur_nom  = $fournisseur_term->name;

					// Extraire l'ID depuis le slug "fournisseur-{id}"
					$fournisseur_id = intval(str_replace('fournisseur-', '', $fournisseur_term->slug));

					if ($fournisseur_id) {
						// phpcs:disable  WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$fournisseur_email_row = $wpdb->get_row($wpdb->prepare(
							"SELECT email FROM %i WHERE id = %d",
							FAND_FOURNISSEURS_TABLE,
							$fournisseur_id
						));
					} else {
						// Fallback pour les anciens termes sans slug normalisé
						// phpcs:disable  WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$fournisseur_email_row = $wpdb->get_row($wpdb->prepare(
							"SELECT email FROM %i WHERE nom = %s",
							FAND_FOURNISSEURS_TABLE,
							$fournisseur_nom
						));
					}

					if (!$fournisseur_email_row || empty($fournisseur_email_row->email)) {
						//error_log("[SplitEmail] Pas d'email fournisseur trouvé pour $fournisseur_nom");
						continue;
					}

					$fournisseur_email = $fournisseur_email_row->email;

					if (!isset($groupes[$fournisseur_email])) {
						$groupes[$fournisseur_email] = [
							'fournisseur' => [
								'nom'   => $fournisseur_nom,
								'email' => $fournisseur_email,
							],
							'produits' => []
						];
					}

					$groupes[$fournisseur_email]['produits'][] = [
						'nom'      => $item->get_name(),
						'quantite' => $item->get_quantity(),
						'gtin'     => $gtin,
						'price'    => $price
					];

					//error_log("[SplitEmail FREE] Ajout produit {$item->get_name()} ({$item->get_quantity()}) pour fournisseur={$fournisseur_nom}");
				}
			}
		}

		if (empty($groupes)) {
			//error_log("[SplitEmail] Aucun groupe (vendeur/fournisseur) à traiter pour commande $order_id");
			return;
		}

		// Envoi des mails
		foreach ($groupes as $key => $data) {

			if (isset($data['vendeur'])) {
				// === Mode PRO ===
				$vendeur     = $data['vendeur'];
				$shop_name     = $vendeur['nom'];
				$fournisseur = $data['fournisseur'];
				$produits    = $data['produits'];
				$shop_logo_url = $vendeur['logo'];
				$shop_address     = $vendeur['adresse'];  // Adresse du vendeur
				$nom_fournisseur  = $fournisseur['nom'];  // Nom du fournisseur

				$fournisseur_email = $fournisseur['email'];
				// translators: %1$s = nom du vendeur, %2$s = nom du fournisseur
				$subject_template = __('New order - %1$s → %2$s', 'split-email-providers');
				$email_subject = sprintf($subject_template, $vendeur['nom'], $fournisseur['nom']);

			} else {
				// === Mode FREE ===
				$fournisseur = $data['fournisseur'];
				$produits    = $data['produits'];
				$shop_logo_url = get_site_icon_url();
				$shop_address = get_option('woocommerce_store_address') . ', ' 
							. get_option('woocommerce_store_city') . ', '
							. get_option('woocommerce_store_postcode') . ', ' 
							. (WC()->countries->countries[get_option('woocommerce_default_country')] ?? '');
				$nom_fournisseur  = $fournisseur['nom'];

				$fournisseur_email = $fournisseur['email'];

				$email_subject = sprintf(
					// translators: %s = numéro de commande
					__('New order for your products - %s', 'split-email-providers'),
					$order->get_order_number()
				);
			}

			//error_log('DEBUG EMAIL: shop_logo_url=' . $shop_logo_url);
			//error_log('DEBUG EMAIL: shop_name=' . $shop_name);
			//error_log('DEBUG EMAIL: shop_address=' . $shop_address);
			//error_log('DEBUG EMAIL: nom_fournisseur=' . $nom_fournisseur);
			//error_log('DEBUG EMAIL: show_price_column=' . $show_price_column);
			//error_log('DEBUG EMAIL: send_shop_address=' . $send_shop_address);
			//error_log('DEBUG EMAIL: shipping_address=' . $shipping_address);
			//error_log('DEBUG EMAIL: produits=' . print_r($produits, true));
			
			// Ensuite tu inclus ton template
			ob_start();
			include FAND_PLUGIN_DIR . '/Templates/email-fournisseur.php';
			$fand_email_body = ob_get_clean();

			$headers = [
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . (isset($vendeur) ? $vendeur['nom'] : get_bloginfo('name')) . ' <' . (isset($vendeur) ? $vendeur['email'] : get_option('admin_email')) . '>',
				'Cc: ' . get_option('admin_email')
			];


			$sent = wp_mail($fournisseur_email, $email_subject, $fand_email_body, $headers);

			//error_log("Envoi à {$nom_fournisseur} ({$fournisseur_email}) : " . ($sent ? 'OK' : 'ECHEC'));
		}
	}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}