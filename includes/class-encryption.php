<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Criptografia AES-256-CBC para armazenamento seguro de chaves de API.
 * Usa AUTH_KEY do WordPress como base para derivação da chave.
 */
class Acromidia_Encryption {

    private static $cipher = 'aes-256-cbc';

    /**
     * Deriva a chave de criptografia a partir do AUTH_KEY do WordPress.
     */
    private static function get_key() {
        $salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'acromidia-fallback-key-change-me';
        return hash( 'sha256', $salt, true );
    }

    /**
     * Criptografa um valor texto.
     * Retorna: base64( IV . ciphertext )
     */
    public static function encrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $key     = self::get_key();
        $iv_len  = openssl_cipher_iv_length( self::$cipher );
        $iv      = openssl_random_pseudo_bytes( $iv_len );
        $cipher  = openssl_encrypt( $value, self::$cipher, $key, OPENSSL_RAW_DATA, $iv );

        if ( $cipher === false ) {
            return '';
        }

        // Armazena IV + ciphertext codificados em base64
        return base64_encode( $iv . $cipher );
    }

    /**
     * Descriptografa um valor previamente criptografado.
     */
    public static function decrypt( $encrypted ) {
        if ( empty( $encrypted ) ) {
            return '';
        }

        $key     = self::get_key();
        $data    = base64_decode( $encrypted, true );
        $iv_len  = openssl_cipher_iv_length( self::$cipher );

        if ( $data === false || strlen( $data ) <= $iv_len ) {
            return $encrypted; // Legacy Plain Text fallback
        }

        $iv         = substr( $data, 0, $iv_len );
        $ciphertext = substr( $data, $iv_len );
        $decrypted  = openssl_decrypt( $ciphertext, self::$cipher, $key, OPENSSL_RAW_DATA, $iv );

        return $decrypted !== false ? $decrypted : $encrypted;
    }
}
