<?php
/**
 * Admin Dashboard Manager for TSPC
 *
 * @package TSPC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSPC_Admin {

	/**
	 * Constructor: Initialize admin actions
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register Admin Menu and Submenus
	 */
	public function register_menu() {
		// Parent menu
		add_menu_page(
			__( 'Service Price Calculator', 'tspc' ),
			__( 'Service Calculator', 'tspc' ),
			'manage_options',
			'tspc-dashboard',
			array( $this, 'render_dashboard_page' ),
			'dashicons-calculator',
			30
		);

		// Submenus
		add_submenu_page(
			'tspc-dashboard',
			__( 'Dashboard', 'tspc' ),
			__( 'Dashboard', 'tspc' ),
			'manage_options',
			'tspc-dashboard',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'tspc-dashboard',
			__( 'Services', 'tspc' ),
			__( 'Services', 'tspc' ),
			'manage_options',
			'tspc-services',
			array( $this, 'render_services_page' )
		);

		add_submenu_page(
			'tspc-dashboard',
			__( 'Settings', 'tspc' ),
			__( 'Settings', 'tspc' ),
			'manage_options',
			'tspc-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue Admin Styles and Scripts
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only enqueue on our plugin pages
		if ( ! in_array( $hook, array( 'toplevel_page_tspc-dashboard', 'service-calculator_page_tspc-services', 'service-calculator_page_tspc-settings' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'tspc-admin-css', plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ), array(), TSPC_VERSION );
		// Load WordPress color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'tspc-admin-js', plugins_url( 'assets/js/admin.js', dirname( __FILE__ ) ), array( 'jquery', 'wp-color-picker' ), TSPC_VERSION, true );
	}

	/**
	 * Handle admin actions (CRUD operations, exports)
	 */
	public function handle_actions() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// 1. Handle Save Settings
		if ( isset( $_POST['tspc_save_settings'] ) && check_admin_referer( 'tspc_save_settings_nonce', '_wpnonce' ) ) {
			// Sanitize color: allow only valid hex colors
			$accent_color = '#6366f1';
			if ( isset( $_POST['accent_color'] ) ) {
				$raw_color = sanitize_text_field( $_POST['accent_color'] );
				if ( preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $raw_color ) ) {
					$accent_color = $raw_color;
				}
			}

			$dynamic_discounts_input = isset( $_POST['dynamic_discounts'] ) ? $_POST['dynamic_discounts'] : array();
			$dynamic_discounts_data = array();
			if ( is_array( $dynamic_discounts_input ) ) {
				foreach ( $dynamic_discounts_input as $dd ) {
					if ( isset( $dd['services'] ) && intval( $dd['services'] ) > 0 ) {
						$dynamic_discounts_data[] = array(
							'services' => intval( $dd['services'] ),
							'discount' => floatval( $dd['discount'] ),
						);
					}
				}
			}

			$settings = array(
				'currency'           => sanitize_text_field( $_POST['currency'] ),
				'dynamic_discounts'  => json_encode( $dynamic_discounts_data ),
				'admin_email'        => sanitize_email( $_POST['admin_email'] ),
				'enable_emails'      => isset( $_POST['enable_emails'] ) ? 1 : 0,
				'accent_color'       => $accent_color,
				'show_name'          => isset( $_POST['show_name'] ) ? 1 : 0,
				'show_phone'         => isset( $_POST['show_phone'] ) ? 1 : 0,
				'show_email'         => isset( $_POST['show_email'] ) ? 1 : 0,
				'show_message'       => isset( $_POST['show_message'] ) ? 1 : 0,
				'req_name'           => isset( $_POST['req_name'] ) ? 1 : 0,
				'req_phone'          => isset( $_POST['req_phone'] ) ? 1 : 0,
				'req_email'          => isset( $_POST['req_email'] ) ? 1 : 0,
				'req_message'        => isset( $_POST['req_message'] ) ? 1 : 0,
				'expand_first'       => isset( $_POST['expand_first'] ) ? 1 : 0,
			);

			update_option( 'tspc_settings', $settings );
			wp_safe_redirect( admin_url( 'admin.php?page=tspc-settings&updated=true' ) );
			exit;
		}

		// 2. Handle Add Service
		if ( isset( $_POST['tspc_add_service'] ) && check_admin_referer( 'tspc_add_service_nonce', '_wpnonce' ) ) {
			$sub_services_input = isset( $_POST['sub_services'] ) ? $_POST['sub_services'] : array();
			$sub_services_data = array();
			if ( is_array( $sub_services_input ) ) {
				foreach ( $sub_services_input as $sub ) {
					if ( isset( $sub['title'] ) && ! empty( trim( $sub['title'] ) ) ) {
						$sub_item = array(
							'title'           => sanitize_text_field( $sub['title'] ),
							'price'           => floatval( $sub['price'] ),
							'default_checked' => isset( $sub['default_checked'] ) ? 1 : 0,
						);
						if ( isset( $sub['children'] ) && is_array( $sub['children'] ) ) {
							$children_data = array();
							foreach ( $sub['children'] as $child ) {
								if ( isset( $child['title'] ) && ! empty( trim( $child['title'] ) ) ) {
									$children_data[] = array(
										'title'           => sanitize_text_field( $child['title'] ),
										'price'           => floatval( $child['price'] ),
										'default_checked' => isset( $child['default_checked'] ) ? 1 : 0,
									);
								}
							}
							if ( ! empty( $children_data ) ) {
								$sub_item['children'] = $children_data;
							}
						}
						$sub_services_data[] = $sub_item;
					}
				}
			}

			$service_data = array(
				'title'            => sanitize_text_field( $_POST['title'] ),
				'description'      => sanitize_textarea_field( $_POST['description'] ),
				'icon'             => sanitize_text_field( $_POST['icon'] ),
				'price'            => floatval( $_POST['price'] ),
				'discount_percent' => isset( $_POST['discount_percent'] ) ? floatval( $_POST['discount_percent'] ) : 0.00,
				'status'           => sanitize_text_field( $_POST['status'] ),
				'is_required'      => isset( $_POST['is_required'] ) ? 1 : 0,
				'sub_services'     => json_encode( $sub_services_data ),
			);

			TSPC_DB::insert_service( $service_data );
			wp_safe_redirect( admin_url( 'admin.php?page=tspc-services&added=true' ) );
			exit;
		}

		// 3. Handle Edit Service
		if ( isset( $_POST['tspc_edit_service'] ) && check_admin_referer( 'tspc_edit_service_nonce', '_wpnonce' ) ) {
			$id = intval( $_POST['service_id'] );
			$sub_services_input = isset( $_POST['sub_services'] ) ? $_POST['sub_services'] : array();
			$sub_services_data = array();
			if ( is_array( $sub_services_input ) ) {
				foreach ( $sub_services_input as $sub ) {
					if ( isset( $sub['title'] ) && ! empty( trim( $sub['title'] ) ) ) {
						$sub_item = array(
							'title'           => sanitize_text_field( $sub['title'] ),
							'price'           => floatval( $sub['price'] ),
							'default_checked' => isset( $sub['default_checked'] ) ? 1 : 0,
						);
						if ( isset( $sub['children'] ) && is_array( $sub['children'] ) ) {
							$children_data = array();
							foreach ( $sub['children'] as $child ) {
								if ( isset( $child['title'] ) && ! empty( trim( $child['title'] ) ) ) {
									$children_data[] = array(
										'title'           => sanitize_text_field( $child['title'] ),
										'price'           => floatval( $child['price'] ),
										'default_checked' => isset( $child['default_checked'] ) ? 1 : 0,
									);
								}
							}
							if ( ! empty( $children_data ) ) {
								$sub_item['children'] = $children_data;
							}
						}
						$sub_services_data[] = $sub_item;
					}
				}
			}

			$service_data = array(
				'title'            => sanitize_text_field( $_POST['title'] ),
				'description'      => sanitize_textarea_field( $_POST['description'] ),
				'icon'             => sanitize_text_field( $_POST['icon'] ),
				'price'            => floatval( $_POST['price'] ),
				'discount_percent' => isset( $_POST['discount_percent'] ) ? floatval( $_POST['discount_percent'] ) : 0.00,
				'status'           => sanitize_text_field( $_POST['status'] ),
				'is_required'      => isset( $_POST['is_required'] ) ? 1 : 0,
				'sub_services'     => json_encode( $sub_services_data ),
			);

			TSPC_DB::update_service( $id, $service_data );
			wp_safe_redirect( admin_url( 'admin.php?page=tspc-services&updated=true' ) );
			exit;
		}

		// 4. Handle Delete Service
		if ( isset( $_GET['action'] ) && 'delete_service' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$id = intval( $_GET['id'] );
			check_admin_referer( 'tspc_delete_service_' . $id );
			TSPC_DB::delete_service( $id );
			wp_safe_redirect( admin_url( 'admin.php?page=tspc-services&deleted=true' ) );
			exit;
		}

		// 5. Handle Delete Inquiry
		if ( isset( $_GET['action'] ) && 'delete_inquiry' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$id = intval( $_GET['id'] );
			check_admin_referer( 'tspc_delete_inquiry_' . $id );
			TSPC_DB::delete_inquiry( $id );
			wp_safe_redirect( admin_url( 'admin.php?page=tspc-dashboard&deleted=true' ) );
			exit;
		}

		// 6. Handle Change Inquiry Status
		if ( isset( $_POST['tspc_update_inquiry_status'] ) && isset( $_POST['inquiry_id'] ) && check_admin_referer( 'tspc_update_inquiry_status_nonce', '_wpnonce' ) ) {
			$status = sanitize_text_field( $_POST['status'] );
			TSPC_DB::update_inquiry_status( intval( $_POST['inquiry_id'] ), $status );
			wp_safe_redirect( admin_url( 'admin.php?page=tspc-dashboard&status_updated=true' ) );
			exit;
		}

		// 7. Handle CSV Export
		if ( isset( $_GET['action'] ) && 'export_inquiries' === $_GET['action'] ) {
			check_admin_referer( 'tspc_export_inquiries_nonce' );
			$this->export_inquiries_csv();
		}
	}

	/**
	 * Export Inquiries to CSV
	 */
	private function export_inquiries_csv() {
		if ( ob_get_length() ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=tspc_inquiries_' . date( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Date', 'Name', 'Email', 'Selected Services', 'Subtotal', 'Discount Saved', 'Total Price', 'Status', 'Message' ) );

		$inquiries = TSPC_DB::get_inquiries( 1000, 0 );
		foreach ( $inquiries as $inq ) {
			$services_arr = json_decode( $inq['services'], true );
			$services_titles = array();
			if ( is_array( $services_arr ) ) {
				foreach ( $services_arr as $s ) {
					$services_titles[] = $s['title'] . ' (৳' . $s['price'] . ')';
				}
			}
			$services_str = implode( ', ', $services_titles );

			fputcsv( $output, array(
				$inq['id'],
				$inq['created_at'],
				$inq['name'],
				$inq['email'],
				$services_str,
				$inq['subtotal'],
				$inq['discount'],
				$inq['total'],
				$inq['status'],
				$inq['message']
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Default settings
	 */
	public static function get_default_settings() {
		$default_discounts = array(
			array( 'services' => 2, 'discount' => 5.0 ),
			array( 'services' => 3, 'discount' => 10.0 ),
			array( 'services' => 4, 'discount' => 15.0 ),
		);

		return array(
			'currency'          => '৳',
			'dynamic_discounts' => json_encode( $default_discounts ),
			'admin_email'       => get_option( 'admin_email' ),
			'enable_emails'     => 1,
			'accent_color'      => '#6366f1',
			'show_name'         => 1,
			'show_phone'        => 1,
			'show_email'        => 1,
			'show_message'      => 1,
			'req_name'          => 1,
			'req_phone'         => 1,
			'req_email'         => 1,
			'req_message'       => 0,
			'expand_first'      => 1,
		);
	}

	/**
	 * Get current settings
	 */
	public static function get_settings() {
		$saved = get_option( 'tspc_settings', array() );
		$defaults = self::get_default_settings();
		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Render Dashboard Page
	 */
	public function render_dashboard_page() {
		global $wpdb;
		$limit = 15;
		$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$offset = ( $paged - 1 ) * $limit;
		$total_inquiries = TSPC_DB::get_inquiries_count();
		$num_pages = ceil( $total_inquiries / $limit );
		$inquiries = TSPC_DB::get_inquiries( $limit, $offset );

		// Stats calculation
		$inquiries_table = TSPC_DB::get_inquiries_table();
		$total_val = (float) $wpdb->get_var( "SELECT SUM(total) FROM $inquiries_table" );
		$new_leads = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $inquiries_table WHERE status = %s", 'new' ) );
		$converted_leads = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $inquiries_table WHERE status = %s", 'converted' ) );

		if ( isset( $_GET['deleted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Inquiry deleted successfully.', 'tspc' ) . '</p></div>';
		}
		if ( isset( $_GET['status_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Status updated successfully.', 'tspc' ) . '</p></div>';
		}
		?>
		<div class="wrap tspc-admin-wrapper">
			<header class="tspc-admin-header">
				<div class="tspc-admin-header-main">
					<div class="tspc-logo-sec">
						<span class="dashicons dashicons-calculator"></span>
						<div>
							<h1><?php esc_html_e( 'Service Price Calculator Dashboard', 'tspc' ); ?></h1>
							<div class="tspc-tagline"><?php esc_html_e( 'View client inquiries and calculator leads.', 'tspc' ); ?></div>
						</div>
				</div>
			</header>

			<!-- Premium Stats Grid -->
			<div class="tspc-stats-grid">
				<div class="tspc-stat-card">
					<div class="tspc-stat-icon tspc-stat-icon-blue">
						<span class="dashicons dashicons-email-alt"></span>
					</div>
					<div class="tspc-stat-details">
						<span class="tspc-stat-value"><?php echo number_format( $total_inquiries ); ?></span>
						<span class="tspc-stat-label"><?php esc_html_e( 'Total Inquiries', 'tspc' ); ?></span>
					</div>
				</div>
				<div class="tspc-stat-card">
					<div class="tspc-stat-icon tspc-stat-icon-green">
						<span class="dashicons dashicons-chart-area"></span>
					</div>
					<div class="tspc-stat-details">
						<span class="tspc-stat-value">৳<?php echo number_format( $total_val ); ?></span>
						<span class="tspc-stat-label"><?php esc_html_e( 'Estimated Value', 'tspc' ); ?></span>
					</div>
				</div>
				<div class="tspc-stat-card">
					<div class="tspc-stat-icon tspc-stat-icon-yellow">
						<span class="dashicons dashicons-bell"></span>
					</div>
					<div class="tspc-stat-details">
						<span class="tspc-stat-value"><?php echo number_format( $new_leads ); ?></span>
						<span class="tspc-stat-label"><?php esc_html_e( 'New Leads', 'tspc' ); ?></span>
					</div>
				</div>
				<div class="tspc-stat-card">
					<div class="tspc-stat-icon tspc-stat-icon-purple">
						<span class="dashicons dashicons-yes-alt"></span>
					</div>
					<div class="tspc-stat-details">
						<span class="tspc-stat-value"><?php echo number_format( $converted_leads ); ?></span>
						<span class="tspc-stat-label"><?php esc_html_e( 'Converted Leads', 'tspc' ); ?></span>
					</div>
				</div>
			</div>

			<div class="tspc-leads-top-bar">
				<h2><?php esc_html_e( 'Submitted Quotes & Inquiries', 'tspc' ); ?></h2>
				<?php if ( $total_inquiries > 0 ) : ?>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=tspc-dashboard&action=export_inquiries' ), 'tspc_export_inquiries_nonce' ) ); ?>" class="button button-primary tspc-export-btn">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export to CSV', 'tspc' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( empty( $inquiries ) ) : ?>
				<div class="tspc-empty-state">
					<span class="dashicons dashicons-inbox"></span>
					<p><?php esc_html_e( 'No quote requests have been submitted yet.', 'tspc' ); ?></p>
					<p class="description"><?php esc_html_e( 'Create service items first, then place [tspc_calculator] on any page.', 'tspc' ); ?></p>
				</div>
			<?php else : ?>
				<div class="tspc-table-card-wrapper">
					<table class="wp-list-table widefat fixed striped table-view-list tspc-leads-table">
						<thead>
							<tr>
								<th class="column-date"><?php esc_html_e( 'Date', 'tspc' ); ?></th>
								<th><?php esc_html_e( 'Client Info', 'tspc' ); ?></th>
								<th><?php esc_html_e( 'Selected Services', 'tspc' ); ?></th>
								<th><?php esc_html_e( 'Price Details', 'tspc' ); ?></th>
								<th><?php esc_html_e( 'Status', 'tspc' ); ?></th>
								<th class="column-actions"><?php esc_html_e( 'Actions', 'tspc' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $inquiries as $inq ) : 
								$services_arr = json_decode( $inq['services'], true );
								$services_titles = array();
								if ( is_array( $services_arr ) ) {
									foreach ( $services_arr as $s ) {
										$services_titles[] = $s['title'];
									}
								}
								?>
								<tr id="inquiry-row-<?php echo esc_attr( $inq['id'] ); ?>">
									<td class="column-date">
										<div class="tspc-date-wrapper">
											<span class="dashicons dashicons-calendar-alt"></span>
											<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $inq['created_at'] ) ) ); ?></strong>
										</div>
									</td>
									<td>
										<div class="tspc-client-info">
											<span class="tspc-client-name"><?php echo esc_html( $inq['name'] ); ?></span>
											<?php if ( ! empty( $inq['email'] ) ) : ?>
												<span class="tspc-client-meta"><span class="dashicons dashicons-email"></span> <a href="mailto:<?php echo esc_attr( $inq['email'] ); ?>"><?php echo esc_html( $inq['email'] ); ?></a></span>
											<?php endif; ?>
											<?php if ( ! empty( $inq['phone'] ) ) : ?>
												<span class="tspc-client-meta"><span class="dashicons dashicons-phone"></span> <a href="tel:<?php echo esc_attr( $inq['phone'] ); ?>"><?php echo esc_html( $inq['phone'] ); ?></a></span>
											<?php endif; ?>
										</div>
									</td>
									<td>
										<div class="tspc-selected-services-tags">
											<?php if ( empty( $services_titles ) ) : ?>
												<span class="tspc-service-tag tspc-tag-none"><?php esc_html_e( 'None', 'tspc' ); ?></span>
											<?php else : ?>
												<?php foreach ( $services_titles as $title ) : ?>
													<span class="tspc-service-tag"><?php echo esc_html( $title ); ?></span>
												<?php endforeach; ?>
											<?php endif; ?>
										</div>
									</td>
									<td>
										<div class="tspc-price-breakdown">
											<div class="tspc-breakdown-row">
												<span class="tspc-lbl"><?php esc_html_e( 'Subtotal:', 'tspc' ); ?></span>
												<span class="tspc-val">৳<?php echo esc_html( number_format( $inq['subtotal'] ) ); ?></span>
											</div>
											<div class="tspc-breakdown-row">
												<span class="tspc-lbl"><?php esc_html_e( 'Discount:', 'tspc' ); ?></span>
												<span class="tspc-val val-discount">-৳<?php echo esc_html( number_format( $inq['discount'] ) ); ?></span>
											</div>
											<div class="tspc-breakdown-row tspc-row-total">
												<span class="tspc-lbl"><?php esc_html_e( 'Total:', 'tspc' ); ?></span>
												<span class="tspc-val val-total">৳<?php echo esc_html( number_format( $inq['total'] ) ); ?></span>
											</div>
										</div>
									</td>
									<td>
										<span class="tspc-badge badge-<?php echo esc_attr( $inq['status'] ); ?>">
											<?php echo esc_html( ucfirst( $inq['status'] ) ); ?>
										</span>
									</td>
									<td class="column-actions">
										<div class="tspc-action-buttons">
											<button type="button" class="button button-small tspc-view-details" data-inquiry="<?php echo esc_attr( json_encode( $inq ) ); ?>">
												<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Details', 'tspc' ); ?>
											</button>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=tspc-dashboard&action=delete_inquiry&id=' . $inq['id'] ), 'tspc_delete_inquiry_' . $inq['id'] ) ); ?>" class="button button-small button-link-delete tspc-delete-lead" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this submission?', 'tspc' ); ?>');">
												<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'tspc' ); ?>
											</a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php if ( $num_pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php printf( _n( '%s inquiry', '%s inquiries', $total_inquiries, 'tspc' ), number_format_i18n( $total_inquiries ) ); ?></span>
							<span class="pagination-links">
								<?php
								echo paginate_links( array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'     => $num_pages,
									'current'   => $paged,
								) );
								?>
							</span>
						</div>
					</div>
				<?php endif; ?>

				<!-- Inquiry Detail Modal -->
				<div id="tspc-lead-modal" class="tspc-modal" style="display:none;">
					<div class="tspc-modal-content">
						<span class="tspc-close-modal">&times;</span>
						<h2><?php esc_html_e( 'Inquiry Specifications', 'tspc' ); ?></h2>
						<div id="tspc-modal-body"></div>
						<div class="tspc-modal-actions">
							<form method="post" action="">
								<?php wp_nonce_field( 'tspc_update_inquiry_status_nonce' ); ?>
								<input type="hidden" name="inquiry_id" id="tspc-modal-lead-id" value="">
								<label for="tspc-modal-status"><strong><?php esc_html_e( 'Update Status:', 'tspc' ); ?></strong></label>
								<select name="status" id="tspc-modal-status">
									<option value="new"><?php esc_html_e( 'New Inquiry', 'tspc' ); ?></option>
									<option value="contacted"><?php esc_html_e( 'Contacted', 'tspc' ); ?></option>
									<option value="negotiating"><?php esc_html_e( 'Negotiating', 'tspc' ); ?></option>
									<option value="converted"><?php esc_html_e( 'Client Won', 'tspc' ); ?></option>
									<option value="lost"><?php esc_html_e( 'Lost / Closed', 'tspc' ); ?></option>
								</select>
								<button type="submit" name="tspc_update_inquiry_status" class="button button-primary"><?php esc_html_e( 'Save Status', 'tspc' ); ?></button>
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Services Page
	 */
	public function render_services_page() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		$service_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$service = $service_id ? TSPC_DB::get_service( $service_id ) : null;

		if ( isset( $_GET['added'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Service added successfully.', 'tspc' ) . '</p></div>';
		}
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Service updated successfully.', 'tspc' ) . '</p></div>';
		}
		if ( isset( $_GET['deleted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Service deleted successfully.', 'tspc' ) . '</p></div>';
		}
		?>
		<div class="wrap tspc-admin-wrapper">
			<header class="tspc-admin-header">
				<div class="tspc-logo-sec">
					<span class="dashicons dashicons-admin-tools"></span>
					<h1><?php esc_html_e( 'Manage Services', 'tspc' ); ?></h1>
				</div>
				<div class="tspc-tagline"><?php esc_html_e( 'Configure active service offerings, price rates, and catalog descriptions.', 'tspc' ); ?></div>
			</header>

			<?php if ( 'add' === $action || 'edit' === $action ) : ?>
				<!-- Add/Edit form -->
				<form method="post" action="" class="tspc-service-form-grid">
					<?php 
					if ( 'add' === $action ) {
						wp_nonce_field( 'tspc_add_service_nonce' );
					} else {
						wp_nonce_field( 'tspc_edit_service_nonce' );
						echo '<input type="hidden" name="service_id" value="' . esc_attr( $service_id ) . '">';
					}
					?>

					<!-- Left Column: Service Details -->
					<div class="tspc-settings-card">
						<h3>
							<span class="dashicons <?php echo 'add' === $action ? 'dashicons-plus' : 'dashicons-edit'; ?>"></span>
							<?php echo 'add' === $action ? esc_html__( 'Add New Service', 'tspc' ) : esc_html__( 'Edit Service Details', 'tspc' ); ?>
						</h3>
						<hr>

						<div class="tspc-field-group">
							<label for="title"><?php esc_html_e( 'Service Title *', 'tspc' ); ?></label>
							<input type="text" id="title" name="title" value="<?php echo $service ? esc_attr( $service['title'] ) : ''; ?>" required placeholder="e.g. Website Design">
						</div>

						<div class="tspc-field-group">
							<label for="description"><?php esc_html_e( 'Short Description', 'tspc' ); ?></label>
							<textarea id="description" name="description" rows="3" placeholder="e.g. Responsive Business Website"><?php echo $service ? esc_textarea( $service['description'] ) : ''; ?></textarea>
						</div>

						<div class="tspc-compact-fields-card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-top: 24px;">
							<div style="display: grid; grid-template-columns: auto 1fr 1fr 1fr; gap: 20px; align-items: end;">
								
								<div class="tspc-field-group tspc-icon-picker-group" style="margin-bottom: 0;">
									<label for="icon" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e( 'Service Icon', 'tspc' ); ?></label>
									<div class="tspc-icon-picker-wrapper" style="margin-bottom: 0;">
										<div class="tspc-icon-preview-box" id="tspc-icon-preview-box" title="<?php esc_attr_e( 'Click to choose icon', 'tspc' ); ?>" style="display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; cursor: pointer; background: #ffffff; border-color: #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
											<span class="dashicons <?php echo $service ? esc_attr( $service['icon'] ) : 'dashicons-admin-site'; ?>" style="margin:0; display:flex; align-items:center; justify-content:center; font-size:24px; width:24px; height:24px; color: var(--tspc-primary);"></span>
										</div>
										<input type="hidden" id="icon" name="icon" value="<?php echo $service ? esc_attr( $service['icon'] ) : 'dashicons-admin-site'; ?>">
									</div>
									
									<!-- Icon Picker Dropdown -->
									<div class="tspc-icon-picker-dropdown" id="tspc-icon-picker-dropdown" style="display:none; position: absolute; z-index: 100; margin-top: 8px;">
										<div class="tspc-icon-picker-search-bar">
											<span class="dashicons dashicons-search"></span>
											<input type="text" id="tspc-icon-search" placeholder="<?php esc_attr_e( 'Search icon...', 'tspc' ); ?>">
										</div>
										<div class="tspc-icon-picker-grid" id="tspc-icon-picker-grid">
											<!-- Dynamically populated via JS -->
										</div>
									</div>
								</div>

								<div class="tspc-field-group" style="margin-bottom: 0;">
									<label for="price" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e( 'All Package Price *', 'tspc' ); ?></label>
									<input type="number" step="0.01" id="price" name="price" value="<?php echo $service ? esc_attr( $service['price'] ) : ''; ?>" required placeholder="e.g. 15000" style="background: #ffffff; border-color: #cbd5e1;">
								</div>

								<div class="tspc-field-group" style="margin-bottom: 0;">
									<label for="discount_percent" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e( 'Total for discount (%)', 'tspc' ); ?></label>
									<input type="number" step="0.1" min="0" max="100" id="discount_percent" name="discount_percent" value="<?php echo $service && isset( $service['discount_percent'] ) ? esc_attr( (float) $service['discount_percent'] ) : '0'; ?>" placeholder="e.g. 15" style="background: #ffffff; border-color: #cbd5e1;">
								</div>

								<div class="tspc-field-group" style="margin-bottom: 0;">
									<label for="status" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e( 'Status', 'tspc' ); ?></label>
									<select id="status" name="status" style="background: #ffffff; border-color: #cbd5e1;">
										<option value="enabled" <?php echo ( $service && 'enabled' === $service['status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Enabled', 'tspc' ); ?></option>
										<option value="disabled" <?php echo ( $service && 'disabled' === $service['status'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Disabled', 'tspc' ); ?></option>
									</select>
								</div>
							</div>

							<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">

							<div class="tspc-field-group tspc-checkbox-field" style="margin-bottom: 0;">
								<label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
									<input type="checkbox" id="is_required" name="is_required" value="1" <?php checked( $service && isset( $service['is_required'] ) ? $service['is_required'] : 0, 1 ); ?> style="width: 18px; height: 18px; margin: 0; cursor: pointer;">
									<span style="font-weight: 600; color: var(--tspc-dark); font-size: 14px;"><?php esc_html_e( 'Always ON (Required)', 'tspc' ); ?></span>
									<span style="font-size: 13px; color: var(--tspc-text-muted); font-weight: normal; margin-left: 6px;">&mdash; <?php esc_html_e( 'Clients cannot turn OFF this service on the frontend.', 'tspc' ); ?></span>
								</label>
							</div>
						</div>
					</div>

					<!-- Right Column: Sub-services / Add-ons -->
					<div class="tspc-settings-card tspc-subservices-editor-card">
						<h3>
							<span class="dashicons dashicons-menu"></span>
							<?php esc_html_e( 'Sub-services / Add-ons (Optional)', 'tspc' ); ?>
						</h3>
						<hr>
						<p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Add specific sub-services or options under this main service. Clients can check/uncheck these to add to their estimate.', 'tspc' ); ?></p>

						<div id="tspc-sub-services-container" class="tspc-sub-services-editor-list">
							<!-- Sub-services row list -->
							<?php 
							$sub_services = array();
							if ( $service && ! empty( $service['sub_services'] ) ) {
								$sub_services = json_decode( $service['sub_services'], true );
							}
							$index = 0;
							foreach ( $sub_services as $sub ) :
							?>
								<div class="tspc-sub-service-group" data-index="<?php echo $index; ?>">
									<div class="tspc-sub-service-row">
										<input type="text" name="sub_services[<?php echo $index; ?>][title]" value="<?php echo esc_attr( $sub['title'] ); ?>" placeholder="<?php esc_attr_e( 'Sub-service Title', 'tspc' ); ?>">
										<input type="number" name="sub_services[<?php echo $index; ?>][price]" value="<?php echo esc_attr( $sub['price'] ); ?>" placeholder="<?php esc_attr_e( 'Price', 'tspc' ); ?>">
										<label class="tspc-sub-default-label">
											<input type="checkbox" name="sub_services[<?php echo $index; ?>][default_checked]" value="1" <?php checked( isset( $sub['default_checked'] ) ? $sub['default_checked'] : 0, 1 ); ?>>
											<?php esc_html_e( 'Default ON', 'tspc' ); ?>
										</label>
										<button type="button" class="button tspc-add-child-sub-btn" title="<?php esc_attr_e( 'Add child item under this sub-service', 'tspc' ); ?>">
											<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Sub-item', 'tspc' ); ?>
										</button>
										<button type="button" class="button tspc-remove-sub-btn" title="<?php esc_attr_e( 'Delete this sub-service', 'tspc' ); ?>"><span class="dashicons dashicons-trash"></span></button>
									</div>
									<div class="tspc-sub-children-container">
										<?php 
										if ( ! empty( $sub['children'] ) && is_array( $sub['children'] ) ) :
											$c_idx = 0;
											foreach ( $sub['children'] as $child ) :
										?>
											<div class="tspc-child-sub-row">
												<span class="tspc-child-arrow-indicator" title="<?php esc_attr_e( 'Child sub-service', 'tspc' ); ?>">
													<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v8a3 3 0 0 0 3 3h10"></path><polyline points="15 10 19 14 15 18"></polyline></svg>
												</span>
												<input type="text" name="sub_services[<?php echo $index; ?>][children][<?php echo $c_idx; ?>][title]" value="<?php echo esc_attr( $child['title'] ); ?>" placeholder="<?php esc_attr_e( 'Child item title', 'tspc' ); ?>">
												<input type="number" name="sub_services[<?php echo $index; ?>][children][<?php echo $c_idx; ?>][price]" value="<?php echo esc_attr( $child['price'] ); ?>" placeholder="<?php esc_attr_e( 'Price', 'tspc' ); ?>">
												<label class="tspc-sub-default-label">
													<input type="checkbox" name="sub_services[<?php echo $index; ?>][children][<?php echo $c_idx; ?>][default_checked]" value="1" <?php checked( isset( $child['default_checked'] ) ? $child['default_checked'] : 0, 1 ); ?>>
													<?php esc_html_e( 'Default ON', 'tspc' ); ?>
												</label>
												<button type="button" class="button tspc-remove-child-sub-btn" title="<?php esc_attr_e( 'Delete child option', 'tspc' ); ?>"><span class="dashicons dashicons-trash"></span></button>
											</div>
										<?php 
												$c_idx++;
											endforeach;
										endif;
										?>
									</div>
								</div>
							<?php 
								$index++;
							endforeach; 
							?>
						</div>

						<button type="button" id="tspc-add-sub-btn" class="button button-secondary tspc-add-sub-service-btn">
							<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Sub-service', 'tspc' ); ?>
						</button>
					</div>

					<!-- Full Width Actions Bar -->
					<div class="tspc-form-actions-full-width">
						<button type="submit" name="<?php echo 'add' === $action ? 'tspc_add_service' : 'tspc_edit_service'; ?>" class="button button-primary button-large"><?php esc_html_e( 'Save Service', 'tspc' ); ?></button>
						<a href="admin.php?page=tspc-services" class="button button-secondary button-large" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'tspc' ); ?></a>
					</div>
				</form>
			<?php else : ?>
				<!-- List view -->
				<div class="tspc-leads-top-bar">
					<h2><?php esc_html_e( 'Configured Services', 'tspc' ); ?></h2>
					<a href="admin.php?page=tspc-services&action=add" class="button button-primary tspc-export-btn">
						<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add New Service', 'tspc' ); ?>
					</a>
				</div>

				<?php 
				$services = TSPC_DB::get_services( 100, 0 );
				if ( empty( $services ) ) : 
				?>
					<div class="tspc-empty-state">
						<span class="dashicons dashicons-admin-tools"></span>
						<p><?php esc_html_e( 'No services created yet.', 'tspc' ); ?></p>
						<p class="description"><?php esc_html_e( 'Click "Add New Service" above to build your service catalog list.', 'tspc' ); ?></p>
					</div>
				<?php else : ?>
					<div class="tspc-table-card-wrapper">
						<table class="wp-list-table widefat fixed striped table-view-list tspc-leads-table">
							<thead>
								<tr>
									<th style="width: 60px;"><?php esc_html_e( 'Icon', 'tspc' ); ?></th>
									<th><?php esc_html_e( 'Service Title', 'tspc' ); ?></th>
									<th><?php esc_html_e( 'Description', 'tspc' ); ?></th>
									<th><?php esc_html_e( 'Price', 'tspc' ); ?></th>
									<th><?php esc_html_e( 'Status', 'tspc' ); ?></th>
									<th class="column-actions"><?php esc_html_e( 'Actions', 'tspc' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $services as $s ) : ?>
									<tr>
										<td>
											<span class="dashicons <?php echo esc_attr( $s['icon'] ); ?>" style="font-size:24px; width:24px; height:24px; color:var(--tspc-primary)"></span>
										</td>
										<td>
											<strong><?php echo esc_html( $s['title'] ); ?></strong>
										</td>
										<td>
											<?php echo esc_html( $s['description'] ); ?>
										</td>
										<td>
											<span class="tspc-lead-price">৳<?php echo esc_html( number_format( $s['price'] ) ); ?></span>
											<?php if ( isset( $s['discount_percent'] ) && (float) $s['discount_percent'] > 0 ) : ?>
												<div style="font-size: 11px; color: #16a34a; font-weight: 600; margin-top: 3px;">
													<?php printf( esc_html__( '%s%% discount', 'tspc' ), esc_html( (float) $s['discount_percent'] ) ); ?>
												</div>
											<?php endif; ?>
										</td>
										<td>
											<div style="display: flex; gap: 8px; flex-wrap: wrap;">
												<span class="tspc-badge <?php echo 'enabled' === $s['status'] ? 'badge-converted' : 'badge-lost'; ?>">
													<?php echo 'enabled' === $s['status'] ? esc_html__( 'Enabled', 'tspc' ) : esc_html__( 'Disabled', 'tspc' ); ?>
												</span>
												<?php if ( isset( $s['is_required'] ) && $s['is_required'] ) : ?>
													<span class="tspc-badge badge-new" style="background-color: #f59e0b; color: #fff;">
														<span class="dashicons dashicons-lock" style="font-size:12px; width:12px; height:12px; margin-right:4px;"></span><?php esc_html_e( 'Always ON', 'tspc' ); ?>
													</span>
												<?php endif; ?>
											</div>
										</td>
										<td class="column-actions">
											<div class="tspc-action-buttons">
												<a href="admin.php?page=tspc-services&action=edit&id=<?php echo esc_attr( $s['id'] ); ?>" class="button button-small tspc-edit-btn">
													<span class="dashicons dashicons-edit" style="font-size:14px; width:14px; height:14px;"></span> <?php esc_html_e( 'Edit', 'tspc' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=tspc-services&action=delete_service&id=' . $s['id'] ), 'tspc_delete_service_' . $s['id'] ) ); ?>" class="button button-small button-link-delete tspc-delete-lead" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this service?', 'tspc' ); ?>');">
													<span class="dashicons dashicons-trash" style="font-size:14px; width:14px; height:14px;"></span> <?php esc_html_e( 'Delete', 'tspc' ); ?>
												</a>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Settings Page
	 */
	public function render_settings_page() {
		$settings = self::get_settings();
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings updated successfully.', 'tspc' ) . '</p></div>';
		}
		?>
		<div class="wrap tspc-admin-wrapper">
			<header class="tspc-admin-header">
				<div class="tspc-logo-sec">
					<span class="dashicons dashicons-admin-generic"></span>
					<h1><?php esc_html_e( 'Calculator Rates & Settings', 'tspc' ); ?></h1>
				</div>
				<div class="tspc-tagline"><?php esc_html_e( 'Adjust currency formats, discount tiers, and notifications.', 'tspc' ); ?></div>
				<div class="tspc-shortcode-badge">
					<span class="dashicons dashicons-embed"></span>
					<span><?php esc_html_e( 'Embed Shortcode:', 'tspc' ); ?> <code class="tspc-code-btn" onclick="navigator.clipboard.writeText('[tspc_calculator]'); alert('Shortcode copied!');">[tspc_calculator]</code></span>
				</div>
			</header>

			<form method="post" action="" class="tspc-settings-form">
				<?php wp_nonce_field( 'tspc_save_settings_nonce' ); ?>

				<div class="tspc-settings-grid">
					
					<!-- General Section (Hidden per user request, preserving data via hidden inputs) -->
					<input type="hidden" name="currency" value="<?php echo esc_attr( $settings['currency'] ); ?>">
					<input type="hidden" name="admin_email" value="<?php echo esc_attr( $settings['admin_email'] ); ?>">
					<?php if ( $settings['enable_emails'] ) : ?>
						<input type="hidden" name="enable_emails" value="1">
					<?php endif; ?>
					<?php if ( isset( $settings['expand_first'] ) && $settings['expand_first'] ) : ?>
						<input type="hidden" name="expand_first" value="1">
					<?php endif; ?>


					<!-- Discount Settings -->
					<div class="tspc-settings-card">
						<h3><span class="dashicons dashicons-percent"></span> <?php esc_html_e( 'Discount Tiers Config', 'tspc' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Define percentages to deduct when multiple services are selected simultaneously.', 'tspc' ); ?></p>
						<hr>
						<div id="tspc-dynamic-discounts-list" class="tspc-sub-services-editor-list" style="margin-top: 15px;">
							<?php 
							$discounts = array();
							if ( isset( $settings['dynamic_discounts'] ) && ! empty( $settings['dynamic_discounts'] ) ) {
								$discounts = json_decode( $settings['dynamic_discounts'], true );
							}
							
							if ( ! is_array( $discounts ) ) {
								$discounts = array();
							}

							$index = 0;
							foreach ( $discounts as $discount_row ) :
							?>
								<div class="tspc-discount-row">
									<input type="number" name="dynamic_discounts[<?php echo $index; ?>][services]" value="<?php echo esc_attr( $discount_row['services'] ); ?>" placeholder="<?php esc_attr_e( 'No. of Services (e.g. 5)', 'tspc' ); ?>" required min="2">
									<input type="number" step="0.1" name="dynamic_discounts[<?php echo $index; ?>][discount]" value="<?php echo esc_attr( $discount_row['discount'] ); ?>" placeholder="<?php esc_attr_e( 'Discount % (e.g. 15)', 'tspc' ); ?>" required min="0">
									<button type="button" class="button tspc-remove-discount-btn"><span class="dashicons dashicons-trash"></span></button>
								</div>
							<?php 
								$index++;
							endforeach; 
							?>
						</div>
						<button type="button" id="tspc-add-discount-btn" class="button button-secondary tspc-add-sub-service-btn">
							<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Discount Tier', 'tspc' ); ?>
						</button>
					</div>

					<!-- Inquiry Form Fields Visibility -->
					<div class="tspc-settings-card">
						<h3><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Inquiry Form Fields Visibility', 'tspc' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Select which contact fields to show or hide on the frontend calculator form.', 'tspc' ); ?></p>
						<hr>
						<div class="tspc-visibility-settings-list" style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
							
							<div class="tspc-visibility-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
								<div class="tspc-vr-left" style="display: flex; align-items: center; gap: 12px;">
									<input type="checkbox" id="show_name" name="show_name" value="1" <?php checked( $settings['show_name'], 1 ); ?> style="margin: 0; width: 18px; height: 18px;">
									<label for="show_name" style="margin: 0; font-weight: 600; color: #1e293b; cursor: pointer;"><?php esc_html_e( 'Name Field', 'tspc' ); ?></label>
								</div>
								<div class="tspc-vr-right" style="display: flex; align-items: center; gap: 8px;">
									<input type="checkbox" id="req_name" name="req_name" value="1" <?php checked( isset($settings['req_name']) ? $settings['req_name'] : 1, 1 ); ?> style="margin: 0; width: 16px; height: 16px;">
									<label for="req_name" style="margin: 0; color: #64748b; font-size: 13px; cursor: pointer;"><?php esc_html_e( 'Required', 'tspc' ); ?></label>
								</div>
							</div>

							<div class="tspc-visibility-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
								<div class="tspc-vr-left" style="display: flex; align-items: center; gap: 12px;">
									<input type="checkbox" id="show_phone" name="show_phone" value="1" <?php checked( $settings['show_phone'], 1 ); ?> style="margin: 0; width: 18px; height: 18px;">
									<label for="show_phone" style="margin: 0; font-weight: 600; color: #1e293b; cursor: pointer;"><?php esc_html_e( 'Phone Field', 'tspc' ); ?></label>
								</div>
								<div class="tspc-vr-right" style="display: flex; align-items: center; gap: 8px;">
									<input type="checkbox" id="req_phone" name="req_phone" value="1" <?php checked( isset($settings['req_phone']) ? $settings['req_phone'] : 1, 1 ); ?> style="margin: 0; width: 16px; height: 16px;">
									<label for="req_phone" style="margin: 0; color: #64748b; font-size: 13px; cursor: pointer;"><?php esc_html_e( 'Required', 'tspc' ); ?></label>
								</div>
							</div>

							<div class="tspc-visibility-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
								<div class="tspc-vr-left" style="display: flex; align-items: center; gap: 12px;">
									<input type="checkbox" id="show_email" name="show_email" value="1" <?php checked( $settings['show_email'], 1 ); ?> style="margin: 0; width: 18px; height: 18px;">
									<label for="show_email" style="margin: 0; font-weight: 600; color: #1e293b; cursor: pointer;"><?php esc_html_e( 'Email Field', 'tspc' ); ?></label>
								</div>
								<div class="tspc-vr-right" style="display: flex; align-items: center; gap: 8px;">
									<input type="checkbox" id="req_email" name="req_email" value="1" <?php checked( isset($settings['req_email']) ? $settings['req_email'] : 1, 1 ); ?> style="margin: 0; width: 16px; height: 16px;">
									<label for="req_email" style="margin: 0; color: #64748b; font-size: 13px; cursor: pointer;"><?php esc_html_e( 'Required', 'tspc' ); ?></label>
								</div>
							</div>

							<div class="tspc-visibility-row" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
								<div class="tspc-vr-left" style="display: flex; align-items: center; gap: 12px;">
									<input type="checkbox" id="show_message" name="show_message" value="1" <?php checked( $settings['show_message'], 1 ); ?> style="margin: 0; width: 18px; height: 18px;">
									<label for="show_message" style="margin: 0; font-weight: 600; color: #1e293b; cursor: pointer;"><?php esc_html_e( 'Message Field', 'tspc' ); ?></label>
								</div>
								<div class="tspc-vr-right" style="display: flex; align-items: center; gap: 8px;">
									<input type="checkbox" id="req_message" name="req_message" value="1" <?php checked( isset($settings['req_message']) ? $settings['req_message'] : 0, 1 ); ?> style="margin: 0; width: 16px; height: 16px;">
									<label for="req_message" style="margin: 0; color: #64748b; font-size: 13px; cursor: pointer;"><?php esc_html_e( 'Required', 'tspc' ); ?></label>
								</div>
							</div>
						</div>
					</div>

					<!-- Accent Color Picker -->
					<div class="tspc-settings-card tspc-color-card">
						<h3><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Brand / Accent Color', 'tspc' ); ?></h3>
						<p class="description"><?php esc_html_e( 'This color applies to selected service cards, icon backgrounds, price highlights, the submit button, and other accent elements in the calculator.', 'tspc' ); ?></p>
						<hr>
						<div class="tspc-color-settings-wrapper" style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; margin-top: 20px; background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
							
							<div class="tspc-color-picker-sec" style="flex: 1; min-width: 250px;">
								<label for="accent_color" style="display: block; margin-bottom: 12px; font-weight: 600; color: #1e293b;"><?php esc_html_e( 'Primary Accent Color', 'tspc' ); ?></label>
								<input type="text" id="accent_color" name="accent_color"
									value="<?php echo esc_attr( isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#6366f1' ); ?>"
									class="tspc-color-picker"
									data-default-color="#6366f1"
								>
								<p class="description" style="margin-top: 12px;"><?php esc_html_e( 'Click the color swatch to open the picker. Default: Indigo (#6366f1).', 'tspc' ); ?></p>
							</div>

							<div class="tspc-color-preview-bar" style="margin: 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; width: 220px; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
								<div style="font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'Live Preview', 'tspc' ); ?></div>
								<div class="tspc-preview-chip" id="tspc-preview-chip" style="background: <?php echo esc_attr( isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#6366f1' ); ?>; margin-bottom: 15px;">
									<span class="dashicons dashicons-yes"></span>
								</div>
								<div class="tspc-preview-labels">
									<span class="tspc-preview-label-item"><?php esc_html_e( 'Selected card border & icon', 'tspc' ); ?></span>
									<span class="tspc-preview-label-item"><?php esc_html_e( 'Price highlight & total', 'tspc' ); ?></span>
									<span class="tspc-preview-label-item"><?php esc_html_e( 'Submit button background', 'tspc' ); ?></span>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="tspc-settings-submit">
					<button type="submit" name="tspc_save_settings" class="button button-primary button-large"><?php esc_html_e( 'Save Configuration settings', 'tspc' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}
}
new TSPC_Admin();
