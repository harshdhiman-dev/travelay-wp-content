<?php
/**
 * Admin dashboard template.
 *
 * @package TravelayCulturalWelcome
 *
 * @var array      $profiles Profiles.
 * @var array      $settings Settings.
 * @var string     $notice   Notice key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$profile_stats = array(
	'total'   => count( $profiles ),
	'enabled' => 0,
	'live'    => 0,
);

foreach ( $profiles as $post ) {
	$p = TCW_Profile::format_post( $post );
	if ( ! empty( $p['is_enabled'] ) ) {
		++$profile_stats['enabled'];
	}
	if ( 'live' === $p['status'] ) {
		++$profile_stats['live'];
	}
}
?>
<div class="wrap tcw-admin-wrap tcw-dashboard">
	<header class="tcw-hero">
		<div class="tcw-hero__main">
			<div class="tcw-hero__icon" aria-hidden="true">
				<span class="dashicons dashicons-welcome-view-site"></span>
			</div>
			<div class="tcw-hero__text">
				<h1><?php esc_html_e( 'Cultural Welcome Profiles', 'travelay-cultural-welcome' ); ?></h1>
				<p class="tcw-hero__tagline"><?php esc_html_e( 'Craft welcoming experiences for every page on your site.', 'travelay-cultural-welcome' ); ?></p>
				<p class="tcw-hero__version"><?php echo esc_html( sprintf( __( 'Version %s', 'travelay-cultural-welcome' ), TCW_VERSION ) ); ?></p>
			</div>
		</div>
		<div class="tcw-hero__meta">
			<div class="tcw-stat-chips">
				<span class="tcw-stat-chip"><strong><?php echo esc_html( (string) $profile_stats['total'] ); ?></strong> <?php esc_html_e( 'Profiles', 'travelay-cultural-welcome' ); ?></span>
				<span class="tcw-stat-chip tcw-stat-chip--live"><strong><?php echo esc_html( (string) $profile_stats['live'] ); ?></strong> <?php esc_html_e( 'Live', 'travelay-cultural-welcome' ); ?></span>
				<span class="tcw-stat-chip tcw-stat-chip--on"><strong><?php echo esc_html( (string) $profile_stats['enabled'] ); ?></strong> <?php esc_html_e( 'Enabled', 'travelay-cultural-welcome' ); ?></span>
			</div>
			<p class="tcw-hero__brand"><?php esc_html_e( 'Developed and Copyright Patent Travelay™', 'travelay-cultural-welcome' ); ?></p>
		</div>
	</header>

	<?php if ( 'saved' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Profile saved.', 'travelay-cultural-welcome' ); ?></p></div>
	<?php elseif ( 'deleted' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Profile deleted.', 'travelay-cultural-welcome' ); ?></p></div>
	<?php elseif ( 'seeded' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Country templates synced.', 'travelay-cultural-welcome' ); ?></p></div>
	<?php elseif ( 'synced' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Site pages synced into profiles.', 'travelay-cultural-welcome' ); ?></p></div>
	<?php elseif ( 'bulk_deleted' === $notice ) : ?>
		<?php $deleted_count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0; ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( _n( '%d profile deleted.', '%d profiles deleted.', $deleted_count, 'travelay-cultural-welcome' ), $deleted_count ) ); ?></p></div>
	<?php endif; ?>

	<div class="tcw-dashboard-panels">
		<section class="tcw-panel-card tcw-panel-card--actions" aria-labelledby="tcw-panel-actions-title">
			<div class="tcw-panel-card__head">
				<span class="tcw-panel-card__icon dashicons dashicons-admin-users" aria-hidden="true"></span>
				<div>
					<h2 id="tcw-panel-actions-title"><?php esc_html_e( 'Quick Actions', 'travelay-cultural-welcome' ); ?></h2>
					<p><?php esc_html_e( 'Create or import welcome profiles.', 'travelay-cultural-welcome' ); ?></p>
				</div>
			</div>
			<div class="tcw-action-tiles">
				<a class="tcw-action-tile tcw-action-tile--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=tcw-edit-profile' ) ); ?>">
					<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
					<span class="tcw-action-tile__label"><?php esc_html_e( 'Add Profile', 'travelay-cultural-welcome' ); ?></span>
				</a>
				<a class="tcw-action-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=tcw-settings' ) ); ?>">
					<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
					<span class="tcw-action-tile__label"><?php esc_html_e( 'Global Settings', 'travelay-cultural-welcome' ); ?></span>
				</a>
				<form class="tcw-action-tile-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'tcw_seed_profiles' ); ?>
					<input type="hidden" name="action" value="tcw_seed_profiles" />
					<button type="submit" class="tcw-action-tile">
						<span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span>
						<span class="tcw-action-tile__label"><?php esc_html_e( 'Sync Country Templates', 'travelay-cultural-welcome' ); ?></span>
					</button>
				</form>
			</div>
		</section>

		<section class="tcw-panel-card tcw-panel-card--sync" aria-labelledby="tcw-panel-sync-title">
			<div class="tcw-panel-card__head">
				<span class="tcw-panel-card__icon dashicons dashicons-update" aria-hidden="true"></span>
				<div>
					<h2 id="tcw-panel-sync-title"><?php esc_html_e( 'Sync All Pages', 'travelay-cultural-welcome' ); ?></h2>
					<p><?php esc_html_e( 'Import published content as profiles (Reviewed & disabled by default).', 'travelay-cultural-welcome' ); ?></p>
				</div>
			</div>
			<form id="tcw-sync-pages-form" class="tcw-sync-form">
				<label for="tcw-sync-scope" class="tcw-sync-form__label"><?php esc_html_e( 'Content scope', 'travelay-cultural-welcome' ); ?></label>
				<div class="tcw-sync-form__controls">
					<select id="tcw-sync-scope" name="scope" class="tcw-sync-form__select">
						<?php foreach ( TCW_Page_Sync::scope_options() as $scope_key => $scope_label ) : ?>
							<option value="<?php echo esc_attr( $scope_key ); ?>"><?php echo esc_html( $scope_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button button-primary tcw-btn-sync" id="tcw-sync-pages-button">
						<span class="dashicons dashicons-download" aria-hidden="true"></span>
						<?php esc_html_e( 'Sync All Pages', 'travelay-cultural-welcome' ); ?>
					</button>
				</div>
			</form>
			<progress id="tcw-sync-progress" class="tcw-sync-progress" value="0" max="100" hidden></progress>
			<div id="tcw-sync-status" class="tcw-inline-notice tcw-sync-status" hidden><p></p></div>
		</section>
	</div>

	<section class="tcw-profiles-card tcw-profiles-section" aria-labelledby="tcw-profiles-heading">
		<div class="tcw-profiles-card__head">
			<div class="tcw-profiles-card__title">
				<h2 id="tcw-profiles-heading"><?php esc_html_e( 'Profile Library', 'travelay-cultural-welcome' ); ?></h2>
				<p><?php esc_html_e( 'Search, select, and manage every welcome profile.', 'travelay-cultural-welcome' ); ?></p>
			</div>
			<div class="tcw-defaults-pills">
				<span class="tcw-defaults-pill <?php echo ! empty( $settings['enabled'] ) ? 'is-on' : ''; ?>">
					<?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'Plugin on', 'travelay-cultural-welcome' ) : esc_html__( 'Plugin off', 'travelay-cultural-welcome' ); ?>
				</span>
				<span class="tcw-defaults-pill"><?php echo esc_html( ucfirst( $settings['default_tone'] ) ); ?></span>
				<span class="tcw-defaults-pill"><?php echo esc_html( ucfirst( $settings['default_trigger'] ) ); ?></span>
			</div>
		</div>

		<form id="tcw-bulk-delete-form" class="tcw-profiles-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'tcw_bulk_delete_profiles' ); ?>
			<input type="hidden" name="action" value="tcw_bulk_delete_profiles" />

			<div class="tcw-profiles-toolbar">
				<div class="tcw-search-field">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<label class="screen-reader-text" for="tcw-profile-search"><?php esc_html_e( 'Search profiles', 'travelay-cultural-welcome' ); ?></label>
					<input
						type="search"
						id="tcw-profile-search"
						class="tcw-profiles-search"
						placeholder="<?php esc_attr_e( 'Search name, slug, type, status, gesture…', 'travelay-cultural-welcome' ); ?>"
						autocomplete="off"
						<?php echo empty( $profiles ) ? 'disabled' : ''; ?>
					/>
					<button type="button" class="tcw-search-clear" id="tcw-search-clear" hidden aria-label="<?php esc_attr_e( 'Clear search', 'travelay-cultural-welcome' ); ?>">&times;</button>
				</div>
				<div class="tcw-profiles-toolbar__actions">
					<span id="tcw-profiles-shown" class="tcw-profiles-shown" aria-live="polite"></span>
					<?php if ( ! empty( $profiles ) ) : ?>
						<span id="tcw-selected-count" class="tcw-selected-count"><?php esc_html_e( 'None selected', 'travelay-cultural-welcome' ); ?></span>
						<button type="submit" class="button tcw-bulk-delete-btn" id="tcw-bulk-delete-btn" disabled>
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
							<?php esc_html_e( 'Bulk Delete', 'travelay-cultural-welcome' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="tcw-table-scroll">
				<table class="tcw-table">
					<thead>
						<tr>
							<th class="tcw-th-check" scope="col">
								<?php if ( ! empty( $profiles ) ) : ?>
									<label class="screen-reader-text" for="tcw-select-all"><?php esc_html_e( 'Select all visible', 'travelay-cultural-welcome' ); ?></label>
									<input id="tcw-select-all" type="checkbox" class="tcw-check-input" />
								<?php endif; ?>
							</th>
							<th scope="col"><?php esc_html_e( 'Name', 'travelay-cultural-welcome' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Type', 'travelay-cultural-welcome' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Slug', 'travelay-cultural-welcome' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Gesture', 'travelay-cultural-welcome' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'travelay-cultural-welcome' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Enabled', 'travelay-cultural-welcome' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Page', 'travelay-cultural-welcome' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'travelay-cultural-welcome' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $profiles ) ) : ?>
							<tr class="tcw-profile-empty">
								<td colspan="9">
									<div class="tcw-empty-state">
										<span class="dashicons dashicons-welcome-write-blog" aria-hidden="true"></span>
										<p><?php esc_html_e( 'No profiles yet. Sync your site or import country templates to get started.', 'travelay-cultural-welcome' ); ?></p>
									</div>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $profiles as $post ) : ?>
								<?php
								$profile     = TCW_Profile::format_post( $post );
								$permalink   = $profile['page_id'] ? get_permalink( $profile['page_id'] ) : '';
								$type_lbl    = TCW_Profile::entity_type_label( $profile['entity_type'] );
								$search_bits = array(
									$profile['display_name'],
									$type_lbl,
									$profile['location_slug'],
									$profile['gesture'],
									$profile['status'],
									$profile['is_enabled'] ? 'enabled' : 'disabled',
									$permalink ? $permalink : 'not linked',
									(string) $profile['page_id'],
								);
								$status_class = 'tcw-badge--' . sanitize_html_class( $profile['status'] );
								?>
								<tr class="tcw-profile-row" data-search="<?php echo esc_attr( strtolower( implode( ' ', $search_bits ) ) ); ?>">
									<td class="tcw-td-check">
										<input type="checkbox" class="tcw-profile-cb tcw-check-input" name="profile_ids[]" value="<?php echo esc_attr( (int) $profile['id'] ); ?>" />
									</td>
									<td class="tcw-td-name"><strong><?php echo esc_html( $profile['display_name'] ); ?></strong></td>
									<td><?php echo esc_html( $type_lbl ); ?></td>
									<td><code class="tcw-slug"><?php echo esc_html( $profile['location_slug'] ); ?></code></td>
									<td><span class="tcw-gesture-tag"><?php echo esc_html( $profile['gesture'] ); ?></span></td>
									<td><span class="tcw-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $profile['status'] ) ); ?></span></td>
									<td>
										<?php if ( $profile['is_enabled'] ) : ?>
											<span class="tcw-badge tcw-badge--enabled"><?php esc_html_e( 'On', 'travelay-cultural-welcome' ); ?></span>
										<?php else : ?>
											<span class="tcw-badge tcw-badge--off"><?php esc_html_e( 'Off', 'travelay-cultural-welcome' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $permalink ) : ?>
											<a class="tcw-link-view" href="<?php echo esc_url( $permalink ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'travelay-cultural-welcome' ); ?></a>
										<?php else : ?>
											<span class="tcw-muted"><?php esc_html_e( 'Not linked', 'travelay-cultural-welcome' ); ?></span>
										<?php endif; ?>
									</td>
									<td class="tcw-td-actions">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=tcw-edit-profile&profile_id=' . (int) $profile['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'travelay-cultural-welcome' ); ?></a>
										<a class="tcw-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tcw_delete_profile&profile_id=' . (int) $profile['id'] ), 'tcw_delete_profile_' . (int) $profile['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this profile?', 'travelay-cultural-welcome' ) ); ?>');"><?php esc_html_e( 'Delete', 'travelay-cultural-welcome' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
							<tr id="tcw-no-matches" class="tcw-profile-no-matches" hidden>
								<td colspan="9">
									<div class="tcw-empty-state tcw-empty-state--small">
										<p><?php esc_html_e( 'No profiles match your search.', 'travelay-cultural-welcome' ); ?></p>
									</div>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</form>
	</section>

	<footer class="tcw-dashboard-footer">
		<span><?php esc_html_e( 'Developed and Copyright Patent Travelay™', 'travelay-cultural-welcome' ); ?></span>
	</footer>
</div>
