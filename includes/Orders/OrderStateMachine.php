<?php

namespace Woompesa\Orders;

use WC_Order;

defined( 'ABSPATH' ) || exit;

class OrderStateMachine {
	public function apply_provider_result( WC_Order $order, $result, $source = 'provider' ) {
		$code        = (string) ( $result['code'] ?? '' );
		$message     = (string) ( $result['message'] ?? '' );
		$transaction = (string) ( $result['transaction_id'] ?? '' );

		$this->write_provider_meta( $order, $result );

		if ( $this->is_success( $code, $result, $source ) ) {
			if ( ! $order->is_paid() ) {
				$order->payment_complete( $transaction );
			}

			$order->update_meta_data( OrderMeta::STATUS, 'paid' );
			$order->add_order_note(
				sprintf(
					/* translators: %s is the provider source. */
					__( 'M-Pesa payment confirmed via %s.', 'woompesa' ),
					$source
				)
			);
			$order->save();

			return 'paid';
		}

		if ( $this->should_remain_pending( $code, $result, $source ) ) {
			if ( ! $order->has_status( 'on-hold' ) ) {
				$order->update_status( 'on-hold', __( 'M-Pesa payment is still pending confirmation.', 'woompesa' ) );
			}

			$order->update_meta_data( OrderMeta::STATUS, 'pending_confirmation' );
			$order->add_order_note(
				sprintf(
					/* translators: 1: provider source, 2: message */
					__( 'M-Pesa payment remains pending via %1$s: %2$s', 'woompesa' ),
					$source,
					$message ?: __( 'awaiting provider confirmation', 'woompesa' )
				)
			);
			$order->save();

			return 'pending_confirmation';
		}

		$order->update_meta_data( OrderMeta::STATUS, 'failed' );

		if ( ! $order->has_status( 'failed' ) ) {
			$order->update_status(
				'failed',
				sprintf(
					/* translators: %s is the provider message. */
					__( 'M-Pesa payment failed: %s', 'woompesa' ),
					$message ?: __( 'unknown error', 'woompesa' )
				)
			);
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s is the provider source. */
				__( 'M-Pesa payment marked failed via %s.', 'woompesa' ),
				$source
			)
		);
		$order->save();

		return 'failed';
	}

	public function touch_reconciliation( WC_Order $order ) {
		$order->update_meta_data( OrderMeta::LAST_RECONCILE_AT, gmdate( 'c' ) );
		$order->save();
	}

	private function write_provider_meta( WC_Order $order, $result ) {
		$order->update_meta_data( OrderMeta::RESULT_CODE, (string) ( $result['code'] ?? '' ) );
		$order->update_meta_data( OrderMeta::RESULT_DESCRIPTION, (string) ( $result['message'] ?? '' ) );

		if ( ! empty( $result['transaction_id'] ) ) {
			$order->update_meta_data( OrderMeta::TRANSACTION_ID, (string) $result['transaction_id'] );
		}

		if ( ! empty( $result['original_transaction_id'] ) ) {
			$order->update_meta_data( OrderMeta::ORIGINAL_TRANSACTION_ID, (string) $result['original_transaction_id'] );
		}

		if ( ! empty( $result['conversation_id'] ) ) {
			$order->update_meta_data( OrderMeta::CONVERSATION_ID, (string) $result['conversation_id'] );
		}

		if ( ! empty( $result['third_party_conversation_id'] ) ) {
			$order->update_meta_data( OrderMeta::THIRD_PARTY_CONVERSATION_ID, (string) $result['third_party_conversation_id'] );
		}

		if ( array_key_exists( 'reversed', $result ) ) {
			$order->update_meta_data( OrderMeta::REVERSED, (string) $result['reversed'] );
		}

		if ( ! empty( $result['raw'] ) ) {
			$order->update_meta_data( OrderMeta::RAW_INITIATE_RESPONSE, wp_json_encode( $result ) );
		}
	}

	private function is_success( $code, $result, $source ) {
		$status = strtoupper( (string) ( $result['transaction_status'] ?? '' ) );
		$source = strtolower( (string) $source );

		if ( in_array( $status, array( 'COMPLETED', 'SUCCESS', 'SUCCEEDED' ), true ) ) {
			return true;
		}

		// The initial C2B/STK response only confirms request acceptance, not captured funds.
		if ( 'payment initiation' === $source ) {
			return false;
		}

		if ( 'INS-0' !== $code ) {
			return false;
		}

		return '' !== (string) ( $result['transaction_id'] ?? '' ) && empty( $result['reversed'] );
	}

	private function should_remain_pending( $code, $result, $source ) {
		$status = strtoupper( (string) ( $result['transaction_status'] ?? '' ) );
		$source = strtolower( (string) $source );

		if ( 'payment initiation' === $source && 'INS-0' === $code ) {
			return true;
		}

		if ( in_array( $status, array( 'PENDING', 'PROCESSING', 'INPROGRESS' ), true ) ) {
			return true;
		}

		return in_array( $code, array( 'INS-9', 'INS-10' ), true );
	}
}
