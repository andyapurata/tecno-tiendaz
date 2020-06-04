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
            const APURATA_DOMAIN = 'http://localhost:8000';

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
                $this->client_id = $this->get_option( 'client_id' );
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
                    ),
                    'client_id' => array
                    (
                        'title' => __('ID de Cliente', self::TEXT_DOMAIN),
                        'type' => 'text',
                        'required' => true,
                        'description' => __('Este ID se obtiene luego de comunicarte con nosotros', self::TEXT_DOMAIN),
                        'default' => ''
                    ),
                );
            }

            function process_payment( $order_id ) {
                global $woocommerce;
                $order = new WC_Order( $order_id );

                $redirect_url = self::APURATA_DOMAIN .
                                 '/pos/crear-orden-y-continuar' .
                                 '?order_id=' . $order->get_id() .
                                 '&pos_client_id=' . $this->client_id.
                                 '&amount=' . $order->get_total() .
                                 '&url_redir_on_canceled=http://localhost:8080/?page_id=7' .
                                 '&url_redir_on_rejected=http://localhost:8080/?page_id=7' .
                                 '&url_redir_on_success=http://localhost:8080/?page_id=7' .
                                 '&customer_data__email=' . $order->get_billing_email() .
                                 '&customer_data__phone=' . $order->get_billing_phone() .
                                 '&customer_data__billing_address_1=' . $order->get_billing_address_1() .
                                 '&customer_data__billing_address_2=' . $order->get_billing_address_2() .
                                 '&customer_data__billing_first_name=' . $order->get_billing_first_name() .
                                 '&customer_data__billing_last_name=' . $order->get_billing_last_name() .
                                 '&customer_data__billing_city=' . $order->get_billing_city() .
                                 '&customer_data__shipping_address_1=' . $order->get_shipping_address_1() .
                                 '&customer_data__shipping_address_2=' . $order->get_shipping_address_2() .
                                 '&customer_data__shipping_first_name=' . $order->get_shipping_first_name() .
                                 '&customer_data__shipping_last_name=' . $order->get_shipping_last_name() .
                                 '&customer_data__shipping_city=' . $order->get_shipping_city() ;


                // Return thankyou redirect
                return array(
                    'result' => 'success',
                    'redirect' => $redirect_url
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