<?php

namespace Woompesa\Webhook;

use WP_REST_Request;
use WP_REST_Response;
use Woompesa\Orders\OrderFinder;
use Woompesa\Orders\OrderMeta;
use Woompesa\Orders\OrderStateMachine;
use Woompesa\Support\Logger;

defined( 'ABSPATH' ) || exit;

class CallbackController {
	const ROUTE_NAMESPACE = 'woompesa/v1';
	const ROUTE_PATH      = '/callback';

	public static function register_hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			self::ROUTE_PATH,
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_callback' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function handle_callback( WP_REST_Request $request ) {
		$payload = self::normalize_payload( $request->get_json_params() ?: $request->get_body_params() );

		Logger::info(
			'Received M-Pesa callback payload.',
			array(
				'payload' => $payload,
			)
		);

		$order = ( new OrderFinder() )->find_by_provider_identifiers( $payload );

		if ( ! $order ) {
			return new WP_REST_Response(
				array(
					'status'  => 'not_found',
					'message' => __( 'No matching order was found for the callback payload.', 'woompesa' ),
				),
				404
			);
		}

		$order->update_meta_data( OrderMeta::CALLBACK_RECEIVED_AT, gmdate( 'c' ) );
		$order->save();

		$state = ( new OrderStateMachine() )->apply_provider_result( $order, $payload, 'callback' );

		return new WP_REST_Response(
			array(
				'output_OriginalConversationID' => (string) ( $payload['original_conversation_id'] ?: $payload['conversation_id'] ),
				'output_ResponseCode'           => '0',
				'output_ResponseDesc'           => 'Successfully Accepted Result',
				'output_ThirdPartyConversationID' => (string) $payload['third_party_conversation_id'],
				'status'                        => 'accepted',
				'order_id'                      => $order->get_id(),
				'state'                         => $state,
			),
			202
		);
	}

	public static function get_callback_url() {
		return rest_url( self::ROUTE_NAMESPACE . self::ROUTE_PATH );
	}

	private static function normalize_payload( $payload ) {
		$payload = is_array( $payload ) ? $payload : array();
		$output  = self::extract_nested_payload( $payload, array( 'output', 'body', 'data', 'Result', 'result' ) );
		$input   = self::extract_nested_payload( $payload, array( 'input', 'request', 'Request', 'requestBody' ) );
		$source  = array_merge( $payload, $output, $input );

		return array(
			'order_id'                    => $source['order_id'] ?? '',
			'code'                        => (string) ( $source['output_ResponseCode'] ?? $source['ResponseCode'] ?? $source['response_code'] ?? $source['code'] ?? '' ),
			'message'                     => (string) ( $source['output_ResponseDesc'] ?? $source['ResponseDesc'] ?? $source['response_description'] ?? $source['description'] ?? $source['message'] ?? '' ),
			'transaction_id'              => (string) ( $source['output_TransactionID'] ?? $source['transaction_id'] ?? $source['output_OriginalTransactionID'] ?? $source['OriginalTransactionID'] ?? '' ),
			'original_transaction_id'     => (string) ( $source['input_OriginalTransactionID'] ?? $source['original_transaction_id'] ?? $source['output_OriginalTransactionID'] ?? '' ),
			'original_conversation_id'    => (string) ( $source['input_OriginalConversationID'] ?? $source['original_conversation_id'] ?? '' ),
			'conversation_id'             => (string) ( $source['output_ConversationID'] ?? $source['conversation_id'] ?? $source['ConversationID'] ?? '' ),
			'third_party_reference'       => (string) ( $source['input_ThirdPartyConversationID'] ?? $source['input_ThirdPartyReference'] ?? $source['third_party_reference'] ?? '' ),
			'third_party_conversation_id' => (string) ( $source['output_ThirdPartyConversationID'] ?? $source['third_party_conversation_id'] ?? $source['input_ThirdPartyConversationID'] ?? $source['input_ThirdPartyReference'] ?? '' ),
			'transaction_reference'       => (string) ( $source['input_TransactionReference'] ?? $source['transaction_reference'] ?? $source['TransactionReference'] ?? '' ),
			'transaction_status'          => (string) ( $source['output_ResponseTransactionStatus'] ?? $source['transaction_status'] ?? $source['TransactionStatus'] ?? $source['status'] ?? '' ),
			'reversed'                    => (string) ( $source['input_Reversed'] ?? $source['output_Reversed'] ?? $source['reversed'] ?? '' ),
			'raw'                         => $payload,
		);
	}

	private static function extract_nested_payload( $payload, $keys ) {
		foreach ( $keys as $key ) {
			if ( ! empty( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
				return $payload[ $key ];
			}
		}

		return array();
	}
}
