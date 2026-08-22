<?php
// Construction du corps de l'email avec un bandeau, logo, et informations de la boutique
if ( ! defined( 'ABSPATH' ) ) exit;

    // translators: %s = nom du fournisseur
    $fand_hello_text = sprintf(
        // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
        esc_html__('Hello %s, you have a new order with the following products:', 'split-email-providers'),
        esc_html($nom_fournisseur)
    );

    $fand_fournisseur_email_content = '
    <html>
        <body>

            <div style="background-color:#f0f0f0; padding:20px; text-align:center;">

                <img src="' . esc_url($shop_logo_url) . '" alt="Logo" style="max-width: 150px; max-height: 80px; width: auto; height: auto; display: inline-block;" />

            </div>

            <div style="padding:20px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>

                <p><strong>' . esc_html__('Shop details:', 'split-email-providers') . '</strong></p>

                <p>' . esc_html($shop_name) . '<br>' . esc_html($shop_address) . '<br><strong>' . esc_html__('Ref:', 'split-email-providers') . '</strong> ' . esc_html($order->get_order_number()) . '</p>

            </div>

            <div style="text-align: right;">

                <p><strong>' . esc_html__('Date:', 'split-email-providers') . '</strong> ' . date_i18n('j F Y', strtotime($order->get_date_created())) . '</p>

            </div>

            </div>

            <div style="padding:20px;">
                <p>' . $fand_hello_text . '</p>

                <table border="1" cellpadding="10" cellspacing="0" style="border-collapse:collapse; width:100%;">

                    <thead>

                        <tr>
                            <th>' . esc_html__('Product name', 'split-email-providers') . '</th>
                            <th>' . esc_html__('Quantity', 'split-email-providers') . '</th>
                            <th>' . esc_html__('GTIN/EAN code', 'split-email-providers') . '</th>';
                        if ($show_price_column == 1) {
                            $fand_fournisseur_email_content .= '<th>' . esc_html__('Price', 'split-email-providers') . '</th>';
                        }
                        $fand_fournisseur_email_content .= '</tr>
                    </thead>
                    <tbody>';

                    foreach ($produits as $fand_produit ) {

                        $fand_fournisseur_email_content .= '

                        <tr>

                            <td>' . esc_html($fand_produit ['nom']) . '</td>

                            <td>' . intval($fand_produit ['quantite']) . '</td>

                            <td>' . (!empty($fand_produit['gtin']) ? esc_html($fand_produit['gtin']) : esc_html__('N/A', 'split-email-providers')) . '</td>';

                            if ($show_price_column == 1) {
                                $fand_fournisseur_email_content .= '<td>' . (!empty($fand_produit['price']) ? esc_html($fand_produit['price']) : esc_html__('N/A', 'split-email-providers')) . '</td>';
                            }

                        $fand_fournisseur_email_content .= '</tr>';
                    }

                    $fand_fournisseur_email_content .= '

                    </tbody>

                </table>

                <br>';

                if ($send_shop_address == 1) {
                    $fand_fournisseur_email_content .= '<p><strong>' . esc_html__('Delivery address:', 'split-email-providers') . '</strong><br>' . wp_kses($shop_address, ['br' => []]) . '</p>';
                } else {
                    $fand_fournisseur_email_content .= '<p><strong>' . esc_html__('Delivery address:', 'split-email-providers') . '</strong><br>' . wp_kses($shipping_address, ['br' => []]) . '</p>';
                }

                $fand_fournisseur_email_content .= '<br>

                <p>' . esc_html__('Thank you for processing this order quickly.', 'split-email-providers') . '</p>

            </div>

        </body>

    </html>';

echo wp_kses_post($fand_fournisseur_email_content);