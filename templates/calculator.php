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
$r = null !== $r ? $r : 99;
$g = null !== $g ? $g : 102;
$b = null !== $b ? $b : 241;

$hover_color = sprintf( '#%02x%02x%02x',
	max( 0, $r - 38 ),
	max( 0, $g - 38 ),
	max( 0, $b - 38 )
);
$rgb_str          = $r . ',' . $g . ',' . $b;
$glow_color       = 'rgba(' . $rgb_str . ',0.08)';
$sub_bg_color     = 'rgba(' . $rgb_str . ',0.08)';
$sub_border_color = 'rgba(' . $rgb_str . ',0.18)';

// Fetch all active enabled services
$services = TSPC_DB::get_services( 100, 0, true );
?>
<div id="tspc-calculator-container" class="tspc-calculator-wrapper-v2"
	style="--tspc-primary:<?php echo esc_attr( $accent_color ); ?>; --tspc-primary-hover:<?php echo esc_attr( $hover_color ); ?>; --tspc-primary-rgb:<?php echo esc_attr( $rgb_str ); ?>; --tspc-glow:<?php echo esc_attr( $glow_color ); ?>; --tspc-sub-bg:<?php echo esc_attr( $sub_bg_color ); ?>; --tspc-sub-border:<?php echo esc_attr( $sub_border_color ); ?>;"
	data-currency="<?php echo esc_attr( $currency ); ?>"
	data-dynamic-discounts="<?php echo esc_attr( isset( $settings['dynamic_discounts'] ) ? $settings['dynamic_discounts'] : '[]' ); ?>"
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
				<button type="button" id="tspc-clear-all-btn" class="tspc-clear-btn" style="display: none;">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear All', 'tspc' ); ?>
				</button>
			</div>
			
			<?php if ( empty( $services ) ) : ?>
				<div class="tspc-no-services">
					<p><?php esc_html_e( 'No services are currently configured.', 'tspc' ); ?></p>
				</div>
			<?php else : ?>
				<div class="tspc-services-list">
					<?php 
					$service_index = 0;
					foreach ( $services as $s ) : 
						$sub_services = array();
						if ( ! empty( $s['sub_services'] ) ) {
							$sub_services = json_decode( $s['sub_services'], true );
						}
						$has_subs = ! empty( $sub_services ) && is_array( $sub_services );
						$is_req      = isset( $s['is_required'] ) && $s['is_required'];
						$expand_this = $is_req || ( $service_index === 0 && isset( $settings['expand_first'] ) && $settings['expand_first'] );
					?>
						<div class="tspc-service-card-container <?php echo $expand_this ? 'tspc-expanded' : ''; ?>" data-service-id="<?php echo esc_attr( $s['id'] ); ?>">
							<label class="tspc-service-item-card <?php echo $has_subs ? 'tspc-has-sub-services' : ''; ?>">
								<input type="checkbox" name="services[]" value="<?php echo esc_attr( $s['id'] ); ?>" 
									data-price="<?php echo esc_attr( $s['price'] ); ?>" 
									data-title="<?php echo esc_attr( $s['title'] ); ?>" 
									class="tspc-service-checkbox"
									<?php echo $is_req ? 'checked data-required="1"' : ''; ?>
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
										<div class="tspc-service-price-tag" style="display: none;" data-base-price="<?php echo esc_attr( $s['price'] ); ?>" data-discount-percent="<?php echo esc_attr( isset( $s['discount_percent'] ) ? (float) $s['discount_percent'] : 0 ); ?>">
											<div class="tspc-price-line">
												<span class="tspc-all-pkg-label" style="display: none;"><?php esc_html_e( 'All Package Price ', 'tspc' ); ?></span><span class="tspc-price-symbol"><?php echo esc_html( $currency ); ?></span><span class="tspc-price-tag-display" data-current-price="<?php echo esc_attr( $s['price'] ); ?>"><?php echo esc_html( number_format( $s['price'] ) ); ?></span>
											</div>
										</div>
									</div>

								</div>
							</label>

							<!-- Sub-services checklist -->
							<?php if ( $has_subs ) : ?>
								<?php 
								// Check if any sub-service is marked as default_checked
								$has_any_default = false;
								foreach ( $sub_services as $temp_sub ) {
									if ( ! empty( $temp_sub['default_checked'] ) ) {
										$has_any_default = true;
										break;
									}
								}
								?>
								<div class="tspc-sub-services-wrapper" style="<?php echo $expand_this ? 'display: block;' : 'display: none;'; ?>">
									<div class="tspc-sub-services-list">
										<?php foreach ( $sub_services as $sub_index => $sub ) : ?>
											<?php 
											$is_sub_checked = false;
											if ( $is_req ) {
												if ( $has_any_default ) {
													$is_sub_checked = ! empty( $sub['default_checked'] );
												} else {
													$is_sub_checked = true;
												}
											} else {
												$is_sub_checked = false;
											}
											$has_children = ! empty( $sub['children'] ) && is_array( $sub['children'] );
											$is_children_expanded = $has_children && ! empty( $sub['default_checked'] );
											?>
											<label class="tspc-sub-service-item tspc-parent-sub-item <?php echo $is_sub_checked ? 'tspc-sub-checked' : ''; ?> <?php echo $has_children ? 'tspc-has-children' : ''; ?> <?php echo $is_children_expanded ? 'tspc-sub-expanded' : ''; ?>" data-sub-index="<?php echo esc_attr( $sub_index ); ?>">
												<input type="checkbox" name="sub_services[<?php echo esc_attr( $s['id'] ); ?>][<?php echo esc_attr( $sub_index ); ?>]" value="<?php echo esc_attr( $sub_index ); ?>" 
													data-price="<?php echo esc_attr( $sub['price'] ); ?>" 
													data-title="<?php echo esc_attr( $sub['title'] ); ?>" 
													data-parent-id="<?php echo esc_attr( $s['id'] ); ?>"
													data-parent-title="<?php echo esc_attr( $s['title'] ); ?>"
													data-sub-index="<?php echo esc_attr( $sub_index ); ?>"
													data-default-checked="<?php echo ! empty( $sub['default_checked'] ) ? '1' : '0'; ?>"
													class="tspc-sub-service-checkbox tspc-parent-sub-checkbox"
													<?php echo $is_sub_checked ? 'checked' : ''; ?>
													<?php echo $is_req ? '' : 'disabled'; ?>
												>
												<div class="tspc-sub-checkbox-custom">
													<div class="tspc-sub-checkbox-inner"></div>
												</div>
												<span class="tspc-sub-service-title"><?php echo esc_html( $sub['title'] ); ?></span>
												<span class="tspc-sub-service-price">+<?php echo esc_html( $currency ) . esc_html( number_format( $sub['price'] ) ); ?></span>
												<?php if ( $has_children ) : ?>
													<span class="tspc-sub-chevron-toggle" title="<?php esc_attr_e( 'Toggle sub-items', 'tspc' ); ?>">
														<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
															<polyline points="6 9 12 15 18 9"></polyline>
														</svg>
													</span>
												<?php endif; ?>
											</label>

											<?php if ( $has_children ) : ?>
												<div class="tspc-sub-children-wrapper" data-parent-sub-index="<?php echo esc_attr( $sub_index ); ?>" style="<?php echo $is_children_expanded ? 'display: block;' : 'display: none;'; ?>">
													<?php foreach ( $sub['children'] as $child_index => $child ) : ?>
														<?php 
														$is_child_checked = false;
														if ( $is_sub_checked && ! empty( $child['default_checked'] ) ) {
															$is_child_checked = true;
														}
														?>
														<label class="tspc-sub-service-item tspc-sub-child-item <?php echo $is_child_checked ? 'tspc-sub-checked' : ''; ?>">
															<input type="checkbox" name="sub_services[<?php echo esc_attr( $s['id'] ); ?>][<?php echo esc_attr( $sub_index ); ?>][children][<?php echo esc_attr( $child_index ); ?>]" 
																value="<?php echo esc_attr( $sub_index . '-' . $child_index ); ?>" 
																data-price="<?php echo esc_attr( $child['price'] ); ?>" 
																data-title="<?php echo esc_attr( $child['title'] ); ?>" 
																data-parent-id="<?php echo esc_attr( $s['id'] ); ?>"
																data-parent-title="<?php echo esc_attr( $sub['title'] ); ?>"
																data-parent-sub-index="<?php echo esc_attr( $sub_index ); ?>"
																data-child-index="<?php echo esc_attr( $child_index ); ?>"
																data-default-checked="<?php echo ! empty( $child['default_checked'] ) ? '1' : '0'; ?>"
																class="tspc-sub-service-checkbox tspc-child-sub-checkbox"
																<?php echo $is_child_checked ? 'checked' : ''; ?>
																<?php echo $is_sub_checked ? '' : 'disabled'; ?>
															>
															<div class="tspc-sub-checkbox-custom">
																<div class="tspc-sub-checkbox-inner"></div>
															</div>
															<span class="tspc-sub-service-title"><?php echo esc_html( $child['title'] ); ?></span>
															<span class="tspc-sub-service-price">+<?php echo esc_html( $currency ) . esc_html( number_format( $child['price'] ) ); ?></span>
														</label>
													<?php endforeach; ?>
												</div>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php 
						$service_index++;
					endforeach; 
					?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Right Column: Cart Summary & Quote Form -->
		<div class="tspc-summary-col">
			<div class="tspc-sticky-sidebar">
				<h2 class="tspc-col-title"><?php esc_html_e( 'Cost Summary', 'tspc' ); ?></h2>
				
				<!-- Selected Services List -->
				<div class="tspc-selected-panel">
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
					
					<h3 class="tspc-form-heading">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
							<polyline points="14 2 14 8 20 8"></polyline>
							<line x1="16" y1="13" x2="8" y2="13"></line>
							<line x1="16" y1="17" x2="8" y2="17"></line>
							<polyline points="10 9 9 9 8 9"></polyline>
						</svg>
						<span><?php esc_html_e( 'Request a Quote', 'tspc' ); ?></span>
					</h3>

					<?php 
					$show_name = isset( $settings['show_name'] ) ? (int) $settings['show_name'] : 1;
					$show_phone = isset( $settings['show_phone'] ) ? (int) $settings['show_phone'] : 1;
					$show_email = isset( $settings['show_email'] ) ? (int) $settings['show_email'] : 1;
					$show_message = isset( $settings['show_message'] ) ? (int) $settings['show_message'] : 1;

					$req_name = isset( $settings['req_name'] ) ? (int) $settings['req_name'] : 1;
					$req_phone = isset( $settings['req_phone'] ) ? (int) $settings['req_phone'] : 1;
					$req_email = isset( $settings['req_email'] ) ? (int) $settings['req_email'] : 1;
					$req_message = isset( $settings['req_message'] ) ? (int) $settings['req_message'] : 0;
					?>

					<!-- Name + Phone side by side if both shown, else stacked -->
					<?php if ( $show_name && $show_phone ) : ?>
						<div class="tspc-form-row-2col">
							<div class="tspc-form-field">
								<label for="tspc-name"><?php esc_html_e( 'Full Name', 'tspc' ); ?></label>
								<input type="text" id="tspc-name" name="name" <?php echo $req_name ? 'required' : ''; ?> placeholder="<?php esc_attr_e( 'Your Name', 'tspc' ); ?>">
							</div>

							<div class="tspc-form-field">
								<label for="tspc-phone"><?php esc_html_e( 'Phone Number', 'tspc' ); ?></label>
								<div class="tspc-phone-wrapper">
									<input type="tel" id="tspc-phone" name="phone" <?php echo $req_phone ? 'required' : ''; ?> placeholder="<?php esc_attr_e( 'Phone Number', 'tspc' ); ?>"
										pattern="[\+]?[0-9\s\-\(\)]{7,20}"
										title="<?php esc_attr_e( 'Please enter a valid phone number', 'tspc' ); ?>">
								</div>
							</div>
						</div>
					<?php else : ?>
						<?php if ( $show_name ) : ?>
							<div class="tspc-form-field">
								<label for="tspc-name"><?php esc_html_e( 'Full Name', 'tspc' ); ?></label>
								<input type="text" id="tspc-name" name="name" <?php echo $req_name ? 'required' : ''; ?> placeholder="<?php esc_attr_e( 'Your Name', 'tspc' ); ?>">
							</div>
						<?php endif; ?>

						<?php if ( $show_phone ) : ?>
							<div class="tspc-form-field">
								<label for="tspc-phone"><?php esc_html_e( 'Phone Number', 'tspc' ); ?></label>
								<div class="tspc-phone-wrapper">
									<input type="tel" id="tspc-phone" name="phone" <?php echo $req_phone ? 'required' : ''; ?> placeholder="<?php esc_attr_e( 'Phone Number', 'tspc' ); ?>"
										pattern="[\+]?[0-9\s\-\(\)]{7,20}"
										title="<?php esc_attr_e( 'Please enter a valid phone number', 'tspc' ); ?>">
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $show_email ) : ?>
						<div class="tspc-form-field">
							<label for="tspc-email"><?php esc_html_e( 'Email Address', 'tspc' ); ?></label>
							<input type="email" id="tspc-email" name="email" <?php echo $req_email ? 'required' : ''; ?> placeholder="<?php esc_attr_e( 'Your Email', 'tspc' ); ?>">
						</div>
					<?php endif; ?>

					<?php if ( $show_message ) : ?>
						<div class="tspc-form-field">
							<label for="tspc-msg"><?php esc_html_e( 'Project Details / Message', 'tspc' ); ?></label>
							<textarea id="tspc-msg" name="message" rows="3" <?php echo $req_message ? 'required' : ''; ?> placeholder="<?php esc_attr_e( 'Project requirements...', 'tspc' ); ?>"></textarea>
						</div>
					<?php endif; ?>

					<button type="submit" id="tspc-submit-btn" class="tspc-submit-btn-block" disabled>
						<?php esc_html_e( 'Send Cost Inquiry', 'tspc' ); ?>
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
			<h2><?php esc_html_e( 'Estimate Submitted!', 'tspc' ); ?></h2>
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
				<?php esc_html_e( 'Failed to send request. Please fill in your name/email and select at least one service.', 'tspc' ); ?>
			</p>
			<button type="button" class="tspc-btn tspc-btn-secondary" id="tspc-dismiss-error">
				<?php esc_html_e( 'Return to Form', 'tspc' ); ?>
			</button>
		</div>
	</div>
</div>
