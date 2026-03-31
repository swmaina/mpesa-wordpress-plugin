<?php

namespace Woompesa\Api;

defined( 'ABSPATH' ) || exit;

class ResponseParser {
	public function parse_session_response( $response ) {
		$body = is_array( $response['body'] ?? null ) ? $response['body'] : array();

		return array(
			'code'        => (string) ( $body['output_ResponseCode'] ?? '' ),
			'description' => (string) ( $body['output_ResponseDesc'] ?? '' ),
			'session_id'  => (string) ( $body['output_SessionID'] ?? '' ),
			'raw'         => $body,
		);
	}

	public function parse_c2b_response( $response ) {
		$body = is_array( $response['body'] ?? null ) ? $response['body'] : array();

		return array(
			'code'                         => (string) ( $body['output_ResponseCode'] ?? '' ),
			'message'                      => (string) ( $body['output_ResponseDesc'] ?? '' ),
			'transaction_id'               => (string) ( $body['output_TransactionID'] ?? '' ),
			'conversation_id'              => (string) ( $body['output_ConversationID'] ?? '' ),
			'third_party_conversation_id'  => (string) ( $body['output_ThirdPartyConversationID'] ?? '' ),
			'raw'                          => $body,
		);
	}

	public function parse_query_response( $response ) {
		$body = is_array( $response['body'] ?? null ) ? $response['body'] : array();

		return array(
			'code'                        => (string) ( $body['output_ResponseCode'] ?? '' ),
			'message'                     => (string) ( $body['output_ResponseDesc'] ?? '' ),
			'transaction_status'          => (string) ( $body['output_ResponseTransactionStatus'] ?? '' ),
			'transaction_id'              => (string) ( $body['output_OriginalTransactionID'] ?? $body['output_TransactionID'] ?? '' ),
			'original_transaction_id'     => (string) ( $body['output_OriginalTransactionID'] ?? '' ),
			'conversation_id'             => (string) ( $body['output_ConversationID'] ?? '' ),
			'third_party_conversation_id' => (string) ( $body['output_ThirdPartyConversationID'] ?? '' ),
			'reversed'                    => (string) ( $body['output_Reversed'] ?? '' ),
			'raw'                         => $body,
		);
	}
}
