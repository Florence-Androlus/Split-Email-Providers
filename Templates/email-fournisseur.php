<?php
// Construction du corps de l'email avec un bandeau, logo, et informations de la boutique
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	$email_body = '
	<html>
		<body>

			<div style="background-color:#f0f0f0; padding:20px; text-align:center;">

				<img src="' . esc_url($shop_logo_url) . '" alt="Logo" style="max-width: 150px; max-height: 80px; width: auto; height: auto; display: inline-block;" />

			</div>

			<div style="padding:20px; display: flex; justify-content: space-between; align-items: flex-start;">
			<div>

				<p><strong>Coordonnées de la boutique :</strong></p>

				<p>' . esc_html($shop_name) . '<br>' . esc_html($shop_address) . '<br><strong>Ref :</strong> ' . esc_html($order->get_order_number()) . '</p>

			</div>

			<div style="text-align: right;">

				<p><strong>Date :</strong> ' . date_i18n('j F Y', strtotime($order->get_date_created())) . '</p>

			</div>

			</div>

			<div style="padding:20px;">

				<p>Bonjour ' . esc_html($nom_fournisseur) . ', vous avez une nouvelle commande avec les produits suivants :</p>

				<table border="1" cellpadding="10" cellspacing="0" style="border-collapse:collapse; width:100%;">

					<thead>

						<tr>

							<th>Nom du produit</th>

							<th>Quantité</th>

							<th>Code GTIN/EAN</th>';
						//error_log($show_price_column);
						if ($show_price_column==1) {
							$email_body .= '<th>Prix</th>';
						}
							
							$email_body .= '</tr>

					</thead>

					<tbody>';

			foreach ($produits as $produit) {

				$email_body .= '

				<tr>

					<td>' . esc_html($produit['nom']) . '</td>

					<td>' . intval($produit['quantite']) . '</td>

					<td>' . (!empty($produit['gtin']) ? esc_html($produit['gtin']) : 'N/A') . '</td>';
				
					if ($show_price_column==1) {
						$email_body .= '<td>' . (!empty($produit['price']) ? esc_html($produit['price']) : 'N/A') . '</td>';
					}
			
				$email_body .= '</tr>';
			}

			$email_body .= '

					</tbody>

				</table>

				<br>';

				if ($send_shop_address==1) {
					$email_body .= '<p><strong>Adresse de livraison :</strong><br>' . esc_html($shop_address) . '</p>';
				}
				else{
					$email_body .= '<p><strong>Adresse de livraison :</strong><br>' . esc_html($shipping_address) . '</p>';
				}

				$email_body .= '<br>

				<p>Merci de traiter cette commande rapidement.</p>

			</div>

		</body>

	</html>';

	return $email_body;