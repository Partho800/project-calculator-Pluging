<?php
/**
 * Frontend Calculator Template - 2 Column Layout
 *
 * @package TSPC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = TSPC_Admin::get_settings();
$currency     = $settings['currency'];
$accent_color = isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#6366f1';

// Derive a darker hover shade (~15% darker) via simple hex manipulation
list( $r, $g, $b ) = sscanf( ltrim( $accent_color, '#' ), "%02x%02x%02x" );
$hover_color = sprintf( '#%02x%02x%02x',
	max( 0, $r - 38 ),
	max( 0, $g - 38 ),
	max( 0, $b - 38 )
);
// Derive a very light glow (8% opacity) for box-shadow
$glow_color = 'rgba(' . $r . ',' . $g . ',' . $b . ',0.08)';

// Fetch all active enabled services
$services = TSPC_DB::get_services( 100, 0, true );
?>
<div id="tspc-calculator-container" class="tspc-calculator-wrapper-v2"
	style="--tspc-primary:<?php echo esc_attr( $accent_color ); ?>; --tspc-primary-hover:<?php echo esc_attr( $hover_color ); ?>; --tspc-glow:<?php echo esc_attr( $glow_color ); ?>;"
	data-currency="<?php echo esc_attr( $currency ); ?>"
	data-discount-2="<?php echo esc_attr( $settings['discount_2'] ); ?>"
	data-discount-3="<?php echo esc_attr( $settings['discount_3'] ); ?>"
	data-discount-4="<?php echo esc_attr( $settings['discount_4'] ); ?>"
	data-show-name="<?php echo esc_attr( isset( $settings['show_name'] ) ? $settings['show_name'] : 1 ); ?>"
	data-show-phone="<?php echo esc_attr( isset( $settings['show_phone'] ) ? $settings['show_phone'] : 1 ); ?>"
	data-show-email="<?php echo esc_attr( isset( $settings['show_email'] ) ? $settings['show_email'] : 1 ); ?>"
	data-show-message="<?php echo esc_attr( isset( $settings['show_message'] ) ? $settings['show_message'] : 1 ); ?>"
	data-expand-first="<?php echo esc_attr( isset( $settings['expand_first'] ) ? $settings['expand_first'] : 1 ); ?>"
>
	<div class="tspc-calc-grid">
		<!-- Left Column: Services Checklist -->
		<div class="tspc-services-col">
			<div class="tspc-services-header-row">
				<h2 class="tspc-col-title"><?php esc_html_e( 'Choose Services', 'tspc' ); ?></h2>
				<button type="button" id="tspc-clear-all-btn" class="tspc-clear-btn" style="display: none;">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear All', 'tspc' ); ?>
				</button>
			</div>
			<p class="tspc-col-subtitle"><?php esc_html_e( 'Select the services you need to estimate your final project cost.', 'tspc' ); ?></p>
			
			<?php if ( empty( $services ) ) : ?>
				<div class="tspc-no-services">
					<p><?php esc_html_e( 'No services are currently configured.', 'tspc' ); ?></p>
				</div>
			<?php else : ?>
				<div class="tspc-services-list">
					<?php foreach ( $services as $s ) : 
						$sub_services = array();
						if ( ! empty( $s['sub_services'] ) ) {
							$sub_services = json_decode( $s['sub_services'], true );
						}
						$has_subs = ! empty( $sub_services ) && is_array( $sub_services );
					?>
						<div class="tspc-service-card-container" data-service-id="<?php echo esc_attr( $s['id'] ); ?>">
							<label class="tspc-service-item-card <?php echo $has_subs ? 'tspc-has-sub-services' : ''; ?>">
								<input type="checkbox" name="services[]" value="<?php echo esc_attr( $s['id'] ); ?>" 
									data-price="<?php echo esc_attr( $s['price'] ); ?>" 
									data-title="<?php echo esc_attr( $s['title'] ); ?>" 
									class="tspc-service-checkbox"
								>
								<div class="tspc-service-card-body">
									<!-- Check Indicator -->
									<div class="tspc-checkbox-custom">
										<div class="tspc-checkbox-inner"></div>
									</div>

									<!-- Service Icon -->
									<?php if ( ! empty( $s['icon'] ) ) : ?>
										<div class="tspc-service-icon-box">
											<span class="dashicons <?php echo esc_attr( $s['icon'] ); ?>"></span>
										</div>
									<?php endif; ?>

									<!-- Service Description & Title -->
									<div class="tspc-service-details">
										<h3><?php echo esc_html( $s['title'] ); ?></h3>
										<?php if ( ! empty( $s['description'] ) ) : ?>
											<p><?php echo esc_html( $s['description'] ); ?></p>
										<?php endif; ?>
										<div class="tspc-service-price-tag" data-base-price="<?php echo esc_attr( $s['price'] ); ?>">
											<span class="tspc-price-symbol"><?php echo esc_html( $currency ); ?></span><span class="tspc-price-tag-display" data-current-price="<?php echo esc_attr( $s['price'] ); ?>"><?php echo esc_html( number_format( $s['price'] ) ); ?></span>
										</div>
									</div>

									<!-- Chevron Indicator for Sub-services -->
									<?php if ( $has_subs ) : ?>
										<div class="tspc-chevron-indicator">
											<span class="dashicons dashicons-arrow-down-alt2"></span>
										</div>
									<?php endif; ?>
								</div>
							</label>

							<!-- Sub-services checklist -->
							<?php if ( $has_subs ) : ?>
								<div class="tspc-sub-services-wrapper" style="display: none;">
									<div class="tspc-sub-services-list">
										<?php foreach ( $sub_services as $sub_index => $sub ) : ?>
											<label class="tspc-sub-service-item">
												<input type="checkbox" name="sub_services[<?php echo esc_attr( $s['id'] ); ?>][]" value="<?php echo esc_attr( $sub_index ); ?>" 
													data-price="<?php echo esc_attr( $sub['price'] ); ?>" 
													data-title="<?php echo esc_attr( $sub['title'] ); ?>" 
													data-parent-id="<?php echo esc_attr( $s['id'] ); ?>"
													data-parent-title="<?php echo esc_attr( $s['title'] ); ?>"
													class="tspc-sub-service-checkbox"
													disabled
												>
												<div class="tspc-sub-checkbox-custom">
													<div class="tspc-sub-checkbox-inner"></div>
												</div>
												<span class="tspc-sub-service-title"><?php echo esc_html( $sub['title'] ); ?></span>
												<span class="tspc-sub-service-price">+<?php echo esc_html( $currency ) . esc_html( number_format( $sub['price'] ) ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Right Column: Cart Summary & Quote Form -->
		<div class="tspc-summary-col">
			<div class="tspc-sticky-sidebar">
				<h2 class="tspc-col-title"><?php esc_html_e( 'Quote Summary', 'tspc' ); ?></h2>
				
				<!-- Selected Services List -->
				<div class="tspc-selected-panel">
					<h3><?php esc_html_e( 'Selected Services', 'tspc' ); ?></h3>
					<div id="tspc-selected-services-list" class="tspc-selected-items-empty">
						<p><?php esc_html_e( 'No services selected.', 'tspc' ); ?></p>
					</div>
				</div>

				<!-- Pricing calculations -->
				<div class="tspc-pricing-breakdown">
					<div class="tspc-price-row">
						<span><?php esc_html_e( 'Sub Total', 'tspc' ); ?></span>
						<span class="tspc-price-val"><span class="currency"><?php echo esc_html( $currency ); ?></span><span id="tspc-subtotal-val">0</span></span>
					</div>
					<div class="tspc-price-row discount-row" style="display:none;">
						<span id="tspc-discount-label"><?php esc_html_e( 'Discount', 'tspc' ); ?></span>
						<span class="tspc-price-val text-success">-<span class="currency"><?php echo esc_html( $currency ); ?></span><span id="tspc-discount-val">0</span></span>
					</div>
					<hr class="tspc-break-line">
					<div class="tspc-price-row total-row">
						<span><?php esc_html_e( 'Total', 'tspc' ); ?></span>
						<span class="tspc-price-val total-price"><span class="currency"><?php echo esc_html( $currency ); ?></span><span id="tspc-total-val">0</span></span>
					</div>
				</div>

				<!-- Contact Form -->
				<form id="tspc-client-quote-form" method="post" action="" class="tspc-quote-submission-form">
					<?php wp_nonce_field( 'tspc_submit_calculator_nonce', 'tspc_nonce' ); ?>
					
					<h3><?php esc_html_e( 'Request Detailed Quote', 'tspc' ); ?></h3>

					<?php 
					$show_name = isset( $settings['show_name'] ) ? (int) $settings['show_name'] : 1;
					$show_phone = isset( $settings['show_phone'] ) ? (int) $settings['show_phone'] : 1;
					$show_email = isset( $settings['show_email'] ) ? (int) $settings['show_email'] : 1;
					$show_message = isset( $settings['show_message'] ) ? (int) $settings['show_message'] : 1;
					?>

					<!-- Name + Phone side by side if both shown, else stacked -->
					<?php if ( $show_name && $show_phone ) : ?>
						<div class="tspc-form-row-2col">
							<div class="tspc-form-field">
								<label for="tspc-name"><?php esc_html_e( 'Full Name *', 'tspc' ); ?></label>
								<input type="text" id="tspc-name" name="name" required placeholder="<?php esc_attr_e( 'Enter your name', 'tspc' ); ?>">
							</div>

							<div class="tspc-form-field">
								<label for="tspc-phone"><?php esc_html_e( 'Phone Number *', 'tspc' ); ?></label>
								<div class="tspc-phone-wrapper">
									<span class="tspc-phone-icon">
										<span class="dashicons dashicons-phone"></span>
									</span>
									<input type="tel" id="tspc-phone" name="phone" required placeholder="<?php esc_attr_e( 'Enter your phone number', 'tspc' ); ?>"
										pattern="[\+]?[0-9\s\-\(\)]{7,20}"
										title="<?php esc_attr_e( 'Please enter a valid phone number', 'tspc' ); ?>">
								</div>
							</div>
						</div>
					<?php else : ?>
						<?php if ( $show_name ) : ?>
							<div class="tspc-form-field">
								<label for="tspc-name"><?php esc_html_e( 'Full Name *', 'tspc' ); ?></label>
								<input type="text" id="tspc-name" name="name" required placeholder="<?php esc_attr_e( 'Enter your name', 'tspc' ); ?>">
							</div>
						<?php endif; ?>

						<?php if ( $show_phone ) : ?>
							<div class="tspc-form-field">
								<label for="tspc-phone"><?php esc_html_e( 'Phone Number *', 'tspc' ); ?></label>
								<div class="tspc-phone-wrapper">
									<span class="tspc-phone-icon">
										<span class="dashicons dashicons-phone"></span>
									</span>
									<input type="tel" id="tspc-phone" name="phone" required placeholder="<?php esc_attr_e( 'Enter your phone number', 'tspc' ); ?>"
										pattern="[\+]?[0-9\s\-\(\)]{7,20}"
										title="<?php esc_attr_e( 'Please enter a valid phone number', 'tspc' ); ?>">
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $show_email ) : ?>
						<div class="tspc-form-field">
							<label for="tspc-email"><?php esc_html_e( 'Email Address *', 'tspc' ); ?></label>
							<input type="email" id="tspc-email" name="email" required placeholder="<?php esc_attr_e( 'Enter your email address', 'tspc' ); ?>">
						</div>
					<?php endif; ?>

					<?php if ( $show_message ) : ?>
						<div class="tspc-form-field">
							<label for="tspc-msg"><?php esc_html_e( 'Brief Message / Requirement Description', 'tspc' ); ?></label>
							<textarea id="tspc-msg" name="message" rows="3" placeholder="<?php esc_attr_e( 'Enter your project requirements...', 'tspc' ); ?>"></textarea>
						</div>
					<?php endif; ?>

					<button type="submit" id="tspc-submit-btn" class="tspc-submit-btn-block" disabled>
						<?php esc_html_e( 'Send Proposal Inquiry', 'tspc' ); ?>
					</button>
				</form>
			</div>
		</div>
	</div>

	<!-- Status Overlays -->
	<div id="tspc-overlay-success" class="tspc-status-overlay" style="display:none;">
		<div class="tspc-overlay-content">
			<div class="tspc-success-checkmark">
				<div class="check-icon">
					<span class="icon-line line-tip"></span>
					<span class="icon-line line-long"></span>
					<div class="icon-circle"></div>
					<div class="icon-fix"></div>
				</div>
			</div>
			<h2><?php esc_html_e( 'Quote Submitted!', 'tspc' ); ?></h2>
			<p class="tspc-overlay-client-msg">
				<?php esc_html_e( 'Thank you! We have logged your selection and will get back to you shortly.', 'tspc' ); ?>
			</p>
			<button type="button" class="tspc-btn tspc-btn-primary" id="tspc-reset-calculator">
				<?php esc_html_e( 'Reset Calculator', 'tspc' ); ?>
			</button>
		</div>
	</div>

	<div id="tspc-overlay-error" class="tspc-status-overlay error-mode" style="display:none;">
		<div class="tspc-overlay-content">
			<div class="tspc-error-cross">
				<div class="cross-icon">&#10006;</div>
			</div>
			<h2><?php esc_html_e( 'Submission Error', 'tspc' ); ?></h2>
			<p class="tspc-overlay-err-desc">
				<?php esc_html_e( 'Failed to send quote. Please fill in your name/email and select at least one service.', 'tspc' ); ?>
			</p>
			<button type="button" class="tspc-btn tspc-btn-secondary" id="tspc-dismiss-error">
				<?php esc_html_e( 'Return to Form', 'tspc' ); ?>
			</button>
		</div>
	</div>
</div>
