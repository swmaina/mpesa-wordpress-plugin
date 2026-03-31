<?php

namespace Woompesa\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {
	public static function register_hooks() {
		add_filter( 'plugin_action_links_' . plugin_basename( WOOMPESA_FILE ), array( __CLASS__, 'add_settings_link' ) );
	}

	public static function add_settings_link( $links ) {
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woompesa_tz' );

		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Settings', 'woompesa' )
			)
		);

		return $links;
	}
}
