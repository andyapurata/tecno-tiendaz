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
const APURATA_TEXT_DOMAIN = 'woocommerce-apurata-payment-gateway';
const APURATA_DOMAIN = 'http://localhost:8000';

// Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function init_wc_apurata_payment_gateway() {
        class WC_Apurata_Payment_Gateway extends WC_Payment_Gateway {

            public function __construct() {
                $this->id = 'apurata';

                $this->title = __('Apurata - Cuotas quincenales', APURATA_TEXT_DOMAIN);
                $this->icon = 'https://static.apurata.com/img/logo-dark.svg';
                $this->has_fields = FALSE;

                // Shown in the admin panel:
                $this->method_title = 'Apurata';
                $this->method_description = __('Evalúa a tus clientes y financia su compra con cuotas quincenales', APURATA_TEXT_DOMAIN);

                $this->init_form_fields();
                $this->init_settings();

                // Get settings, e.g.
                $this->client_id = $this->get_option( 'client_id' );
                // $this->title = $this->get_option( 'title' );

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            }


            function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array
                    (
                        'title' => __('Habilitar', APURATA_TEXT_DOMAIN) . '/' . __('Deshabilitar', APURATA_TEXT_DOMAIN),
                        'type' => 'checkbox',
                        'label' => __('Habilitar Apurata', APURATA_TEXT_DOMAIN),
                        'default' => 'yes'
                    ),
                    'client_id' => array
                    (
                        'title' => __('ID de Cliente', APURATA_TEXT_DOMAIN),
                        'type' => 'text',
                        'required' => true,
                        'description' => __('Para obtener este ID comunícate con nosotros al correo merchants@apurata.com', APURATA_TEXT_DOMAIN),
                        'default' => ''
                    ),
                );
            }

            function process_payment( $order_id ) {
                $order = wc_get_order( $order_id );

                $redirect_url = APURATA_DOMAIN .
                                 '/pos/crear-orden-y-continuar' .
                                 '?order_id=' . urlencode($order->get_id()) .
                                 '&pos_client_id=' . urlencode($this->client_id) .
                                 '&amount=' . urlencode($order->get_total()) .
                                 '&url_redir_on_canceled=' . urlencode(wc_get_checkout_url()) .
                                 '&url_redir_on_rejected=' . urlencode(wc_get_checkout_url()) .
                                 '&url_redir_on_success=' . urlencode($this->get_return_url( $order )) .
                                 '&customer_data__email=' . urlencode($order->get_billing_email()) .
                                 '&customer_data__phone=' . urlencode($order->get_billing_phone()) .
                                 '&customer_data__billing_address_1=' . urlencode($order->get_billing_address_1()) .
                                 '&customer_data__billing_address_2=' . urlencode($order->get_billing_address_2()) .
                                 '&customer_data__billing_first_name=' . urlencode($order->get_billing_first_name()) .
                                 '&customer_data__billing_last_name=' . urlencode($order->get_billing_last_name()) .
                                 '&customer_data__billing_city=' . urlencode($order->get_billing_city()) .
                                 '&customer_data__shipping_address_1=' . urlencode($order->get_shipping_address_1()) .
                                 '&customer_data__shipping_address_2=' . urlencode($order->get_shipping_address_2()) .
                                 '&customer_data__shipping_first_name=' . urlencode($order->get_shipping_first_name()) .
                                 '&customer_data__shipping_last_name=' . urlencode($order->get_shipping_last_name()) .
                                 '&customer_data__shipping_city=' . urlencode($order->get_shipping_city()) ;


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

    /* BEGIN OF HOOKS */
    function on_new_event_from_apurata() {
        // Still require validation
        global $woocommerce;

        $order_id = intval($_GET["order_id"]);
        $event = $_GET["event"];

        $order = wc_get_order( $order_id );

        if (!$order) {
            error_log('Orden no encontrada: ' . $order_id);
            return;
        }

        if ($event == 'approved' && $order->get_status() == 'pending') {
            $order->update_status('on-hold', __( 'Esperando validación de identidad del comprador', APURATA_TEXT_DOMAIN ));
            $woocommerce->cart->empty_cart();
        } else if ($event == 'validated') {
            $order->update_status('processing');
        } else if ($event == 'rejected') {
            $order->update_status('failed');
        } else if ($event == 'canceled') {
            $order->update_status('failed');
        } else {
            error_log('Evento no soportado: ' . $event);
        }
    }

    add_action( 'woocommerce_api_on_new_event_from_apurata', 'on_new_event_from_apurata' );
    /* END OF HOOKS */
}
?>