<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Acromidia_Asaas_API implements Acromidia_Gateway_Interface {
    private $api_key;
    private $base_url;

    public function __construct() {
        $this->api_key  = Acromidia_Settings::get( 'asaas_api_key' );
        $mode           = Acromidia_Settings::get( 'asaas_mode' );
        $this->base_url = ( $mode === 'sandbox' ) ? 'https://sandbox.asaas.com/api/v3' : 'https://api.asaas.com/v3';
    }

    public function request( $endpoint, $method = 'GET', $body = [] ) {
        $url = $this->base_url . $endpoint;
        
        $args = [
            'method'  => $method,
            'headers' => [
                'access_token' => $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 45,
        ];

        if ( ! empty( $body ) ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => true, 'message' => $response->get_error_message() ];
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $body          = wp_remote_retrieve_body( $response );
        $data          = json_decode( $body, true );

        if ( $response_code >= 400 || is_null( $data ) ) {
            error_log( "AcroManager - Erro na API Asaas ($response_code): " . $body );
            return [ 'error' => true, 'message' => "Erro HTTP $response_code", 'raw' => $body ];
        }

        if (strpos($endpoint, '/customers') !== false) {
            error_log("AcroManager Debug - /customers response: " . substr($body, 0, 500));
        }

        return $data;
    }

    public function create_customer( $name, $cpfCnpj, $email, $phone ) {
        $data = [
            'name'                 => $name,
            'cpfCnpj'              => $cpfCnpj,
            'email'                => $email,
            'phone'                => $phone,
            'notificationDisabled' => false
        ];

        return $this->request( '/customers', 'POST', $data );
    }

    public function update_customer( $customer_id, $data ) {
        return $this->request( '/customers/' . $customer_id, 'POST', $data );
    }

    public function create_subscription( $customer_id, $value, $next_due_date, $description ) {
        $data = [
            'customer'    => $customer_id,
            'billingType' => 'PIX', // Padrão moderno e rápido
            'value'       => $value,
            'nextDueDate' => $next_due_date,
            'cycle'       => 'MONTHLY',
            'description' => $description
        ];

        return $this->request( '/subscriptions', 'POST', $data );
    }

    public function get_customer( $customer_id ) {
        return $this->request( '/customers/' . $customer_id );
    }

    public function get_payment_pix_qrcode( $payment_id ) {
        return $this->request( '/payments/' . $payment_id . '/pixQrCode' );
    }

    public function list_payments( $customer_id, $status = '' ) {
        $url = '/payments?customer=' . $customer_id;
        if ( ! empty( $status ) ) {
            $url .= '&status=' . $status;
        }
        return $this->request( $url );
    }

    public function get_balance() {
        return $this->request( '/finance/balance' );
    }

    public function list_customers( $limit = 100, $offset = 0 ) {
        return $this->request( "/customers?limit={$limit}&offset={$offset}" );
    }

    public function list_overdue_payments() {
        return $this->request( '/payments?status=OVERDUE&limit=100' );
    }

    public function get_pending_payments_by_date($date) {
        return $this->request("/payments?status=PENDING&dueDate[ge]={$date}&dueDate[le]={$date}");
    }
}
