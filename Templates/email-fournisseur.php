<?php
// Construction du corps de l'email avec un bandeau, logo, et informations de la boutique
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

			$email_body = '

			<html>

			<body>

			<div style="background-color:#f0f0f0; padding:20px; text-align:center;">

				<img src="' . $shop_logo_url . '" alt="Logo de la boutique" style="max-width:150px;"/>

			</div>

			<div style="padding:20px; display: flex; justify-content: space-between; align-items: flex-start;">

			<div>

				<p><strong>Coordonnées de la boutique :</strong></p>

				<p>' . $shop_name . '<br>' . $shop_address . '<br><strong>Ref :</strong> ' . $order->get_order_number() . '</p>

			</div>

			<div style="text-align: right;">

				<p><strong>Date :</strong> ' . date_i18n('j F Y', strtotime($order->get_date_created())) . '</p>

			</div>

			</div>

			<div style="padding:20px;">

				<p>Bonjour ' . $nom_fournisseur . ', vous avez une nouvelle commande avec les produits suivants :</p>

				<table border="1" cellpadding="10" cellspacing="0" style="border-collapse:collapse; width:100%;">

					<thead>

						<tr>

							<th>Nom du produit</th>

							<th>Quantité</th>

							<th>Code GTIN/EAN</th>';
						error_log($show_price_column);
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
					$email_body .= '<p><strong>Adresse de livraison :</strong><br>' . $shop_address . '</p>';
				}
				else{
					$email_body .= '<p><strong>Adresse de livraison :</strong><br>' . $shipping_address . '</p>';
				}

				$email_body .= '<br>

				<p>Merci de traiter cette commande rapidement.</p>

			</div>

			</body>

			</html>';