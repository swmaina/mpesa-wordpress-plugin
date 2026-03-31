<?php

namespace Woompesa\Api;

defined( 'ABSPATH' ) || exit;

class RequestBuilder {
	public function build_session_request( Credentials $credentials ) {
		return array(
			'method'  => 'GET',
			'url'     => $credentials->get_base_url() . 'getSession/',
			'headers' => array_filter(
				array(
					'Accept'        => 'application/json',
					'Authorization' => $credentials->get_encrypted_api_key(),
					'Origin'        => $credentials->get_origin(),
				)
			),
			'body'    => null,
		);
	}

	public function build_c2b_request( Credentials $credentials, $session_id, $payload ) {
		return array(
			'method'  => 'POST',
			'url'     => $credentials->get_base_url() . 'c2bPayment/singleStage/',
			'headers' => array_filter(
				array(
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
					'Authorization' => $credentials->get_encrypted_session_id( $session_id ),
					'Origin'        => $credentials->get_origin(),
				)
			),
			'body'    => array(
				'input_Amount'                   => (string) $payload['amount'],
				'input_Country'                  => $credentials->get_country_code(),
				'input_Currency'                 => $credentials->get_currency_code(),
				'input_CustomerMSISDN'           => $payload['phone'],
				'input_ServiceProviderCode'      => $credentials->get_service_provider_code(),
				'input_ThirdPartyConversationID' => $payload['third_party_reference'],
				'input_TransactionReference'     => $payload['transaction_reference'],
				'input_PurchasedItemsDesc'       => $payload['item_description'],
			),
		);
	}

	public function build_query_request( Credentials $credentials, $session_id, $payload ) {
		return array(
			'method'  => 'POST',
			'url'     => $credentials->get_base_url() . 'queryTransactionStatus/',
			'headers' => array_filter(
				array(
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
					'Authorization' => $credentials->get_encrypted_session_id( $session_id ),
					'Origin'        => $credentials->get_origin(),
				)
			),
			'body'    => array(
				'input_QueryReference'           => $payload['query_reference'],
				'input_ServiceProviderCode'      => $credentials->get_service_provider_code(),
				'input_ThirdPartyConversationID' => $payload['third_party_conversation_id'],
				'input_Country'                  => $credentials->get_country_code(),
			),
		);
	}
}
