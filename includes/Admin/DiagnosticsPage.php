<?php

namespace Woompesa\Admin;

use Woompesa\Orders\ReconciliationService;
use Woompesa\Webhook\CallbackController;

defined( 'ABSPATH' ) || exit;

class DiagnosticsPage {
	const SLUG = 'woompesa-diagnostics';

	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_post_woompesa_manual_reconcile', array( __CLASS__, 'handle_manual_reconcile' ) );
	}

	public static function register_page() {
		add_submenu_page(
			'woocommerce',
			__( 'M-Pesa Diagnostics', 'woompesa' ),
			__( 'M-Pesa Diagnostics', 'woompesa' ),
			'manage_woocommerce',
			self::SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings        = get_option( 'woocommerce_woompesa_tz_settings', array() );
		$manual_result   = sanitize_text_field( wp_unslash( $_GET['woompesa_status'] ?? '' ) );
		$credentials_ok  = self::credentials_complete( $settings );
		$cron_scheduled  = (bool) wp_next_scheduled( ReconciliationService::CRON_HOOK );
		$blocks_enabled  = class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' );
		$currency_ok     = function_exists( 'get_woocommerce_currency' ) && 'TZS' === get_woocommerce_currency();
		$openssl_ok      = extension_loaded( 'openssl' );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'M-Pesa Tanzania Diagnostics', 'woompesa' ) . '</h1>';

		if ( 'reconciled' === $manual_result ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Manual reconciliation run completed.', 'woompesa' ) . '</p></div>';
		}

		echo '<table class="widefat striped" style="max-width: 900px;">';
		echo '<thead><tr><th>' . esc_html__( 'Check', 'woompesa' ) . '</th><th>' . esc_html__( 'Status', 'woompesa' ) . '</th><th>' . esc_html__( 'Details', 'woompesa' ) . '</th></tr></thead><tbody>';
		self::render_row( __( 'Store currency', 'woompesa' ), $currency_ok, $currency_ok ? 'TZS' : __( 'The gateway expects TZS.', 'woompesa' ) );
		self::render_row( __( 'OpenSSL extension', 'woompesa' ), $openssl_ok, $openssl_ok ? __( 'Available', 'woompesa' ) : __( 'Required for token encryption.', 'woompesa' ) );
		self::render_row( __( 'Credential completeness', 'woompesa' ), $credentials_ok, $credentials_ok ? __( 'Required settings are present.', 'woompesa' ) : __( 'One or more required credentials are missing.', 'woompesa' ) );
		self::render_row( __( 'Callback URL', 'woompesa' ), true, CallbackController::get_callback_url() );
		self::render_row( __( 'Reconciliation cron', 'woompesa' ), $cron_scheduled, $cron_scheduled ? __( 'Scheduled hourly.', 'woompesa' ) : __( 'Not currently scheduled.', 'woompesa' ) );
		self::render_row( __( 'Checkout Blocks integration', 'woompesa' ), $blocks_enabled, $blocks_enabled ? __( 'WooCommerce Blocks classes detected.', 'woompesa' ) : __( 'Blocks package not detected in this WooCommerce install.', 'woompesa' ) );
		echo '</tbody></table>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top: 20px;">';
		wp_nonce_field( 'woompesa_manual_reconcile' );
		echo '<input type="hidden" name="action" value="woompesa_manual_reconcile" />';
		submit_button( __( 'Run Manual Reconciliation', 'woompesa' ), 'primary', 'submit', false );
		echo '</form>';
		echo '</div>';
	}

	public static function handle_manual_reconcile() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to run reconciliation.', 'woompesa' ) );
		}

		check_admin_referer( 'woompesa_manual_reconcile' );
		ReconciliationService::run();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => self::SLUG,
					'woompesa_status' => 'reconciled',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function credentials_complete( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$prefix   = ( ! empty( $settings['sandbox_mode'] ) && 'yes' === $settings['sandbox_mode'] ) ? 'sandbox_' : 'live_';

		$required = array(
			$prefix . 'api_key',
			$prefix . 'api_host',
			$prefix . 'public_key',
			$prefix . 'service_provider_code',
		);

		foreach ( $required as $key ) {
			if ( '' === trim( (string) ( $settings[ $key ] ?? '' ) ) ) {
				return false;
			}
		}

		return true;
	}

	private static function render_row( $label, $ok, $details ) {
		echo '<tr>';
		echo '<td>' . esc_html( $label ) . '</td>';
		echo '<td><strong>' . esc_html( $ok ? __( 'OK', 'woompesa' ) : __( 'Needs attention', 'woompesa' ) ) . '</strong></td>';
		echo '<td>' . esc_html( $details ) . '</td>';
		echo '</tr>';
	}
}
