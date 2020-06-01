<?php
/**
 * Plugin Name:       WooCommerce Apurata Payment Gateway
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Finance your purchases with a quick Apurata loan.
 * Version:           0.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Jose Carrillo
 * Author URI:        https://www.linkedin.com/in/jose-enrique-carrillo-pino-40b09877
 * License:           GPL3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       woocommerce-apurata-payment-gateway
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    function init_wc_apurata_payment_gateway() {
        class WC_Apurata_Payment_Gateway extends WC_Payment_Gateway {
            const TEXT_DOMAIN = 'woocommerce-apurata-payment-gateway';

            public function __construct() {
                $this->id = 'apurata';

                $this->title = __('Cuotas quincenales', self::TEXT_DOMAIN);
                $this->icon = 'https://static.apurata.com/img/logo-dark.svg';
                $this->has_fields = FALSE;

                // Shown in the admin panel:
                $this->method_title = 'Apurata';
                $this->method_description = __('Evalúa a tus clientes y financia su compra con cuotas quincenales', self::TEXT_DOMAIN);

                $this->init_form_fields();
                $this->init_settings();

                // Get settings, e.g.
                // $this->title = $this->get_option( 'title' );

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_api_on_approved', array( $this, 'on_approved' ) );
            }

            function on_approved($order_id) {
                $order          = wc_get_order( $order_id );
                global $woocommerce;
                $woocommerce->cart->empty_cart();
            }

            function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array
                    (
                        'title' => __('Habilitar', self::TEXT_DOMAIN) . '/' . __('Deshabilitar', self::TEXT_DOMAIN),
                        'type' => 'checkbox',
                        'label' => __('Habilitar Apurata', self::TEXT_DOMAIN),
                        'default' => 'yes'
                    )
                );
            }

            function process_payment( $order_id ) {
                global $woocommerce;
                $order = new WC_Order( $order_id );

                // Return thankyou redirect
                return array(
                    'result' => 'success',
                    'redirect' => 'http://apurata.com'
                );
            }
        }
    }

    add_action( 'plugins_loaded', 'init_wc_apurata_payment_gateway' );

    function add_wc_apurata_payment_gateway( $methods ) {
        $methods[] = 'WC_Apurata_Payment_Gateway';
        return $methods;
    }

    add_filter( 'woocommerce_payment_gateways', 'add_wc_apurata_payment_gateway' );
}
?>