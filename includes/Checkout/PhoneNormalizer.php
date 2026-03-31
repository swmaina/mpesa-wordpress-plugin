<?php

namespace Woompesa\Checkout;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

class PhoneNormalizer {
	public static function normalize_for_environment( $phone, $is_sandbox = false ) {
		$digits = preg_replace( '/\D+/', '', (string) $phone );

		if ( $is_sandbox && preg_match( '/^0{11}\d$/', $digits ) ) {
			return $digits;
		}

		return self::normalize_tanzania_msisdn( $phone );
	}

	public static function normalize_tanzania_msisdn( $phone ) {
		$digits = preg_replace( '/\D+/', '', (string) $phone );

		if ( preg_match( '/^(255)(6|7)\d{8}$/', $digits ) ) {
			return $digits;
		}

		if ( preg_match( '/^0(6|7)\d{8}$/', $digits ) ) {
			return '255' . substr( $digits, 1 );
		}

		if ( preg_match( '/^(6|7)\d{8}$/', $digits ) ) {
			return '255' . $digits;
		}

		throw new InvalidArgumentException( __( 'Please enter a valid Tanzania mobile number for M-Pesa.', 'woompesa' ) );
	}
}
