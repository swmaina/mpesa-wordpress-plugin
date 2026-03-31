<?php

namespace Woompesa\Http;

use WP_Error;

defined( 'ABSPATH' ) || exit;

class Transport {
	public function request( $method, $url, $headers = array(), $body = null ) {
		$args = array(
			'method'      => strtoupper( (string) $method ),
			'headers'     => is_array( $headers ) ? $headers : array(),
			'timeout'     => 70,
			'redirection' => 0,
			'sslverify'   => true,
		);

		if ( null !== $body ) {
			$args['body'] = is_string( $body ) ? $body : wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$raw_body    = (string) wp_remote_retrieve_body( $response );
		$decoded     = json_decode( $raw_body, true );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_message = sprintf(
				/* translators: %d is an HTTP status code. */
				__( 'M-Pesa API request failed with HTTP %d.', 'woompesa' ),
				$status_code
			);

			if ( is_array( $decoded ) ) {
				$provider_message = (string) ( $decoded['output_ResponseDesc'] ?? $decoded['error'] ?? '' );

				if ( '' !== $provider_message ) {
					$error_message .= ' ' . $provider_message;
				}
			}

			return new WP_Error(
				'woompesa_http_error',
				$error_message,
				array(
					'status_code' => $status_code,
					'body'        => $decoded ?: $raw_body,
				)
			);
		}

		return array(
			'status_code' => $status_code,
			'headers'     => wp_remote_retrieve_headers( $response ),
			'body'        => is_array( $decoded ) ? $decoded : array(),
			'raw_body'    => $raw_body,
		);
	}
}
