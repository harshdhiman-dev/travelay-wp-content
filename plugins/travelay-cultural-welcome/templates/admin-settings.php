<?php
/**
 * Admin settings template.
 *
 * @package TravelayCulturalWelcome
 *
 * @var array $settings Settings.
 * @var array $voice_catalog Voice catalog.
 * @var array      $voice_meta Catalog meta.
 * @var array|null $compat_report Cached compatibility report.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$presets       = TCW_Settings::presets();
$active_preset = isset( $settings['experience_preset'] ) ? (string) $settings['experience_preset'] : 'full';
$preset_label  = $presets[ $active_preset ]['label'] ?? ucfirst( $active_preset );
$notice        = isset( $_GET['tcw_notice'] ) ? sanitize_key( wp_unslash( $_GET['tcw_notice'] ) ) : '';
$opt           = TCW_Settings::OPTION_KEY;
?>
<div class="wrap tcw-admin-wrap tcw-admin-page">
	<?php
	TCW_Admin::render_hero(
		array(
			'title'   => __( 'Global Settings', 'travelay-cultural-welcome' ),
			'tagline' => __( 'Shape the welcome experience across your entire site.', 'travelay-cultural-welcome' ),
			'icon'    => 'dashicons-admin-generic',
			'chips'   => array(
				array(
					'strong' => ! empty( $settings['enabled'] ) ? __( 'On', 'travelay-cultural-welcome' ) : __( 'Off', 'travelay-cultural-welcome' ),
					'text'   => __( 'Plugin', 'travelay-cultural-welcome' ),
					'class'  => ! empty( $settings['enabled'] ) ? 'tcw-stat-chip--live' : '',
				),
				array(
					'strong' => $preset_label,
					'text'   => __( 'Preset', 'travelay-cultural-welcome' ),
				),
				array(
					'strong' => ucfirst( (string) $settings['default_tone'] ),
					'text'   => __( 'Tone', 'travelay-cultural-welcome' ),
				),
			),
			'actions' => '<a class="tcw-hero-link" href="' . esc_url( admin_url( 'admin.php?page=tcw-dashboard' ) ) . '"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>' . esc_html__( 'Back to Profiles', 'travelay-cultural-welcome' ) . '</a>',
		)
	);
	?>

	<?php if ( 'voices_refreshed' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Google TTS voice catalog refreshed successfully.', 'travelay-cultural-welcome' ); ?></p></div>
	<?php endif; ?>

	<section class="tcw-form-card tcw-form-card--compat" id="tcw-compatibility-card">
		<div class="tcw-form-card__head">
			<span class="tcw-panel-card__icon dashicons dashicons-heart" aria-hidden="true"></span>
			<div>
				<h2><?php esc_html_e( 'Compatibility Check', 'travelay-cultural-welcome' ); ?></h2>
				<p><?php esc_html_e( 'Run a full diagnostic on your site before going live — profiles, API, assets, and frontend.', 'travelay-cultural-welcome' ); ?></p>
			</div>
		</div>
		<div class="tcw-form-card__body tcw-compat-body">
			<div class="tcw-compat-actions">
				<button type="button" class="button button-primary tcw-btn-compat" id="tcw-run-compatibility">
					<span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
					<?php esc_html_e( 'Run Compatibility', 'travelay-cultural-welcome' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="tcw-copy-compatibility" <?php echo empty( $compat_report ) ? 'disabled' : ''; ?>>
					<?php esc_html_e( 'Copy report', 'travelay-cultural-welcome' ); ?>
				</button>
				<span class="tcw-compat-running" hidden><?php esc_html_e( 'Running checks…', 'travelay-cultural-welcome' ); ?></span>
			</div>
			<div class="tcw-compat-results" id="tcw-compat-results" <?php echo empty( $compat_report ) ? 'hidden' : ''; ?>>
				<div class="tcw-compat-summary">
					<div class="tcw-compat-summary__counts"></div>
					<p class="tcw-compat-summary__verdict"></p>
					<p class="tcw-compat-summary__time"></p>
				</div>
				<div class="tcw-compat-groups"></div>
			</div>
			<?php if ( empty( $compat_report ) ) : ?>
				<p class="tcw-field-hint"><?php esc_html_e( 'Recommended after a fresh install or before deploying to production.', 'travelay-cultural-welcome' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<form method="post" action="options.php" id="tcw-settings-form" class="tcw-admin-form">
		<?php settings_fields( 'tcw_settings_group' ); ?>

		<div class="tcw-form-layout">
			<section class="tcw-form-card tcw-form-card--featured">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-star-filled" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Welcome Experience', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Choose a named preset bundle — Classic, Voice, IP Welcome, or Full.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<div class="tcw-form-card__body">
					<p class="tcw-field-row">
						<label for="tcw-experience-preset" class="tcw-field-label"><?php esc_html_e( 'Experience preset', 'travelay-cultural-welcome' ); ?></label>
						<select id="tcw-experience-preset" name="<?php echo esc_attr( $opt ); ?>[experience_preset]" class="regular-text">
							<?php foreach ( $presets as $preset_key => $preset_data ) : ?>
								<option value="<?php echo esc_attr( $preset_key ); ?>" <?php selected( $active_preset, $preset_key ); ?>><?php echo esc_html( $preset_data['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p id="tcw-preset-description" class="tcw-field-hint"><?php echo esc_html( $presets[ $active_preset ]['description'] ?? '' ); ?></p>
				</div>
			</section>

			<section class="tcw-form-card">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-admin-site-alt3" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Core Behavior', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Master switch, tone, triggers, and IP-based welcome.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Plugin', 'travelay-cultural-welcome' ); ?></th>
						<td><label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> /> <?php esc_html_e( 'Show cultural welcomes on matched pages', 'travelay-cultural-welcome' ); ?></label></td>
					</tr>
					<tr class="tcw-preset-feature">
						<th scope="row"><?php esc_html_e( 'IP Location Welcome', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enable_ip_welcome]" value="1" <?php checked( ! empty( $settings['enable_ip_welcome'] ) ); ?> /> <?php esc_html_e( 'Greet visitors by IP country on homepage and non-profile pages', 'travelay-cultural-welcome' ); ?></label>
							<p class="description"><?php esc_html_e( 'Country landing pages always use the page profile first.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Tone', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $opt ); ?>[default_tone]">
								<option value="elegant" <?php selected( $settings['default_tone'], 'elegant' ); ?>><?php esc_html_e( 'Subtle Elegant', 'travelay-cultural-welcome' ); ?></option>
								<option value="playful" <?php selected( $settings['default_tone'], 'playful' ); ?>><?php esc_html_e( 'Bold Playful', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Trigger', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $opt ); ?>[default_trigger]">
								<option value="auto" <?php selected( $settings['default_trigger'], 'auto' ); ?>><?php esc_html_e( 'Auto only', 'travelay-cultural-welcome' ); ?></option>
								<option value="manual" <?php selected( $settings['default_trigger'], 'manual' ); ?>><?php esc_html_e( 'User-triggered only', 'travelay-cultural-welcome' ); ?></option>
								<option value="both" <?php selected( $settings['default_trigger'], 'both' ); ?>><?php esc_html_e( 'Auto + user-triggered', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</section>

			<section class="tcw-form-card">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-clock" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Timing & Frequency', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'When and how often welcomes appear.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto Delay (ms)', 'travelay-cultural-welcome' ); ?></th>
						<td><input type="number" class="small-text" min="0" max="10000" step="100" name="<?php echo esc_attr( $opt ); ?>[auto_delay_ms]" value="<?php echo esc_attr( (int) $settings['auto_delay_ms'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto Duration (ms)', 'travelay-cultural-welcome' ); ?></th>
						<td><input type="number" class="small-text" min="2000" max="15000" step="500" name="<?php echo esc_attr( $opt ); ?>[auto_duration_ms]" value="<?php echo esc_attr( (int) $settings['auto_duration_ms'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto Frequency', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $opt ); ?>[frequency]">
								<option value="session" <?php selected( $settings['frequency'], 'session' ); ?>><?php esc_html_e( 'Once per session', 'travelay-cultural-welcome' ); ?></option>
								<option value="day" <?php selected( $settings['frequency'], 'day' ); ?>><?php esc_html_e( 'Once per day', 'travelay-cultural-welcome' ); ?></option>
								<option value="week" <?php selected( $settings['frequency'], 'week' ); ?>><?php esc_html_e( 'Once per week', 'travelay-cultural-welcome' ); ?></option>
								<option value="always" <?php selected( $settings['frequency'], 'always' ); ?>><?php esc_html_e( 'Every page load', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Replay Button', 'travelay-cultural-welcome' ); ?></th>
						<td><label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[show_replay_button]" value="1" <?php checked( ! empty( $settings['show_replay_button'] ) ); ?> /> <?php esc_html_e( 'Show floating replay button after welcome', 'travelay-cultural-welcome' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Reduced Motion', 'travelay-cultural-welcome' ); ?></th>
						<td><label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[respect_reduced_motion]" value="1" <?php checked( ! empty( $settings['respect_reduced_motion'] ) ); ?> /> <?php esc_html_e( 'Respect prefers-reduced-motion', 'travelay-cultural-welcome' ); ?></label></td>
					</tr>
				</table>
			</section>

			<section class="tcw-form-card">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-art" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Avatars & Visuals', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Confetti, renderer, Lottie, and Rive animation settings.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr class="tcw-preset-feature">
						<th scope="row"><?php esc_html_e( 'Country Confetti', 'travelay-cultural-welcome' ); ?></th>
						<td><label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enable_confetti]" value="1" <?php checked( ! empty( $settings['enable_confetti'] ) ); ?> /> <?php esc_html_e( 'Celebrate with country-themed confetti', 'travelay-cultural-welcome' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Confetti Intensity', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $opt ); ?>[confetti_intensity]">
								<option value="low" <?php selected( $settings['confetti_intensity'], 'low' ); ?>><?php esc_html_e( 'Low', 'travelay-cultural-welcome' ); ?></option>
								<option value="medium" <?php selected( $settings['confetti_intensity'], 'medium' ); ?>><?php esc_html_e( 'Medium', 'travelay-cultural-welcome' ); ?></option>
								<option value="high" <?php selected( $settings['confetti_intensity'], 'high' ); ?>><?php esc_html_e( 'High', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Avatar Renderer', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( $opt ); ?>[avatar_renderer]">
								<option value="auto" <?php selected( $settings['avatar_renderer'], 'auto' ); ?>><?php esc_html_e( 'Auto (Rive → Lottie → Premium SVG)', 'travelay-cultural-welcome' ); ?></option>
								<option value="rive" <?php selected( $settings['avatar_renderer'], 'rive' ); ?>><?php esc_html_e( 'Rive only', 'travelay-cultural-welcome' ); ?></option>
								<option value="lottie" <?php selected( $settings['avatar_renderer'], 'lottie' ); ?>><?php esc_html_e( 'Lottie only', 'travelay-cultural-welcome' ); ?></option>
								<option value="svg" <?php selected( $settings['avatar_renderer'], 'svg' ); ?>><?php esc_html_e( 'Premium SVG only', 'travelay-cultural-welcome' ); ?></option>
							</select>
						</td>
					</tr>
					<tr class="tcw-preset-feature">
						<th scope="row"><?php esc_html_e( 'Lottie Animations', 'travelay-cultural-welcome' ); ?></th>
						<td><label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enable_lottie]" value="1" <?php checked( ! empty( $settings['enable_lottie'] ) ); ?> /> <?php esc_html_e( 'Enable Lottie avatar animations', 'travelay-cultural-welcome' ); ?></label></td>
					</tr>
					<tr class="tcw-preset-feature">
						<th scope="row"><?php esc_html_e( 'Rive Animations', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enable_rive]" value="1" <?php checked( ! empty( $settings['enable_rive'] ) ); ?> /> <?php esc_html_e( 'Enable Rive when .riv files are uploaded', 'travelay-cultural-welcome' ); ?></label>
							<p class="description"><?php esc_html_e( 'Upload to assets/avatars/rive/{slug}.riv', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
					<tr class="tcw-preset-feature">
						<th scope="row"><?php esc_html_e( 'Typewriter Message', 'travelay-cultural-welcome' ); ?></th>
						<td><label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[typewriter_enabled]" value="1" <?php checked( ! empty( $settings['typewriter_enabled'] ) ); ?> /> <?php esc_html_e( 'Animate welcome message character by character', 'travelay-cultural-welcome' ); ?></label></td>
					</tr>
				</table>
			</section>

			<section class="tcw-form-card">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-microphone" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Voice & Sound', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Google TTS voices and celebration audio.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Google API Key', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<input type="password" class="regular-text" name="<?php echo esc_attr( $opt ); ?>[google_api_key]" value="" autocomplete="new-password" placeholder="<?php echo TCW_Google_API::is_configured() ? esc_attr__( 'Key saved — leave blank to keep', 'travelay-cultural-welcome' ) : esc_attr__( 'Paste Google Cloud API key', 'travelay-cultural-welcome' ); ?>" />
							<p class="description"><?php esc_html_e( 'Server-side only. Or define TCW_GOOGLE_API_KEY in wp-config.php.', 'travelay-cultural-welcome' ); ?></p>
							<?php if ( TCW_Google_API::is_configured() ) : ?>
								<p class="tcw-field-success"><?php esc_html_e( 'API key is configured.', 'travelay-cultural-welcome' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr class="tcw-preset-feature">
						<th scope="row"><?php esc_html_e( 'Avatar Voice (TTS)', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enable_voice_welcome]" value="1" <?php checked( ! empty( $settings['enable_voice_welcome'] ) ); ?> /> <?php esc_html_e( 'Speak welcome text with Google Neural2 voices', 'travelay-cultural-welcome' ); ?></label>
							<p class="description">
								<?php esc_html_e( 'Volume', 'travelay-cultural-welcome' ); ?>:
								<input type="number" class="small-text" min="0" max="1" step="0.05" name="<?php echo esc_attr( $opt ); ?>[voice_volume]" value="<?php echo esc_attr( (float) $settings['voice_volume'] ); ?>" />
								<?php esc_html_e( 'Speaking rate', 'travelay-cultural-welcome' ); ?>:
								<input type="number" class="small-text" min="0.5" max="1.5" step="0.05" name="<?php echo esc_attr( $opt ); ?>[voice_speaking_rate]" value="<?php echo esc_attr( (float) $settings['voice_speaking_rate'] ); ?>" />
							</p>
							<?php if ( ! empty( $voice_meta['synced_at'] ) ) : ?>
								<p class="description">
									<strong><?php esc_html_e( 'Voice catalog:', 'travelay-cultural-welcome' ); ?></strong>
									<?php
									printf(
										esc_html__( '%1$d languages · %2$d accents · synced %3$s', 'travelay-cultural-welcome' ),
										(int) ( $voice_meta['language_count'] ?? 0 ),
										(int) ( $voice_meta['voice_count'] ?? 0 ),
										esc_html( wp_date( 'M j, Y g:i a', (int) $voice_meta['synced_at'] ) )
									);
									?>
								</p>
							<?php endif; ?>
							<?php if ( ! empty( $voice_catalog['error'] ) ) : ?>
								<p class="tcw-field-warning"><?php echo esc_html( $voice_catalog['error'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr class="tcw-preset-feature">
						<th scope="row"><?php esc_html_e( 'Celebration Sound', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<label class="tcw-toggle-label"><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enable_sound]" value="1" <?php checked( ! empty( $settings['enable_sound'] ) ); ?> /> <?php esc_html_e( 'Play subtle country-themed chime', 'travelay-cultural-welcome' ); ?></label>
							<p class="description"><?php esc_html_e( 'Volume', 'travelay-cultural-welcome' ); ?>: <input type="number" class="small-text" min="0" max="1" step="0.05" name="<?php echo esc_attr( $opt ); ?>[sound_volume]" value="<?php echo esc_attr( (float) $settings['sound_volume'] ); ?>" /></p>
						</td>
					</tr>
				</table>
			</section>

			<section class="tcw-form-card">
				<div class="tcw-form-card__head">
					<span class="tcw-panel-card__icon dashicons dashicons-admin-tools" aria-hidden="true"></span>
					<div>
						<h2><?php esc_html_e( 'Advanced', 'travelay-cultural-welcome' ); ?></h2>
						<p><?php esc_html_e( 'Sync exclusions and layering.', 'travelay-cultural-welcome' ); ?></p>
					</div>
				</div>
				<table class="form-table tcw-form-table" role="presentation">
					<tr>
						<th scope="row"><label for="tcw-sync-exclude"><?php esc_html_e( 'Sync Exclude Slugs', 'travelay-cultural-welcome' ); ?></label></th>
						<td>
							<input class="large-text" type="text" id="tcw-sync-exclude" name="<?php echo esc_attr( $opt ); ?>[sync_exclude_slugs]" value="<?php echo esc_attr( (string) ( $settings['sync_exclude_slugs'] ?? '' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Comma-separated slugs skipped during Sync all pages.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Z-Index', 'travelay-cultural-welcome' ); ?></th>
						<td>
							<input type="number" class="regular-text" min="1000000" max="2147483647" name="<?php echo esc_attr( $opt ); ?>[z_index]" value="<?php echo esc_attr( (int) $settings['z_index'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Must sit above site overlays. Default: 2147483000.', 'travelay-cultural-welcome' ); ?></p>
						</td>
					</tr>
				</table>
			</section>
		</div>

		<div class="tcw-submit-bar">
			<?php submit_button( __( 'Save Settings', 'travelay-cultural-welcome' ), 'primary', 'submit', false ); ?>
		</div>
	</form>

	<section class="tcw-form-card tcw-form-card--voice-sync">
		<div class="tcw-form-card__head">
			<span class="tcw-panel-card__icon dashicons dashicons-update" aria-hidden="true"></span>
			<div>
				<h2><?php esc_html_e( 'Google TTS Voice Catalog', 'travelay-cultural-welcome' ); ?></h2>
				<p><?php esc_html_e( 'Sync languages and accents for profile voice dropdowns.', 'travelay-cultural-welcome' ); ?></p>
			</div>
		</div>
		<div class="tcw-form-card__body">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'tcw_refresh_voice_catalog' ); ?>
				<input type="hidden" name="action" value="tcw_refresh_voice_catalog" />
				<button type="submit" class="button button-secondary">
					<span class="dashicons dashicons-update" aria-hidden="true"></span>
					<?php esc_html_e( 'Refresh Voice Catalog from Google', 'travelay-cultural-welcome' ); ?>
				</button>
			</form>
		</div>
	</section>

	<footer class="tcw-dashboard-footer">
		<span><?php esc_html_e( 'Developed and Copyright Patent Travelay™', 'travelay-cultural-welcome' ); ?></span>
	</footer>
</div>
