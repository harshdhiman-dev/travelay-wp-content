/**
 * External dependencies
 */
const { registerPaymentMethod } = wc.wcBlocksRegistry;
const { getSetting } = wc.wcSettings;
const { decodeEntities } = wp.htmlEntities;
const { __ } = wp.i18n;
const { createElement } = wp.element;

/**
 * Internal dependencies
 */
const PAYMENT_METHOD_NAME = 'crypto_pay';

const settings = getSetting('crypto_pay_data', {});
const defaultLabel = __('Crypto.com Pay', 'crypto-pay');
const label = decodeEntities(settings.title || defaultLabel);

/**
 * Content component
 */
const Content = () => {
    return decodeEntities(settings.description || '');
};

/**
 * Label component
 */
const Label = () => {
    return createElement('div', {
        style: {
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
        }
    }, [
        createElement('img', {
            src: settings.icon,
            alt: label,
            style: {
                width: 'auto',
                maxHeight: '24px'
            }
        }),
        createElement('span', null, label)
    ]);
};

/**
 * Crypto.com Pay payment method config object.
 */
const CryptoPayPaymentMethod = {
    name: PAYMENT_METHOD_NAME,
    label: createElement(Label),
    content: createElement(Content),
    edit: createElement(Content),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports || ['products', 'refunds'],
    },
};

registerPaymentMethod(CryptoPayPaymentMethod); 