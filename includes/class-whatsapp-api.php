<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Acromidia_WhatsApp_API {
    private $token;
    private $phone_id;

    public function __construct() {
        $this->token    = Acromidia_Settings::get( 'wa_token' );
        $this->phone_id = Acromidia_Settings::get( 'wa_phone_id' );
    }

    private function log_dispatch( $number, $client_name, $type, $is_success ) {
        $status = $is_success ? 'SUCESSO' : 'FALHA';
        $num_fmt = preg_replace( '/\D/', '', $number );
        $num_fmt = (strlen($num_fmt) == 11) ? '(' . substr($num_fmt, 0, 2) . ') ' . substr($num_fmt, 2, 5) . '-' . substr($num_fmt, 7) : $num_fmt;

        wp_insert_post([
            'post_type'    => 'acro_log',
            'post_title'   => "[{$status}] {$type} - {$client_name}",
            'post_content' => "Enviado para: {$num_fmt}\nTipo: {$type}\nStatus Técnico: " . ($is_success ? '200 OK' : 'Erro na Graph API'),
            'post_status'  => 'publish'
        ]);
    }

    public function send_billing_message( $to_number, $client_name, $pix_copy_paste, $invoice_url, $reminder_type = 'manual' ) {
        $url = "https://graph.facebook.com/v17.0/{$this->phone_id}/messages";
        
        // Formatando mensagem amigável
        $text = "Olá, *{$client_name}*! Tudo bem?\n\n";
        
        if ( $reminder_type === '5_days_before' ) {
            $text .= "Sua fatura da Acromidia já está disponível! Passando para lembrar que ela vencerá nos próximos *5 dias*.\n\n";
        } elseif ( $reminder_type === 'today' ) {
            $text .= "Hoje é o dia do vencimento da sua fatura da Acromidia. Fique em dia para evitar juros!\n\n";
        } elseif ( $reminder_type === '2_days_after' ) {
            $text .= "Notamos que o pagamento da sua fatura ainda não compensou em nosso sistema.\n\n";
        } elseif ( $reminder_type === '7_days_after' ) {
            $text .= "Até o momento não identificamos o repasse do seu pagamento. Para evitar o bloqueio dos serviços, pedimos que regularize a situação.\n\n";
        } elseif ( $reminder_type === '15_days_after' ) {
            $text .= "⚠️ *Aviso Importante:*\nDevido à falta de compensação da sua fatura, seus serviços online entrarão em *Suspensão Técnica* preventivamente hoje. Para restabelecer imediatamente, efetue o pagamento abaixo.\n\n";
        } else {
            $text .= "Sua mensalidade da Acromidia está disponível para pagamento.\n\n";
        }
        
        $text .= "🧾 *Fatura:* {$invoice_url}\n\n";
        $text .= "Pix Copia e Cola abaixo:\n";
        $text .= "```{$pix_copy_paste}```\n\n";
        $text .= "Qualquer dúvida, estamos à disposição!";

        $body = [
            'messaging_product' => 'whatsapp',
            'to'                => $to_number,
            'type'              => 'text',
            'text'              => [
                'preview_url' => true,
                'body'        => $text
            ]
        ];

        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ];

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $success = ( $response_code === 200 || $response_code === 201 );
        
        $type_label = 'Cobrança Manual';
        if ( $reminder_type === '5_days_before' ) $type_label = 'Lembrete (5 Dias Antes)';
        elseif ( $reminder_type === 'today' ) $type_label = 'Lembrete (Vence Hoje)';
        elseif ( $reminder_type === '2_days_after' ) $type_label = 'Cobrança (2 Dias de Atraso)';
        elseif ( $reminder_type === '7_days_after' ) $type_label = 'Aviso de Bloqueio (7 Dias)';
        elseif ( $reminder_type === '15_days_after' ) $type_label = 'Suspensão de Serviço (15 Dias)';
        
        $this->log_dispatch( $to_number, $client_name, $type_label, $success );
        
        return $success;
    }

    public function send_confirmation_message( $to_number, $client_name ) {
        $url = "https://graph.facebook.com/v17.0/{$this->phone_id}/messages";

        $text = "✅ *Pagamento Confirmado!*\n\n";
        $text .= "Olá, *{$client_name}*!\n\n";
        $text .= "Recebemos seu pagamento com sucesso. Sua mensalidade está em dia.\n\n";
        $text .= "Obrigado por confiar na Acromidia! 🚀";

        $body = [
            'messaging_product' => 'whatsapp',
            'to'                => $to_number,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $text
            ]
        ];

        $args = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ];

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $success = ( $response_code === 200 || $response_code === 201 );
        
        $this->log_dispatch( $to_number, $client_name, 'Recibo / Confirmação', $success );
        
        return $success;
    }
}
