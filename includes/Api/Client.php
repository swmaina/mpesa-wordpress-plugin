<?php

namespace Woompesa\Api;

use Exception;
use WP_Error;
use Woompesa\Http\Transport;
use Woompesa\Support\Logger;

defined( 'ABSPATH' ) || exit;

class Client {
	/**
	 * @var Credentials
	 */
	private $credentials;

	/**
	 * @var Transport
	 */
	private $transport;

	/**
	 * @var RequestBuilder
	 */
	private $request_builder;

	/**
	 * @var ResponseParser
	 */
	private $response_parser;

	public function __construct( Credentials $credentials ) {
		$this->credentials     = $credentials;
		$this->transport       = new Transport();
		$this->request_builder = new RequestBuilder();
		$this->response_parser = new ResponseParser();
	}

	public function initiate_c2b_payment( $payload ) {
		if ( ! $this->credentials->is_complete() ) {
			return new WP_Error(
				'woompesa_missing_credentials',
				__( 'M-Pesa gateway credentials are incomplete. Please review the plugin settings.', 'woompesa' )
			);
		}

		try {
			$session = $this->create_session( true );
		} catch ( Exception $exception ) {
			Logger::error(
				'Failed to create M-Pesa session.',
				array(
					'message' => $exception->getMessage(),
				)
			);

			return new WP_Error( 'woompesa_session_error', $exception->getMessage() );
		}

		$request = $this->request_builder->build_c2b_request( $this->credentials, $session['session_id'], $payload );

		Logger::info(
			'Sending C2B payment request.',
			array(
				'environment' => $this->credentials->get_environment_label(),
				'order_id'    => $payload['order_id'],
				'url'         => $request['url'],
			)
		);

		$response = $this->transport->request( $request['method'], $request['url'], $request['headers'], $request['body'] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$parsed = $this->response_parser->parse_c2b_response( $response );

		$parsed['session_id'] = $session['session_id'];

		return $parsed;
	}

	public function query_transaction_status( $payload ) {
		if ( ! $this->credentials->is_complete() ) {
			return new WP_Error(
				'woompesa_missing_credentials',
				__( 'M-Pesa gateway credentials are incomplete. Please review the plugin settings.', 'woompesa' )
			);
		}

		try {
			$session = $this->create_session( true );
		} catch ( Exception $exception ) {
			return new WP_Error( 'woompesa_session_error', $exception->getMessage() );
		}

		$request = $this->request_builder->build_query_request( $this->credentials, $session['session_id'], $payload );
		$response = $this->transport->request( $request['method'], $request['url'], $request['headers'], $request['body'] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$parsed = $this->response_parser->parse_query_response( $response );
		$parsed['session_id'] = $session['session_id'];

		return $parsed;
	}

	private function create_session( $wait_until_live = false ) {
		$request = $this->request_builder->build_session_request( $this->credentials );

		Logger::info(
			'Requesting M-Pesa session.',
			array(
				'environment' => $this->credentials->get_environment_label(),
				'url'         => $request['url'],
			)
		);

		$response = $this->transport->request( $request['method'], $request['url'], $request['headers'], $request['body'] );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$parsed = $this->response_parser->parse_session_response( $response );

		if ( 'INS-0' !== $parsed['code'] || '' === $parsed['session_id'] ) {
			throw new Exception(
				sprintf(
					/* translators: %s is the provider response description. */
					__( 'M-Pesa session request failed: %s', 'woompesa' ),
					$parsed['description'] ?: __( 'unknown error', 'woompesa' )
				)
			);
		}

		if ( $wait_until_live ) {
			// The official sample code notes the SessionID may take up to 30 seconds to become live.
			sleep( 30 );
		}

		return $parsed;
	}
}
