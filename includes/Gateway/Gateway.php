<?php

namespace Woompesa\Gateway;

use WC_Payment_Gateway;
use Woompesa\Checkout\Availability;
use Woompesa\Checkout\PaymentProcessor;
use Woompesa\Webhook\CallbackController;

defined( 'ABSPATH' ) || exit;

class Gateway extends WC_Payment_Gateway {
	public function __construct() {
		$this->id                 = 'woompesa_tz';
		$this->icon               = WOOMPESA_URL . 'img/logo.jpg';
		$this->has_fields         = true;
		$this->method_title       = __( 'M-Pesa Tanzania', 'woompesa' );
		$this->method_description = __( 'Accept Vodacom Tanzania M-Pesa payments in TZS using the M-Pesa OpenAPI portal.', 'woompesa' );
		$this->supports           = array(
			'products',
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title', __( 'Mobile Money - TZ', 'woompesa' ) );
		$this->description = $this->get_option( 'description', __( 'Pay from your Vodacom Tanzania M-Pesa wallet.', 'woompesa' ) );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woompesa' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable M-Pesa Tanzania payments', 'woompesa' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'woompesa' ),
				'type'        => 'text',
				'description' => __( 'This controls the title shown to customers during checkout.', 'woompesa' ),
				'default'     => __( 'Mobile Money - TZ', 'woompesa' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woompesa' ),
				'type'        => 'textarea',
				'description' => __( 'Customer-facing payment description.', 'woompesa' ),
				'default'     => __( 'Authorize the payment on your phone after placing the order.', 'woompesa' ),
				'desc_tip'    => true,
			),
			'sandbox_mode' => array(
				'title'       => __( 'Sandbox Mode', 'woompesa' ),
				'label'       => __( 'Use sandbox credentials', 'woompesa' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'Switch between the OpenAPI sandbox and live merchant credentials.', 'woompesa' ),
			),
			'sandbox_api_key' => array(
				'title' => __( 'Sandbox API Key', 'woompesa' ),
				'type'  => 'password',
			),
			'sandbox_api_host' => array(
				'title'       => __( 'Sandbox API Host', 'woompesa' ),
				'type'        => 'text',
				'default'     => 'openapi.m-pesa.com',
				'description' => __( 'Default inferred host for Vodacom Tanzania OpenAPI sandbox integrations.', 'woompesa' ),
			),
			'sandbox_public_key' => array(
				'title' => __( 'Sandbox Public Key', 'woompesa' ),
				'type'  => 'textarea',
			),
			'sandbox_service_provider_code' => array(
				'title' => __( 'Sandbox Service Provider Code', 'woompesa' ),
				'type'  => 'text',
			),
			'sandbox_third_party_conversation_id' => array(
				'title' => __( 'Sandbox Third Party Conversation ID (Deprecated)', 'woompesa' ),
				'type'  => 'text',
				'description' => __( 'No longer required. The plugin now generates a unique Third Party Conversation ID for every request.', 'woompesa' ),
			),
			'sandbox_origin' => array(
				'title'       => __( 'Sandbox Origin', 'woompesa' ),
				'type'        => 'text',
				'default'     => home_url(),
				'description' => __( 'Set this only if your OpenAPI app configuration requires an Origin header.', 'woompesa' ),
			),
			'live_api_key' => array(
				'title' => __( 'Live API Key', 'woompesa' ),
				'type'  => 'password',
			),
			'live_api_host' => array(
				'title'       => __( 'Live API Host', 'woompesa' ),
				'type'        => 'text',
				'default'     => 'openapi.m-pesa.com',
				'description' => __( 'Default inferred host for Vodacom Tanzania OpenAPI live integrations.', 'woompesa' ),
			),
			'live_public_key' => array(
				'title' => __( 'Live Public Key', 'woompesa' ),
				'type'  => 'textarea',
			),
			'live_service_provider_code' => array(
				'title' => __( 'Live Service Provider Code', 'woompesa' ),
				'type'  => 'text',
			),
			'live_third_party_conversation_id' => array(
				'title' => __( 'Live Third Party Conversation ID (Deprecated)', 'woompesa' ),
				'type'  => 'text',
				'description' => __( 'No longer required. The plugin now generates a unique Third Party Conversation ID for every request.', 'woompesa' ),
			),
			'live_origin' => array(
				'title'       => __( 'Live Origin', 'woompesa' ),
				'type'        => 'text',
				'default'     => home_url(),
				'description' => __( 'Use your production site URL when the live OpenAPI app requires an Origin header.', 'woompesa' ),
			),
			'debug_logging' => array(
				'title'   => __( 'Debug Logging', 'woompesa' ),
				'label'   => __( 'Enable WooCommerce log entries for gateway actions', 'woompesa' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		);
	}

	public function admin_options() {
		echo '<h2>' . esc_html__( 'M-Pesa Tanzania', 'woompesa' ) . '</h2>';
		echo '<p>' . esc_html__( 'Modernized Vodacom Tanzania M-Pesa gateway foundation for WooCommerce.', 'woompesa' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Callback URL:', 'woompesa' ) . '</strong> ' . esc_html( CallbackController::get_callback_url() ) . '</p>';
		echo '<p>' . esc_html__( 'Phase 2 now sends server-side session and C2B requests. Callback handling remains available for provider-side extensions and reconciliation.', 'woompesa' ) . '</p>';

		if ( ! Availability::currency_is_supported() ) {
			echo '<div class="notice notice-warning inline"><p>' .
				esc_html__( 'The gateway is designed for stores using Tanzanian Shilling (TZS).', 'woompesa' ) .
			'</p></div>';
		}

		parent::admin_options();
	}

	public function is_available() {
		if ( 'yes' !== $this->get_option( 'enabled', 'yes' ) ) {
			return false;
		}

		return parent::is_available() && Availability::currency_is_supported();
	}

	public function payment_fields() {
		wp_enqueue_style( 'woompesa-checkout' );
		wp_enqueue_script( 'woompesa-checkout-classic' );

		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}

		woocommerce_form_field(
			'woompesa_phone',
			array(
				'type'              => 'tel',
				'label'             => __( 'Enter your Tanzania phone number', 'woompesa' ),
				'required'          => true,
				'class'             => array( 'form-row-wide' ),
				'input_class'       => array( 'woompesa-phone-input' ),
				'custom_attributes' => array(
					'autocomplete' => 'tel',
					'placeholder'  => __( 'e.g. 0744XXXXXX or 255744XXXXXX', 'woompesa' ),
				),
			),
			wc_clean( wp_unslash( $_POST['woompesa_phone'] ?? '' ) )
		);

		echo '<p class="woompesa-checkout-help">' .
			esc_html__( 'After placing the order, confirm the payment on your phone. The order will be completed once our M-Pesa confirms the transaction.', 'woompesa' ) .
		'</p>';
	}

	public function validate_fields() {
		$phone = wc_clean( wp_unslash( $_POST['woompesa_phone'] ?? '' ) );

		if ( '' === $phone ) {
			wc_add_notice( __( 'Please enter the M-Pesa phone number.', 'woompesa' ), 'error' );

			return false;
		}

		return true;
	}

	public function process_payment( $order_id ) {
		$order      = wc_get_order( $order_id );
		$phone      = wc_clean( wp_unslash( $_POST['woompesa_phone'] ?? $_POST['wc-woompesa-phone'] ?? '' ) );
		$processor  = new PaymentProcessor( $order, $this->settings );
		$result     = $processor->initiate( $phone );

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );

			return array(
				'result' => 'failure',
			);
		}

		WC()->cart->empty_cart();

		return $result;
	}

	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		echo '<p>' .
			esc_html__( 'Your payment request has been prepared. The order will be updated automatically when Vodacom confirms the transaction.', 'woompesa' ) .
		'</p>';
	}
}
