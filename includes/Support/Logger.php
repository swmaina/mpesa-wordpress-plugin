<?php

namespace Woompesa\Support;

defined( 'ABSPATH' ) || exit;

class Logger {
	const SOURCE = 'woompesa';

	public static function info( $message, $context = array() ) {
		self::logger()->info( $message, self::normalize_context( $context ) );
	}

	public static function error( $message, $context = array() ) {
		self::logger()->error( $message, self::normalize_context( $context ) );
	}

	private static function logger() {
		return wc_get_logger();
	}

	private static function normalize_context( $context ) {
		if ( ! is_array( $context ) ) {
			$context = array( 'data' => $context );
		}

		$context['source'] = self::SOURCE;

		return $context;
	}
}
