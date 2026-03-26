<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Acromidia_Webhook_Handler {

    /**
     * Processa o webhook recebido do Asaas.
     * Endpoint público: POST /acromidia/v1/webhook/asaas
     */
    public function handle( \WP_REST_Request $request ) {
        $body = $request->get_json_params();

        // Log para debug (remover em produção estável)
        error_log( '[Acromidia Webhook] Evento recebido: ' . wp_json_encode( $body ) );

        if ( empty( $body['event'] ) ) {
            return new \WP_REST_Response( [ 'error' => 'Evento ausente' ], 400 );
        }

        $event   = sanitize_text_field( $body['event'] );
        $payment = $body['payment'] ?? null;

        // Se o evento não possui dados de pagamento, ignoramos com sucesso (evita erro 400 no gateway)
        if ( ! $payment ) {
            error_log( "[Acromidia Webhook] Evento genérico recebido e ignorado: {$event}" );
            return new \WP_REST_Response( [ 'message' => 'Evento recebido e ignorado (sem dados de pagamento)' ], 200 );
        }

        // Identificar o cliente pelo customer ID do Asaas
        $customer_id = sanitize_text_field( $payment['customer'] ?? '' );
        if ( empty( $customer_id ) ) {
            return new \WP_REST_Response( [ 'error' => 'Customer ID ausente' ], 400 );
        }

        // Buscar o post do cliente pelo meta _acro_gateway_customer_id
        $client_post = $this->find_client_by_asaas_id( $customer_id );

        if ( ! $client_post ) {
            error_log( "[Acromidia Webhook] Cliente não encontrado para Asaas ID: {$customer_id}" );
            return new \WP_REST_Response( [ 'message' => 'Cliente não encontrado, evento ignorado' ], 200 );
        }

        $client_name = $client_post->post_title;
        $client_phone = get_post_meta( $client_post->ID, '_acro_phone', true );

        switch ( $event ) {
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                $this->handle_payment_confirmed( $client_post->ID, $client_name, $client_phone, $body['payment'] );
                break;

            case 'PAYMENT_OVERDUE':
                $this->handle_payment_overdue( $client_post->ID, $client_name, $client_phone, $payment );
                break;

            case 'PAYMENT_CREATED':
                // Apenas loga — pode ser expandido no futuro
                error_log( "[Acromidia Webhook] Cobrança criada para {$client_name}" );
                break;

            default:
                error_log( "[Acromidia Webhook] Evento não tratado: {$event}" );
                break;
        }

        return new \WP_REST_Response( [ 'message' => 'Webhook processado' ], 200 );
    }

    /**
     * Valida o webhook via accessToken no header (configurável no Asaas).
     */
    public function verify_webhook( \WP_REST_Request $request ) {
        $webhook_token = Acromidia_Settings::get( 'asaas_webhook_token' );

        // Se não configurou token, aceita tudo (desenvolvimento)
        if ( empty( $webhook_token ) ) {
            return true;
        }

        $received_token = $request->get_header( 'asaas-access-token' );
        return hash_equals( $webhook_token, $received_token ?? '' );
    }

    /**
     * Pagamento confirmado: atualiza status e envia WhatsApp de confirmação.
     */
    private function handle_payment_confirmed( $post_id, $client_name, $client_phone, $payment ) {
        update_post_meta( $post_id, '_acro_status', 'ativo' );
        update_post_meta( $post_id, '_acro_last_payment', current_time( 'mysql' ) );

        // --- INTEGRAÇÃO COM FLUXO DE CAIXA (ERP) ---
        // Registra a entrada real baseada no pagamento recebido do Asaas
        $payment_id = $payment['id'] ?? '';
        $amount     = floatval( $payment['value'] ?? 0 );
        $pay_date   = $payment['confirmedDate'] ?? $payment['paymentDate'] ?? current_time('Y-m-d');
        $desc       = !empty($payment['description']) ? $payment['description'] : "Recebimento Asaas #{$payment_id}";

        if ( ! empty( $payment_id ) ) {
            // Verifica duplicidade
            $existing = get_posts([
                'post_type'  => 'acro_transaction',
                'meta_key'   => '_acro_asaas_id',
                'meta_value' => $payment_id,
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ]);

            if ( empty( $existing ) ) {
                $trans_id = wp_insert_post([
                    'post_type'   => 'acro_transaction',
                    'post_title'  => sanitize_text_field( $desc ),
                    'post_status' => 'publish'
                ]);
                if ( ! is_wp_error( $trans_id ) ) {
                    update_post_meta( $trans_id, '_acro_amount', $amount );
                    update_post_meta( $trans_id, '_acro_type', 'income' );
                    update_post_meta( $trans_id, '_acro_category', 'Vendas' );
                    update_post_meta( $trans_id, '_acro_date', sanitize_text_field( $pay_date ) );
                    update_post_meta( $trans_id, '_acro_asaas_id', $payment_id );
                    error_log( "[Acromidia Webhook] Lançamento financeiro automático criado: {$desc} (R$ {$amount})" );
                }
            }
        }
        
        $current_site_status = get_post_meta( $post_id, '_acro_site_status', true );
        if ( $current_site_status === 'blocked' ) {
            update_post_meta( $post_id, '_acro_site_status', 'active' );
            error_log( "[Acromidia Webhook] Pagamento compensado para {$client_name}. Site DESBLOQUEADO automaticamente." );
            
            wp_insert_post([
                'post_type'    => 'acro_log',
                'post_title'   => "[SISTEMA] Desbloqueio Técnico (Pagamento) - {$client_name}",
                'post_content' => "Status do site normalizado automaticamente de BLOCKED para ACTIVE devido à compensação de fatura no Asaas.",
                'post_status'  => 'publish'
            ]);
        }

        error_log( "[Acromidia Webhook] Pagamento confirmado para {$client_name}. Status → ativo." );

        // Dispara WhatsApp de confirmação
        if ( ! empty( $client_phone ) ) {
            $wa = new Acromidia_WhatsApp_API();
            $sent = $wa->send_confirmation_message( $client_phone, $client_name );
            error_log( "[Acromidia Webhook] WhatsApp confirmação para {$client_name}: " . ( $sent ? 'ENVIADO' : 'FALHOU' ) );
        }
    }

    /**
     * Pagamento atrasado: atualiza status e envia cobrança por WhatsApp.
     */
    private function handle_payment_overdue( $post_id, $client_name, $client_phone, $payment ) {
        update_post_meta( $post_id, '_acro_status', 'inadimplente' );

        error_log( "[Acromidia Webhook] Pagamento atrasado para {$client_name}. Status → inadimplente." );

        // Tenta buscar Pix copia-e-cola e enviar cobrança
        if ( ! empty( $client_phone ) && ! empty( $payment['id'] ) ) {
            $asaas = new Acromidia_Asaas_API();
            $pix_data = $asaas->get_payment_pix_qrcode( $payment['id'] );

            $pix_copy_paste = $pix_data['payload'] ?? 'Indisponível';
            $invoice_url    = $payment['invoiceUrl'] ?? $payment['bankSlipUrl'] ?? '';

            $wa = new Acromidia_WhatsApp_API();
            $sent = $wa->send_billing_message( $client_phone, $client_name, $pix_copy_paste, $invoice_url );
            error_log( "[Acromidia Webhook] WhatsApp cobrança para {$client_name}: " . ( $sent ? 'ENVIADO' : 'FALHOU' ) );
        }
    }

    /**
     * Busca um post acro_client pelo _acro_gateway_customer_id.
     */
    private function find_client_by_asaas_id( $asaas_id ) {
        $query = new \WP_Query( [
            'post_type'      => 'acro_client',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_acro_gateway_customer_id',
                    'value' => $asaas_id,
                ]
            ],
        ] );

        if ( $query->have_posts() ) {
            return $query->posts[0];
        }

        return null;
    }
}
