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

// Domain to use in browser
$APURATA_DOMAIN = getenv('APURATA_DOMAIN');
// Domain to use in API calls.
// In docker, there is a special IP for the host network.
// We use this IP to access the local apurata server from inside the container.
$APURATA_API_DOMAIN = getenv('APURATA_API_DOMAIN');

if ($APURATA_DOMAIN == false) {
    $APURATA_DOMAIN = 'https://apurata.com';
}
if ($APURATA_API_DOMAIN == false) {
    $APURATA_API_DOMAIN = $APURATA_DOMAIN;
}

// Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function init_wc_apurata_payment_gateway() {
        class WC_Apurata_Payment_Gateway extends WC_Payment_Gateway {

            public function __construct() {
                $this->id = 'apurata';

                $this->title = __('Apurata - Cuotas quincenales', APURATA_TEXT_DOMAIN);
                $this->description = '<img style="max-width: 450px" width="100%" src="https://static.apurata.com/img/steps-explanation.png"/>';
                $this->icon = 'https://static.apurata.com/img/logo-dark.svg';
                $this->has_fields = FALSE;

                // Shown in the admin panel:
                $this->method_title = 'Apurata';
                $this->method_description = __('Evalúa a tus clientes y financia su compra con cuotas quincenales', APURATA_TEXT_DOMAIN);

                $this->init_form_fields();
                $this->init_settings();

                // Get settings, e.g.
                $this->client_id = $this->get_option( 'client_id' );
                $this->allow_http = $this->get_option( 'allow_http' );
                $this->secret_token = $this->get_option( 'secret_token' );

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

                add_filter( 'woocommerce_available_payment_gateways', array( $this, 'hide_apurata_gateway' ) );
                add_action( 'woocommerce_api_on_new_event_from_apurata', array($this, 'on_new_event_from_apurata') );
            }

            function get_landing_confing() {
                if (!$this->landing_config) {
                    $ch = curl_init();

                    global $APURATA_API_DOMAIN;
                    $url = $APURATA_API_DOMAIN .
                             '/pos/client/landing_config';
                    curl_setopt($ch, CURLOPT_URL, $url);

                    $headers = array("Authorization: Bearer " . $this->secret_token);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $landing_config = curl_exec($ch);
                    $landing_config = json_decode($landing_config);
                    curl_close($ch);
                    $this->landing_config = $landing_config;
                }
                return $this->landing_config;
            }

            function hide_apurata_gateway( $gateways ) {
                /* Hide Apurata gateway based on some conditions. */
                $currency = get_woocommerce_currency();

                if ($this->allow_http == "no" && $_SERVER['REQUEST_SCHEME'] != 'https') {
                    error_log('Apurata solo soporta https');
                    unset( $gateways['apurata'] );
                }
                if( $currency != 'PEN' ){
                    //disable gateway paypal if currency is ABC
                    error_log('Apurata sólo soporta currency=PEN. Currency actual=' . $currency);
                    unset( $gateways['apurata'] );
                }

                $landing_config = $this->get_landing_confing();
                if ($landing_config->min_amount > WC()->cart->total || $landing_config->max_amount < WC()->cart->total) {
                    error_log('Apurata no financia el monto del carrito: ' . WC()->cart->total);
                    unset( $gateways['apurata'] );
                }

                return $gateways;
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
                    'allow_http' => array
                    (
                        'title' => __('Habilitar HTTP', APURATA_TEXT_DOMAIN),
                        'type' => 'checkbox',
                        'label' => __('Habilitar HTTP (no seguro)', APURATA_TEXT_DOMAIN),
                        'default' => 'no'
                    ),
                    'client_id' => array
                    (
                        'title' => __('ID de Cliente', APURATA_TEXT_DOMAIN),
                        'type' => 'text',
                        'required' => true,
                        'description' => __('Para obtener este ID comunícate con nosotros al correo merchants@apurata.com', APURATA_TEXT_DOMAIN),
                        'default' => ''
                    ),
                    'secret_token' => array
                    (
                        'title' => __('Token Secreto', APURATA_TEXT_DOMAIN),
                        'type' => 'text',
                        'required' => true,
                        'description' => __('Para obtener este Token comunícate con nosotros al correo merchants@apurata.com', APURATA_TEXT_DOMAIN),
                        'default' => ''
                    ),
                );
            }

            function process_payment( $order_id ) {
                global $APURATA_DOMAIN;
                $order = wc_get_order( $order_id );

                $redirect_url = $APURATA_DOMAIN .
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

            /* BEGIN OF HOOKS */
            function on_new_event_from_apurata() {
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
            /* END OF HOOKS */
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