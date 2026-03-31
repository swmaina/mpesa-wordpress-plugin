<?php

namespace Woompesa\Checkout;

use Exception;
use WC_Order;
use WP_Error;
use Woompesa\Api\Client;
use Woompesa\Api\Credentials;
use Woompesa\Orders\OrderMeta;
use Woompesa\Orders\OrderStateMachine;

defined( 'ABSPATH' ) || exit;

class PaymentProcessor {
	/**
	 * @var WC_Order
	 */
	private $order;

	/**
	 * @var array<string, mixed>
	 */
	private $settings;

	public function __construct( WC_Order $order, $settings ) {
		$this->order    = $order;
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	public function initiate( $raw_phone ) {
		if ( ! Availability::currency_is_supported() ) {
			return new WP_Error(
				'woompesa_invalid_currency',
				__( 'M-Pesa Tanzania is only available when the store currency is Tanzanian Shilling (TZS).', 'woompesa' )
			);
		}

		$credentials = new Credentials( $this->settings );

		try {
			$phone = PhoneNormalizer::normalize_for_environment( $raw_phone, $credentials->is_sandbox() );
		} catch ( Exception $exception ) {
			return new WP_Error( 'woompesa_invalid_phone', $exception->getMessage() );
		}

		$third_party_reference = $this->build_third_party_reference();
		$transaction_reference = $this->build_transaction_reference();
		$client                = new Client( $credentials );
		$item_description      = $this->build_item_description();

		$response = $client->initiate_c2b_payment(
			array(
				'order_id'              => $this->order->get_id(),
				'amount'                => (int) round( (float) $this->order->get_total(), 0 ),
				'phone'                 => $phone,
				'third_party_reference' => $third_party_reference,
				'transaction_reference' => $transaction_reference,
				'item_description'      => $item_description,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$this->order->update_meta_data( OrderMeta::PHONE, $phone );
		$this->order->update_meta_data( OrderMeta::ENVIRONMENT, $credentials->get_environment_label() );
		$this->order->update_meta_data( OrderMeta::THIRD_PARTY_REFERENCE, $third_party_reference );
		$this->order->update_meta_data( OrderMeta::THIRD_PARTY_CONVERSATION_ID, (string) ( $response['third_party_conversation_id'] ?? $third_party_reference ) );
		$this->order->update_meta_data( OrderMeta::TRANSACTION_REFERENCE, $transaction_reference );
		$this->order->update_meta_data( OrderMeta::TRANSACTION_ID, (string) ( $response['transaction_id'] ?? '' ) );
		$this->order->update_meta_data( OrderMeta::CONVERSATION_ID, (string) ( $response['conversation_id'] ?? '' ) );
		$this->order->update_meta_data( OrderMeta::SESSION_ID, (string) ( $response['session_id'] ?? '' ) );
		$this->order->update_meta_data( OrderMeta::RESULT_CODE, (string) ( $response['code'] ?? '' ) );
		$this->order->update_meta_data( OrderMeta::RESULT_DESCRIPTION, (string) ( $response['message'] ?? '' ) );
		$this->order->update_meta_data( OrderMeta::RAW_INITIATE_RESPONSE, wp_json_encode( $response ) );
		$state = ( new OrderStateMachine() )->apply_provider_result( $this->order, $response, 'payment initiation' );

		if ( 'failed' === $state ) {
			return new WP_Error(
				'woompesa_payment_failed',
				$this->humanize_provider_error(
					(string) ( $response['code'] ?? '' ),
					(string) ( $response['message'] ?? '' )
				)
			);
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->order->get_checkout_order_received_url(),
		);
	}

	private function build_third_party_reference() {
		return substr( strtolower( bin2hex( random_bytes( 16 ) ) ), 0, 32 );
	}

	private function build_transaction_reference() {
		return substr( 'ORDER' . $this->order->get_id(), 0, 20 );
	}

	private function build_item_description() {
		$description = sprintf(
			/* translators: %d is the order number. */
			__( 'WooCommerce order %d', 'woompesa' ),
			$this->order->get_id()
		);

		return substr( $description, 0, 100 );
	}

	private function humanize_provider_error( $code, $message ) {
		$known_messages = array(
			'INS-5'    => __( 'The transaction was cancelled by the customer.', 'woompesa' ),
			'INS-6'    => __( 'The M-Pesa transaction failed. Please try again.', 'woompesa' ),
			'INS-9'    => __( 'The M-Pesa transaction timed out. Please try again.', 'woompesa' ),
			'INS-10'   => __( 'Another M-Pesa transaction is already in progress for this account. Please wait and try again.', 'woompesa' ),
			'INS-2001' => __( 'The entered M-Pesa PIN was incorrect.', 'woompesa' ),
			'INS-2006' => __( 'The M-Pesa wallet has insufficient balance for this payment.', 'woompesa' ),
			'INS-996'  => __( 'The customer M-Pesa account is not active.', 'woompesa' ),
		);

		if ( isset( $known_messages[ $code ] ) ) {
			return $known_messages[ $code ];
		}

		if ( '' !== $message ) {
			return sprintf(
				/* translators: %s is the provider error message. */
				__( 'M-Pesa payment failed: %s', 'woompesa' ),
				$message
			);
		}

		return __( 'The M-Pesa payment could not be completed.', 'woompesa' );
	}
}
