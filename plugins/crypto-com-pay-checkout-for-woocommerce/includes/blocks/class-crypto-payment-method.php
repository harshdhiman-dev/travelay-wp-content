<?php
/**
 * Crypto.com Pay Payment Method
 *
 * @package Crypto/Classes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for integrating with WooCommerce Blocks
 */
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Crypto_Payment_Method class
 */
class Crypto_Payment_Method extends AbstractPaymentMethodType
{
    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'crypto_pay';

    /**
     * Settings from the gateway options.
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Initialize the payment method.
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_crypto_pay_settings', array());
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active()
    {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles()
    {
        $script_path = '/assets/js/blocks/build/crypto-pay.js';
        $script_url = plugins_url($script_path, dirname(__FILE__, 2));
        $script_asset_path = dirname(__FILE__, 2) . '/assets/js/blocks/build/crypto-pay.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version' => '1.0.0'
            );

        wp_register_script(
            'wc-crypto-pay-blocks',
            $script_url,
            array_merge(
                $script_asset['dependencies'],
                array('wc-blocks-registry', 'wc-settings')
            ),
            $script_asset['version'],
            true
        );

        wp_set_script_translations(
            'wc-crypto-pay-blocks',
            'crypto-pay',
            dirname(__FILE__, 2) . '/languages'
        );

        return array('wc-crypto-pay-blocks');
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return array(
            'title' => $this->get_setting('title', __('Crypto.com Pay', 'crypto-pay')),
            'description' => $this->get_setting('description', __('Accept Bitcoin and more cryptocurrencies without the risk of price fluctuation.', 'crypto-pay')),
            'icon' => plugins_url('/assets/icon.svg', dirname(__FILE__, 2)),
            'supports' => array('products', 'refunds'),
            'environment' => $this->get_setting('environment', 'test'),
            'publishable_key' => $this->get_setting('environment') === 'production' 
                ? $this->get_setting('live_publishable_key', '')
                : $this->get_setting('test_publishable_key', ''),
        );
    }

    protected function get_setting($key, $default = '')
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
}