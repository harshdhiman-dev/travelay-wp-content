<?php
/**
 * E-Commerce Nav Template
 *
 * @package DS_Theme
 */

if ( wp_is_mobile() ) {
	return;
}
?>
<div class="site-header__widget site-header__account">
	<?php
	echo do_blocks( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'
    <!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconStyle":"line","iconClass":"wc-block-customer-account__account-icon"} /-->
	<!-- wp:woocommerce/mini-cart /-->
	'
	);
	?>
</div>
