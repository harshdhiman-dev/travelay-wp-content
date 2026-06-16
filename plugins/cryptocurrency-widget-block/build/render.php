<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 * @package block-developer-examples
 */

// Ensure the function exists before calling it.
if ( ! function_exists( 'ccwfg_fetch_coin_data' ) ) {
    return '<p>Function ccwfg_fetch_coin_data() does not exist.</p>';
}

$coin_data = ccwfg_fetch_coin_data();
$enabled_fields = [];

// Map attributes to field names.
$fields_map = [
    'showRank'       => 'rank',
    'showName'       => 'name',
    'showPrice'      => 'price',
    'show24hchanges' => '24hchanges',
    'showVolume'     => 'volume',
    'showMarketcap'  => 'marketcap',
];

// Populate enabled fields based on attributes.
foreach ( $fields_map as $attribute => $field ) {
    if ( ! empty( $attributes[ $attribute ] ) ) {
        $enabled_fields[ $field ] = $field;
    }
}

// If no columns are selected, display a message.
if ( empty( $enabled_fields ) ) {
    return '<p>No columns selected.</p>';
}

if(empty($coin_data)){
    return "Data not loaded yet";
}

// Filter or slice coin data based on attributes.
$finaldata = ( 'custom' === $attributes['limit'] )
    ? ccwfg_filterSelectedCoins( $attributes['selectedCoins'], $coin_data )
    : array_slice( $coin_data, 0, (int) $attributes['limit'] );

// Output the block content.
ob_start();
?>
<div <?php echo wp_kses_post( get_block_wrapper_attributes() ); ?>>
    <?php
    // Render content based on widget type.
    if ( 'list' === $attributes['widgettype'] ) {
        ccwfg_display_coin_data_table( $finaldata, $enabled_fields );
    } elseif ( 'label' === $attributes['widgettype'] ) {
        ccwfg_display_coin_data_label( $finaldata);
    }
    elseif ( 'ticker' === $attributes['widgettype'] ) {
        ccwfg_display_coin_data_ticker( $finaldata, $attributes['tickerspeed'] );
    }
    elseif ( 'text' === $attributes['widgettype'] ) {
        ccwfg_display_coin_data_text( $finaldata ,$attributes['Textwidget'] );
    }
    ?>
</div>
<?php

echo ob_get_clean();
