<?php

namespace Woompesa\Api;

use RuntimeException;
use Woompesa\Support\Crypto;

defined( 'ABSPATH' ) || exit;

class Credentials {
	/**
	 * @var array<string, mixed>
	 */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	public function is_sandbox() {
		return 'yes' === ( $this->settings['sandbox_mode'] ?? 'yes' );
	}

	public function get_environment_label() {
		return $this->is_sandbox() ? 'sandbox' : 'live';
	}

	public function get_api_key() {
		return $this->value_for_mode( 'sandbox_api_key', 'live_api_key' );
	}

	public function get_public_key() {
		return $this->value_for_mode( 'sandbox_public_key', 'live_public_key' );
	}

	public function get_service_provider_code() {
		return $this->value_for_mode( 'sandbox_service_provider_code', 'live_service_provider_code' );
	}

	public function get_third_party_conversation_id() {
		return $this->value_for_mode( 'sandbox_third_party_conversation_id', 'live_third_party_conversation_id' );
	}

	public function get_origin() {
		return $this->value_for_mode( 'sandbox_origin', 'live_origin' );
	}

	public function get_api_host() {
		return $this->value_for_mode( 'sandbox_api_host', 'live_api_host' );
	}

	public function get_country_code() {
		return 'TZN';
	}

	public function get_currency_code() {
		return 'TZS';
	}

	public function get_base_url() {
		$host = $this->get_api_host();
		$path = $this->is_sandbox() ? '/sandbox/ipg/v2/vodacomTZN/' : '/openapi/ipg/v2/vodacomTZN/';

		return 'https://' . untrailingslashit( $host ) . $path;
	}

	public function is_complete() {
		return '' !== $this->get_api_key()
			&& '' !== $this->get_public_key()
			&& '' !== $this->get_service_provider_code()
			&& '' !== $this->get_api_host();
	}

	public function get_encrypted_api_key() {
		return Crypto::encrypt_bearer_token( $this->get_api_key(), $this->get_public_key() );
	}

	public function get_encrypted_session_id( $session_id ) {
		return Crypto::encrypt_bearer_token( $session_id, $this->get_public_key() );
	}

	private function value_for_mode( $sandbox_key, $live_key ) {
		$key = $this->is_sandbox() ? $sandbox_key : $live_key;

		return trim( (string) ( $this->settings[ $key ] ?? '' ) );
	}
}
