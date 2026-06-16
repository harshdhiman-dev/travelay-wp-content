<?php
/**
 * Admin profile editor template.
 *
 * @package TravelayCulturalWelcome
 *
 * @var array $profile Profile data.
 * @var array $gestures Gestures.
 * @var array $voice_catalog Voice catalog.
 * @var array $voice_meta Catalog meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notice       = isset( $_GET['tcw_notice'] ) ? sanitize_key( wp_unslash( $_GET['tcw_notice'] ) ) : '';
$is_edit      = ! empty( $profile['id'] );
$hero_title   = $is_edit ? $profile['display_name'] : __( 'Add Welcome Profile', 'travelay-cultural-welcome' );
$gesture_lbl  = isset( $gestures[ $profile['gesture'] ] ) ? $gestures[ $profile['gesture'] ]['label'] : $profile['gesture'];
$status_chip  = 'tcw-stat-chip--' . sanitize_html_class( $profile['status'] );
?>
<div class="wrap tcw-admin-wrap tcw-admin-page">
	<?php
	TCW_Admin::render_hero(
		array(
			'title'   => $hero_title,
			'tagline' => $is_edit
				? __( 'Fine-tune the welcome experience for this page or destination.', 'travelay-cultural-welcome' )
				: __( 'Create a new cultural welcome profile.', 'travelay-cultural-welcome' ),
			'icon'    => $is_edit ? 'dashicons-id-alt' : 'dashicons-plus-alt2',
			'chips'   => $is_edit ? array(
				array(
					'strong' => ucfirst( (string) $profile['status'] ),
					'text'   => __( 'Status', 'travelay-cultural-welcome' ),
					'class'  => $status_chip,
				),
				array(
					'strong' => ! empty( $profile['is_enabled'] ) ? __( 'On', 'travelay-cultural-welcome' ) : __( 'Off', 'travelay-cultural-welcome' ),
					'text'   => __( 'Enabled', 'travelay-cultural-welcome' ),
					'class'  => ! empty( $profile['is_enabled'] ) ? 'tcw-stat-chip--live' : '',
				),
				array(
					'strong' => $gesture_lbl,
					'text'   => __( 'Gesture', 'travelay-cultural-welcome' ),
				),
			) : array(),
			'actions' => '<a class="tcw-hero-link" href="' . esc_url( admin_url( 'admin.php?page=tcw-dashboard' ) ) . '"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>' . esc_html__( 'Back to Profiles', 'travelay-cultural-welcome' ) . '</a>',
		)
	);
	?>

	<?php if ( 'saved' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Profile saved successfully.', 'travelay-cultural-welcome' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tcw-admin-form">
		<?php wp_nonce_field( 'tcw_save_profile' ); ?>
		<input type="hidden" name="action" value="tcw_save_profile" />
		<input type="hidden" name="profile_id" value="<?php echo esc_attr( (int) $profile['id'] ); ?>" />

		<div class="tcw-form-layout">
			<section class="tcw-form-card">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-location" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Profile Identity', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Link this welcome to a page, post, or destination.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><label for="entity_type"><?php esc_html_e( 'Entity Type', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select name="entity_type" id="entity_type">
								<option value="page" <?php selected( $profile['entity_type'], 'page' ); ?>><?php esc_html_e( 'Page', 'travelay-cultural-welcome' ); ?></option>
								<option value="post" <?php selected( $profile['entity_type'], 'post' ); ?>><?php esc_html_e( 'Post', 'travelay-cultural-welcome' ); ?></option>
								<option value="country" <?php selected( $profile['entity_type'], 'country' ); ?>><?php esc_html_e( 'Country', 'travelay-cultural-welcome' ); ?></option>
								<option value="city" <?php selected( $profile['entity_type'], 'city' ); ?>><?php esc_html_e( 'City (phase 2)', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="display_name"><?php esc_html_e( 'Display Name', 'travelay-cultural-welcome' ); ?></label></th>
						<td><input class="regular-text" type="text" name="display_name" id="display_name" value="<?php echo esc_attr( $profile['display_name'] ); ?>" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="location_slug"><?php esc_html_e( 'Location Slug', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<input class="regular-text" type="text" name="location_slug" id="location_slug" value="<?php echo esc_attr( $profile['location_slug'] ); ?>" required />
							<p class="description"><?php esc_html_e( 'Page slug or nested path, e.g. japan, about/team.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="country_code"><?php esc_html_e( 'Country Code', 'travelay-cultural-welcome' ); ?></label></th>
						<td><input class="regular-text" type="text" name="country_code" id="country_code" maxlength="2" value="<?php echo esc_attr( $profile['country_code'] ); ?>" placeholder="US" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="parent_country_slug"><?php esc_html_e( 'Parent Country Slug', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<input class="regular-text" type="text" name="parent_country_slug" id="parent_country_slug" value="<?php echo esc_attr( $profile['parent_country_slug'] ); ?>" />
							<p class="description"><?php esc_html_e( 'For city profiles. Leave empty for countries and pages.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="page_id"><?php esc_html_e( 'Linked Page ID', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<input type="number" class="small-text" name="page_id" id="page_id" value="<?php echo esc_attr( (int) $profile['page_id'] ); ?>" min="0" />
							<?php if ( $profile['page_id'] && get_permalink( $profile['page_id'] ) ) : ?>
								<a class="tcw-link-view" href="<?php echo esc_url( get_permalink( $profile['page_id'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View linked page', 'travelay-cultural-welcome' ); ?></a>
							<?php endif; ?>
							<p class="description"><?php esc_html_e( 'Set automatically when you Sync all pages.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
				</table>
			</section>

			<section class="tcw-form-card tcw-form-card--featured">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-welcome-write-blog" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Welcome Message & Gesture', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'The heart of the cultural welcome experience.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gesture"><?php esc_html_e( 'Gesture', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select name="gesture" id="gesture">
								<?php foreach ( $gestures as $key => $gesture ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $profile['gesture'], $key ); ?>><?php echo esc_html( $gesture['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description" id="gesture-description"></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="welcome_message_en"><?php esc_html_e( 'Welcome Message', 'travelay-cultural-welcome' ); ?></label></th>
						<td><textarea class="large-text" rows="4" name="welcome_message_en" id="welcome_message_en" placeholder="<?php esc_attr_e( 'Welcome to our site — glad you are here.', 'travelay-cultural-welcome' ); ?>"><?php echo esc_textarea( $profile['welcome_message_en'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="cultural_notes"><?php esc_html_e( 'Cultural Notes', 'travelay-cultural-welcome' ); ?></label></th>
						<td><textarea class="large-text" rows="3" name="cultural_notes" id="cultural_notes" placeholder="<?php esc_attr_e( 'Internal notes for your team about cultural sensitivity.', 'travelay-cultural-welcome' ); ?>"><?php echo esc_textarea( $profile['cultural_notes'] ); ?></textarea></td>
					</tr>
				</table>
			</section>

			<section class="tcw-form-card">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-microphone" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Spoken Voice', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Google Neural2 text-to-speech for this profile.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Avatar Voice', 'travelay-cultural-welcome' ); ?></th>
						<td><label class="tcw-toggle-label"><input type="checkbox" name="voice_enabled" value="1" <?php checked( ! empty( $profile['voice_enabled'] ) ); ?> /> <?php esc_html_e( 'Enable spoken welcome for this profile', 'travelay-cultural-welcome' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="voice_script"><?php esc_html_e( 'Voice Script', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<textarea class="large-text" rows="3" name="voice_script" id="voice_script" placeholder="<?php esc_attr_e( 'Leave empty to use the welcome message above.', 'travelay-cultural-welcome' ); ?>"><?php echo esc_textarea( $profile['voice_script'] ?? '' ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="voice_language"><?php esc_html_e( 'Voice Language', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<?php if ( empty( $voice_catalog['voices'] ) ) : ?>
								<p class="tcw-field-warning"><?php echo esc_html( ! empty( $voice_catalog['error'] ) ? $voice_catalog['error'] : __( 'Voice catalog not loaded. Configure Google API in Settings.', 'travelay-cultural-welcome' ) ); ?></p>
								<input class="regular-text" type="text" name="voice_language" id="voice_language" value="<?php echo esc_attr( $profile['voice_language'] ?? '' ); ?>" placeholder="en-US" />
							<?php else : ?>
								<select name="voice_language" id="tcw_voice_language" class="regular-text">
									<option value=""><?php esc_html_e( 'Auto (country default)', 'travelay-cultural-welcome' ); ?></option>
									<?php foreach ( $voice_catalog['languages'] as $language ) : ?>
										<option value="<?php echo esc_attr( $language['code'] ); ?>" <?php selected( $profile['voice_language'] ?? '', $language['code'] ); ?>><?php echo esc_html( $language['label'] . ' (' . (int) $language['voice_count'] . ' voices)' ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tcw_voice_feature"><?php esc_html_e( 'Voice Technology', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select id="tcw_voice_feature" class="regular-text" <?php disabled( empty( $voice_catalog['voices'] ) ); ?>>
								<option value=""><?php esc_html_e( 'All voice technologies', 'travelay-cultural-welcome' ); ?></option>
								<?php foreach ( ( $voice_catalog['features'] ?? array() ) as $feature ) : ?>
									<option value="<?php echo esc_attr( $feature ); ?>"><?php echo esc_html( $feature ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="voice_name"><?php esc_html_e( 'Voice Accent', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<?php if ( ! empty( $voice_catalog['voices'] ) ) : ?>
								<select name="voice_name" id="tcw_voice_name" class="large-text" data-selected="<?php echo esc_attr( $profile['voice_name'] ?? '' ); ?>">
									<option value=""><?php esc_html_e( 'Auto (best match for language)', 'travelay-cultural-welcome' ); ?></option>
								</select>
								<p class="description" id="tcw-voice-preview-meta"><?php esc_html_e( 'Choose a specific Google voice accent and gender.', 'travelay-cultural-welcome' ); ?></p>
							<?php else : ?>
								<input class="regular-text" type="text" name="voice_name" id="voice_name" value="<?php echo esc_attr( $profile['voice_name'] ?? '' ); ?>" placeholder="en-US-Neural2-A" />
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="voice_speaking_rate"><?php esc_html_e( 'Speaking Rate', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<input type="number" class="small-text" min="0" max="1.5" step="0.05" name="voice_speaking_rate" id="voice_speaking_rate" value="<?php echo esc_attr( (float) ( $profile['voice_speaking_rate'] ?? 0 ) ); ?>" />
							<p class="description"><?php esc_html_e( '0 = use global default. Range 0.5–1.5.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
				</table>
			</section>

			<section class="tcw-form-card">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-controls-play" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Behavior & Publishing', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Overrides, review status, and frontend visibility.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><label for="tone_override"><?php esc_html_e( 'Tone Override', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select name="tone_override" id="tone_override">
								<option value="inherit" <?php selected( $profile['tone_override'], 'inherit' ); ?>><?php esc_html_e( 'Use global default', 'travelay-cultural-welcome' ); ?></option>
								<option value="elegant" <?php selected( $profile['tone_override'], 'elegant' ); ?>><?php esc_html_e( 'Subtle Elegant', 'travelay-cultural-welcome' ); ?></option>
								<option value="playful" <?php selected( $profile['tone_override'], 'playful' ); ?>><?php esc_html_e( 'Bold Playful', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="trigger_override"><?php esc_html_e( 'Trigger Override', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select name="trigger_override" id="trigger_override">
								<option value="inherit" <?php selected( $profile['trigger_override'], 'inherit' ); ?>><?php esc_html_e( 'Use global default', 'travelay-cultural-welcome' ); ?></option>
								<option value="auto" <?php selected( $profile['trigger_override'], 'auto' ); ?>><?php esc_html_e( 'Auto only', 'travelay-cultural-welcome' ); ?></option>
								<option value="manual" <?php selected( $profile['trigger_override'], 'manual' ); ?>><?php esc_html_e( 'User-triggered only', 'travelay-cultural-welcome' ); ?></option>
								<option value="both" <?php selected( $profile['trigger_override'], 'both' ); ?>><?php esc_html_e( 'Auto + user-triggered', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="status"><?php esc_html_e( 'Review Status', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select name="status" id="status">
								<option value="draft" <?php selected( $profile['status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'travelay-cultural-welcome' ); ?></option>
								<option value="reviewed" <?php selected( $profile['status'], 'reviewed' ); ?>><?php esc_html_e( 'Reviewed', 'travelay-cultural-welcome' ); ?></option>
								<option value="live" <?php selected( $profile['status'], 'live' ); ?>><?php esc_html_e( 'Live', 'travelay-cultural-welcome' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Only Live + Enabled profiles appear on the frontend.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enabled', 'travelay-cultural-welcome' ); ?></th>
						<td><label class="tcw-toggle-label"><input type="checkbox" name="is_enabled" value="1" <?php checked( $profile['is_enabled'] ); ?> /> <?php esc_html_e( 'Enable this profile on the frontend', 'travelay-cultural-welcome' ); ?></label></td>
					</tr>
				</table>
			</section>

			<section class="tcw-form-card tcw-form-card--rive" id="tcw-rive-panel">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-format-video" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Rive Animation Inputs', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Map hover and tap triggers from your .riv state machine.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<div class="tcw-form-card__body">
					<p id="tcw-rive-status" class="description"></p>
					<p class="tcw-rive-scan-row">
						<button type="button" class="button button-secondary" id="tcw-rive-scan-btn" <?php disabled( ! $profile['id'] ); ?>>
							<span class="dashicons dashicons-search" aria-hidden="true"></span>
							<?php esc_html_e( 'Scan Rive Inputs', 'travelay-cultural-welcome' ); ?>
						</button>
						<span id="tcw-rive-scan-meta" class="description"></span>
					</p>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><label for="rive_state_machine"><?php esc_html_e( 'State machine', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select name="rive_state_machine" id="rive_state_machine" class="regular-text" disabled>
								<option value=""><?php esc_html_e( '— Scan first —', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rive_hover_input"><?php esc_html_e( 'Hover (boolean)', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select name="rive_hover_input" id="rive_hover_input" class="regular-text" disabled>
								<option value=""><?php esc_html_e( '— None —', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rive_entry_trigger"><?php esc_html_e( 'Welcome open (trigger)', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select name="rive_entry_trigger" id="rive_entry_trigger" class="regular-text" disabled>
								<option value=""><?php esc_html_e( '— None —', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rive_tap_triggers"><?php esc_html_e( 'Tap sequence', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<select id="rive_tap_triggers" class="large-text" multiple size="6" disabled></select>
							<input type="hidden" name="rive_tap_triggers" id="rive_tap_triggers_json" value="<?php echo esc_attr( wp_json_encode( $profile['rive_tap_triggers'] ?? array() ) ); ?>" />
							<input type="hidden" name="rive_scan_cache" id="rive_scan_cache" value="" />
							<p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd for multi-select. Order = tap cycle.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
				</table>
			</section>
		</div>

		<div class="tcw-submit-bar">
			<?php submit_button( __( 'Save Profile', 'travelay-cultural-welcome' ), 'primary', 'submit', false ); ?>
			<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=tcw-dashboard' ) ); ?>"><?php esc_html_e( 'Cancel', 'travelay-cultural-welcome' ); ?></a>
		</div>
	</form>

	<footer class="tcw-dashboard-footer">
		<span><?php esc_html_e( 'Developed and Copyright Patent Travelay™', 'travelay-cultural-welcome' ); ?></span>
	</footer>
</div>
