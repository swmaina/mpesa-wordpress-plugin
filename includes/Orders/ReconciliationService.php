<?php

namespace Woompesa\Orders;

use Woompesa\Api\Client;
use Woompesa\Api\Credentials;
use Woompesa\Support\Logger;

defined( 'ABSPATH' ) || exit;

class ReconciliationService {
	const CRON_HOOK = 'woompesa_reconcile_pending_orders';

	public static function register_hooks() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}
	}

	public static function run() {
		$orders = wc_get_orders(
			array(
				'limit'      => 20,
				'status'     => array( 'on-hold' ),
				'return'     => 'objects',
				'meta_query' => array(
					array(
						'key'   => OrderMeta::STATUS,
						'value' => 'pending_confirmation',
					),
				),
			)
		);

		$state_machine = new OrderStateMachine();

		foreach ( $orders as $order ) {
			$settings    = get_option( 'woocommerce_woompesa_tz_settings', array() );
			$client      = new Client( new Credentials( $settings ) );
			$query_ref   = (string) $order->get_meta( OrderMeta::TRANSACTION_ID, true );

			if ( '' === $query_ref ) {
				$query_ref = (string) $order->get_meta( OrderMeta::CONVERSATION_ID, true );
			}

			if ( '' === $query_ref ) {
				$query_ref = (string) $order->get_meta( OrderMeta::THIRD_PARTY_CONVERSATION_ID, true );
			}

			if ( '' === $query_ref ) {
				$query_ref = (string) $order->get_meta( OrderMeta::TRANSACTION_REFERENCE, true );
			}

			if ( '' === $query_ref ) {
				continue;
			}

			$result = $client->query_transaction_status(
				array(
					'query_reference'             => $query_ref,
					'third_party_conversation_id' => (string) $order->get_meta( OrderMeta::THIRD_PARTY_CONVERSATION_ID, true ),
				)
			);

			if ( is_wp_error( $result ) ) {
				Logger::error(
					'M-Pesa reconciliation query failed.',
					array(
						'order_id' => $order->get_id(),
						'message'  => $result->get_error_message(),
					)
				);
				continue;
			}

			$state_machine->touch_reconciliation( $order );
			$state_machine->apply_provider_result( $order, $result, 'reconciliation' );
		}
	}
}
