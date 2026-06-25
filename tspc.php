<?php
/**
 * Plugin Name: Techsoul Project Calculator (TSPC)
 * Description: A premium, highly interactive Service Price Calculator featuring dynamic check lists, custom tier discount calculations, and AJAX lead logging.
 * Version:     1.0.0
 * Author:      Techsoul
 * Text Domain: tspc
 * Domain Path: /languages
 *
 * @package TSPC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'TSPC_VERSION', '1.0.2' );
define( 'TSPC_PATH', plugin_dir_path( __FILE__ ) );
define( 'TSPC_URL', plugin_dir_url( __FILE__ ) );

// Load DB operations
require_once TSPC_PATH . 'includes/class-tspc-db.php';

// Activation Hook
register_activation_hook( __FILE__, 'tspc_activate_plugin' );
function tspc_activate_plugin() {
	TSPC_DB::create_table();
	
	// Set default settings if not already present
	if ( ! get_option( 'tspc_settings' ) ) {
		require_once TSPC_PATH . 'includes/class-tspc-admin.php';
		update_option( 'tspc_settings', TSPC_Admin::get_default_settings() );
	}
}

// Load Admin Panel
require_once TSPC_PATH . 'includes/class-tspc-admin.php';

// Register frontend shortcode
add_shortcode( 'tspc_calculator', 'tspc_render_calculator_shortcode' );
function tspc_render_calculator_shortcode() {
	// Register and enqueue assets on shortcode execution to optimize page weight
	wp_enqueue_style( 'dashicons' ); // Enqueue WordPress dashicons for frontend representation
	wp_register_style( 'tspc-calculator-style', TSPC_URL . 'assets/css/calculator.css', array(), TSPC_VERSION );
	wp_register_script( 'tspc-calculator-script', TSPC_URL . 'assets/js/calculator.js', array( 'jquery' ), TSPC_VERSION, true );

	// Pass AJAX URL to Frontend Script
	wp_localize_script( 'tspc-calculator-script', 'tspc_ajax_obj', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	));

	wp_enqueue_style( 'tspc-calculator-style' );
	wp_enqueue_script( 'tspc-calculator-script' );

	// Output buffering
	ob_start();
	include TSPC_PATH . 'templates/calculator.php';
	return ob_get_clean();
}

// AJAX Form Submission Handlers
add_action( 'wp_ajax_tspc_submit_estimate', 'tspc_handle_lead_submission' );
add_action( 'wp_ajax_nopriv_tspc_submit_estimate', 'tspc_handle_lead_submission' );

function tspc_handle_lead_submission() {
	// Security check
	if ( ! isset( $_POST['tspc_nonce'] ) || ! wp_verify_nonce( $_POST['tspc_nonce'], 'tspc_submit_calculator_nonce' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'tspc' ) ) );
		exit;
	}

	// Validate inputs
	$name     = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
	$email    = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
	$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
	$message  = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
	$subtotal = isset( $_POST['subtotal'] ) ? floatval( $_POST['subtotal'] ) : 0.00;
	$discount = isset( $_POST['discount'] ) ? floatval( $_POST['discount'] ) : 0.00;
	$total    = isset( $_POST['total'] ) ? floatval( $_POST['total'] ) : 0.00;

	// Validate Services Array
	$raw_services = isset( $_POST['services'] ) ? json_decode( stripslashes( $_POST['services'] ), true ) : array();
	$sanitized_services = array();
	if ( is_array( $raw_services ) ) {
		foreach ( $raw_services as $s ) {
			$sanitized_services[] = array(
				'id'    => intval( $s['id'] ),
				'title' => sanitize_text_field( $s['title'] ),
				'price' => floatval( $s['price'] ),
			);
		}
	}
	$services_json = wp_json_encode( $sanitized_services );

	if ( empty( $name ) || empty( $email ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Name and email are required.', 'tspc' ) ) );
		exit;
	}

	if ( empty( $sanitized_services ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Please select at least one service.', 'tspc' ) ) );
		exit;
	}

	// Get settings for email notifications
	$settings = get_option( 'tspc_settings', array() );
	$currency = isset( $settings['currency'] ) ? $settings['currency'] : '৳';

	// Save to DB
	$inquiry_data = array(
		'name'     => $name,
		'email'    => $email,
		'phone'    => $phone,
		'services' => $services_json,
		'subtotal' => $subtotal,
		'discount' => $discount,
		'total'    => $total,
		'message'  => $message,
		'status'   => 'new',
	);

	$inserted_id = TSPC_DB::insert_inquiry( $inquiry_data );

	if ( ! $inserted_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Failed to save submission to the database.', 'tspc' ) ) );
		exit;
	}

	// Optional: Send emails if configured
	$enable_emails = isset( $settings['enable_emails'] ) ? intval( $settings['enable_emails'] ) : 0;
	if ( $enable_emails ) {
		$admin_email = isset( $settings['admin_email'] ) ? sanitize_email( $settings['admin_email'] ) : get_option( 'admin_email' );
		
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		// Build services list HTML
		$services_list_html = '<ul style="margin: 0; padding-left: 20px; list-style-type: disc;">';
		foreach ( $sanitized_services as $s ) {
			$services_list_html .= "<li>" . esc_html( $s['title'] ) . " - <strong>" . esc_html( $currency ) . number_format( $s['price'] ) . "</strong></li>";
		}
		$services_list_html .= '</ul>';

		// 1. Email to Admin
		$admin_subject = sprintf( '[New Lead] Service Price Estimate from %s', $name );
		$admin_body = "
		<h2>New Service Quote Submission</h2>
		<p><strong>Client Name:</strong> {$name}</p>
		<p><strong>Email Address:</strong> <a href='mailto:{$email}'>{$email}</a></p>
		<p><strong>Phone Number:</strong> <a href='tel:{$phone}'>{$phone}</a></p>
		<hr>
		<h3>Selected Services:</h3>
		{$services_list_html}
		<hr>
		<p><strong>Subtotal:</strong> {$currency}" . number_format( $subtotal ) . "</p>
		<p><strong>Discount Savings:</strong> -{$currency}" . number_format( $discount ) . "</p>
		<p><strong>Estimated Total:</strong> <strong style='font-size: 18px; color: #6366f1;'>{$currency}" . number_format( $total ) . "</strong></p>
		<p><strong>Client Message:</strong></p>
		<blockquote style='background: #f3f4f6; padding: 12px; border-left: 4px solid #6366f1;'>{$message}</blockquote>
		<p>You can manage this lead under <a href='" . admin_url( 'admin.php?page=tspc-dashboard' ) . "'>Service Calculator Submissions</a></p>";

		wp_mail( $admin_email, $admin_subject, $admin_body, $headers );

		// 2. Thank you email to client
		$client_subject = 'Your Service Price Estimate - Techsoul';
		$client_body = "
		<h2>Hi {$name},</h2>
		<p>Thank you for requesting a project cost estimate. We have logged your service requests.</p>
		<hr>
		<h3>Your Estimate Details:</h3>
		{$services_list_html}
		<hr>
		<p><strong>Subtotal:</strong> {$currency}" . number_format( $subtotal ) . "</p>
		<p><strong>Discount Savings:</strong> -{$currency}" . number_format( $discount ) . "</p>
		<p><strong>Estimated Total:</strong> <strong style='font-size: 18px; color: #6366f1;'>{$currency}" . number_format( $total ) . "</strong></p>
		<p>Our solutions engineer will review these requirements and reach out to you within 24 business hours to arrange a free consultation call.</p>
		<p>Best Regards,<br><strong>Techsoul Team</strong></p>";

		wp_mail( $email, $client_subject, $client_body, $headers );
	}

	wp_send_json_success( array(
		'message' => esc_html__( 'Inquiry recorded successfully.', 'tspc' ),
		'lead_id' => $inserted_id,
	) );
	exit;
}
