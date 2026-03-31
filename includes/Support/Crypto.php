<?php

namespace Woompesa\Support;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

class Crypto {
	public static function encrypt_bearer_token( $plain_text, $public_key ) {
		$plain_text = trim( (string) $plain_text );
		$public_key = trim( (string) $public_key );

		if ( '' === $plain_text || '' === $public_key ) {
			throw new RuntimeException( __( 'M-Pesa token encryption requires both a token value and a public key.', 'woompesa' ) );
		}

		$formatted_public_key = self::format_public_key( $public_key );
		$key_resource         = openssl_get_publickey( $formatted_public_key );

		if ( false === $key_resource ) {
			throw new RuntimeException( __( 'The configured M-Pesa public key could not be loaded.', 'woompesa' ) );
		}

		$encrypted = '';
		$success   = openssl_public_encrypt( $plain_text, $encrypted, $key_resource, OPENSSL_PKCS1_PADDING );

		if ( is_resource( $key_resource ) ) {
			openssl_free_key( $key_resource );
		}

		if ( ! $success ) {
			throw new RuntimeException( __( 'Failed to encrypt the M-Pesa authorization token.', 'woompesa' ) );
		}

		return 'Bearer ' . base64_encode( $encrypted );
	}

	private static function format_public_key( $public_key ) {
		if ( false !== strpos( $public_key, 'BEGIN PUBLIC KEY' ) ) {
			return $public_key;
		}

		return "-----BEGIN PUBLIC KEY-----\n" .
			wordwrap( preg_replace( '/\s+/', '', $public_key ), 64, "\n", true ) .
			"\n-----END PUBLIC KEY-----";
	}
}
