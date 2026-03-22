<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Acromidia_PagBank_API implements Acromidia_Gateway_Interface {
    private $api_key;
    private $base_url = 'https://api.pagseguro.com';

    public function __construct() {
        $this->api_key = Acromidia_Settings::get( 'pagbank_api_key' );
        $mode          = Acromidia_Settings::get( 'pagbank_mode' );
        $this->base_url = ( $mode === 'sandbox' ) ? 'https://sandbox.api.pagseguro.com' : 'https://api.pagseguro.com';
    }

    public function request( $endpoint, $method = 'GET', $body = [] ) {
        $url = $this->base_url . $endpoint;
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ],
            'timeout' => 45,
        ];

        if ( ! empty( $body ) ) {
            $args['body'] = json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );
        if ( is_wp_error( $response ) ) return [ 'error' => true, 'message' => $response->get_error_message() ];
        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    public function create_customer( $name, $cpf_cnpj, $email, $phone ) {
        $cpf_cnpj = preg_replace('/\D/', '', $cpf_cnpj);
        $phone = preg_replace('/\D/', '', $phone);
        
        $data = [
            'name'  => $name,
            'email' => $email,
            'tax_id' => $cpf_cnpj,
            'phones' => [
                [
                    'country' => '55',
                    'area' => substr($phone, 0, 2) ?? '11',
                    'number' => substr($phone, 2) ?? '900000000',
                    'type' => 'MOBILE'
                ]
            ]
        ];

        return $this->request( '/customers', 'POST', $data );
    }

    public function update_customer( $customer_id, $data ) {
         return $this->request( '/customers/' . $customer_id, 'PUT', $data );
    }

    public function create_subscription( $customer_id, $value, $next_due_date, $description ) {
        // PagBank usually generates orders with recurring billing via Pre-Approval
        return $this->request( '/recurring/plans', 'POST', [
            'reference_id' => 'acro_' . uniqid(),
            'name' => $description,
            'amount' => [
                'value' => intval($value * 100),
                'currency' => 'BRL'
            ],
            'payment_method' => ['type' => 'BOLETO'],
            'customer' => ['id' => $customer_id]
        ]);
    }

    public function list_payments( $customer_id ) {
        // Obter charges/orders do cliente
        $res = $this->request( '/orders?customer_id=' . $customer_id );
        $data = [];
        if(!empty($res['orders'])) {
            foreach($res['orders'] as $ord) {
                // Paid, Waiting, Canceled, Declined
                $status = 'PENDING';
                $st = strtolower($ord['charges'][0]['status'] ?? 'waiting');
                if($st === 'paid') $status = 'RECEIVED';
                if($st === 'canceled' || $st === 'declined') $status = 'OVERDUE';
                
                $data[] = [
                    'id' => $ord['id'],
                    'status' => $status,
                    'customer' => $customer_id,
                    'invoiceUrl' => $ord['charges'][0]['links'][0]['href'] ?? ''
                ];
            }
        }
        return ['data' => $data];
    }

    public function list_overdue_payments() {
        return ['data' => []]; // Simplified - PagSeguro requires iterating orders
    }

    public function get_balance() {
        // Pelo endpoint unificado de balances
        $res = $this->request( '/balances' );
        return ['balance' => ($res['available']['value'] ?? 0) / 100];
    }

    public function list_customers( $limit = 100, $offset = 0 ) {
        return $this->request( "/customers?limit={$limit}" );
    }

    public function get_payment_pix_qrcode( $payment_id ) {
        $res = $this->request('/orders/' . $payment_id);
        $qr = $res['charges'][0]['payment_method']['pix']['qr_codes'][0]['text'] ?? 'Código PIX Indisponível';
        return ['payload' => $qr];
    }

    public function get_pending_payments_by_date( $date ) {
        return ['data' => []];
    }
}
