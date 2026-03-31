<?php

namespace Woompesa\Orders;

use WC_Order;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class OrderFinder {
	public function find_by_provider_identifiers( $payload ) {
		$candidates = array_filter(
			array(
				OrderMeta::THIRD_PARTY_REFERENCE       => $payload['third_party_reference'] ?? '',
				OrderMeta::THIRD_PARTY_CONVERSATION_ID => $payload['third_party_conversation_id'] ?? '',
				OrderMeta::TRANSACTION_ID              => $payload['transaction_id'] ?? '',
				OrderMeta::CONVERSATION_ID             => $payload['conversation_id'] ?? '',
				OrderMeta::TRANSACTION_REFERENCE       => $payload['transaction_reference'] ?? '',
			)
		);

		foreach ( $candidates as $meta_key => $value ) {
			$order = $this->find_by_meta( $meta_key, $value );

			if ( $order ) {
				return $order;
			}
		}

		if ( ! empty( $payload['order_id'] ) ) {
			$order = wc_get_order( absint( $payload['order_id'] ) );

			if ( $order instanceof WC_Order ) {
				return $order;
			}
		}

		return null;
	}

	private function find_by_meta( $meta_key, $meta_value ) {
		if ( '' === (string) $meta_value ) {
			return null;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'objects',
				'meta_query' => array(
					array(
						'key'   => $meta_key,
						'value' => (string) $meta_value,
					),
				),
			)
		);

		if ( ! empty( $orders ) && $orders[0] instanceof WC_Order ) {
			return $orders[0];
		}

		return null;
	}
}
