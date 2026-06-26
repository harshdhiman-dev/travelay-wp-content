/**
 * Amadex Flight Booking Page JavaScript
 * Version: 1.0.4 - Multi-Passenger Seat Selection, Progress Navigation, API Fixes
 */

(function ($) {
    'use strict';

    // IMMEDIATE LOG - Should appear when file loads
    // Suppress ApplePay/PaymentRequest errors globally (we're using Collect.js, not ApplePay)
    // Also suppress verbose Stripe error logging in production
    // This must be set up immediately to catch all errors from NMI Collect.js
    if (window.console && window.console.error) {
        const originalError = window.console.error;
        window.console.error = function (...args) {
            const message = args.join(' ');
            // Suppress ApplePay/PaymentRequest errors - these are harmless warnings
            if (message.includes('PaymentRequestAbstraction') ||
                message.includes('ApplePayRequest') ||
                message.includes('Could not create PaymentRequestAbstraction') ||
                message.includes('PaymentRequest') ||
                message.includes('Apple Pay')) {
                // Silently ignore - this is expected when Apple Pay is not available
                return;
            }
            // Suppress verbose AJAX error details in production (keep user-friendly messages)
            if (message.includes('=== Amadex Payment Page: AJAX Request Failed ===') ||
                message.includes('=== Amadex Payment Page: Checkout Session AJAX Error ===') ||
                message.includes('=== End AJAX Error Details ===') ||
                message.includes('=== End of Error Details ===') ||
                message.includes('Response is NOT valid JSON') ||
                message.includes('Response appears to be HTML') ||
                message.includes('Response Text Length:') ||
                message.includes('Response text length:')) {
                // Suppress verbose debugging output - errors are still shown to users via UI
                return;
            }
            originalError.apply(console, args);
        };
    }

    // Also suppress warnings about PaymentRequest
    if (window.console && window.console.warn) {
        const originalWarn = window.console.warn;
        window.console.warn = function (...args) {
            const message = args.join(' ');
            if (message.includes('PaymentRequestAbstraction') ||
                message.includes('ApplePayRequest') ||
                message.includes('PaymentRequest') ||
                message.includes('Apple Pay')) {
                return;
            }
            originalWarn.apply(console, args);
        };
    }

    const AMADEX_COUNTRY_LIST = [
        { code: 'AF', name: 'Afghanistan' },
        { code: 'AL', name: 'Albania' },
        { code: 'DZ', name: 'Algeria' },
        { code: 'AD', name: 'Andorra' },
        { code: 'AO', name: 'Angola' },
        { code: 'AG', name: 'Antigua and Barbuda' },
        { code: 'AR', name: 'Argentina' },
        { code: 'AM', name: 'Armenia' },
        { code: 'AU', name: 'Australia' },
        { code: 'AT', name: 'Austria' },
        { code: 'AZ', name: 'Azerbaijan' },
        { code: 'BS', name: 'Bahamas' },
        { code: 'BH', name: 'Bahrain' },
        { code: 'BD', name: 'Bangladesh' },
        { code: 'BB', name: 'Barbados' },
        { code: 'BY', name: 'Belarus' },
        { code: 'BE', name: 'Belgium' },
        { code: 'BZ', name: 'Belize' },
        { code: 'BJ', name: 'Benin' },
        { code: 'BT', name: 'Bhutan' },
        { code: 'BO', name: 'Bolivia' },
        { code: 'BA', name: 'Bosnia and Herzegovina' },
        { code: 'BW', name: 'Botswana' },
        { code: 'BR', name: 'Brazil' },
        { code: 'BN', name: 'Brunei' },
        { code: 'BG', name: 'Bulgaria' },
        { code: 'BF', name: 'Burkina Faso' },
        { code: 'BI', name: 'Burundi' },
        { code: 'CV', name: 'Cabo Verde' },
        { code: 'KH', name: 'Cambodia' },
        { code: 'CM', name: 'Cameroon' },
        { code: 'CA', name: 'Canada' },
        { code: 'CF', name: 'Central African Republic' },
        { code: 'TD', name: 'Chad' },
        { code: 'CL', name: 'Chile' },
        { code: 'CN', name: 'China' },
        { code: 'CO', name: 'Colombia' },
        { code: 'KM', name: 'Comoros' },
        { code: 'CG', name: 'Congo' },
        { code: 'CD', name: 'Congo (DRC)' },
        { code: 'CR', name: 'Costa Rica' },
        { code: 'CI', name: "Cote d'Ivoire" },
        { code: 'HR', name: 'Croatia' },
        { code: 'CU', name: 'Cuba' },
        { code: 'CY', name: 'Cyprus' },
        { code: 'CZ', name: 'Czechia' },
        { code: 'DK', name: 'Denmark' },
        { code: 'DJ', name: 'Djibouti' },
        { code: 'DM', name: 'Dominica' },
        { code: 'DO', name: 'Dominican Republic' },
        { code: 'EC', name: 'Ecuador' },
        { code: 'EG', name: 'Egypt' },
        { code: 'SV', name: 'El Salvador' },
        { code: 'GQ', name: 'Equatorial Guinea' },
        { code: 'ER', name: 'Eritrea' },
        { code: 'EE', name: 'Estonia' },
        { code: 'SZ', name: 'Eswatini' },
        { code: 'ET', name: 'Ethiopia' },
        { code: 'FJ', name: 'Fiji' },
        { code: 'FI', name: 'Finland' },
        { code: 'FR', name: 'France' },
        { code: 'GA', name: 'Gabon' },
        { code: 'GM', name: 'Gambia' },
        { code: 'GE', name: 'Georgia' },
        { code: 'DE', name: 'Germany' },
        { code: 'GH', name: 'Ghana' },
        { code: 'GR', name: 'Greece' },
        { code: 'GD', name: 'Grenada' },
        { code: 'GT', name: 'Guatemala' },
        { code: 'GN', name: 'Guinea' },
        { code: 'GW', name: 'Guinea-Bissau' },
        { code: 'GY', name: 'Guyana' },
        { code: 'HT', name: 'Haiti' },
        { code: 'HN', name: 'Honduras' },
        { code: 'HU', name: 'Hungary' },
        { code: 'IS', name: 'Iceland' },
        { code: 'IN', name: 'India' },
        { code: 'ID', name: 'Indonesia' },
        { code: 'IR', name: 'Iran' },
        { code: 'IQ', name: 'Iraq' },
        { code: 'IE', name: 'Ireland' },
        { code: 'IL', name: 'Israel' },
        { code: 'IT', name: 'Italy' },
        { code: 'JM', name: 'Jamaica' },
        { code: 'JP', name: 'Japan' },
        { code: 'JO', name: 'Jordan' },
        { code: 'KZ', name: 'Kazakhstan' },
        { code: 'KE', name: 'Kenya' },
        { code: 'KI', name: 'Kiribati' },
        { code: 'KW', name: 'Kuwait' },
        { code: 'KG', name: 'Kyrgyzstan' },
        { code: 'LA', name: 'Laos' },
        { code: 'LV', name: 'Latvia' },
        { code: 'LB', name: 'Lebanon' },
        { code: 'LS', name: 'Lesotho' },
        { code: 'LR', name: 'Liberia' },
        { code: 'LY', name: 'Libya' },
        { code: 'LI', name: 'Liechtenstein' },
        { code: 'LT', name: 'Lithuania' },
        { code: 'LU', name: 'Luxembourg' },
        { code: 'MG', name: 'Madagascar' },
        { code: 'MW', name: 'Malawi' },
        { code: 'MY', name: 'Malaysia' },
        { code: 'MV', name: 'Maldives' },
        { code: 'ML', name: 'Mali' },
        { code: 'MT', name: 'Malta' },
        { code: 'MH', name: 'Marshall Islands' },
        { code: 'MR', name: 'Mauritania' },
        { code: 'MU', name: 'Mauritius' },
        { code: 'MX', name: 'Mexico' },
        { code: 'FM', name: 'Micronesia' },
        { code: 'MD', name: 'Moldova' },
        { code: 'MC', name: 'Monaco' },
        { code: 'MN', name: 'Mongolia' },
        { code: 'ME', name: 'Montenegro' },
        { code: 'MA', name: 'Morocco' },
        { code: 'MZ', name: 'Mozambique' },
        { code: 'MM', name: 'Myanmar' },
        { code: 'NA', name: 'Namibia' },
        { code: 'NR', name: 'Nauru' },
        { code: 'NP', name: 'Nepal' },
        { code: 'NL', name: 'Netherlands' },
        { code: 'NZ', name: 'New Zealand' },
        { code: 'NI', name: 'Nicaragua' },
        { code: 'NE', name: 'Niger' },
        { code: 'NG', name: 'Nigeria' },
        { code: 'KP', name: 'North Korea' },
        { code: 'MK', name: 'North Macedonia' },
        { code: 'NO', name: 'Norway' },
        { code: 'OM', name: 'Oman' },
        { code: 'PK', name: 'Pakistan' },
        { code: 'PW', name: 'Palau' },
        { code: 'PA', name: 'Panama' },
        { code: 'PG', name: 'Papua New Guinea' },
        { code: 'PY', name: 'Paraguay' },
        { code: 'PE', name: 'Peru' },
        { code: 'PH', name: 'Philippines' },
        { code: 'PL', name: 'Poland' },
        { code: 'PT', name: 'Portugal' },
        { code: 'QA', name: 'Qatar' },
        { code: 'RO', name: 'Romania' },
        { code: 'RU', name: 'Russia' },
        { code: 'RW', name: 'Rwanda' },
        { code: 'KN', name: 'Saint Kitts and Nevis' },
        { code: 'LC', name: 'Saint Lucia' },
        { code: 'VC', name: 'Saint Vincent and the Grenadines' },
        { code: 'WS', name: 'Samoa' },
        { code: 'SM', name: 'San Marino' },
        { code: 'ST', name: 'Sao Tome and Principe' },
        { code: 'SA', name: 'Saudi Arabia' },
        { code: 'SN', name: 'Senegal' },
        { code: 'RS', name: 'Serbia' },
        { code: 'SC', name: 'Seychelles' },
        { code: 'SL', name: 'Sierra Leone' },
        { code: 'SG', name: 'Singapore' },
        { code: 'SK', name: 'Slovakia' },
        { code: 'SI', name: 'Slovenia' },
        { code: 'SB', name: 'Solomon Islands' },
        { code: 'SO', name: 'Somalia' },
        { code: 'ZA', name: 'South Africa' },
        { code: 'KR', name: 'South Korea' },
        { code: 'SS', name: 'South Sudan' },
        { code: 'ES', name: 'Spain' },
        { code: 'LK', name: 'Sri Lanka' },
        { code: 'SD', name: 'Sudan' },
        { code: 'SR', name: 'Suriname' },
        { code: 'SE', name: 'Sweden' },
        { code: 'CH', name: 'Switzerland' },
        { code: 'SY', name: 'Syria' },
        { code: 'TW', name: 'Taiwan' },
        { code: 'TJ', name: 'Tajikistan' },
        { code: 'TZ', name: 'Tanzania' },
        { code: 'TH', name: 'Thailand' },
        { code: 'TL', name: 'Timor-Leste' },
        { code: 'TG', name: 'Togo' },
        { code: 'TO', name: 'Tonga' },
        { code: 'TT', name: 'Trinidad and Tobago' },
        { code: 'TN', name: 'Tunisia' },
        { code: 'TR', name: 'Turkey' },
        { code: 'TM', name: 'Turkmenistan' },
        { code: 'TV', name: 'Tuvalu' },
        { code: 'UG', name: 'Uganda' },
        { code: 'UA', name: 'Ukraine' },
        { code: 'AE', name: 'United Arab Emirates' },
        { code: 'GB', name: 'United Kingdom' },
        { code: 'US', name: 'United States' },
        { code: 'UY', name: 'Uruguay' },
        { code: 'UZ', name: 'Uzbekistan' },
        { code: 'VU', name: 'Vanuatu' },
        { code: 'VA', name: 'Vatican City' },
        { code: 'VE', name: 'Venezuela' },
        { code: 'VN', name: 'Vietnam' },
        { code: 'YE', name: 'Yemen' },
        { code: 'ZM', name: 'Zambia' },
        { code: 'ZW', name: 'Zimbabwe' }
    ];

    const AMADEX_COUNTRY_STATES = {
        US: [
            { code: 'AL', name: 'Alabama' },
            { code: 'AK', name: 'Alaska' },
            { code: 'AZ', name: 'Arizona' },
            { code: 'AR', name: 'Arkansas' },
            { code: 'CA', name: 'California' },
            { code: 'CO', name: 'Colorado' },
            { code: 'CT', name: 'Connecticut' },
            { code: 'DE', name: 'Delaware' },
            { code: 'DC', name: 'District of Columbia' },
            { code: 'FL', name: 'Florida' },
            { code: 'GA', name: 'Georgia' },
            { code: 'HI', name: 'Hawaii' },
            { code: 'ID', name: 'Idaho' },
            { code: 'IL', name: 'Illinois' },
            { code: 'IN', name: 'Indiana' },
            { code: 'IA', name: 'Iowa' },
            { code: 'KS', name: 'Kansas' },
            { code: 'KY', name: 'Kentucky' },
            { code: 'LA', name: 'Louisiana' },
            { code: 'ME', name: 'Maine' },
            { code: 'MD', name: 'Maryland' },
            { code: 'MA', name: 'Massachusetts' },
            { code: 'MI', name: 'Michigan' },
            { code: 'MN', name: 'Minnesota' },
            { code: 'MS', name: 'Mississippi' },
            { code: 'MO', name: 'Missouri' },
            { code: 'MT', name: 'Montana' },
            { code: 'NE', name: 'Nebraska' },
            { code: 'NV', name: 'Nevada' },
            { code: 'NH', name: 'New Hampshire' },
            { code: 'NJ', name: 'New Jersey' },
            { code: 'NM', name: 'New Mexico' },
            { code: 'NY', name: 'New York' },
            { code: 'NC', name: 'North Carolina' },
            { code: 'ND', name: 'North Dakota' },
            { code: 'OH', name: 'Ohio' },
            { code: 'OK', name: 'Oklahoma' },
            { code: 'OR', name: 'Oregon' },
            { code: 'PA', name: 'Pennsylvania' },
            { code: 'RI', name: 'Rhode Island' },
            { code: 'SC', name: 'South Carolina' },
            { code: 'SD', name: 'South Dakota' },
            { code: 'TN', name: 'Tennessee' },
            { code: 'TX', name: 'Texas' },
            { code: 'UT', name: 'Utah' },
            { code: 'VT', name: 'Vermont' },
            { code: 'VA', name: 'Virginia' },
            { code: 'WA', name: 'Washington' },
            { code: 'WV', name: 'West Virginia' },
            { code: 'WI', name: 'Wisconsin' },
            { code: 'WY', name: 'Wyoming' },
            { code: 'PR', name: 'Puerto Rico' },
            { code: 'VI', name: 'U.S. Virgin Islands' },
            { code: 'GU', name: 'Guam' },
            { code: 'AS', name: 'American Samoa' },
            { code: 'MP', name: 'Northern Mariana Islands' }
        ],
        CA: [
            { code: 'AB', name: 'Alberta' },
            { code: 'BC', name: 'British Columbia' },
            { code: 'MB', name: 'Manitoba' },
            { code: 'NB', name: 'New Brunswick' },
            { code: 'NL', name: 'Newfoundland and Labrador' },
            { code: 'NT', name: 'Northwest Territories' },
            { code: 'NS', name: 'Nova Scotia' },
            { code: 'NU', name: 'Nunavut' },
            { code: 'ON', name: 'Ontario' },
            { code: 'PE', name: 'Prince Edward Island' },
            { code: 'QC', name: 'Quebec' },
            { code: 'SK', name: 'Saskatchewan' },
            { code: 'YT', name: 'Yukon' }
        ],
        AU: [
            { code: 'ACT', name: 'Australian Capital Territory' },
            { code: 'NSW', name: 'New South Wales' },
            { code: 'NT', name: 'Northern Territory' },
            { code: 'QLD', name: 'Queensland' },
            { code: 'SA', name: 'South Australia' },
            { code: 'TAS', name: 'Tasmania' },
            { code: 'VIC', name: 'Victoria' },
            { code: 'WA', name: 'Western Australia' }
        ],
        IN: [
            { code: 'AP', name: 'Andhra Pradesh' },
            { code: 'AR', name: 'Arunachal Pradesh' },
            { code: 'AS', name: 'Assam' },
            { code: 'BR', name: 'Bihar' },
            { code: 'CG', name: 'Chhattisgarh' },
            { code: 'GA', name: 'Goa' },
            { code: 'GJ', name: 'Gujarat' },
            { code: 'HR', name: 'Haryana' },
            { code: 'HP', name: 'Himachal Pradesh' },
            { code: 'JH', name: 'Jharkhand' },
            { code: 'KA', name: 'Karnataka' },
            { code: 'KL', name: 'Kerala' },
            { code: 'MP', name: 'Madhya Pradesh' },
            { code: 'MH', name: 'Maharashtra' },
            { code: 'MN', name: 'Manipur' },
            { code: 'ML', name: 'Meghalaya' },
            { code: 'MZ', name: 'Mizoram' },
            { code: 'NL', name: 'Nagaland' },
            { code: 'OD', name: 'Odisha' },
            { code: 'PB', name: 'Punjab' },
            { code: 'RJ', name: 'Rajasthan' },
            { code: 'SK', name: 'Sikkim' },
            { code: 'TN', name: 'Tamil Nadu' },
            { code: 'TS', name: 'Telangana' },
            { code: 'TR', name: 'Tripura' },
            { code: 'UP', name: 'Uttar Pradesh' },
            { code: 'UK', name: 'Uttarakhand' },
            { code: 'WB', name: 'West Bengal' },
            { code: 'AN', name: 'Andaman and Nicobar Islands' },
            { code: 'CH', name: 'Chandigarh' },
            { code: 'DH', name: 'Dadra and Nagar Haveli and Daman and Diu' },
            { code: 'DL', name: 'Delhi' },
            { code: 'JK', name: 'Jammu and Kashmir' },
            { code: 'LA', name: 'Ladakh' },
            { code: 'LD', name: 'Lakshadweep' },
            { code: 'PY', name: 'Puducherry' }
        ],
        GB: [
            { code: 'ENG', name: 'England' },
            { code: 'SCT', name: 'Scotland' },
            { code: 'WLS', name: 'Wales' },
            { code: 'NIR', name: 'Northern Ireland' },
            { code: 'IM', name: 'Isle of Man' },
            { code: 'JE', name: 'Jersey' },
            { code: 'GG', name: 'Guernsey' }
        ]
    };

    const AMADEX_STATE_LABELS = {
        US: 'State',
        CA: 'Province / Territory',
        AU: 'State / Territory',
        IN: 'State / Union Territory',
        GB: 'County / Region'
    };

    // Cache for dynamically fetched states keyed by country code
    const AMADEX_DYNAMIC_STATE_CACHE = {};
    const AMADEX_COUNTRY_STATE_REQUESTS = {};

    const AMADEX_COUNTRY_AIRPORTS = {
        US: ['JFK', 'LAX', 'ORD', 'ATL', 'DFW', 'DEN', 'SFO', 'SEA', 'LAS', 'MCO', 'EWR', 'CLT', 'PHX', 'IAH', 'MIA', 'BOS', 'MSP', 'FLL', 'DTW', 'PHL', 'LGA', 'BWI', 'SLC', 'SAN', 'IAD', 'DCA', 'MDW', 'TPA', 'PDX', 'STL'],
        IN: ['DEL', 'BOM', 'BLR', 'MAA', 'HYD', 'CCU', 'AMD', 'GOI', 'COK', 'PNQ', 'JAI', 'LKO', 'TRV', 'IXC', 'GAU', 'IXB', 'IXR', 'IXA', 'IXL', 'IXJ'],
        GB: ['LHR', 'LGW', 'MAN', 'STN', 'EDI', 'BHX', 'GLA', 'BRS', 'NCL', 'EMA', 'LBA', 'ABZ', 'BFS', 'SOU'],
        CA: ['YYZ', 'YVR', 'YUL', 'YYC', 'YOW', 'YEG', 'YHZ', 'YWG', 'YQB'],
        AU: ['SYD', 'MEL', 'BNE', 'PER', 'ADL', 'CNS', 'DRW', 'HBA', 'OOL'],
        CN: ['PEK', 'PVG', 'CAN', 'CTU', 'SZX', 'CKG', 'XIY', 'KMG', 'HGH', 'NKG'],
        JP: ['NRT', 'HND', 'KIX', 'FUK', 'CTS', 'OKA', 'NGO'],
        DE: ['FRA', 'MUC', 'DUS', 'TXL', 'HAM', 'CGN', 'STR'],
        FR: ['CDG', 'ORY', 'NCE', 'LYS', 'MRS', 'TLS', 'BOD'],
        ES: ['MAD', 'BCN', 'AGP', 'PMI', 'SVQ', 'VLC', 'BIO'],
        IT: ['FCO', 'MXP', 'VCE', 'NAP', 'BLQ', 'CTA'],
        BR: ['GRU', 'GIG', 'BSB', 'CGH', 'SSA', 'FOR', 'REC'],
        MX: ['MEX', 'CUN', 'GDL', 'MTY', 'TIJ', 'SJD'],
        AE: ['DXB', 'AUH', 'SHJ'],
        SG: ['SIN'],
        TH: ['BKK', 'HKT', 'CNX', 'DMK'],
        MY: ['KUL', 'PEN', 'JHB']
    };

    const AMADEX_COUNTRY_CODE_ENDPOINT = 'https://restcountries.com/v3.1/all?fields=cca2,name,idd';
    const AMADEX_FALLBACK_DIAL_CODES = [
        { code: 'US', name: 'United States', dial: '+1' },
        { code: 'CA', name: 'Canada', dial: '+1' },
        { code: 'GB', name: 'United Kingdom', dial: '+44' },
        { code: 'AU', name: 'Australia', dial: '+61' },
        { code: 'IN', name: 'India', dial: '+91' },
        { code: 'AE', name: 'United Arab Emirates', dial: '+971' },
        { code: 'SG', name: 'Singapore', dial: '+65' },
        { code: 'DE', name: 'Germany', dial: '+49' },
        { code: 'FR', name: 'France', dial: '+33' },
        { code: 'BR', name: 'Brazil', dial: '+55' },
        { code: 'MX', name: 'Mexico', dial: '+52' }
    ];

    let AMADEX_CONTACT_CODES_CACHE = null;
    let AMADEX_CONTACT_CODES_REQUEST = null;

    /**
     * Helper to parse price values that might include currency text or formatting
     */
    function parsePriceValue(value) {
        if (typeof value === 'number' && !isNaN(value)) {
            return value;
        }

        if (typeof value === 'string') {
            const cleaned = value.replace(/[^0-9.,-]/g, '').replace(/,/g, '');
            if (cleaned.length) {
                const parsed = parseFloat(cleaned);
                if (!isNaN(parsed)) {
                    return parsed;
                }
            }
        }

        return null;
    }

    function resolvePriceValue(values = []) {
        for (const val of values) {
            const parsed = parsePriceValue(val);
            if (parsed !== null && !isNaN(parsed)) {
                return parsed;
            }
        }
        return null;
    }

    function formatCurrencyValue(amount, currency = 'USD') {
        if (amount === null || typeof amount === 'undefined' || isNaN(amount)) {
            return '';
        }

        try {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency,
                maximumFractionDigits: 2,
                minimumFractionDigits: 2
            }).format(amount);
        } catch (error) {
            const symbol = currency === 'USD' ? '$' : '';
            return `${symbol}${amount.toFixed(2)}`;
        }
    }

    function initializeContactCountryCodes() {
        const select = $('#contact-country-code');
        if (!select.length) {
            return;
        }

        const defaultCountry = (select.data('defaultCountry') || 'US').toString().toUpperCase();
        setContactCountryPlaceholder(select, 'Loading country codes...');

        fetchContactCountryCodes()
            .then(list => {
                if (list && list.length) {
                    populateContactCountrySelect(select, list, defaultCountry);
                } else {
                    populateContactCountrySelect(select, AMADEX_FALLBACK_DIAL_CODES, defaultCountry);
                }
            })
            .catch(error => {
                populateContactCountrySelect(select, AMADEX_FALLBACK_DIAL_CODES, defaultCountry);
            });
    }

    function fetchContactCountryCodes() {
        if (AMADEX_CONTACT_CODES_CACHE) {
            return Promise.resolve(AMADEX_CONTACT_CODES_CACHE);
        }

        if (AMADEX_CONTACT_CODES_REQUEST) {
            return AMADEX_CONTACT_CODES_REQUEST;
        }

        AMADEX_CONTACT_CODES_REQUEST = fetch(AMADEX_COUNTRY_CODE_ENDPOINT, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const parsed = parseCountryCodeResponse(data);
                AMADEX_CONTACT_CODES_CACHE = parsed;
                return parsed;
            })
            .finally(() => {
                AMADEX_CONTACT_CODES_REQUEST = null;
            });

        return AMADEX_CONTACT_CODES_REQUEST;
    }

    function parseCountryCodeResponse(data) {
        if (!Array.isArray(data)) {
            return [];
        }

        const entries = [];
        const seenDialCodes = new Set(); // Track seen dial codes to prevent duplicates

        data.forEach(country => {
            if (!country) {
                return;
            }

            const code = (country.cca2 || '').toString().toUpperCase();
            const name = (country.name && (country.name.common || country.name.official)) || '';
            const idd = country.idd || {};
            const root = idd.root || '';
            const suffixes = Array.isArray(idd.suffixes) && idd.suffixes.length ? idd.suffixes : [''];

            if (!code || !name || !root) {
                return;
            }

            // For US, only use the first suffix (or empty) to avoid multiple +1 entries
            const suffixesToUse = code === 'US' ? [''] : suffixes;

            suffixesToUse.forEach(suffix => {
                const dial = `${root}${suffix || ''}`.replace(/\s+/g, '');
                if (!dial) {
                    return;
                }

                // Create unique key for dial code + country code combination
                const uniqueKey = `${code}-${dial}`;

                // Skip if we've already seen this dial code for this country
                // For US, only allow one +1 entry
                if (code === 'US' && seenDialCodes.has('US-+1')) {
                    return;
                }

                if (seenDialCodes.has(uniqueKey)) {
                    return;
                }

                seenDialCodes.add(uniqueKey);
                if (code === 'US') {
                    seenDialCodes.add('US-+1'); // Mark US-+1 as seen
                }

                entries.push({
                    code,
                    name,
                    dial,
                    flag: countryCodeToFlag(code)
                });
            });
        });

        return entries
            .filter(entry => entry.dial && entry.name)
            .sort((a, b) => a.name.localeCompare(b.name));
    }

    function populateContactCountrySelect(select, entries = [], defaultCountry = 'US') {
        const enrichedEntries = enrichDialCodeEntries(entries);
        const placeholder = select.data('placeholder') || 'Select Country Code';

        if (!enrichedEntries.length) {
            setContactCountryPlaceholder(select, placeholder, false);
            return;
        }

        select.empty();
        select.append(`<option value="">${placeholder}</option>`);

        enrichedEntries.forEach(entry => {
            const label = `${entry.name} (${entry.dial})`;
            const option = $('<option></option>')
                .val(entry.dial)
                .text(label)
                .attr('data-country', entry.code);

            if (entry.code === defaultCountry.toUpperCase()) {
                option.prop('selected', true);
            }

            select.append(option);
        });

        select.prop('disabled', false);
    }

    function setContactCountryPlaceholder(select, text, disabled = true) {
        select
            .empty()
            .append(`<option value="">${text}</option>`)
            .prop('disabled', !!disabled);
    }

    function enrichDialCodeEntries(entries = []) {
        return entries.map(entry => ({
            ...entry,
            code: (entry.code || '').toString().toUpperCase(),
            flag: entry.flag || countryCodeToFlag(entry.code)
        }));
    }

    function countryCodeToFlag(code) {
        if (!code || code.length !== 2) {
            return '';
        }
        return code
            .toUpperCase()
            .split('')
            .map(char => String.fromCodePoint(127397 + char.charCodeAt(0)))
            .join('');
    }

    $(document).ready(function () {
        // Initialize booking page
        initBookingPage();

        // Handle 3DS return: show error if payment failed
        (function () {
            var urlParams = new URLSearchParams(window.location.search);
            var paymentError = urlParams.get('amadex_payment_error');
            if (paymentError) {
                setTimeout(function () {
                    showPaymentError(decodeURIComponent(paymentError));
                    // Scroll to error
                    $('html, body').animate({ scrollTop: $('#amadex-payment-section').offset().top - 100 }, 400);
                }, 500);
            }
        })();

        // Clear timer state when navigating away from booking page (industry best practice)
        // Timer should reset when user leaves booking page, not persist across navigation
        window.addEventListener('beforeunload', function () {
            // Clear timer state when navigating away (user is leaving booking page)
            // This ensures fresh timer when user returns, prevents stale pricing
            sessionStorage.removeItem('amadex_booking_timer_start');
            sessionStorage.removeItem('amadex_booking_timer_remaining');
            sessionStorage.removeItem('amadex_booking_timer_paused_at');
        });

        // Handle visibility change - pause/resume timer but preserve urgency
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                // Page is hidden (phone locked, tab switched, etc.) - save current state
                if (window.amadexBookingTimerInterval && window.amadexTimerRemaining !== undefined) {
                    // Save current remaining time
                    sessionStorage.setItem('amadex_booking_timer_remaining', window.amadexTimerRemaining.toString());
                    // Save the time when page was hidden (for pause duration calculation)
                    sessionStorage.setItem('amadex_booking_timer_paused_at', Date.now().toString());
                } else {
                    // If timer interval doesn't exist, try to get from display
                    const timerDisplay = $('#amadex-booking-timer-badge').find('.timer-display');
                    if (timerDisplay.length) {
                        const displayText = timerDisplay.text();
                        const timeMatch = displayText.match(/(\d{2}):(\d{2})/);
                        if (timeMatch) {
                            const minutes = parseInt(timeMatch[1]);
                            const seconds = parseInt(timeMatch[2]);
                            const totalSeconds = (minutes * 60) + seconds;
                            sessionStorage.setItem('amadex_booking_timer_remaining', totalSeconds.toString());
                            sessionStorage.setItem('amadex_booking_timer_paused_at', Date.now().toString());
                        }
                    }
                }
            } else {
                // Page is visible again (phone unlocked, tab switched back) - resume timer
                if ($('#amadex-booking-page').length > 0) {
                    // Only resume if timer exists and it's the same flight
                    const timerStartTime = sessionStorage.getItem('amadex_booking_timer_start');
                    const lastFlightId = sessionStorage.getItem('amadex_last_booking_flight_id');
                    const currentFlightId = (window.amadexBookingFlight && (window.amadexBookingFlight.id || window.amadexBookingFlight.offerId || (window.amadexBookingFlight.rawOffer && window.amadexBookingFlight.rawOffer.id))) || null;

                    // Only resume if same flight and timer exists
                    if (timerStartTime && lastFlightId && currentFlightId === lastFlightId) {
                        // Clear existing interval if any
                        if (window.amadexBookingTimerInterval) {
                            clearInterval(window.amadexBookingTimerInterval);
                            window.amadexBookingTimerInterval = null;
                        }
                        // Restart timer to sync with saved state (will resume from where it left off)
                        setTimeout(function () {
                            startBookingTimer();
                        }, 100);
                    } else {
                    }
                }
            }
        });
    });

    /**
     * Initialize booking page
     */
    // Define seat selection module early (before it's used)
    function initBookingPage() {
        // Check if we're on booking page
        if ($('#amadex-booking-page').length === 0) {
            return;
        }

        // CRITICAL CHECK: If booking was already cleared (after successful booking), redirect immediately
        // This prevents loading booking page with stale data after user clicks back button
        if (sessionStorage.getItem('amadex_booking_cleared') === 'true') {
            sessionStorage.removeItem('amadex_booking_cleared'); // Clean up flag
            alert('Your booking has been completed. Redirecting to search page.');
            window.location.href = '/';
            return;
        }

        // Load flight data from session storage
        const flightData = sessionStorage.getItem('amadex_booking_flight');
        if (!flightData) {
            alert('No flight selected. Redirecting to search page.');
            window.location.href = '/';
            return;
        }

        let flight = null;
        try {
            flight = JSON.parse(flightData);
        } catch (e) {
            alert('Error loading flight data. Please try again.');
            window.location.href = '/';
            return;
        }

        // Check if this is a new flight selection - always start fresh timer (industry best practice)
        // Timer resets on navigation away, only persists for tab switching (visibility change)
        const currentFlightId = flight.id || flight.offerId || (flight.rawOffer && flight.rawOffer.id) || null;
        const lastFlightId = sessionStorage.getItem('amadex_last_booking_flight_id');

        if (currentFlightId && currentFlightId !== lastFlightId) {
            // New flight selected - clear timer and start fresh
            sessionStorage.removeItem('amadex_booking_timer_start');
            sessionStorage.removeItem('amadex_booking_timer_remaining');
            sessionStorage.removeItem('amadex_booking_timer_paused_at');
            // Store the new flight ID
            sessionStorage.setItem('amadex_last_booking_flight_id', currentFlightId.toString());
            // Clear warning modal flag for new flight
            window.amadexWarningModalShown = false;
        } else if (currentFlightId) {
            // Same flight - store ID if not already stored
            if (!lastFlightId) {
                sessionStorage.setItem('amadex_last_booking_flight_id', currentFlightId.toString());
            }
            // Always clear timer state when returning to booking page (navigation away clears timer state)
            // Timer state is only preserved for tab switching (visibility change), not navigation
            sessionStorage.removeItem('amadex_booking_timer_start');
            sessionStorage.removeItem('amadex_booking_timer_remaining');
            // Keep paused_at only if page was just hidden (tab switch), not navigated away
            const pausedAt = sessionStorage.getItem('amadex_booking_timer_paused_at');
            if (pausedAt) {
                // Check if pause was recent (within last few seconds) - if so, it's a tab switch
                const pauseTime = parseInt(pausedAt);
                const timeSincePause = Date.now() - pauseTime;
                if (timeSincePause > 3000) {
                    // Pause was more than 3 seconds ago - likely navigation, clear it
                    sessionStorage.removeItem('amadex_booking_timer_paused_at');
                }
            }
        }

        // Load search data from session storage (contains passenger counts, dates, etc.)
        let searchData = {};
        const searchDataStr = sessionStorage.getItem('amadex_search_data');
        if (searchDataStr) {
            try {
                searchData = JSON.parse(searchDataStr);
            } catch (e) {
                // Continue with default values
            }
        } else {
        }

        // Ensure we have default values
        // Ensure we have default values
        if (!searchData.adults) searchData.adults = 1;
        if (!searchData.children) searchData.children = 0;
        if (!searchData.infants) searchData.infants = 0;

        // Also check URL parameters as fallback and merge into searchData
        const urlParams = new URLSearchParams(window.location.search);

        // Get passenger counts from URL if available (fallback)
        if (urlParams.get('adults')) {
            searchData.adults = parseInt(urlParams.get('adults')) || 1;
        }
        if (urlParams.get('children')) {
            searchData.children = parseInt(urlParams.get('children')) || 0;
        }
        if (urlParams.get('infants')) {
            searchData.infants = parseInt(urlParams.get('infants')) || 0;
        }

        // Set global searchData so seat selection module and other functions can access it
        window.amadexSearchData = searchData;
        // Priority 1: URL parameters (highest priority)
        const urlTripType = urlParams.get('trip_type') || urlParams.get('tripType');
        const urlOneWay = urlParams.get('one_way') || urlParams.get('oneWay');

        if (urlTripType) {
            searchData.trip_type = urlTripType;
        } else if (urlOneWay === 'Yes' || urlOneWay === 'true' || urlOneWay === '1') {
            searchData.trip_type = 'oneway';
        }

        // Priority 2: Search data from sessionStorage
        if (!searchData.trip_type && !searchData.tripType) {
            if (searchData.trip_type) {
                searchData.trip_type = searchData.trip_type;
            } else if (searchData.one_way === 'Yes' || searchData.one_way === true || searchData.one_way === 'true' || searchData.one_way === '1') {
                searchData.trip_type = 'oneway';
            }
        }

        // Priority 3: Determine from other indicators (lowest priority)
        if (!searchData.trip_type && !searchData.tripType) {
            // Check if multi-city
            if (searchData.multi_segments && searchData.multi_segments.length > 1) {
                searchData.trip_type = 'multi-city';
            } else if (searchData.segments && searchData.segments.length > 1) {
                searchData.trip_type = 'multi-city';
            } else if (!searchData.return || searchData.return === '' || searchData.return === null) {
                searchData.trip_type = 'oneway';
            } else {
                searchData.trip_type = 'round';
            }
        }

        // Ensure trip_type is standardized
        if (searchData.trip_type) {
            searchData.trip_type = String(searchData.trip_type).toLowerCase().trim();
        } else if (searchData.tripType) {
            searchData.trip_type = String(searchData.tripType).toLowerCase().trim();
        }

        // Check if multi-city trip and load all segment flights
        let isMultiCity = searchData.trip_type === 'multi-city' || searchData.trip_type === 'multicity';
        let allSegmentFlights = null;

        if (isMultiCity) {
            // Try to load all selected flights for multi-city segments
            const multiCityBookings = sessionStorage.getItem('amadex_multi_city_bookings');
            const storedSegments = sessionStorage.getItem('amadex_multi_city_segments');

            if (multiCityBookings) {
                try {
                    allSegmentFlights = JSON.parse(multiCityBookings);
                } catch (e) {
                }
            }

            // If we have segments stored, use them to determine how many flights to show
            if (storedSegments) {
                try {
                    const segments = JSON.parse(storedSegments);
                    searchData.multi_segments = segments;
                    searchData.segment_count = segments.length;
                } catch (e) {
                }
            }
        }

        // Populate flight itinerary with cabin class
        populateItinerary(flight, searchData, allSegmentFlights);

        // Populate passenger forms - get counts from search data
        const adults = parseInt(searchData.adults || 1);
        const children = parseInt(searchData.children || 0);
        const infants = parseInt(searchData.infants || 0);

        const showPassportFields = shouldShowPassportFields(flight, searchData);
        populatePassengerForms(adults, children, infants, showPassportFields);
        $('body').toggleClass('amadex-passport-not-required', !showPassportFields);
        initializeContactCountryCodes();
        initializeBillingLocationControls();

        // Store original passenger counts in flight data for price calculation
        if (!flight.originalAdults) {
            flight.originalAdults = parseInt(searchData.adults || 1);
            flight.originalChildren = parseInt(searchData.children || 0);
            flight.originalInfants = parseInt(searchData.infants || 0);
        }

        // Ensure user's selected currency is stored in flight data (from regional settings)
        // This ensures currency is available throughout the booking journey
        // ALWAYS updates from storage to prevent stale values (Expert/God mode fix)
        const currentCurrency = ensureFlightCurrency(flight);
        // Update sessionStorage with flight data including currency
        sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flight));

        // Populate price breakdown - get price from flight data (from Amadeus API)
        // populatePriceBreakdown(flight, searchData);
        setTimeout(function () {
            populatePriceBreakdown(flight, searchData);
        }, 300);
        // EXPERT/GOD MODE FIX: Listen for currency changes while on booking page
        // This allows prices to update in real-time when user changes regional settings
        // Remove any existing listener first to prevent duplicates
        $(document).off('amadex:currency-changed.booking').on('amadex:currency-changed.booking', function (event, newCurrency) {
            // Check if regional settings system is enabled
            const regionalSettingsEnabled = (typeof AmadexConfig !== 'undefined' &&
                AmadexConfig.currency &&
                AmadexConfig.currency.regionalSettingsEnabled !== false) ||
                (window.AmadexCurrency &&
                    window.AmadexCurrency.regionalSettingsEnabled !== false);

            if (!regionalSettingsEnabled) {
                return;
            }

            // Reload flight data from sessionStorage (it may have been updated by triggerCurrencyChange)
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            if (flightData) {
                try {
                    const updatedFlight = JSON.parse(flightData);

                    // Ensure currency is updated in flight data
                    ensureFlightCurrency(updatedFlight);

                    // Reload search data
                    const searchDataStr = sessionStorage.getItem('amadex_search_data');
                    let updatedSearchData = {};
                    if (searchDataStr) {
                        try {
                            updatedSearchData = JSON.parse(searchDataStr);
                        } catch (e) {
                            // Fallback: try to use existing searchData from scope
                            updatedSearchData = searchData || {};
                        }
                    } else {
                        // Use existing searchData from scope if sessionStorage is empty
                        updatedSearchData = searchData || {};
                    }

                    // Ensure we have default values
                    if (!updatedSearchData.adults) updatedSearchData.adults = 1;
                    if (!updatedSearchData.children) updatedSearchData.children = 0;
                    if (!updatedSearchData.infants) updatedSearchData.infants = 0;

                    // Re-populate price breakdown with new currency
                    populatePriceBreakdown(updatedFlight, updatedSearchData, true); // true = animate

                    // Also update sticky price bar if it exists (mobile)
                    if (typeof updatePriceBar === 'function') {
                        updatePriceBar();
                    }

                } catch (e) {
                }
            } else {
            }
        });

        // Initialize premium services functionality
        try {
            initPremiumServices();
            initPremiumServicesCheckbox();
        } catch (e) {
        }

        // Initialize seat selection - always try to initialize
        if (typeof window.AmadexSeatSelection !== 'undefined') {
        } else {
        }

        try {
            if (typeof window.AmadexSeatSelection !== 'undefined' && typeof window.AmadexSeatSelection.init === 'function') {
                // window.AmadexSeatSelection.init(flight);
                window.AmadexSeatSelection.init(flight, allSegmentFlights);
            } else {
                if (typeof window.AmadexSeatSelection !== 'undefined') {
                } else {
                }
            }
        } catch (error) {
        }
        // Start countdown timer
        startBookingTimer();

        // Initialize payment method tabs if available
        if (typeof initPaymentMethodTabs === 'function') {
            initPaymentMethodTabs();
        }

        // Show/hide sections based on current step
        if (typeof updateBookingStep === 'function') {
            updateBookingStep('passengers');
        }

        // Initialize back button handlers for mobile compatibility
        initBackButtonHandlers();

        // Initialize form validation
        initFormValidation();

        // Set flag for delayed payment initialization (don't show errors on first load)
        window.amadexPaymentInitialized = false;
        window.amadexPaymentAttempted = false;

        // Delay payment initialization until user reaches payment section
        // This prevents showing error messages on first page load
        const bypassPayment = typeof AmadexConfig !== 'undefined' && AmadexConfig.bypassPayment === true;
        if (bypassPayment) {
            // Ensure button is enabled for bypass mode (step navigation button)
            $('#amadex-step-next').prop('disabled', false);
            // Also update hidden button if it still exists (fallback)
            $('#amadex-confirm-book').prop('disabled', false);
        } else {
            // Initialize payment when user scrolls to payment section or clicks continue
            initializePaymentOnDemand(flight);
        }

        // Card name field still needs manual handling
        // Card name field still needs manual handling
        $('#card-name').attr('autocomplete', 'cc-name');

        // Format card number as user types (adds spaces every 4 digits)
        $(document).on('input', '#card-number', function () {
            var val = $(this).val().replace(/\D/g, '').substring(0, 16);
            var formatted = val.replace(/(.{4})/g, '$1 ').trim();
            $(this).val(formatted);
        });

        // Only allow digits in month, year, CVV
        $(document).on('input', '#card-month, #card-year, #card-cvv', function () {
            $(this).val($(this).val().replace(/\D/g, ''));
        });
    }

    /**
     * Populate flight itinerary
     */
    function populateItinerary(flight, searchData, allSegmentFlights) {
        // Debug log
        // Debug log
        // Debug log
        const container = $('#amadex-booking-itinerary');
        const cabinClass = getCabinClassName(searchData.cabin || 'ECONOMY');
        const defaultBaggageDetails = getFlightBaggageDetails(flight);
        const defaultPassengerLabel = defaultBaggageDetails?.traveler || 'Adult';
        const defaultCheckInAllowance = defaultBaggageDetails?.checkIn || 'Not Available';
        const defaultCabinAllowance = defaultBaggageDetails?.cabin || 'Not Available';
        let html = '';

        // Check if multi-city trip with multiple segment flights
        const isMultiCity = searchData.trip_type === 'multi-city' || searchData.trip_type === 'multicity';
        const segments = searchData.multi_segments || searchData.segments || [];
        const hasMultipleSegments = segments.length > 1;

        if (isMultiCity && hasMultipleSegments && allSegmentFlights && Object.keys(allSegmentFlights).length > 0) {
            // Multi-city trip - display all segments with their selected flights
            segments.forEach((segment, segmentIndex) => {
                const segmentFlight = allSegmentFlights[segmentIndex] || (segmentIndex === 0 ? flight : null);

                if (segmentFlight) {
                    const originCode = segment.origin || segment.originLocationCode || '';
                    const destCode = segment.destination || segment.destinationLocationCode || '';
                    const originInfo = getAirportInfo(originCode);
                    const destInfo = getAirportInfo(destCode);
                    const originCity = originInfo ? originInfo.city : originCode;
                    const destCity = destInfo ? destInfo.city : destCode;

                    html += `<div class="amadex-itinerary-section amadex-multi-city-segment">
                        <h4 class="amadex-itinerary-label">Flight ${segmentIndex + 1}: ${originCity} → ${destCity}</h4>`;

                    // Process this segment's flight
                    if (segmentFlight.itineraries && segmentFlight.itineraries.length > 0) {
                        const itinerary = segmentFlight.itineraries[0]; // Use first itinerary

                        itinerary.segments.forEach((segment, segIdx) => {
                            const segmentAllowance = getSegmentBaggageDetails(segmentFlight, segment) || defaultBaggageDetails;
                            html += buildSegmentHtml(segment, segIdx, itinerary.segments.length, cabinClass, {
                                passengerLabel: segmentAllowance?.traveler || defaultPassengerLabel,
                                checkInAllowance: segmentAllowance?.checkIn || defaultCheckInAllowance,
                                cabinAllowance: segmentAllowance?.cabin || defaultCabinAllowance,
                                cabinClassName: cabinClass
                            });

                            // Add layover if not last segment
                            if (segIdx < itinerary.segments.length - 1) {
                                const nextSeg = itinerary.segments[segIdx + 1];
                                const layover = calculateLayoverTime(segment, nextSeg);
                                const layoverAirport = segment.arrival?.iataCode || segment.arrival?.iata_code || segment.arrival?.code || 'Unknown';
                                html += `
                                    <div class="amadex-stopover-banner">
                                        <strong>Change of plane/stopover</strong> | Stopover of ${layover} in ${layoverAirport}
                                    </div>
                                `;
                            }
                        });
                    }

                    html += `</div>`;
                }
            });

            container.html(html);
            return;
        }

        // Determine trip type from search data - check multiple sources with proper normalization
        let tripType = '';

        // First check searchData stored in sessionStorage
        if (searchData.trip_type) {
            tripType = String(searchData.trip_type).toLowerCase().trim();
        } else if (searchData.tripType) {
            tripType = String(searchData.tripType).toLowerCase().trim();
        }

        // Check URL parameters as fallback
        // ✅ Declare urlParams once at function level to avoid duplicate declaration
        const urlParams = new URLSearchParams(window.location.search);

        if (!tripType) {
            const urlTripType = urlParams.get('trip_type') || urlParams.get('tripType');
            if (urlTripType) {
                tripType = String(urlTripType).toLowerCase().trim();
            }
        }

        // Normalize variations
        tripType = tripType.replace(/[_-]/g, '');

        // If still not found, determine from other indicators
        if (!tripType) {
            // Check one_way flag
            const oneWay = searchData.one_way === 'Yes' || searchData.one_way === true || searchData.one_way === 'true' || searchData.one_way === '1';
            // ✅ Reuse urlParams already declared above
            const urlOneWay = urlParams.get('one_way') || urlParams.get('oneWay');

            if (oneWay || urlOneWay === 'Yes' || urlOneWay === 'true' || urlOneWay === '1') {
                tripType = 'oneway';
            } else if (searchData.multi_segments && searchData.multi_segments.length > 1) {
                tripType = 'multicity';
            } else if (searchData.segments && searchData.segments.length > 1) {
                tripType = 'multicity';
            } else if (searchData.segment_count && searchData.segment_count > 1) {
                tripType = 'multicity';
            } else if (!searchData.return || searchData.return === '' || searchData.return === null) {
                tripType = 'oneway';
            } else {
                tripType = 'round';
            }
        }

        // Final normalization - map common variations
        if (tripType.includes('one') || tripType === 'one' || tripType === 'one-way' || tripType === 'one_way') {
            tripType = 'oneway';
        } else if (tripType.includes('round') || tripType.includes('return')) {
            tripType = 'round';
        } else if (tripType.includes('multi') || tripType.includes('city')) {
            tripType = 'multicity';
        }

        if (flight.itineraries) {
            // For one-way or multi-city, only show first itinerary (departure)
            // For round trip, show all itineraries
            const isRoundTrip = tripType === 'round' || tripType === 'roundtrip' || tripType === 'roundrip';
            const shouldShowReturn = isRoundTrip && flight.itineraries.length > 1;

            const itinerariesToShow = shouldShowReturn ? flight.itineraries : [flight.itineraries[0]];

            // Debug log

            if (itinerariesToShow.length === 0) {
                html = '<p class="amadex-empty-message">No flight itinerary available.</p>';
            } else {
                itinerariesToShow.forEach((itinerary, idx) => {
                    const label = idx === 0 ? 'Departure' : 'Return';

                    // Calculate total duration and stops
                    const totalDuration = itinerary.duration || calculateTotalDuration(itinerary.segments);
                    const readableDuration = formatReadableDuration(totalDuration);
                    const stops = itinerary.segments.length - 1;
                    const stopsText = stops === 0 ? 'Non-stop' : stops === 1 ? '1 Stop' : `${stops} Stops`;

                    // Get first and last segment for route
                    const firstSeg = itinerary.segments[0];
                    const lastSeg = itinerary.segments[itinerary.segments.length - 1];
                    const depIata = firstSeg.departure?.iataCode || firstSeg.departure?.iata_code || firstSeg.departure?.code || 'N/A';
                    const arrIata = lastSeg.arrival?.iataCode || lastSeg.arrival?.iata_code || lastSeg.arrival?.code || 'N/A';
                    const depTerminalSummary = firstSeg.departure?.terminal || '';
                    const arrTerminalSummary = lastSeg.arrival?.terminal || '';

                    // Get airport city names if available
                    const depAirportInfo = getAirportInfo(depIata);
                    const arrAirportInfo = getAirportInfo(arrIata);
                    const depCity = depAirportInfo.city || depIata;
                    const arrCity = arrAirportInfo.city || arrIata;

                    // Format date as "5 Nov, 25 Tuesday"
                    const depDateObj = new Date(firstSeg.departure.at);
                    const depDateFormatted = formatBookingDate(depDateObj);
                    const depSummaryDate = formatFullDate(new Date(firstSeg.departure.at));
                    const arrSummaryDate = formatFullDate(new Date(lastSeg.arrival.at));
                    const depSummaryTime = formatTime(new Date(firstSeg.departure.at));
                    const arrSummaryTime = formatTime(new Date(lastSeg.arrival.at));
                    const segmentAllowance = getSegmentBaggageDetails(flight, itinerary.segments[0]) || defaultBaggageDetails;

                    // Create collapsible flight card - first card expanded by default
                    const isFirstCard = idx === 0;
                    const expandedClass = isFirstCard ? ' is-expanded' : '';
                    const contentDisplay = isFirstCard ? 'block' : 'none';
                    const chevronRotation = isFirstCard ? 'rotate(180deg)' : 'rotate(0deg)';

                    html += `<div class="amadex-flight-detail-card${expandedClass}">
                        <div class="amadex-flight-card-header" data-toggle="flight-${idx}">
                            <div class="amadex-flight-card-summary">
                                <div class="amadex-flight-summary-top">
                                    <div>
                                        <p class="amadex-flight-route-title">${depCity} - ${arrCity}</p>
                                        <p class="amadex-flight-meta-line">${readableDuration} · ${stopsText} · ${depDateFormatted}</p>
                                    </div>
                                    <div class="amadex-flight-summary-side">
                                        <span class="amadex-flight-badge">${label}</span>
                                        <span class="amadex-travel-class-pill">${cabinClass}</span>
                                    </div>
                                </div>
                                <div class="amadex-flight-summary-grid">
                                    <div class="amadex-flight-summary-item">
                                        <span class="summary-label">Flight Departure</span>
                                        <strong>${depSummaryTime}</strong>
                                        <span>${depAirportInfo.airport || depIata}</span>
                                        <span class="summary-sub">${depSummaryDate}${depTerminalSummary ? ' · Terminal ' + depTerminalSummary : ''}</span>
                                    </div>
                                    <div class="amadex-flight-summary-item amadex-flight-summary-item--center">
                                        <span class="summary-label">Total Travel</span>
                                        <strong>${readableDuration}</strong>
                                        <span>${stopsText}</span>
                                        <span class="summary-sub">${itinerary.segments.length > 1 ? 'Includes layover time' : 'Non-stop flight'}</span>
                                    </div>
                                    <div class="amadex-flight-summary-item">
                                        <span class="summary-label">Flight Arrival</span>
                                        <strong>${arrSummaryTime}</strong>
                                        <span>${arrAirportInfo.airport || arrIata}</span>
                                        <span class="summary-sub">${arrSummaryDate}${arrTerminalSummary ? ' · Terminal ' + arrTerminalSummary : ''}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="amadex-flight-card-toggle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="9.148" viewBox="0 0 16 9.148" class="amadex-chevron-icon" style="transform: ${chevronRotation};">
  <path id="_16" data-name="16" d="M13.032,17.143a1.15,1.15,0,0,1-.815-.331L5.333,9.955A1.15,1.15,0,0,1,6.963,8.332l6.07,6.057L19.1,8.343A1.145,1.145,0,0,1,20.72,9.955l-6.884,6.857a1.15,1.15,0,0,1-.8.331Z" transform="translate(-4.996 -7.996)"/>
</svg>
                            </div>
                        </div>
                        <div class="amadex-flight-card-content" id="flight-${idx}-content" style="display: ${contentDisplay};">`;

                    itinerary.segments.forEach((segment, segIdx) => {
                        const perSegmentAllowance = getSegmentBaggageDetails(flight, segment) || segmentAllowance || defaultBaggageDetails;
                        html += buildSegmentHtml(segment, segIdx, itinerary.segments.length, cabinClass, {
                            passengerLabel: perSegmentAllowance?.traveler || defaultPassengerLabel,
                            checkInAllowance: perSegmentAllowance?.checkIn || defaultCheckInAllowance,
                            cabinAllowance: perSegmentAllowance?.cabin || defaultCabinAllowance,
                            cabinClassName: cabinClass
                        });

                        if (segIdx < itinerary.segments.length - 1) {
                            const nextSeg = itinerary.segments[segIdx + 1];
                            const layover = calculateLayoverTime(segment, nextSeg);
                            const layoverAirport = segment.arrival?.iataCode || segment.arrival?.iata_code || segment.arrival?.code || 'Unknown';
                            html += `
                            <div class="amadex-stopover-banner">
                                <strong>Change planes at ${layoverAirport}</strong>
                                <span>Connecting time: ${layover}</span>
                            </div>
                        `;
                        }
                    });

                    html += `</div></div>`;
                });
            }
        } else {
            html = '<p class="amadex-empty-message">No flight itinerary available.</p>';
        }

        container.html(html);

        // Initialize toggle functionality for flight cards
        initFlightCardToggles();

        // Ensure first card is expanded on load
        const $firstCard = $('.amadex-flight-detail-card').first();
        if ($firstCard.length && !$firstCard.hasClass('is-expanded')) {
            const $firstContent = $firstCard.find('.amadex-flight-card-content').first();
            const $firstChevron = $firstCard.find('.amadex-chevron-icon').first();
            if ($firstContent.length) {
                $firstContent.show();
                $firstCard.addClass('is-expanded');
                if ($firstChevron.length) {
                    $firstChevron.css('transform', 'rotate(180deg)');
                }
            }
        }
    }

    /**
     * Initialize toggle functionality for flight detail cards
     */
    function initFlightCardToggles() {
        $('.amadex-flight-card-header').off('click').on('click', function () {
            const toggleId = $(this).data('toggle');
            const $content = $('#' + toggleId + '-content');
            const $chevron = $(this).find('.amadex-chevron-icon');
            const $card = $(this).closest('.amadex-flight-detail-card');

            if ($content.is(':visible')) {
                $content.slideUp(300);
                $chevron.css('transform', 'rotate(0deg)');
                $card.removeClass('is-expanded');
            } else {
                $content.slideDown(300);
                $chevron.css('transform', 'rotate(180deg)');
                $card.addClass('is-expanded');
            }
        });
    }

    /**
     * Calculate total duration from segments
     */
    function calculateTotalDuration(segments) {
        if (!segments || segments.length === 0) return '';

        const firstDep = new Date(segments[0].departure.at);
        const lastArr = new Date(segments[segments.length - 1].arrival.at);
        const diffMs = lastArr - firstDep;
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

        return `${hours}h ${minutes}m`;
    }

    function formatReadableDuration(duration) {
        if (!duration || typeof duration !== 'string') {
            return duration || '';
        }

        if (!duration.startsWith('P')) {
            return duration;
        }

        const hoursMatch = duration.match(/(\d+)H/);
        const minutesMatch = duration.match(/(\d+)M/);
        const hours = hoursMatch ? `${parseInt(hoursMatch[1], 10)}h` : '';
        const minutes = minutesMatch ? `${parseInt(minutesMatch[1], 10)}m` : '';

        return `${hours} ${minutes}`.trim();
    }

    function calculateSegmentDurationText(segment) {
        if (!segment) return '';
        if (segment.duration) {
            return formatReadableDuration(segment.duration);
        }

        const dep = new Date(segment.departure.at);
        const arr = new Date(segment.arrival.at);
        const diffMs = arr - dep;
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

        return `${hours}h ${minutes}m`;
    }

    /**
     * Get day name from date
     */
    function getDayName(date) {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[date.getDay()];
    }

    /**
     * Format date for booking page as "5 Nov, 25 Tuesday"
     */
    function formatBookingDate(date) {
        const day = date.getDate();
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[date.getMonth()];
        const year = String(date.getFullYear()).slice(-2);
        const dayName = getDayName(date);
        return `${day} ${month}, ${year} ${dayName}`;
    }

    /**
     * Populate passenger forms
     */
    function populatePassengerForms(adults, children, infants, showPassportFields = true) {
        const container = $('#amadex-passenger-forms');
        let html = '';
        let passengerNum = 1;

        // Add adult forms
        for (let i = 1; i <= adults; i++) {
            const passportSection = showPassportFields ? `
                <div class="amadex-form-row amadex-form-row--passport">
                    <div class="amadex-form-field">
                        <label>Passport No.<span class="required">*</span></label>
                        <input type="text" id="pax${i}-passport-no" placeholder="Enter Passport No." ${showPassportFields ? 'required' : ''}>
                    </div>
                    <div class="amadex-form-field">
                        <label>Passport issuing Country<span class="required">*</span></label>
                        <select id="pax${i}-passport-country" ${showPassportFields ? 'required' : ''}>
                            <option value="">Passport issuing Country</option>
                            ${generateNationalityOptions('US')}
                        </select>
                    </div>
                    <div class="amadex-form-field">
                        <label>Passport Expiry<span class="required">*</span></label>
                        <div class="amadex-form-inline">
                            <select id="pax${i}-passport-exp-day" ${showPassportFields ? 'required' : ''}>
                                <option value="">Date</option>
                                ${generateOptions(1, 31)}
                            </select>
                            <select id="pax${i}-passport-exp-month" ${showPassportFields ? 'required' : ''}>
                                <option value="">Month</option>
                                ${generateMonthOptions()}
                            </select>
                            <select id="pax${i}-passport-exp-year" ${showPassportFields ? 'required' : ''}>
                                <option value="">Year</option>
                                ${generateYearOptions(new Date().getFullYear(), new Date().getFullYear() + 20)}
                            </select>
                        </div>
                    </div>
                </div>
            ` : '';

            // Add/Remove buttons row - show Add button for last adult, Remove button for all adults (except when only 1 adult)
            // Show Add Passenger dropdown if any passenger type can be added
            const canAddAdult = adults < 9;
            const canAddChild = children < 9;
            const canAddInfant = infants < 9;
            // const showAddButton = (canAddAdult || canAddChild || canAddInfant) && i === adults;

            const showAddButton = false;

            const addRemoveButtons = `
                <div class="amadex-form-row amadex-form-row--full amadex-passenger-actions-row">
                    ${showAddButton ? `
                        <div class="amadex-add-passenger-dropdown-wrapper">
                            <button type="button" class="amadex-add-passenger-btn" data-adult-index="${i}">
                                Add Passenger <span class="amadex-dropdown-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="9.148" viewBox="0 0 16 9.148" class="amadex-chevron-icon">
  <path id="_16" data-name="16" d="M13.032,17.143a1.15,1.15,0,0,1-.815-.331L5.333,9.955A1.15,1.15,0,0,1,6.963,8.332l6.07,6.057L19.1,8.343A1.145,1.145,0,0,1,20.72,9.955l-6.884,6.857a1.15,1.15,0,0,1-.8.331Z" transform="translate(-4.996 -7.996)"></path>
</svg></span>
                            </button>
                            <div class="amadex-add-passenger-dropdown" style="display: none;">
                                ${canAddAdult ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="adult">Add Adult</button>` : ''}
                                ${canAddChild ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="child">Add Child</button>` : ''}
                                ${canAddInfant ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="infant">Add Infant</button>` : ''}
                </div>
                        </div>
                    ` : ''}
                    ${adults > 1 ? `<button type="button" class="amadex-remove-adult-btn" data-adult-index="${i}">Remove Adult -</button>` : ''}
                </div>
            `;

            html += `
                <div class="amadex-passenger-form-card ${i === 1 ? 'is-active' : ''}" data-passenger-index="${i}">
                    <div class="amadex-passenger-form-header">
                        <h5>Adult ${i} 
                        <span>(12+ years)</span></h5>
                    </div>
                    <div class="amadex-passenger-form-content">
                        <div class="amadex-form-row amadex-form-row--triple">
                            <div class="amadex-form-field">
                                <label>First Name<span class="required">*</span></label>
                                <input type="text" id="pax${i}-firstname" placeholder="First Name" required>
                            </div>
                            <div class="amadex-form-field">
                                <label>Middle Name</label>
                                <input type="text" id="pax${i}-middlename" placeholder="Middle Name">
                            </div>
                            <div class="amadex-form-field">
                                <label>Last Name<span class="required">*</span></label>
                                <input type="text" id="pax${i}-lastname" placeholder="Last Name" required>
                            </div>
                            <div class="amadex-form-field amadex-form-field--gender">
                                <label>Gender<span class="required">*</span></label>
                                <div class="amadex-radio-group">
                                    <label class="amadex-radio-label">
                                        <input type="radio" name="pax${i}-gender" value="M" ${i === 1 ? 'checked' : ''} required>
                                        <span>Male</span>
                                    </label>
                                    <label class="amadex-radio-label">
                                        <input type="radio" name="pax${i}-gender" value="F" required>
                                        <span>Female</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="amadex-form-row amadex-form-row--double">
                            <div class="amadex-form-field">
                                <label>Date of Birth<span class="required">*</span></label>
                                <div class="amadex-form-inline">
                                    <select id="pax${i}-dob-day" required>
                                        <option value="">Date</option>
                                        ${generateOptions(1, 31)}
                                    </select>
                                    <select id="pax${i}-dob-month" required>
                                        <option value="">Month</option>
                                        ${generateMonthOptions()}
                                    </select>
                                    <select id="pax${i}-dob-year" required>
                                        <option value="">Year</option>
                                        ${generateYearOptions()}
                                    </select>
                                </div>
                            </div>
                            <div class="amadex-form-field">
                                <label>Nationality<span class="required">*</span></label>
                                <select id="pax${i}-nationality" required>
                                    <!-- <option value="US" selected>United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="IN">India</option>
                                    <option value="CA">Canada</option>
                                    <option value="AU">Australia</option> -->
                                     ${generateNationalityOptions('US')}
                                </select>
                            </div>
                        </div>
${passportSection}
                    </div>
                </div>
                ${addRemoveButtons}
            `;
            passengerNum++;
        }

        // Add children forms
        for (let i = 1; i <= children; i++) {
            const isLastChild = i === children;
            const canAddAdult = adults < 9;
            const canAddChild = children < 9;
            const canAddInfant = infants < 9;
            // const showAddButton = isLastChild && (canAddAdult || canAddChild || canAddInfant);

            const showAddButton = false;

            const addPassengerDropdown = showAddButton ? `
                <div class="amadex-form-row amadex-form-row--full amadex-passenger-actions-row">
                    <div class="amadex-add-passenger-dropdown-wrapper">
                        <button type="button" class="amadex-add-passenger-btn" data-child-index="${i}">
                            Add Passenger <span class="amadex-dropdown-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="9.148" viewBox="0 0 16 9.148" class="amadex-chevron-icon">
  <path id="_16" data-name="16" d="M13.032,17.143a1.15,1.15,0,0,1-.815-.331L5.333,9.955A1.15,1.15,0,0,1,6.963,8.332l6.07,6.057L19.1,8.343A1.145,1.145,0,0,1,20.72,9.955l-6.884,6.857a1.15,1.15,0,0,1-.8.331Z" transform="translate(-4.996 -7.996)"></path>
</svg></span>
                        </button>
                        <div class="amadex-add-passenger-dropdown" style="display: none;">
                            ${canAddAdult ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="adult">Add Adult</button>` : ''}
                            ${canAddChild ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="child">Add Child</button>` : ''}
                            ${canAddInfant ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="infant">Add Infant</button>` : ''}
                        </div>
                    </div>
                </div>
            ` : '';

            html += `
                <div class="amadex-passenger-form" data-passenger-type="child" data-child-index="${i}">
                    <div class="amadex-passenger-form-header">
                    <h5>Child ${i} 
                        <span>(2-11 years)</span></h5>
                    </div>
                    <div class="amadex-passenger-form-content">
                    <div class="amadex-form-row">
                        <div class="amadex-form-field">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" id="pax${adults + i}-firstname" placeholder="First Name*" required>
                        </div>
                        <div class="amadex-form-field">
                            <label>Middle Name</label>
                            <input type="text" id="pax${adults + i}-middlename" placeholder="Middle Name">
                        </div>
                        <div class="amadex-form-field">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" id="pax${adults + i}-lastname" placeholder="Last Name*" required>
                        </div>
                    </div>
                    <div class="amadex-form-row">
                        <div class="amadex-form-field">
                            <label>Gender <span class="required">*</span></label>
                            <select id="pax${adults + i}-gender" required>
                                <option value="">Select</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="amadex-form-field">
                            <label>Date Of Birth <span class="required">*</span></label>
                            <div class="amadex-form-inline">
                                <select id="pax${adults + i}-dob-day" required>
                                    <option value="">Day</option>
                                    ${generateOptions(1, 31)}
                                </select>
                                <select id="pax${adults + i}-dob-month" required>
                                    <option value="">Month</option>
                                    ${generateMonthOptions()}
                                </select>
                                <select id="pax${adults + i}-dob-year" required>
                                    <option value="">Year</option>
                                    ${generateYearOptions()}
                                </select>
                            </div>
                        </div>
                    </div>
                    ${showPassportFields ? `
                        <div class="amadex-form-row amadex-form-row--passport">
                            <div class="amadex-form-field">
                                <label>Passport No.<span class="required">*</span></label>
                                <input type="text" id="pax${adults + i}-passport-no" placeholder="Enter Passport No." required>
                            </div>
                            <div class="amadex-form-field">
                                <label>Passport issuing Country<span class="required">*</span></label>
                                <select id="pax${adults + i}-passport-country" required>
                                    <option value="">Passport issuing Country</option>
                                    ${generateNationalityOptions('US')}
                                </select>
                            </div>
                            <div class="amadex-form-field">
                                <label>Passport Expiry<span class="required">*</span></label>
                                <div class="amadex-form-inline">
                                    <select id="pax${adults + i}-passport-exp-day" required>
                                        <option value="">Date</option>
                                        ${generateOptions(1, 31)}
                                    </select>
                                    <select id="pax${adults + i}-passport-exp-month" required>
                                        <option value="">Month</option>
                                        ${generateMonthOptions()}
                                    </select>
                                    <select id="pax${adults + i}-passport-exp-year" required>
                                        <option value="">Year</option>
                                        ${generateYearOptions(new Date().getFullYear(), new Date().getFullYear() + 20)}
                                    </select>
                                </div>
                            </div>
                        </div>
                    ` : ''}
</div>
                </div>
                ${addPassengerDropdown}
            `;
            passengerNum++;
        }

        // Add infant forms
        for (let i = 1; i <= infants; i++) {
            const isLastInfant = i === infants;
            const canAddAdult = adults < 9;
            const canAddChild = children < 9;
            const canAddInfant = infants < 9;
            // const showAddButton = isLastInfant && (canAddAdult || canAddChild || canAddInfant);

            const showAddButton = false;

            const addPassengerDropdown = showAddButton ? `
                <div class="amadex-form-row amadex-form-row--full amadex-passenger-actions-row">
                    <div class="amadex-add-passenger-dropdown-wrapper">
                        <button type="button" class="amadex-add-passenger-btn" data-infant-index="${i}">
                            Add Passenger <span class="amadex-dropdown-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="9.148" viewBox="0 0 16 9.148" class="amadex-chevron-icon">
  <path id="_16" data-name="16" d="M13.032,17.143a1.15,1.15,0,0,1-.815-.331L5.333,9.955A1.15,1.15,0,0,1,6.963,8.332l6.07,6.057L19.1,8.343A1.145,1.145,0,0,1,20.72,9.955l-6.884,6.857a1.15,1.15,0,0,1-.8.331Z" transform="translate(-4.996 -7.996)"></path>
</svg></span>
                        </button>
                        <div class="amadex-add-passenger-dropdown" style="display: none;">
                            ${canAddAdult ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="adult">Add Adult</button>` : ''}
                            ${canAddChild ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="child">Add Child</button>` : ''}
                            ${canAddInfant ? `<button type="button" class="amadex-add-passenger-option" data-passenger-type="infant">Add Infant</button>` : ''}
                        </div>
                    </div>
                </div>
            ` : '';

            html += `
                <div class="amadex-passenger-form" data-passenger-type="infant" data-infant-index="${i}">
                    <div class="amadex-passenger-form-header">
                    <h5>Infant ${i} 
                        <span>(Under 2 years)</span></h5>
                    </div>
                    <div class="amadex-passenger-form-content">
                    <div class="amadex-form-row">
                        <div class="amadex-form-field">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" id="pax${adults + children + i}-firstname" placeholder="First Name*" required>
                        </div>
                        <div class="amadex-form-field">
                            <label>Middle Name</label>
                            <input type="text" id="pax${adults + children + i}-middlename" placeholder="Middle Name">
                        </div>
                        <div class="amadex-form-field">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" id="pax${adults + children + i}-lastname" placeholder="Last Name*" required>
                        </div>
                    </div>
                    <div class="amadex-form-row">
                        <div class="amadex-form-field">
                            <label>Gender <span class="required">*</span></label>
                            <select id="pax${adults + children + i}-gender" required>
                                <option value="">Select</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="amadex-form-field">
                            <label>Date Of Birth <span class="required">*</span></label>
                            <div class="amadex-form-inline">
                                <select id="pax${adults + children + i}-dob-day" required>
                                    <option value="">Day</option>
                                    ${generateOptions(1, 31)}
                                </select>
                                <select id="pax${adults + children + i}-dob-month" required>
                                    <option value="">Month</option>
                                    ${generateMonthOptions()}
                                </select>
                                <select id="pax${adults + children + i}-dob-year" required>
                                    <option value="">Year</option>
                                    ${generateYearOptions()}
                                </select>
                            </div>
                        </div>
                    </div>
                    ${showPassportFields ? `
                        <div class="amadex-form-row amadex-form-row--passport">
                            <div class="amadex-form-field">
                                <label>Passport No.<span class="required">*</span></label>
                                <input type="text" id="pax${adults + children + i}-passport-no" placeholder="Enter Passport No." required>
                            </div>
                            <div class="amadex-form-field">
                                <label>Passport issuing Country<span class="required">*</span></label>
                                <select id="pax${adults + children + i}-passport-country" required>
                                    <option value="">Passport issuing Country</option>
                                    ${generateNationalityOptions('US')}
                                </select>
                            </div>
                            <div class="amadex-form-field">
                                <label>Passport Expiry<span class="required">*</span></label>
                                <div class="amadex-form-inline">
                                    <select id="pax${adults + children + i}-passport-exp-day" required>
                                        <option value="">Date</option>
                                        ${generateOptions(1, 31)}
                                    </select>
                                    <select id="pax${adults + children + i}-passport-exp-month" required>
                                        <option value="">Month</option>
                                        ${generateMonthOptions()}
                                    </select>
                                    <select id="pax${adults + children + i}-passport-exp-year" required>
                                        <option value="">Year</option>
                                        ${generateYearOptions(new Date().getFullYear(), new Date().getFullYear() + 20)}
                                    </select>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    </div>
                </div>
                ${addPassengerDropdown}
            `;
            passengerNum++;
        }

        //    container.html(html);
        //     initAddPassengerDropdownHandler();
        //     initRemoveAdultButtonHandler();
        //     initRemovePassengerIconHandler();
        // }
        container.html(html);
        initAddPassengerDropdownHandler();
        initRemoveAdultButtonHandler();
        initRemovePassengerIconHandler();
        // Use window reference since restorePassengerFromCookie is defined inside initFormValidation scope
        if (typeof window.amadexRestorePassengerFromCookie === 'function') {
            setTimeout(window.amadexRestorePassengerFromCookie, 50);
        }
    }
    /**
    * Initialize "Add Passenger" dropdown handler (supports Adult, Child, Infant)
    */
    function initAddPassengerDropdownHandler() {
        // Toggle dropdown on button click
        // Build and inject the booking-page travellers popup (once)
        function ensureBookingTravellersPopup() {
            // Popup div already exists in PHP markup — just fill it if empty
            const $popup = $('#amadex-booking-travellers-popup');
            if (!$popup.length) return;
            if ($popup.data('initialized')) return;
            $popup.data('initialized', true);

            $popup.html(`
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <span style="font-size:16px;font-weight:600;color:#1a1a1a;">Travellers</span>
                    <button id="amadex-btp-close" type="button" style="background:none;border:none;cursor:pointer;font-size:22px;color:#888;line-height:1;">&times;</button>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <div><div style="font-weight:500;color:#1a1a1a;">Adults</div><div style="font-size:12px;color:#888;">Above 12 years</div></div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <button type="button" class="amadex-btp-btn" data-type="adults" data-action="minus" style="width:32px;height:32px;border-radius:50%;border:1px solid #ccc;background:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">−</button>
                        <span id="amadex-btp-adults" style="font-size:16px;font-weight:600;min-width:20px;text-align:center;">1</span>
                        <button type="button" class="amadex-btp-btn" data-type="adults" data-action="plus" style="width:32px;height:32px;border-radius:50%;border:1px solid #0e7d3f;background:#0e7d3f;color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">+</button>
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <div><div style="font-weight:500;color:#1a1a1a;">Children</div><div style="font-size:12px;color:#888;">2 – 12 years</div></div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <button type="button" class="amadex-btp-btn" data-type="children" data-action="minus" style="width:32px;height:32px;border-radius:50%;border:1px solid #ccc;background:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">−</button>
                        <span id="amadex-btp-children" style="font-size:16px;font-weight:600;min-width:20px;text-align:center;">0</span>
                        <button type="button" class="amadex-btp-btn" data-type="children" data-action="plus" style="width:32px;height:32px;border-radius:50%;border:1px solid #0e7d3f;background:#0e7d3f;color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">+</button>
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                    <div><div style="font-weight:500;color:#1a1a1a;">Infants</div><div style="font-size:12px;color:#888;">Below 2 years</div></div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <button type="button" class="amadex-btp-btn" data-type="infants" data-action="minus" style="width:32px;height:32px;border-radius:50%;border:1px solid #ccc;background:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">−</button>
                        <span id="amadex-btp-infants" style="font-size:16px;font-weight:600;min-width:20px;text-align:center;">0</span>
                        <button type="button" class="amadex-btp-btn" data-type="infants" data-action="plus" style="width:32px;height:32px;border-radius:50%;border:1px solid #0e7d3f;background:#0e7d3f;color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">+</button>
                    </div>
                </div>

                <button id="amadex-btp-apply" type="button" style="width:100%;padding:13px;background:#0e7d3f;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;">Check Availability</button>
            `);

            // +/- counter logic
            $(document).on('click', '.amadex-btp-btn', function () {
                const type = $(this).data('type');
                const action = $(this).data('action');
                const $count = $(`#amadex-btp-${type}`);
                let val = parseInt($count.text()) || 0;
                const adults = parseInt($('#amadex-btp-adults').text()) || 1;
                const children = parseInt($('#amadex-btp-children').text()) || 0;
                const infants = parseInt($('#amadex-btp-infants').text()) || 0;
                const total = adults + children + infants;

                if (action === 'plus') {
                    if (total >= 9) return;
                    if (type === 'adults' && val >= 9) return;
                    val++;
                } else {
                    if (type === 'adults' && val <= 1) return;
                    if (type !== 'adults' && val <= 0) return;
                    val--;
                }
                $count.text(val);
            });

            // Close on X button or clicking outside
            $(document).on('click', '#amadex-btp-close', function () {
                $('#amadex-booking-travellers-popup').hide();
            });
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.amadex-top-add-passenger-wrapper').length) {
                    $('#amadex-booking-travellers-popup').hide();
                }
            });

            // Apply
            //             $(document).on('click', '#amadex-btp-apply', function() {
            //                 const newAdults   = parseInt($('#amadex-btp-adults').text())   || 1;
            //                 const newChildren = parseInt($('#amadex-btp-children').text()) || 0;
            //                 const newInfants  = parseInt($('#amadex-btp-infants').text())  || 0;

            // $('#amadex-booking-travellers-popup').hide();

            //                 const flightData    = sessionStorage.getItem('amadex_booking_flight');
            //                 const searchDataStr = sessionStorage.getItem('amadex_search_data');
            //                 if (!flightData || !searchDataStr) return;

            //                 let flight, searchData;
            //                 try { flight = JSON.parse(flightData); searchData = JSON.parse(searchDataStr); } catch(e) { return; }

            //                 // Capture existing form data safely
            //                 function captureCard($card) {
            //                     const get = (s) => { const $e = $card.find(`[id$="-${s}"]`); return $e.length ? ($e.val() || '') : ''; };
            //                     return {
            //                         firstname: get('firstname'), middlename: get('middlename'), lastname: get('lastname'),
            //                         gender: ($card.find('input[type="radio"]:checked').val() || get('gender')),
            //                         dobDay: get('dob-day'), dobMonth: get('dob-month'), dobYear: get('dob-year'),
            //                         nationality: get('nationality'),
            //                         passportNo: get('passport-no'), passportCountry: get('passport-country'),
            //                         passportExpDay: get('passport-exp-day'), passportExpMonth: get('passport-exp-month'), passportExpYear: get('passport-exp-year'),
            //                     };
            //                 }
            //                 const saved = { adults: [], children: [], infants: [] };
            //                 $('.amadex-passenger-form-card').each(function()                              { saved.adults.push(captureCard($(this))); });
            //                 $('.amadex-passenger-form[data-passenger-type="child"]').each(function()     { saved.children.push(captureCard($(this))); });
            //                 $('.amadex-passenger-form[data-passenger-type="infant"]').each(function()    { saved.infants.push(captureCard($(this))); });

            //                 searchData.adults   = newAdults;
            //                 searchData.children = newChildren;
            //                 searchData.infants  = newInfants;
            //                 sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));
            //                 window.amadexSearchData = searchData;

            //                 const showPassportFields = shouldShowPassportFields(flight, searchData);
            //                 populatePassengerForms(newAdults, newChildren, newInfants, showPassportFields);

            //                 setTimeout(function() {
            //                     function restoreCard($card, data) {
            //                         if (!data) return;
            //                         const set = (s, v) => { if (!v) return; const $e = $card.find(`[id$="-${s}"]`); if ($e.length) $e.val(v); };
            //                         set('firstname', data.firstname); set('middlename', data.middlename); set('lastname', data.lastname);
            //                         set('dob-day', data.dobDay); set('dob-month', data.dobMonth); set('dob-year', data.dobYear);
            //                         set('nationality', data.nationality);
            //                         set('passport-no', data.passportNo); set('passport-country', data.passportCountry);
            //                         set('passport-exp-day', data.passportExpDay); set('passport-exp-month', data.passportExpMonth); set('passport-exp-year', data.passportExpYear);
            //                         if (data.gender) {
            //                             const $r = $card.find(`input[type="radio"][value="${data.gender}"]`);
            //                             if ($r.length) $r.prop('checked', true);
            //                             else $card.find(`[id$="-gender"]`).val(data.gender);
            //                         }
            //                     }
            //                     $('.amadex-passenger-form-card').each(function(i)                         { restoreCard($(this), saved.adults[i]); });
            //                     $('.amadex-passenger-form[data-passenger-type="child"]').each(function(i) { restoreCard($(this), saved.children[i]); });
            //                     $('.amadex-passenger-form[data-passenger-type="infant"]').each(function(i){ restoreCard($(this), saved.infants[i]); });
            //                     sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flight));
            //                     updatePriceWithAmadeusAPI(flight, searchData, 'adult');
            //                 }, 100);
            //             });

            // Apply — re-search Amadeus with new passenger counts, then re-render
            $(document).on('click', '#amadex-btp-apply', function () {
                const newAdults = parseInt($('#amadex-btp-adults').text()) || 1;
                const newChildren = parseInt($('#amadex-btp-children').text()) || 0;
                const newInfants = parseInt($('#amadex-btp-infants').text()) || 0;

                $('#amadex-booking-travellers-popup').hide();

                const flightData = sessionStorage.getItem('amadex_booking_flight');
                const searchDataStr = sessionStorage.getItem('amadex_search_data');
                if (!flightData || !searchDataStr) return;

                let flight, searchData;
                try { flight = JSON.parse(flightData); searchData = JSON.parse(searchDataStr); } catch (e) { return; }

                // Capture existing form data safely before re-render
                function captureCard($card) {
                    const get = (s) => { const $e = $card.find(`[id$="-${s}"]`); return $e.length ? ($e.val() || '') : ''; };
                    return {
                        firstname: get('firstname'), middlename: get('middlename'), lastname: get('lastname'),
                        gender: ($card.find('input[type="radio"]:checked').val() || get('gender')),
                        dobDay: get('dob-day'), dobMonth: get('dob-month'), dobYear: get('dob-year'),
                        nationality: get('nationality'),
                        passportNo: get('passport-no'), passportCountry: get('passport-country'),
                        passportExpDay: get('passport-exp-day'), passportExpMonth: get('passport-exp-month'), passportExpYear: get('passport-exp-year'),
                    };
                }
                const saved = { adults: [], children: [], infants: [] };
                $('.amadex-passenger-form-card').each(function () { saved.adults.push(captureCard($(this))); });
                $('.amadex-passenger-form[data-passenger-type="child"]').each(function () { saved.children.push(captureCard($(this))); });
                $('.amadex-passenger-form[data-passenger-type="infant"]').each(function () { saved.infants.push(captureCard($(this))); });

                // ── FULL-PAGE LOADER ─────────────────────────────────────────────────
                let $overlay = $('#amadex-recheck-overlay');
                if (!$overlay.length) {
                    $('body').append(`
                        <div id="amadex-recheck-overlay" style="
                            position:fixed;inset:0;z-index:999999;
                            background:rgb(36 36 36 / 98%);
                            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;">
                            <div style="width:52px;height:52px;border:4px solid #e0e0e0;border-top-color:#0e7d3f;border-radius:50%;animation:amadex-spin 0.8s linear infinite;"></div>
                            <div style="font-size:16px;font-weight:600;color:#fff;">Checking availability...</div>
                            <div style="font-size:13px;color:#888;">Finding the best price for ${newAdults} adult${newAdults > 1 ? 's' : ''}${newChildren ? ', ' + newChildren + ' child' + (newChildren > 1 ? 'ren' : '') : ''}${newInfants ? ', ' + newInfants + ' infant' + (newInfants > 1 ? 's' : '') : ''}</div>
                        </div>
                        <style>@keyframes amadex-spin{to{transform:rotate(360deg)}}</style>
                    `);
                    $overlay = $('#amadex-recheck-overlay');
                } else {
                    $overlay.find('div:nth-child(3)').text(`Finding the best price for ${newAdults} adult${newAdults > 1 ? 's' : ''}${newChildren ? ', ' + newChildren + ' child' + (newChildren > 1 ? 'ren' : '') : ''}${newInfants ? ', ' + newInfants + ' infant' + (newInfants > 1 ? 's' : '') : ''}`);
                    $overlay.show();
                }

                // ── RE-SEARCH AMADEUS ────────────────────────────────────────────────
                const ajaxUrl = typeof amadexAjax !== 'undefined' ? amadexAjax.ajaxurl
                    : (typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '');
                const nonce = typeof amadexAjax !== 'undefined' ? amadexAjax.nonce
                    : (typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '');

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'amadex_search_flights',
                        origin: searchData.origin || searchData.from || '',
                        destination: searchData.destination || searchData.to || '',
                        departure_date: searchData.departure_date || searchData.departure || searchData.departureDate || '',
                        return_date: searchData.return_date || searchData.return || searchData.returnDate || '',
                        adults: newAdults,
                        children: newChildren,
                        infants: newInfants,
                        travel_class: searchData.travel_class || searchData.cabin || 'ECONOMY',
                        currency: searchData.currency || 'USD',
                        trip_type: searchData.trip_type || 'roundtrip',
                        load_more: '0',
                        nonce: nonce
                    },
                    timeout: 30000,
                    success: function (response) {
                        $overlay.hide();

                        if (!response.success || !response.data || !response.data.flights || !response.data.flights.length) {
                            alert('No flights found for the selected passengers. Please try different options.');
                            return;
                        }

                        const flights = response.data.flights;

                        // Match the same flight by carrier + departure time + route
                        const origItin = flight.itineraries && flight.itineraries[0];
                        const origSeg = origItin && origItin.segments && origItin.segments[0];
                        const origCarrier = origSeg && (origSeg.carrierCode || (origSeg.operating && origSeg.operating.carrierCode) || '');
                        const origDepTime = origSeg && origSeg.departure && (origSeg.departure.at || '').slice(0, 16);
                        const origArrTime = origSeg && origSeg.arrival && (origSeg.arrival.at || '').slice(0, 16);
                        const origFlight = origSeg && (origSeg.number || origSeg.flightNumber || '');

                        let matchedFlight = null;

                        // Try to find exact same flight in new results
                        for (const f of flights) {
                            const itin = f.itineraries && f.itineraries[0];
                            const seg = itin && itin.segments && itin.segments[0];
                            if (!seg) continue;
                            const carrier = seg.carrierCode || (seg.operating && seg.operating.carrierCode) || '';
                            const depTime = seg.departure && (seg.departure.at || '').slice(0, 16);
                            const arrTime = seg.arrival && (seg.arrival.at || '').slice(0, 16);
                            const flightNo = seg.number || seg.flightNumber || '';

                            if (carrier === origCarrier && depTime === origDepTime && arrTime === origArrTime && flightNo === origFlight) {
                                matchedFlight = f;
                                break;
                            }
                        }

                        // Fallback: use cheapest available flight
                        if (!matchedFlight) {
                            matchedFlight = flights[0];
                        }

                        // Update session with new flight + new passenger counts
                        // Update session with new flight + new passenger counts
                        const updatedSearchData = Object.assign({}, searchData, {
                            adults: newAdults,
                            children: newChildren,
                            infants: newInfants
                        });

                        // Stamp new passenger counts — prevents price falling back to old calculation
                        delete matchedFlight.originalAdults;
                        delete matchedFlight.originalChildren;
                        delete matchedFlight.originalInfants;
                        matchedFlight.originalAdults = newAdults;
                        matchedFlight.originalChildren = newChildren;
                        matchedFlight.originalInfants = newInfants;

                        sessionStorage.setItem('amadex_search_data', JSON.stringify(updatedSearchData));
                        sessionStorage.setItem('amadex_search_results', JSON.stringify(response.data));
                        sessionStorage.setItem('amadex_booking_flight', JSON.stringify(matchedFlight));
                        window.amadexSearchData = updatedSearchData;

                        // GA4 add_to_cart: fires after availability re-check succeeds
                        // with the new traveler count and updated pricing
                        pushAmadexAddToCartEvent(matchedFlight, updatedSearchData, newAdults, newChildren, newInfants);

                        // Immediately sync the new flight into AmadexSeatSelection so
                        // its updatePrice() uses the new offer — not the old stale one
                        if (typeof window.AmadexSeatSelection !== 'undefined') {
                            window.AmadexSeatSelection.flightOffer = matchedFlight;
                            if (matchedFlight.rawOffer) {
                                window.AmadexSeatSelection.flightOffer.rawOffer = matchedFlight.rawOffer;
                            }
                            // Clear any selected seats — they're for the old offer
                            window.AmadexSeatSelection.selectedSeats = {};
                            window.AmadexSeatSelection.totalSeatCharges = 0;
                        }

                        // Block AmadexSeatSelection.updatePrice from overwriting
                        // the correct price while our reprice AJAX is in flight
                        window.amadexRecheckInProgress = true;

                        // Reset timer for new flight offer
                        sessionStorage.removeItem('amadex_booking_timer_start');
                        sessionStorage.removeItem('amadex_booking_timer_remaining');
                        sessionStorage.removeItem('amadex_booking_timer_paused_at');
                        if (matchedFlight.id) {
                            sessionStorage.setItem('amadex_last_booking_flight_id', String(matchedFlight.id));
                        }

                        // Re-render passenger forms
                        const showPassportFields = shouldShowPassportFields(matchedFlight, updatedSearchData);
                        populatePassengerForms(newAdults, newChildren, newInfants, showPassportFields);

                        // Restore saved form data into new forms
                        setTimeout(function () {
                            function restoreCard($card, data) {
                                if (!data) return;
                                const set = (s, v) => { if (!v) return; const $e = $card.find(`[id$="-${s}"]`); if ($e.length) $e.val(v); };
                                set('firstname', data.firstname); set('middlename', data.middlename); set('lastname', data.lastname);
                                set('dob-day', data.dobDay); set('dob-month', data.dobMonth); set('dob-year', data.dobYear);
                                set('nationality', data.nationality);
                                set('passport-no', data.passportNo); set('passport-country', data.passportCountry);
                                set('passport-exp-day', data.passportExpDay); set('passport-exp-month', data.passportExpMonth); set('passport-exp-year', data.passportExpYear);
                                if (data.gender) {
                                    const $r = $card.find(`input[type="radio"][value="${data.gender}"]`);
                                    if ($r.length) $r.prop('checked', true);
                                    else $card.find(`[id$="-gender"]`).val(data.gender);
                                }
                            }
                            $('.amadex-passenger-form-card').each(function (i) { restoreCard($(this), saved.adults[i]); });
                            $('.amadex-passenger-form[data-passenger-type="child"]').each(function (i) { restoreCard($(this), saved.children[i]); });
                            $('.amadex-passenger-form[data-passenger-type="infant"]').each(function (i) { restoreCard($(this), saved.infants[i]); });
                            // Reprice with the matched flight + new passengers
                            // Clear the block flag just before calling so updatePrice
                            // doesn't interfere after our reprice completes
                            window.amadexRecheckInProgress = false;
                            updatePriceWithAmadeusAPI(matchedFlight, updatedSearchData, 'adult');
                        }, 100);
                    },
                    error: function () {
                        $overlay.hide();
                        alert('Could not connect to search. Please check your connection and try again.');
                    }
                });
            });

        }

        $(document).off('click.addPassengerBtn').on('click.addPassengerBtn', '.amadex-add-passenger-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            ensureBookingTravellersPopup();

            // Pre-fill counts from current forms
            const currentAdults = $('.amadex-passenger-form-card').length || 1;
            const currentChildren = $('.amadex-passenger-form[data-passenger-type="child"]').length || 0;
            const currentInfants = $('.amadex-passenger-form[data-passenger-type="infant"]').length || 0;

            $('#amadex-btp-adults').text(currentAdults);
            $('#amadex-btp-children').text(currentChildren);
            $('#amadex-btp-infants').text(currentInfants);

            $('#amadex-booking-travellers-popup').show();
        });

        // Handle passenger type selection
        $(document).off('click.addPassengerOption').on('click.addPassengerOption', '.amadex-add-passenger-option', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const passengerType = $(this).data('passenger-type'); // 'adult', 'child', or 'infant'

            // Get current passenger counts from the page
            const currentAdults = $('.amadex-passenger-form-card').length;
            const currentChildren = $('.amadex-passenger-form').filter(function () {
                return $(this).find('h5').text().includes('Child');
            }).length;
            const currentInfants = $('.amadex-passenger-form').filter(function () {
                return $(this).find('h5').text().includes('Infant');
            }).length;

            // Validate limits
            if (passengerType === 'adult' && currentAdults >= 9) {
                alert('Maximum 9 adults allowed per booking.');
                return;
            }
            if (passengerType === 'child' && currentChildren >= 9) {
                alert('Maximum 9 children allowed per booking.');
                return;
            }
            if (passengerType === 'infant' && currentInfants >= 9) {
                alert('Maximum 9 infants allowed per booking.');
                return;
            }

            // Preserve existing form data before re-rendering
            // const existingFormData = {};
            // for (let i = 1; i <= currentAdults; i++) {
            //     existingFormData[`pax${i}`] = {
            //         firstname: $(`#pax${i}-firstname`).val() || '',
            //         middlename: $(`#pax${i}-middlename`).val() || '',
            //         lastname: $(`#pax${i}-lastname`).val() || '',
            //         gender: $(`input[name="pax${i}-gender"]:checked`).val() || '',
            //         dobDay: $(`#pax${i}-dob-day`).val() || '',
            //         dobMonth: $(`#pax${i}-dob-month`).val() || '',
            //         dobYear: $(`#pax${i}-dob-year`).val() || '',
            //         nationality: $(`#pax${i}-nationality`).val() || 'US',
            //         passportNo: $(`#pax${i}-passport-no`).val() || '',
            //         passportCountry: $(`#pax${i}-passport-country`).val() || '',
            //         passportExpDay: $(`#pax${i}-passport-exp-day`).val() || '',
            //         passportExpMonth: $(`#pax${i}-passport-exp-month`).val() || '',
            //         passportExpYear: $(`#pax${i}-passport-exp-year`).val() || ''
            //     };
            // }

            // Preserve ALL existing form data before re-rendering (adults + children + infants)
            // Save by role (adult1, child1, infant1) NOT by paxIndex,
            // so re-indexing after add doesn't corrupt data
            const existingFormData = {};

            // Save adults
            for (let i = 1; i <= currentAdults; i++) {
                existingFormData[`adult${i}`] = {
                    firstname: $(`#pax${i}-firstname`).val() || '',
                    middlename: $(`#pax${i}-middlename`).val() || '',
                    lastname: $(`#pax${i}-lastname`).val() || '',
                    gender: $(`input[name="pax${i}-gender"]:checked`).val() || '',
                    dobDay: $(`#pax${i}-dob-day`).val() || '',
                    dobMonth: $(`#pax${i}-dob-month`).val() || '',
                    dobYear: $(`#pax${i}-dob-year`).val() || '',
                    nationality: $(`#pax${i}-nationality`).val() || 'US',
                    passportNo: $(`#pax${i}-passport-no`).val() || '',
                    passportCountry: $(`#pax${i}-passport-country`).val() || '',
                    passportExpDay: $(`#pax${i}-passport-exp-day`).val() || '',
                    passportExpMonth: $(`#pax${i}-passport-exp-month`).val() || '',
                    passportExpYear: $(`#pax${i}-passport-exp-year`).val() || ''
                };
            }

            // Save children (paxIndex = currentAdults + childIndex)
            for (let i = 1; i <= currentChildren; i++) {
                const paxIdx = currentAdults + i;
                existingFormData[`child${i}`] = {
                    firstname: $(`#pax${paxIdx}-firstname`).val() || '',
                    middlename: $(`#pax${paxIdx}-middlename`).val() || '',
                    lastname: $(`#pax${paxIdx}-lastname`).val() || '',
                    gender: $(`#pax${paxIdx}-gender`).val() || $(`input[name="pax${paxIdx}-gender"]:checked`).val() || '',
                    dobDay: $(`#pax${paxIdx}-dob-day`).val() || '',
                    dobMonth: $(`#pax${paxIdx}-dob-month`).val() || '',
                    dobYear: $(`#pax${paxIdx}-dob-year`).val() || ''
                };
            }

            // Save infants (paxIndex = currentAdults + currentChildren + infantIndex)
            for (let i = 1; i <= currentInfants; i++) {
                const paxIdx = currentAdults + currentChildren + i;
                existingFormData[`infant${i}`] = {
                    firstname: $(`#pax${paxIdx}-firstname`).val() || '',
                    middlename: $(`#pax${paxIdx}-middlename`).val() || '',
                    lastname: $(`#pax${paxIdx}-lastname`).val() || '',
                    gender: $(`#pax${paxIdx}-gender`).val() || $(`input[name="pax${paxIdx}-gender"]:checked`).val() || '',
                    dobDay: $(`#pax${paxIdx}-dob-day`).val() || '',
                    dobMonth: $(`#pax${paxIdx}-dob-month`).val() || '',
                    dobYear: $(`#pax${paxIdx}-dob-year`).val() || ''
                };
            }

            // Get flight and search data from sessionStorage


            // Get flight and search data from sessionStorage
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            const searchDataStr = sessionStorage.getItem('amadex_search_data');

            if (!flightData || !searchDataStr) {
                return;
            }

            let flight, searchData;
            try {
                flight = JSON.parse(flightData);
                searchData = JSON.parse(searchDataStr);
            } catch (e) {
                return;
            }

            // Store original passenger counts BEFORE updating (for price calculation reference)
            // This is the count BEFORE adding the new passenger
            if (!flight.originalAdults) {
                flight.originalAdults = parseInt(currentAdults || searchData.adults || 1);
                flight.originalChildren = parseInt(currentChildren || searchData.children || 0);
                flight.originalInfants = parseInt(currentInfants || searchData.infants || 0);
            }

            // Increment the appropriate passenger count
            let newAdults = currentAdults;
            let newChildren = currentChildren;
            let newInfants = currentInfants;

            if (passengerType === 'adult') {
                newAdults = currentAdults + 1;
                searchData.adults = newAdults;
            } else if (passengerType === 'child') {
                newChildren = currentChildren + 1;
                searchData.children = newChildren;
            } else if (passengerType === 'infant') {
                newInfants = currentInfants + 1;
                searchData.infants = newInfants;
            }

            // GA4 add_to_cart: fires every time a new traveler is added
            pushAmadexAddToCartEvent(flight, searchData, newAdults, newChildren, newInfants);

            // Update sessionStorage
            sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));

            // Update global searchData variable
            window.amadexSearchData = searchData;

            // Get passport fields requirement
            const showPassportFields = shouldShowPassportFields(flight, searchData);

            // Re-render passenger forms
            populatePassengerForms(newAdults, newChildren, newInfants, showPassportFields);

            // Update seat selection module when passenger is added
            //             if (typeof window.AmadexSeatSelection !== 'undefined') {
            // // Re-extract passengers and regenerate seat maps after DOM is updated
            //                 setTimeout(() => {
            //                     if (typeof window.AmadexSeatSelection.updatePassengersFromForms === 'function') {
            //                         window.AmadexSeatSelection.updatePassengersFromForms();
            //                     } else if (window.AmadexSeatSelection.extractPassengers && window.AmadexSeatSelection.renderSeatMaps) {
            //                         // Fallback: re-extract passengers and re-render seat maps
            //                         window.AmadexSeatSelection.extractPassengers(flight);
            //                         window.AmadexSeatSelection.renderSeatMaps();
            //                     }
            //                     // Recalculate price after passenger addition (seat charges may change)
            //                     if (typeof window.AmadexSeatSelection.updatePrice === 'function') {
            //                         window.AmadexSeatSelection.updatePrice();
            //                     }
            //                 }, 500);
            //             }

            // Inject new passenger into flightOffer.rawOffer.travelerPricings
            // so extractPassengers() can find the new passenger
            if (typeof window.AmadexSeatSelection !== 'undefined' && window.AmadexSeatSelection.flightOffer) {
                const fo = window.AmadexSeatSelection.flightOffer;

                // Determine new travelerId (next integer after current count)
                const existingPricings = (fo.rawOffer && fo.rawOffer.travelerPricings)
                    ? fo.rawOffer.travelerPricings
                    : (fo.traveler_pricings || []);

                const newId = String(existingPricings.length + 1);
                const typeMap = { adult: 'ADULT', child: 'CHILD', infant: 'HELD_INFANT' };
                const newTravelerType = typeMap[passengerType] || 'ADULT';

                // Clone the first pricing of the same type as a template (or first available)
                const template = existingPricings.find(p =>
                    (p.travelerType || p.traveler_type || 'ADULT') === newTravelerType
                ) || existingPricings[0] || {};

                const newPricing = Object.assign({}, template, {
                    travelerId: newId,
                    traveler_id: newId,
                    travelerType: newTravelerType,
                    traveler_type: newTravelerType
                });

                // Inject into rawOffer.travelerPricings
                if (fo.rawOffer) {
                    if (!fo.rawOffer.travelerPricings) fo.rawOffer.travelerPricings = [];
                    fo.rawOffer.travelerPricings.push(newPricing);
                }
                // Also inject into traveler_pricings (flat structure)
                if (!fo.traveler_pricings) fo.traveler_pricings = [];
                fo.traveler_pricings.push(newPricing);

                // Persist updated flightOffer back to sessionStorage
                try {
                    const stored = JSON.parse(sessionStorage.getItem('amadex_booking_flight') || '{}');
                    if (stored.rawOffer) {
                        if (!stored.rawOffer.travelerPricings) stored.rawOffer.travelerPricings = [];
                        stored.rawOffer.travelerPricings.push(newPricing);
                    }
                    if (!stored.traveler_pricings) stored.traveler_pricings = [];
                    stored.traveler_pricings.push(newPricing);
                    sessionStorage.setItem('amadex_booking_flight', JSON.stringify(stored));
                } catch (e) { }
            }

            // Update seat selection module when passenger is added
            if (typeof window.AmadexSeatSelection !== 'undefined') {
                setTimeout(() => {
                    if (typeof window.AmadexSeatSelection.updatePassengersFromForms === 'function') {
                        window.AmadexSeatSelection.updatePassengersFromForms();
                    } else if (window.AmadexSeatSelection.extractPassengers && window.AmadexSeatSelection.renderSeatMaps) {
                        window.AmadexSeatSelection.extractPassengers(window.AmadexSeatSelection.flightOffer);
                        window.AmadexSeatSelection.renderSeatMaps();
                    }
                    if (typeof window.AmadexSeatSelection.updatePrice === 'function') {
                        window.AmadexSeatSelection.updatePrice();
                    }
                }, 500);
            }

            // Restore existing form data after a brief delay to ensure DOM is ready
            // setTimeout(function() {
            //     for (let i = 1; i <= currentAdults; i++) {
            //         const data = existingFormData[`pax${i}`];
            //         if (data) {
            //             $(`#pax${i}-firstname`).val(data.firstname);
            //             if (data.middlename) $(`#pax${i}-middlename`).val(data.middlename);
            //             $(`#pax${i}-lastname`).val(data.lastname);
            //             if (data.gender) {
            //                 $(`input[name="pax${i}-gender"][value="${data.gender}"]`).prop('checked', true);
            //             }
            //             $(`#pax${i}-dob-day`).val(data.dobDay);
            //             $(`#pax${i}-dob-month`).val(data.dobMonth);
            //             $(`#pax${i}-dob-year`).val(data.dobYear);
            //             $(`#pax${i}-nationality`).val(data.nationality);
            //             if (data.passportNo) $(`#pax${i}-passport-no`).val(data.passportNo);
            //             if (data.passportCountry) $(`#pax${i}-passport-country`).val(data.passportCountry);
            //             if (data.passportExpDay) $(`#pax${i}-passport-exp-day`).val(data.passportExpDay);
            //             if (data.passportExpMonth) $(`#pax${i}-passport-exp-month`).val(data.passportExpMonth);
            //             if (data.passportExpYear) $(`#pax${i}-passport-exp-year`).val(data.passportExpYear);
            //         }
            //     }
            // }, 100);

            // Restore existing form data after a brief delay to ensure DOM is ready
            setTimeout(function () {
                // Restore adults (same index as before)
                for (let i = 1; i <= currentAdults; i++) {
                    const data = existingFormData[`adult${i}`];
                    if (data) {
                        $(`#pax${i}-firstname`).val(data.firstname);
                        if (data.middlename) $(`#pax${i}-middlename`).val(data.middlename);
                        $(`#pax${i}-lastname`).val(data.lastname);
                        if (data.gender) $(`input[name="pax${i}-gender"][value="${data.gender}"]`).prop('checked', true);
                        $(`#pax${i}-dob-day`).val(data.dobDay);
                        $(`#pax${i}-dob-month`).val(data.dobMonth);
                        $(`#pax${i}-dob-year`).val(data.dobYear);
                        $(`#pax${i}-nationality`).val(data.nationality);
                        if (data.passportNo) $(`#pax${i}-passport-no`).val(data.passportNo);
                        if (data.passportCountry) $(`#pax${i}-passport-country`).val(data.passportCountry);
                        if (data.passportExpDay) $(`#pax${i}-passport-exp-day`).val(data.passportExpDay);
                        if (data.passportExpMonth) $(`#pax${i}-passport-exp-month`).val(data.passportExpMonth);
                        if (data.passportExpYear) $(`#pax${i}-passport-exp-year`).val(data.passportExpYear);
                    }
                }

                // Restore children using NEW adult count (paxIndex shifted if adult was added)
                for (let i = 1; i <= currentChildren; i++) {
                    const data = existingFormData[`child${i}`];
                    if (data) {
                        const paxIdx = newAdults + i;
                        $(`#pax${paxIdx}-firstname`).val(data.firstname);
                        if (data.middlename) $(`#pax${paxIdx}-middlename`).val(data.middlename);
                        $(`#pax${paxIdx}-lastname`).val(data.lastname);
                        if (data.gender) {
                            $(`input[name="pax${paxIdx}-gender"][value="${data.gender}"]`).prop('checked', true);
                            $(`#pax${paxIdx}-gender`).val(data.gender);
                        }
                        $(`#pax${paxIdx}-dob-day`).val(data.dobDay);
                        $(`#pax${paxIdx}-dob-month`).val(data.dobMonth);
                        $(`#pax${paxIdx}-dob-year`).val(data.dobYear);
                    }
                }

                // Restore infants using NEW adult + NEW children count
                for (let i = 1; i <= currentInfants; i++) {
                    const data = existingFormData[`infant${i}`];
                    if (data) {
                        const paxIdx = newAdults + newChildren + i;
                        $(`#pax${paxIdx}-firstname`).val(data.firstname);
                        if (data.middlename) $(`#pax${paxIdx}-middlename`).val(data.middlename);
                        $(`#pax${paxIdx}-lastname`).val(data.lastname);
                        if (data.gender) {
                            $(`input[name="pax${paxIdx}-gender"][value="${data.gender}"]`).prop('checked', true);
                            $(`#pax${paxIdx}-gender`).val(data.gender);
                        }
                        $(`#pax${paxIdx}-dob-day`).val(data.dobDay);
                        $(`#pax${paxIdx}-dob-month`).val(data.dobMonth);
                        $(`#pax${paxIdx}-dob-year`).val(data.dobYear);
                    }
                }
            }, 100);

            // Recalculate price breakdown with new passenger count via Amadeus API
            // updatePriceWithAmadeusAPI(flight, searchData, passengerType);

            // // Scroll to the newly added passenger form
            // setTimeout(function() {
            //     let $newForm;
            //     if (passengerType === 'adult') {
            //         $newForm = $(`.amadex-passenger-form-card:eq(${newAdults - 1})`);
            //     } else if (passengerType === 'child') {
            //         $newForm = $(`.amadex-passenger-form:has(h5:contains('Child')):eq(${newChildren - 1})`);
            //     } else if (passengerType === 'infant') {
            //         $newForm = $(`.amadex-passenger-form:has(h5:contains('Infant')):eq(${newInfants - 1})`);
            //     }

            // If a new ADULT was added while children/infants exist,
            // move the new adult form to appear AFTER all children/infants
            // (maintains the visual order the user expects)
            if (passengerType === 'adult' && (currentChildren > 0 || currentInfants > 0)) {
                setTimeout(function () {
                    const $container = $('#amadex-passenger-forms');
                    // The new adult form is the last .amadex-passenger-form-card (adults are rendered first)
                    const $newAdultForm = $('.amadex-passenger-form-card').last();
                    // Move it to the very end of the container (after all children and infants)
                    if ($newAdultForm.length) {
                        $container.append($newAdultForm);
                    }
                }, 150);
            }

            // Recalculate price breakdown with new passenger count via Amadeus API
            updatePriceWithAmadeusAPI(flight, searchData, passengerType);

            // Scroll to the newly added passenger form
            setTimeout(function () {
                let $newForm;
                if (passengerType === 'adult') {
                    $newForm = $(`.amadex-passenger-form-card:eq(${newAdults - 1})`);
                } else if (passengerType === 'child') {
                    $newForm = $(`.amadex-passenger-form:has(h5:contains('Child')):eq(${newChildren - 1})`);
                } else if (passengerType === 'infant') {
                    $newForm = $(`.amadex-passenger-form:has(h5:contains('Infant')):eq(${newInfants - 1})`);
                }

                if ($newForm && $newForm.length) {
                    $('html, body').animate({
                        scrollTop: $newForm.offset().top - 100
                    }, 500);

                    // Highlight the new form briefly
                    $newForm.css({
                        'box-shadow': '0 0 0 3px rgba(14, 125, 63, 0.3)',
                        'transition': 'box-shadow 0.3s ease'
                    });

                    setTimeout(function () {
                        $newForm.css({
                            'box-shadow': '',
                            'transition': ''
                        });
                    }, 2000);

                    // Focus on first input of new form
                    setTimeout(function () {
                        $newForm.find('input[type="text"]').first().focus();
                    }, 600);
                }
            }, 150);

            // Close dropdown
            $('.amadex-add-passenger-dropdown').hide();
        });
    }

    /**
     * Initialize "Remove Passenger" icon handler (in header) - supports Adult, Child, and Infant
     */
    function initRemovePassengerIconHandler() {
        $('.amadex-remove-passenger-icon').off('click.removePassenger').on('click.removePassenger', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $icon = $(this);
            const passengerType = $icon.data('passenger-type') || 'adult';
            const adultIndexToRemove = parseInt($icon.data('adult-index')) || 0;
            const childIndexToRemove = parseInt($icon.data('child-index')) || 0;
            const infantIndexToRemove = parseInt($icon.data('infant-index')) || 0;

            // Count passengers from DOM
            const currentAdults = $('.amadex-passenger-form-card').length;
            const currentChildren = $('.amadex-passenger-form[data-passenger-type="child"]').length;
            const currentInfants = $('.amadex-passenger-form[data-passenger-type="infant"]').length;

            // Check if we can remove (must have at least 1 adult)
            if (passengerType === 'adult' && currentAdults <= 1) {
                alert('At least one adult passenger is required.');
                return;
            }

            // Confirm removal
            let confirmMessage = '';
            if (passengerType === 'adult') {
                confirmMessage = `Are you sure you want to remove Adult ${adultIndexToRemove}?`;
            } else if (passengerType === 'child') {
                confirmMessage = `Are you sure you want to remove Child ${childIndexToRemove}?`;
            } else if (passengerType === 'infant') {
                confirmMessage = `Are you sure you want to remove Infant ${infantIndexToRemove}?`;
            }

            if (!confirm(confirmMessage)) {
                return;
            }

            // ── SAFE DATA CAPTURE ──────────────────────────────────────────────────
            // Read directly from DOM fields inside each card using ID suffix matching.
            // Never reconstruct paxN IDs from counts — that's what caused wrong data
            // being saved/restored when a middle passenger was removed.
            // ───────────────────────────────────────────────────────────────────────
            const existingFormData = {};

            function captureCardData($card) {
                const get = (suffix) => {
                    const $el = $card.find(`[id$="-${suffix}"]`);
                    return $el.length ? ($el.val() || '') : '';
                };
                const getRadio = () => {
                    const $checked = $card.find('input[type="radio"]:checked');
                    return $checked.length ? $checked.val() : '';
                };
                return {
                    firstname: get('firstname'),
                    middlename: get('middlename'),
                    lastname: get('lastname'),
                    gender: getRadio() || get('gender'),
                    dobDay: get('dob-day'),
                    dobMonth: get('dob-month'),
                    dobYear: get('dob-year'),
                    nationality: get('nationality'),
                    passportNo: get('passport-no'),
                    passportCountry: get('passport-country'),
                    passportExpDay: get('passport-exp-day'),
                    passportExpMonth: get('passport-exp-month'),
                    passportExpYear: get('passport-exp-year'),
                };
            }

            // Save adults (skip the one being removed)
            let newAdultIndex = 1;
            $('.amadex-passenger-form-card').each(function () {
                const thisIndex = parseInt($(this).data('passenger-index'));
                if (passengerType !== 'adult' || thisIndex !== adultIndexToRemove) {
                    existingFormData[`pax${newAdultIndex}`] = captureCardData($(this));
                    newAdultIndex++;
                }
            });

            // Save children (skip the one being removed)
            let newChildIndex = 1;
            $('.amadex-passenger-form[data-passenger-type="child"]').each(function () {
                const thisIndex = parseInt($(this).data('child-index'));
                if (passengerType !== 'child' || thisIndex !== childIndexToRemove) {
                    existingFormData[`child${newChildIndex}`] = captureCardData($(this));
                    newChildIndex++;
                }
            });

            // Save infants (skip the one being removed)
            let newInfantIndex = 1;
            $('.amadex-passenger-form[data-passenger-type="infant"]').each(function () {
                const thisIndex = parseInt($(this).data('infant-index'));
                if (passengerType !== 'infant' || thisIndex !== infantIndexToRemove) {
                    existingFormData[`infant${newInfantIndex}`] = captureCardData($(this));
                    newInfantIndex++;
                }
            });

            // Get flight and search data from sessionStorage
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            const searchDataStr = sessionStorage.getItem('amadex_search_data');

            if (!flightData || !searchDataStr) {
                return;
            }

            let flight, searchData;
            try {
                flight = JSON.parse(flightData);
                searchData = JSON.parse(searchDataStr);
            } catch (e) {
                return;
            }

            // Calculate new passenger counts
            let newAdults = currentAdults;
            let newChildren = currentChildren;
            let newInfants = currentInfants;

            if (passengerType === 'adult') {
                newAdults = currentAdults - 1;
                searchData.adults = newAdults;
            } else if (passengerType === 'child') {
                newChildren = currentChildren - 1;
                searchData.children = newChildren;
            } else if (passengerType === 'infant') {
                newInfants = currentInfants - 1;
                searchData.infants = newInfants;
            }

            // Calculate removed traveler ID before re-rendering
            const removedTravelerId = passengerType === 'adult' ? adultIndexToRemove :
                (passengerType === 'child' ? (currentAdults + childIndexToRemove) :
                    (currentAdults + currentChildren + infantIndexToRemove));

            // Update sessionStorage
            sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));

            // Update global searchData variable
            window.amadexSearchData = searchData;

            // Get passport fields requirement
            const showPassportFields = shouldShowPassportFields(flight, searchData);

            // Re-render passenger forms
            populatePassengerForms(newAdults, newChildren, newInfants, showPassportFields);

            //             // Update seat selection module when passenger is removed
            //             if (typeof window.AmadexSeatSelection !== 'undefined') {
            // // Remove seat selections for this traveler immediately
            //                 if (window.AmadexSeatSelection.selectedSeats) {
            //                     Object.keys(window.AmadexSeatSelection.selectedSeats).forEach((segmentId) => {
            //                         if (window.AmadexSeatSelection.selectedSeats[segmentId] && 
            //                             window.AmadexSeatSelection.selectedSeats[segmentId][removedTravelerId]) {
            //                             delete window.AmadexSeatSelection.selectedSeats[segmentId][removedTravelerId];
            // }
            //                     });
            //                 }

            //                 // Note: Full seat selection update (passenger buttons, etc.) will happen after API response
            //                 // This is handled in updatePriceWithAmadeusAPI success callback to ensure flight.travelerPricings is updated
            //             }

            // Update seat selection module when passenger is removed
            if (typeof window.AmadexSeatSelection !== 'undefined') {
                // Remove seat selections for this traveler immediately
                if (window.AmadexSeatSelection.selectedSeats) {
                    Object.keys(window.AmadexSeatSelection.selectedSeats).forEach((segmentId) => {
                        if (window.AmadexSeatSelection.selectedSeats[segmentId] &&
                            window.AmadexSeatSelection.selectedSeats[segmentId][removedTravelerId]) {
                            delete window.AmadexSeatSelection.selectedSeats[segmentId][removedTravelerId];
                        }
                    });
                }

                // Remove travelerPricing entry from flightOffer so extractPassengers()
                // doesn't find the removed passenger anymore
                if (window.AmadexSeatSelection.flightOffer) {
                    const fo = window.AmadexSeatSelection.flightOffer;
                    const removeById = (arr) => arr
                        ? arr.filter(p => String(p.travelerId || p.traveler_id) !== String(removedTravelerId))
                        : arr;

                    if (fo.rawOffer) {
                        fo.rawOffer.travelerPricings = removeById(fo.rawOffer.travelerPricings);
                    }
                    fo.traveler_pricings = removeById(fo.traveler_pricings);
                }

                // Update the seat passenger panel to reflect the removal immediately
                setTimeout(function () {
                    if (typeof window.AmadexSeatSelection.updatePassengersFromForms === 'function') {
                        window.AmadexSeatSelection.updatePassengersFromForms();
                    }
                    if (typeof window.AmadexSeatSelection.updatePrice === 'function') {
                        window.AmadexSeatSelection.updatePrice();
                    }
                }, 200);
            }

            // Restore data into freshly rendered forms
            setTimeout(function () {

                function restoreCardData($card, data) {
                    if (!data) return;
                    const set = (suffix, val) => {
                        if (!val) return;
                        const $el = $card.find(`[id$="-${suffix}"]`);
                        if ($el.length) $el.val(val);
                    };
                    set('firstname', data.firstname);
                    set('middlename', data.middlename);
                    set('lastname', data.lastname);
                    set('dob-day', data.dobDay);
                    set('dob-month', data.dobMonth);
                    set('dob-year', data.dobYear);
                    set('nationality', data.nationality);
                    set('passport-no', data.passportNo);
                    set('passport-country', data.passportCountry);
                    set('passport-exp-day', data.passportExpDay);
                    set('passport-exp-month', data.passportExpMonth);
                    set('passport-exp-year', data.passportExpYear);
                    // Radio for adults, select for children/infants
                    if (data.gender) {
                        const $radio = $card.find(`input[type="radio"][value="${data.gender}"]`);
                        if ($radio.length) {
                            $radio.prop('checked', true);
                        } else {
                            $card.find(`[id$="-gender"]`).val(data.gender);
                        }
                    }
                }

                // Restore adults
                $('.amadex-passenger-form-card').each(function (idx) {
                    restoreCardData($(this), existingFormData[`pax${idx + 1}`]);
                });

                // Restore children
                $('.amadex-passenger-form[data-passenger-type="child"]').each(function (idx) {
                    restoreCardData($(this), existingFormData[`child${idx + 1}`]);
                });

                // Restore infants
                $('.amadex-passenger-form[data-passenger-type="infant"]').each(function (idx) {
                    restoreCardData($(this), existingFormData[`infant${idx + 1}`]);
                });

                sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flight));
                updatePriceWithAmadeusAPI(flight, searchData, passengerType);
            }, 100);
        });
    }

    /**
     * Initialize "Remove Adult" button handler
     */
    function initRemoveAdultButtonHandler() {
        $('.amadex-remove-adult-btn').off('click.removeAdult').on('click.removeAdult', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $button = $(this);
            const adultIndexToRemove = parseInt($button.data('adult-index'));

            // Get current passenger counts from the page
            const currentAdults = $('.amadex-passenger-form-card').length;
            const currentChildren = $('.amadex-passenger-form').filter(function () {
                return $(this).find('h5').text().includes('Child');
            }).length;
            const currentInfants = $('.amadex-passenger-form').filter(function () {
                return $(this).find('h5').text().includes('Infant');
            }).length;

            // Check if we can remove (must have at least 1 adult)
            if (currentAdults <= 1) {
                alert('At least one adult passenger is required.');
                return;
            }

            // Confirm removal
            if (!confirm(`Are you sure you want to remove Adult ${adultIndexToRemove}?`)) {
                return;
            }

            // Preserve existing form data before re-rendering (skip the one being removed)
            const existingFormData = {};
            let newIndex = 1;
            for (let i = 1; i <= currentAdults; i++) {
                if (i !== adultIndexToRemove) {
                    existingFormData[`pax${newIndex}`] = {
                        firstname: $(`#pax${i}-firstname`).val() || '',
                        middlename: $(`#pax${i}-middlename`).val() || '',
                        lastname: $(`#pax${i}-lastname`).val() || '',
                        gender: $(`input[name="pax${i}-gender"]:checked`).val() || '',
                        dobDay: $(`#pax${i}-dob-day`).val() || '',
                        dobMonth: $(`#pax${i}-dob-month`).val() || '',
                        dobYear: $(`#pax${i}-dob-year`).val() || '',
                        nationality: $(`#pax${i}-nationality`).val() || 'US',
                        passportNo: $(`#pax${i}-passport-no`).val() || '',
                        passportCountry: $(`#pax${i}-passport-country`).val() || '',
                        passportExpDay: $(`#pax${i}-passport-exp-day`).val() || '',
                        passportExpMonth: $(`#pax${i}-passport-exp-month`).val() || '',
                        passportExpYear: $(`#pax${i}-passport-exp-year`).val() || ''
                    };
                    newIndex++;
                }
            }

            // Get flight and search data from sessionStorage
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            const searchDataStr = sessionStorage.getItem('amadex_search_data');

            if (!flightData || !searchDataStr) {
                return;
            }

            let flight, searchData;
            try {
                flight = JSON.parse(flightData);
                searchData = JSON.parse(searchDataStr);
            } catch (e) {
                return;
            }

            // CRITICAL: Original passenger counts are set ONLY in initBookingPage()
            // DO NOT overwrite them here - they must remain as the initial counts
            // for accurate price calculations when using fallback pricing

            // Decrement adult count
            const newAdults = currentAdults - 1;
            searchData.adults = newAdults;

            // Update sessionStorage
            sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));

            // Update global searchData variable
            window.amadexSearchData = searchData;

            // Get passport fields requirement
            const showPassportFields = shouldShowPassportFields(flight, searchData);

            // Re-render passenger forms
            populatePassengerForms(newAdults, currentChildren, currentInfants, showPassportFields);

            // Restore existing form data after a brief delay to ensure DOM is ready
            setTimeout(function () {
                for (let i = 1; i <= newAdults; i++) {
                    const data = existingFormData[`pax${i}`];
                    if (data) {
                        $(`#pax${i}-firstname`).val(data.firstname);
                        if (data.middlename) $(`#pax${i}-middlename`).val(data.middlename);
                        $(`#pax${i}-lastname`).val(data.lastname);
                        if (data.gender) {
                            $(`input[name="pax${i}-gender"][value="${data.gender}"]`).prop('checked', true);
                        }
                        $(`#pax${i}-dob-day`).val(data.dobDay);
                        $(`#pax${i}-dob-month`).val(data.dobMonth);
                        $(`#pax${i}-dob-year`).val(data.dobYear);
                        $(`#pax${i}-nationality`).val(data.nationality);
                        if (data.passportNo) $(`#pax${i}-passport-no`).val(data.passportNo);
                        if (data.passportCountry) $(`#pax${i}-passport-country`).val(data.passportCountry);
                        if (data.passportExpDay) $(`#pax${i}-passport-exp-day`).val(data.passportExpDay);
                        if (data.passportExpMonth) $(`#pax${i}-passport-exp-month`).val(data.passportExpMonth);
                        if (data.passportExpYear) $(`#pax${i}-passport-exp-year`).val(data.passportExpYear);
                    }
                }

                // CRITICAL: Ensure flight object is updated in sessionStorage
                // Original passenger counts should NOT be overwritten - they're set in initBookingPage()
                sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flight));
            }, 100);

            // Recalculate price breakdown with new passenger count via Amadeus API
            updatePriceWithAmadeusAPI(flight, searchData, 'adult');

            // Scroll to the form that was after the removed one (or last form if removed was last)
            setTimeout(function () {
                const scrollToIndex = adultIndexToRemove > newAdults ? newAdults - 1 : adultIndexToRemove - 1;
                const targetForm = $(`.amadex-passenger-form-card:eq(${scrollToIndex})`);
                if (targetForm.length) {
                    $('html, body').animate({
                        scrollTop: targetForm.offset().top - 100
                    }, 500);
                }
            }, 150);
        });
    }


    /**
     * Update price by calling Amadeus API with new passenger counts
     */
    function updatePriceWithAmadeusAPI(flight, searchData, passengerType, rawOffer) {
        // Show loading state
        const $priceContainer = $('#amadex-price-breakdown');
        if ($priceContainer.length) {
            $priceContainer.addClass('price-updating');
            $priceContainer.find('.amadex-price-value').each(function () {
                $(this).addClass('updating');
            });
        }

        // Get flight offer ID
        const flightOfferId = flight.id || flight.offerId || '';

        // Get original price and passenger count for fallback calculation
        const originalPrice = resolvePriceValue([
            flight?.price?.total,
            flight?.price?.grandTotal,
            flight?.totalPrice,
            flight?.total_price
        ]) || 0;

        const originalAdults = parseInt(flight?.originalAdults || searchData?.originalAdults || searchData?.adults || 1);
        const originalChildren = parseInt(flight?.originalChildren || searchData?.originalChildren || searchData?.children || 0);
        const originalInfants = parseInt(flight?.originalInfants || searchData?.originalInfants || searchData?.infants || 0);
        const originalTotalPassengers = originalAdults + originalChildren + originalInfants;
        const originalPayingPassengers = originalAdults + originalChildren; // Adults + Children (infants don't pay for seats)

        // Get airline code
        const airlineCode = (flight.validatingAirlineCodes && flight.validatingAirlineCodes[0]) ||
            (flight.validating_airline_codes && flight.validating_airline_codes[0]) ||
            '';

        // Prepare AJAX request
        const ajaxData = {
            action: 'amadex_recalculate_price',
            nonce: typeof amadexAjax !== 'undefined' ? amadexAjax.nonce : '',
            flight_offer_id: flightOfferId,
            adults: searchData.adults || 1,
            children: searchData.children || 0,
            infants: searchData.infants || 0,
            origin: searchData.origin || searchData.from || '',
            destination: searchData.destination || searchData.to || '',
            departure_date: searchData.departure_date || searchData.departureDate || '',
            return_date: searchData.return_date || searchData.returnDate || '',
            currency: searchData.currency || 'USD',
            travel_class: searchData.travel_class || searchData.cabin || '',
            original_price: originalPrice,
            original_passengers: originalTotalPassengers,
            original_adults: originalAdults,
            original_children: originalChildren,
            original_infants: originalInfants,
            original_paying_passengers: originalPayingPassengers, // Adults + Children (for pricing calculation)
            airline_code: airlineCode
        };

        // If rawOffer is provided (for timer refresh), include it for direct pricing
        if (rawOffer && passengerType === 'timer_refresh') {
            ajaxData.raw_offer = JSON.stringify(rawOffer);
        }

        // Set flag to prevent race conditions
        const requestId = Date.now() + Math.random();
        window.amadexPriceRefreshRequestId = requestId;
        window.amadexPriceRefreshAborted = false;

        // Set timeout for AJAX call (3-4 seconds for faster response)
        const ajaxTimeout = setTimeout(function () {
            // Mark request as aborted to prevent success callback from executing
            if (window.amadexPriceRefreshRequestId === requestId) {
                window.amadexPriceRefreshAborted = true;
            }

            // Clear loading state immediately on timeout
            if ($priceContainer.length) {
                $priceContainer.removeClass('price-updating');
                $priceContainer.find('.amadex-price-value').removeClass('updating');
            }

            // Show error message in timer if this was a timer refresh
            if (window.amadexTimerRefreshInProgress) {
                const timerSubtitle = $('#amadex-booking-timer-badge').find('.amadex-timer-subtitle');
                if (timerSubtitle.length) {
                    timerSubtitle.text('Price refresh timed out. Please try again.').css({
                        'color': '#d32f2f',
                        'font-weight': '600'
                    });
                }

                // Mark refresh as failed - DON'T restart timer automatically
                // Let user manually retry or continue with current price
                window.amadexTimerRefreshInProgress = false;
            } else {
                // For regular price updates, just show error
                populatePriceBreakdown(flight, searchData, false);
            }
        }, 4000); // 4 second timeout (optimized from 8s for faster error detection)

        $.ajax({
            url: typeof amadexAjax !== 'undefined' ? amadexAjax.ajaxurl : (typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : ''),
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            timeout: 4000, // 4 second timeout (optimized from 8s for faster error detection)
            success: function (response) {
                // Check if request was aborted due to timeout
                if (window.amadexPriceRefreshAborted || window.amadexPriceRefreshRequestId !== requestId) {
                    return; // Don't process response if request was aborted
                }

                clearTimeout(ajaxTimeout); // Clear timeout on success
                if (response.success && response.data && response.data.price) {
                    // Update flight data with new prices
                    if (!flight.price) {
                        flight.price = {};
                    }

                    // Extract prices from API response
                    const apiBasePrice = parseFloat(response.data.price.base || 0);
                    const apiTotalPrice = parseFloat(response.data.price.total || response.data.price.grandTotal || 0);
                    const apiGrandTotal = parseFloat(response.data.price.grandTotal || response.data.price.total || 0);

                    flight.price.base = apiBasePrice;
                    flight.price.total = apiTotalPrice;
                    flight.price.grandTotal = apiGrandTotal;
                    flight.price.currency = response.data.price.currency || searchData.currency || 'USD';

                    if (response.data.price.travelerPricings) {
                        flight.travelerPricings = response.data.price.travelerPricings;
                    }

                    // Mark that prices were just updated from API (so we use them directly)
                    flight.pricesUpdated = true;

                    // CRITICAL: Original passenger counts should have been set in initBookingPage()
                    // Only set them here as a fallback if they were never set (shouldn't happen normally)
                    // DO NOT overwrite if they already exist - they must remain as initial booking counts
                    if (!flight.originalAdults && !flight.originalChildren && !flight.originalInfants) {
                        flight.originalAdults = parseInt(searchData.adults || 1);
                        flight.originalChildren = parseInt(searchData.children || 0);
                        flight.originalInfants = parseInt(searchData.infants || 0);
                    }

                    // Update sessionStorage
                    sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flight));

                    // Update price breakdown with smooth animation
                    populatePriceBreakdown(flight, searchData, true);

                    // Update seat selection module after flight.travelerPricings is updated
                    if (typeof window.AmadexSeatSelection !== 'undefined' && window.AmadexSeatSelection.flightOffer) {
                        // Update the flight offer in seat selection module with updated travelerPricings
                        window.AmadexSeatSelection.flightOffer = flight;
                        if (flight.rawOffer) {
                            window.AmadexSeatSelection.flightOffer.rawOffer = flight.rawOffer;
                        }

                        // Update passengers from updated flight data
                        setTimeout(() => {
                            if (typeof window.AmadexSeatSelection.updatePassengersFromForms === 'function') {
                                window.AmadexSeatSelection.updatePassengersFromForms();
                            } else if (window.AmadexSeatSelection.extractPassengers && window.AmadexSeatSelection.renderSeatMaps) {
                                // Fallback: re-extract passengers and re-render seat maps
                                window.AmadexSeatSelection.extractPassengers(flight);
                                window.AmadexSeatSelection.renderSeatMaps();
                            }
                            // Recalculate price after passenger change
                            if (typeof window.AmadexSeatSelection.updatePrice === 'function') {
                                window.AmadexSeatSelection.updatePrice();
                            }
                        }, 300);
                    }

                    // If this was a timer refresh and price update was successful, restart timer
                    if (window.amadexTimerRefreshInProgress) {
                        window.amadexTimerRefreshInProgress = false;
                        // Use setTimeout to ensure DOM is ready and timer can restart properly
                        setTimeout(function () {
                            restartTimerAfterRefresh();
                        }, 100);
                    }
                } else {
                    // Fallback to estimated prices
                    flight.pricesUpdated = false; // Clear flag so we use fallback calculation
                    populatePriceBreakdown(flight, searchData, false);

                    // If this was a timer refresh but response structure is different, still check if we got valid data
                    if (window.amadexTimerRefreshInProgress && response.success && response.data) {
                        // Even if price structure is different, if we got a successful response, restart timer
                        window.amadexTimerRefreshInProgress = false;
                        setTimeout(function () {
                            restartTimerAfterRefresh();
                        }, 100);
                    }

                    // Fallback: Update seat selection from DOM forms if API failed
                    // This ensures passenger buttons update even if API doesn't return updated travelerPricings
                    if (typeof window.AmadexSeatSelection !== 'undefined') {
                        setTimeout(() => {
                            if (typeof window.AmadexSeatSelection.updatePassengersFromForms === 'function') {
                                window.AmadexSeatSelection.updatePassengersFromForms();
                            }
                            // Update price calculation
                            if (typeof window.AmadexSeatSelection.updatePrice === 'function') {
                                window.AmadexSeatSelection.updatePrice();
                            }
                        }, 300);
                    }

                    // If this was a timer refresh but price update failed, don't restart timer
                    if (window.amadexTimerRefreshInProgress) {
                        window.amadexTimerRefreshInProgress = false;
                        const timerSubtitle = $('#amadex-booking-timer-badge').find('.amadex-timer-subtitle');
                        if (timerSubtitle.length) {
                            timerSubtitle.text('Price refresh failed. Please try again.').css({
                                'color': '#d32f2f',
                                'font-weight': '600'
                            });
                        }
                    }
                }
            },
            error: function (xhr, status, error) {
                // Check if request was aborted due to timeout
                if (window.amadexPriceRefreshAborted || window.amadexPriceRefreshRequestId !== requestId) {
                    return; // Don't process error if request was aborted
                }

                clearTimeout(ajaxTimeout); // Clear timeout on error
                // Clear loading state immediately on error
                if ($priceContainer.length) {
                    $priceContainer.removeClass('price-updating');
                    $priceContainer.find('.amadex-price-value').removeClass('updating');
                }

                // Show error message in timer if this was a timer refresh
                if (window.amadexTimerRefreshInProgress) {
                    const timerSubtitle = $('#amadex-booking-timer-badge').find('.amadex-timer-subtitle');
                    if (timerSubtitle.length) {
                        timerSubtitle.text('Price refresh failed. Please try again.').css({
                            'color': '#d32f2f',
                            'font-weight': '600'
                        });
                    }

                    // Mark refresh as failed - DON'T restart timer automatically
                    // Let user manually retry or continue with current price
                    window.amadexTimerRefreshInProgress = false;
                } else {
                    // For regular price updates, just use fallback
                    populatePriceBreakdown(flight, searchData, false);
                }
            },
            complete: function () {
                // Check if this was the active request before clearing
                if (window.amadexPriceRefreshRequestId === requestId) {
                    clearTimeout(ajaxTimeout); // Ensure timeout is cleared
                }
                // Remove loading state
                if ($priceContainer.length) {
                    $priceContainer.removeClass('price-updating');
                    $priceContainer.find('.amadex-price-value').removeClass('updating');
                }

                // Note: Timer restart is now handled in success callback only (not here)
                // This prevents timer from restarting on errors/timeouts
            }
        });
    }

    /**
     * Calculate price with markup (matches results page logic)
     * Applies markup based on plugin settings
     * 
     * @param {number} originalPrice - The original price to apply markup to
     * @param {string} airlineCode - Airline code for airline-specific markup
     * @param {object} flightData - Optional flight data object to check for Pricing Rules Engine
     * @returns {number} Price with markup applied (or original if Rules Engine enabled)
     */
    function calculatePriceWithMarkup(originalPrice, airlineCode, flightData) {
        // Check if Pricing Rules Engine is enabled (via flight data)
        // If pricing_snapshot or pricing_charge_total exists, Rules Engine is enabled
        // In this case, price is already P_display (with discount), so return as-is
        if (flightData && flightData.price) {
            if (flightData.price.pricing_snapshot || flightData.price.pricing_charge_total) {
                // Pricing Rules Engine is enabled - price is already P_display, return as-is
                return parseFloat(originalPrice);
            }
        }

        // Check if pricing settings are available and enabled
        if (typeof amadexSettings === 'undefined' ||
            !amadexSettings.pricing ||
            !amadexSettings.pricing.enabled) {
            return parseFloat(originalPrice);
        }

        const pricing = amadexSettings.pricing;
        let price = parseFloat(originalPrice);

        // Check for airline-specific markup
        if (airlineCode && pricing.airlineMarkups && pricing.airlineMarkups[airlineCode.toUpperCase()]) {
            const airlineMarkup = pricing.airlineMarkups[airlineCode.toUpperCase()];
            price = price + (price * (airlineMarkup / 100));
        } else {
            // Apply global markup
            switch (pricing.type) {
                case 'percentage':
                    price = price + (price * (pricing.percentage / 100));
                    break;

                case 'fixed':
                    price = price + pricing.fixed;
                    break;

                case 'both':
                    price = price + (price * (pricing.percentage / 100)) + pricing.fixed;
                    break;
            }
        }

        // Apply rounding
        price = roundPrice(price, pricing.rounding);

        return price;
    }

    /**
     * Round price based on settings
     */
    function roundPrice(price, roundingType) {
        switch (roundingType) {
            case 'nearest_1':
                return Math.round(price);

            case 'nearest_5':
                return Math.round(price / 5) * 5;

            case 'nearest_10':
                return Math.round(price / 10) * 10;

            case 'round_up_5':
                return Math.ceil(price / 5) * 5;

            case 'round_up_10':
                return Math.ceil(price / 10) * 10;

            case 'ending_99':
                return Math.floor(price) + 0.99;

            case 'none':
            default:
                return Math.round(price * 100) / 100;
        }
    }

    /**
     * Sync current passenger counts from DOM to searchData
     * This ensures we always have accurate passenger counts even if they were added/removed
     */
    /**
     * Lock passenger counts to prevent changes during seat selection operations
     * This ensures price calculations use correct passenger counts
     */
    function lockPassengerCounts() {
        const currentAdults = $('.amadex-passenger-form-card').length;
        const currentChildren = $('.amadex-passenger-form[data-passenger-type="child"]').length;
        const currentInfants = $('.amadex-passenger-form[data-passenger-type="infant"]').length;

        window.amadexLockedPassengerCounts = {
            adults: currentAdults,
            children: currentChildren,
            infants: currentInfants,
            locked: true,
            lockedAt: Date.now()
        };

    }

    /**
     * Unlock passenger counts (allow changes)
     */
    function unlockPassengerCounts() {
        if (window.amadexLockedPassengerCounts) {
            window.amadexLockedPassengerCounts.locked = false;
        }
    }

    /**
     * Get passenger counts - uses locked counts if available, otherwise syncs from DOM
     */
    function getPassengerCounts() {
        // Check if passenger counts are locked (during seat operations)
        if (window.amadexLockedPassengerCounts && window.amadexLockedPassengerCounts.locked) {
            return {
                adults: window.amadexLockedPassengerCounts.adults,
                children: window.amadexLockedPassengerCounts.children,
                infants: window.amadexLockedPassengerCounts.infants,
                locked: true
            };
        }

        // Read current counts from DOM passenger forms
        const currentAdults = $('.amadex-passenger-form-card').length;
        const currentChildren = $('.amadex-passenger-form[data-passenger-type="child"]').length;
        const currentInfants = $('.amadex-passenger-form[data-passenger-type="infant"]').length;

        return {
            adults: currentAdults,
            children: currentChildren,
            infants: currentInfants,
            locked: false
        };
    }

    function syncCurrentPassengerCountsToSearchData() {
        // Get passenger counts (uses locked counts if available)
        const counts = getPassengerCounts();

        // Get existing searchData or create new
        let searchData = window.amadexSearchData || JSON.parse(sessionStorage.getItem('amadex_search_data') || '{}');

        // Update passenger counts (use locked counts if available)
        searchData.adults = counts.adults;
        searchData.children = counts.children;
        searchData.infants = counts.infants;

        // Update global variable
        window.amadexSearchData = searchData;

        // Update sessionStorage
        sessionStorage.setItem('amadex_search_data', JSON.stringify(searchData));

        return searchData;
    }

    /**
     * Populate price breakdown from flight data
     */
    // Currency conversion handler
    let currentSelectedCurrency = window.AmadexCurrency?.default || 'USD';
    let exchangeRates = {}; // Cache for exchange rates

    // Initialize currency selector
    $(document).ready(function () {
        const currencySelector = $('#amadex-currency-selector');
        if (currencySelector.length) {
            // Set default currency
            currencySelector.val(currentSelectedCurrency);

            // Store selected currency in sessionStorage
            if (sessionStorage.getItem('amadex_selected_currency')) {
                currentSelectedCurrency = sessionStorage.getItem('amadex_selected_currency');
                currencySelector.val(currentSelectedCurrency);
            }

            // Handle currency change - DISABLED: Currency conversion functionality removed
            // Currency selector is kept for display only, no conversion is performed
            currencySelector.on('change', function () {
                const newCurrency = $(this).val();
                if (newCurrency !== currentSelectedCurrency) {
                    currentSelectedCurrency = newCurrency;
                    sessionStorage.setItem('amadex_selected_currency', newCurrency);

                    // Currency conversion functionality removed - prices remain in original currency
                    // No price conversion is performed when currency selector changes
                }
            });
        }
    });

    /**
     * Get exchange rate (with caching)
     * 
     * When Regional Settings System is disabled, this function immediately returns 1.0
     * for USD to USD conversions, or returns 1.0 for non-USD conversions (no conversion).
     * 
     * @since 1.0.0
     * @param {string} fromCurrency - Source currency code
     * @param {string} toCurrency - Target currency code
     * @returns {Promise<number>} Exchange rate (1.0 if disabled or same currency)
     * 
     * @example
     * // Get exchange rate (only if regional settings enabled)
     * const rate = await getExchangeRate('USD', 'INR');
     * // Returns 1.0 if regional settings disabled, otherwise actual rate
     */
    async function getExchangeRate(fromCurrency, toCurrency) {
        // Check if regional settings system is enabled
        const regionalSettingsEnabled = (typeof AmadexConfig !== 'undefined' &&
            AmadexConfig.currency &&
            AmadexConfig.currency.regionalSettingsEnabled !== false) ||
            (window.AmadexCurrency &&
                window.AmadexCurrency.regionalSettingsEnabled !== false);

        // If regional settings disabled, return 1.0 (no conversion)
        if (!regionalSettingsEnabled) {
            return 1.0;
        }

        // Regional settings enabled - proceed with normal rate fetching
        if (fromCurrency === toCurrency) return 1.0;

        const cacheKey = `${fromCurrency}_${toCurrency}`;
        if (exchangeRates[cacheKey]) {
            return exchangeRates[cacheKey];
        }

        try {
            const response = await $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_get_exchange_rate',
                    nonce: window.AmadexCurrency?.nonce || AmadexConfig.nonce || '',
                    from_currency: fromCurrency,
                    to_currency: toCurrency
                }
            });

            if (response.success && response.data && response.data.rate) {
                const rate = parseFloat(response.data.rate);
                exchangeRates[cacheKey] = rate;
                return rate;
            }
        } catch (error) {
        }

        return 1.0; // Fallback
    }

    /**
     * Convert amount to selected currency
     * 
     * When Regional Settings System is disabled, this function immediately returns
     * the original amount without performing any conversion.
     * 
     * @since 1.0.0
     * @param {number} amount - Amount to convert
     * @param {string} originalCurrency - Original currency code
     * @returns {Promise<number>} Converted amount or original amount if disabled
     * 
     * @example
     * // Convert amount to selected currency (only if regional settings enabled)
     * const converted = await convertToSelectedCurrency(100, 'USD');
     * // Returns 100 if regional settings disabled, otherwise converted amount
     */
    async function convertToSelectedCurrency(amount, originalCurrency) {
        // Check if regional settings system is enabled
        const regionalSettingsEnabled = (typeof AmadexConfig !== 'undefined' &&
            AmadexConfig.currency &&
            AmadexConfig.currency.regionalSettingsEnabled !== false) ||
            (window.AmadexCurrency &&
                window.AmadexCurrency.regionalSettingsEnabled !== false);

        // If regional settings disabled, return original amount (no conversion)
        if (!regionalSettingsEnabled) {
            return amount;
        }

        // Regional settings enabled - proceed with conversion
        if (!currentSelectedCurrency || currentSelectedCurrency === originalCurrency) {
            return amount;
        }

        const rate = await getExchangeRate(originalCurrency, currentSelectedCurrency);
        const converted = amount * rate;
        return converted;
    }

    /**
     * Get selected currency for booking page
     * 
     * When Regional Settings System is disabled, this function immediately returns 'USD'
     * without checking any storage layers, improving performance.
     * 
     * @since 1.0.0
     * @returns {string} Currency code (e.g., 'USD', 'EUR', 'INR')
     * 
     * @example
     * // Get selected currency (respects regional settings toggle)
     * const currency = getSelectedCurrency();
     * // Returns 'USD' if regional settings disabled, otherwise detected/saved currency
     */
    function getSelectedCurrency() {
        // Check if regional settings system is enabled (from AmadexConfig or window.AmadexCurrency)
        const regionalSettingsEnabled = (typeof AmadexConfig !== 'undefined' &&
            AmadexConfig.currency &&
            AmadexConfig.currency.regionalSettingsEnabled !== false) ||
            (window.AmadexCurrency &&
                window.AmadexCurrency.regionalSettingsEnabled !== false);

        // If regional settings disabled, immediately return USD
        if (!regionalSettingsEnabled) {
            return 'USD';
        }

        // Regional settings enabled - proceed with normal detection
        // Priority 1: Check localStorage (regional settings - manual selection persists)
        const savedSettings = localStorage.getItem('amadex_regional_settings');
        if (savedSettings) {
            try {
                const settings = JSON.parse(savedSettings);
                if (settings.currency) {
                    return settings.currency;
                }
            } catch (e) {
            }
        }

        // Priority 2: Check sessionStorage (current session)
        const sessionCurrency = sessionStorage.getItem('amadex_selected_currency');
        if (sessionCurrency) {
            return sessionCurrency;
        }

        // Priority 3: Use currentSelectedCurrency variable (if set)
        if (currentSelectedCurrency) {
            return currentSelectedCurrency;
        }

        // Priority 4: Use window.AmadexCurrency default
        if (window.AmadexCurrency?.default) {
            return window.AmadexCurrency.default;
        }

        // Fallback to USD
        return 'USD';
    }

    /**
     * Ensure user's selected currency is stored in flight data (from regional settings)
     * 
     * This function ALWAYS updates currency from storage to ensure flight data
     * reflects the user's current preference, even if flight data already has
     * a currency value (which might be stale from before currency change).
     * 
     * Priority Order:
     * 1. sessionStorage (current session preference - highest priority)
     * 2. localStorage (persistent user preference)
     * 3. flight.selected_currency (existing value - only if storage is empty)
     * 4. flight.price.currency (from Amadeus API)
     * 5. flight.currency (fallback)
     * 6. 'USD' (final fallback)
     * 
     * @since 1.1.0
     * @param {Object} flight - Flight data object to update
     * @returns {string} The currency code that was set
     * 
     * @example
     * // Always ensures flight data has current currency from storage
     * const currency = ensureFlightCurrency(flight);
     * // Returns 'USD' if user changed to USD, even if flight had 'INR'
     */
    function ensureFlightCurrency(flight) {
        // Check if regional settings system is enabled
        const regionalSettingsEnabled = (typeof AmadexConfig !== 'undefined' &&
            AmadexConfig.currency &&
            AmadexConfig.currency.regionalSettingsEnabled !== false) ||
            (window.AmadexCurrency &&
                window.AmadexCurrency.regionalSettingsEnabled !== false);

        // If regional settings disabled, always use USD
        if (!regionalSettingsEnabled) {
            const currency = 'USD';
            flight.selected_currency = currency;
            if (!flight.price) {
                flight.price = {};
            }
            flight.price.selected_currency = currency;
            return currency;
        }

        // Regional settings enabled - get current currency from storage (highest priority)
        // Priority 1: sessionStorage (current session preference)
        let selectedCurrency = sessionStorage.getItem('amadex_selected_currency');

        // Priority 2: localStorage (persistent user preference)
        if (!selectedCurrency) {
            const savedSettings = localStorage.getItem('amadex_regional_settings');
            if (savedSettings) {
                try {
                    const settings = JSON.parse(savedSettings);
                    if (settings.currency) {
                        selectedCurrency = settings.currency;
                    }
                } catch (e) {
                }
            }
        }

        // Priority 3: Existing flight currency (only if storage is empty - might be stale)
        if (!selectedCurrency) {
            selectedCurrency = flight.selected_currency ||
                (flight.price && flight.price.selected_currency) ||
                (flight.price && flight.price.currency) ||
                flight.currency;
        }

        // Priority 4: Final fallback
        if (!selectedCurrency) {
            selectedCurrency = 'USD';
        }

        // ALWAYS update flight data with currency from storage (even if flight already had currency)
        // This ensures flight data reflects current user preference, not stale values
        const previousCurrency = flight.selected_currency || 'none';
        flight.selected_currency = selectedCurrency;

        if (!flight.price) {
            flight.price = {};
        }
        flight.price.selected_currency = selectedCurrency;

        // Log if currency was changed (indicates stale data was updated)
        if (previousCurrency !== 'none' && previousCurrency !== selectedCurrency) {
            if (typeof amadex_log === 'function') {
                amadex_log('Flight currency updated from ' + previousCurrency + ' to ' + selectedCurrency + ' (stale value replaced)');
            }
        } else {
        }

        return selectedCurrency;
    }

    /**
     * Resolve currency for price breakdown display
     * 
     * This function determines which currency to use for displaying prices.
     * It prioritizes the user's current preference from storage over potentially
     * stale flight data currency, ensuring consistency with user's regional settings.
     * 
     * Priority Order (when regional settings enabled):
     * 1. sessionStorage (current session preference - highest priority)
     * 2. localStorage (persistent user preference)
     * 3. flight.selected_currency (only if it matches storage or storage is empty)
     * 4. flight.price.selected_currency (fallback)
     * 5. flight.price.currency (from Amadeus API)
     * 6. 'USD' (final fallback)
     * 
     * @since 1.1.0
     * @param {Object} flight - Flight data object
     * @param {boolean} regionalSettingsEnabled - Whether regional settings are enabled
     * @returns {string} Currency code to use for display
     * 
     * @example
     * // Resolves currency, prioritizing current user preference
     * const currency = resolveDisplayCurrency(flight, true);
     * // Returns 'USD' if user changed to USD, even if flight has 'INR'
     */
    function resolveDisplayCurrency(flight, regionalSettingsEnabled) {
        // If regional settings disabled, always use USD
        if (!regionalSettingsEnabled) {
            return 'USD';
        }

        // Regional settings enabled - get current user preference from storage (highest priority)
        let currentUserCurrency = null;

        // Priority 1: sessionStorage (current session preference)
        currentUserCurrency = sessionStorage.getItem('amadex_selected_currency');

        // Priority 2: localStorage (persistent user preference)
        if (!currentUserCurrency) {
            const savedSettings = localStorage.getItem('amadex_regional_settings');
            if (savedSettings) {
                try {
                    const settings = JSON.parse(savedSettings);
                    if (settings.currency) {
                        currentUserCurrency = settings.currency;
                    }
                } catch (e) {
                }
            }
        }

        // If we have current user preference from storage, use it (even if flight has different currency)
        if (currentUserCurrency) {
            // Validate: Does flight currency match current preference?
            const flightCurrency = flight.selected_currency ||
                (flight.price && flight.price.selected_currency);

            if (flightCurrency && flightCurrency !== currentUserCurrency) {
                // Flight currency is stale - log and use current preference
                // Update flight data to match current preference (prevent future mismatches)
                flight.selected_currency = currentUserCurrency;
                if (!flight.price) {
                    flight.price = {};
                }
                flight.price.selected_currency = currentUserCurrency;

                // Log for debugging
                if (typeof amadex_log === 'function') {
                    amadex_log('Currency mismatch resolved: Flight had ' + flightCurrency + ', using ' + currentUserCurrency + ' from storage');
                }
            }

            return currentUserCurrency;
        }

        // No current preference in storage - use flight currency (might be from API or initial detection)
        if (flight.selected_currency) {
            return flight.selected_currency;
        }

        if (flight.price && flight.price.selected_currency) {
            return flight.price.selected_currency;
        }

        if (flight.price && flight.price.currency) {
            return flight.price.currency;
        }

        if (flight.currency) {
            return flight.currency;
        }

        // Final fallback
        return 'USD';
    }

    function populatePriceBreakdown(flight, searchData, animate = false) {
        // MULTI-CITY FIX: Combine prices from all segments
        const allSegmentsStr = sessionStorage.getItem('amadex_booking_all_segments');
        if (allSegmentsStr) {
            try {
                const allSegments = JSON.parse(allSegmentsStr);
                const searchDataForCheck = JSON.parse(sessionStorage.getItem('amadex_search_data') || '{}');
                const tripTypeForCheck = (searchDataForCheck.trip_type || '').toLowerCase();
                const isMultiCityTrip = tripTypeForCheck === 'multi-city' || tripTypeForCheck === 'multicity';
                if (Array.isArray(allSegments) && allSegments.length > 1 && isMultiCityTrip) {
                    const combinedTotal = allSegments.reduce((sum, f) => {
                        return sum + parseFloat(f.price?.total || f.price?.grandTotal || 0);
                    }, 0);
                    const combinedBase = allSegments.reduce((sum, f) => {
                        return sum + parseFloat(f.price?.base || 0);
                    }, 0);
                    if (combinedTotal > 0) {
                        flight = Object.assign({}, flight);
                        flight.price = Object.assign({}, flight.price);
                        flight.price.total = combinedTotal;
                        flight.price.grandTotal = combinedTotal;
                        if (combinedBase > 0) flight.price.base = combinedBase;
                    }
                }
            } catch (e) { }
        }

        const container = $('#amadex-price-breakdown');
        if (!container.length) {
            return;
        }

        // Store current flight offer for currency conversion
        window.currentFlightOffer = flight;

        // If searchData is empty/invalid, sync from DOM
        if (!searchData || Object.keys(searchData).length === 0 || !searchData.adults || searchData.adults === undefined) {
            searchData = syncCurrentPassengerCountsToSearchData();
        }

        // Add animation class if requested
        if (animate) {
            container.addClass('price-updating');
        }

        // Get passenger counts from search data
        const adults = parseInt(searchData.adults || 1);
        const children = parseInt(searchData.children || 0);
        const infants = parseInt(searchData.infants || 0);
        const totalPassengersForBreakdown = adults + children + infants;

        // Check if regional settings system is enabled
        const regionalSettingsEnabled = (typeof AmadexConfig !== 'undefined' &&
            AmadexConfig.currency &&
            AmadexConfig.currency.regionalSettingsEnabled !== false) ||
            (window.AmadexCurrency &&
                window.AmadexCurrency.regionalSettingsEnabled !== false);

        // Resolve currency for formatting
        const currency = resolveDisplayCurrency(flight, regionalSettingsEnabled);
        const originalCurrency = (flight.price && flight.price.currency) || flight.currency || 'USD';

        // Get airline code for markup calculation
        const airlineCode = (flight.validatingAirlineCodes && flight.validatingAirlineCodes[0]) ||
            (flight.validating_airline_codes && flight.validating_airline_codes[0]) ||
            '';

        // Get original prices from flight data
        let originalBasePrice = resolvePriceValue([
            flight?.price?.base,
            flight?.price?.grandTotal,
            flight?.price?.total,
            flight?.basePrice,
            flight?.base_price
        ]);

        let originalTotalPrice = resolvePriceValue([
            flight?.price?.total,
            flight?.price?.grandTotal,
            flight?.totalPrice,
            flight?.total_price,
            flight?.price
        ]);

        // Fallback to travelerPricing sums if available
        if ((!originalBasePrice || originalBasePrice <= 0 || isNaN(originalBasePrice)) && Array.isArray(flight?.travelerPricings)) {
            const travelerBase = flight.travelerPricings.reduce((sum, traveler) => {
                const travelerPrice = resolvePriceValue([
                    traveler?.price?.base,
                    traveler?.price?.total
                ]) || 0;
                return sum + travelerPrice;
            }, 0);
            if (travelerBase > 0) {
                originalBasePrice = travelerBase;
            }
        }

        if ((!originalTotalPrice || originalTotalPrice <= 0 || isNaN(originalTotalPrice)) && Array.isArray(flight?.travelerPricings)) {
            const travelerTotal = flight.travelerPricings.reduce((sum, traveler) => {
                const travelerPrice = resolvePriceValue([
                    traveler?.price?.total,
                    traveler?.price?.grandTotal,
                    traveler?.price?.base
                ]) || 0;
                return sum + travelerPrice;
            }, 0);
            if (travelerTotal > 0) {
                originalTotalPrice = travelerTotal;
            }
        }

        // Final fallback defaults
        if (!originalBasePrice || originalBasePrice <= 0 || isNaN(originalBasePrice)) {
            originalBasePrice = 400;
        }

        if (!originalTotalPrice || originalTotalPrice <= 0 || isNaN(originalTotalPrice)) {
            originalTotalPrice = originalBasePrice;
        }

        // Adjust for passenger count if needed
        const originalAdults = parseInt(flight?.originalAdults || searchData?.originalAdults || adults || 1);
        const originalChildren = parseInt(flight?.originalChildren || searchData?.originalChildren || children || 0);
        const originalInfants = parseInt(flight?.originalInfants || searchData?.originalInfants || infants || 0);
        const originalTotalPassengers = originalAdults + originalChildren + originalInfants;
        const currentTotalPassengers = adults + children + infants;

        if (originalTotalPassengers > 0 && originalTotalPassengers !== currentTotalPassengers) {
            const perPassengerBasePrice = originalBasePrice / originalTotalPassengers;
            const perPassengerTotalPrice = originalTotalPrice / originalTotalPassengers;
            originalBasePrice = perPassengerBasePrice * currentTotalPassengers;
            originalTotalPrice = perPassengerTotalPrice * currentTotalPassengers;
        }

        // Apply markup to match results page pricing
        let basePrice = calculatePriceWithMarkup(originalBasePrice, airlineCode, flight);
        let totalPrice = calculatePriceWithMarkup(originalTotalPrice, airlineCode, flight);

        // ============================================
        // CRITICAL: Check for Price Management Data
        // ============================================
        const pricingSnapshot = flight?.price?.pricing_snapshot;
        const pricingChargeTotal = flight?.price?.pricing_charge_total;

        // If we have pricing snapshot, use its values for consistency
        if (pricingSnapshot && typeof pricingSnapshot === 'object') {
            const snapshotTotal = parseFloat(pricingSnapshot.display_total || pricingSnapshot.grand_total || pricingSnapshot.total || 0);
            const snapshotBase = parseFloat(pricingSnapshot.base_fare || pricingSnapshot.base || 0);

            if (snapshotTotal > 0) {
                totalPrice = snapshotTotal;
                if (snapshotBase > 0) {
                    basePrice = snapshotBase;
                }
            }
        } else if (pricingChargeTotal && parseFloat(pricingChargeTotal) > 0) {
            totalPrice = parseFloat(pricingChargeTotal);
            // Keep basePrice as calculated, but ensure it's not greater than total
            if (basePrice > totalPrice) {
                basePrice = totalPrice;
            }
        }

        // Ensure base price is never greater than total price
        if (basePrice > totalPrice) {
            basePrice = totalPrice;
        }

        // Calculate taxes as the difference (only if positive)
        let taxesAndFees = totalPrice - basePrice;
        if (taxesAndFees < 0) {
            taxesAndFees = 0;
        }

        // Currency conversion
        let rate = 1.0;
        let displayBasePrice = basePrice;
        let displayTotalPrice = totalPrice;
        let displayTaxes = taxesAndFees;

        if (regionalSettingsEnabled && currency !== originalCurrency && originalCurrency === 'USD' && currency !== 'USD') {
            const cacheKey = `USD_${currency}`;
            if (exchangeRates[cacheKey]) {
                rate = exchangeRates[cacheKey];
                displayBasePrice = basePrice * rate;
                displayTotalPrice = totalPrice * rate;
                displayTaxes = taxesAndFees * rate;
            }
        }

        // Calculate passenger summary for display
        const passengerSummaryParts = [];
        if (adults > 0) passengerSummaryParts.push(`${adults} Adult${adults > 1 ? 's' : ''}`);
        if (children > 0) passengerSummaryParts.push(`${children} Child${children > 1 ? 'ren' : ''}`);
        if (infants > 0) passengerSummaryParts.push(`${infants} Infant${infants > 1 ? 's' : ''}`);
        const passengerSummary = passengerSummaryParts.join(', ') || 'Passengers';

        // Get discount if any
        const discount = Math.max(resolvePriceValue([
            flight?.price?.discount,
            flight?.price?.resellerDiscount,
            flight?.discount,
            searchData?.discount
        ]) || 0, 0);

        // Get seat charges
        let seatCharges = 0;
        let hasSeatsSelected = false;

        if (window.AmadexSeatSelection) {
            if (window.AmadexSeatSelection.totalSeatCharges !== undefined &&
                window.AmadexSeatSelection.totalSeatCharges !== null) {
                seatCharges = parseFloat(window.AmadexSeatSelection.totalSeatCharges) || 0;
            }

            if (window.AmadexSeatSelection.selectedSeats &&
                typeof window.AmadexSeatSelection.selectedSeats === 'object') {
                const selectedSeatsObj = window.AmadexSeatSelection.selectedSeats;
                const segmentKeys = Object.keys(selectedSeatsObj);
                hasSeatsSelected = segmentKeys.length > 0;
            }
        }

        let displaySeatCharges = seatCharges * rate;

        // Get add-ons total
        let addonsTotal = 0;
        let displayAddonsTotal = 0;
        const savedAddons = sessionStorage.getItem('amadex_booking_addons');
        const addonItems = [];

        if (savedAddons) {
            try {
                const selectedAddons = JSON.parse(savedAddons);
                Object.values(selectedAddons).forEach(addon => {
                    const addonPrice = parseFloat(addon.price || 0);
                    if (addonPrice > 0) {
                        addonsTotal += addonPrice;
                        let addonPriceDisplay = addonPrice * rate;
                        displayAddonsTotal += addonPriceDisplay;
                        addonItems.push({
                            id: addon.id,
                            title: addon.title || 'Add-on',
                            price: addonPriceDisplay,
                            description: addon.description || 'Optional service'
                        });
                    }
                });
            } catch (e) { }
        }

        // Calculate final total
        const finalTotal = displayTotalPrice + displaySeatCharges + displayAddonsTotal - discount;

        // ============================================
        // Build HTML - USING CORRECT BASE PRICE
        // ============================================
        let html = '';

        // Show Price Management note if applicable
        if (pricingSnapshot || pricingChargeTotal) {
            html += `<div class="amadex-price-management-note" style="background: #f0f9ff; border-left: 3px solid #0E7D3F; padding: 8px 12px; margin-bottom: 12px; font-size: 12px; color: #666; border-radius: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; margin-right: 6px; vertical-align: middle;">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="#0E7D3F"/>
            </svg>
            <span>Prices include all applicable taxes and fees</span>
        </div>`;
        }

        // Base Fare row - NOW USING displayBasePrice (not totalPrice)
        html += `<div class="amadex-price-row amadex-price-row--expandable">
        <div class="amadex-price-info">
            <span class="amadex-price-label">Base Fare</span>
            <span class="amadex-price-subtext">${passengerSummary} · From airline</span>
        </div>
        <div class="amadex-price-row-right">
            <span class="amadex-price-value">${formatCurrencyValue(displayBasePrice, currency)}</span>
            <button type="button" class="amadex-fare-breakdown-toggle" aria-label="Show breakdown">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
        </div>
    </div>
    <div class="amadex-fare-breakdown" style="display:none;">`;

        // Per-person breakdown - USING displayBasePrice, NOT totalPrice
        if (totalPassengersForBreakdown > 0 && displayBasePrice > 0) {
            const pricePerPerson = displayBasePrice / totalPassengersForBreakdown;

            if (adults > 0) {
                html += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
                <span>Adults <em>(${formatCurrencyValue(pricePerPerson, currency)} × ${adults})</em></span>
                <span>${formatCurrencyValue(pricePerPerson * adults, currency)}</span>
            </div>`;
                // Individual rows
                for (let i = 1; i <= adults; i++) {
                    html += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
                    <span>Adult ${i}</span>
                    <span>${formatCurrencyValue(pricePerPerson, currency)}</span>
                </div>`;
                }
            }

            if (children > 0) {
                html += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
                <span>Children <em>(${formatCurrencyValue(pricePerPerson, currency)} × ${children})</em></span>
                <span>${formatCurrencyValue(pricePerPerson * children, currency)}</span>
            </div>`;
                for (let i = 1; i <= children; i++) {
                    html += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
                    <span>Child ${i}</span>
                    <span>${formatCurrencyValue(pricePerPerson, currency)}</span>
                </div>`;
                }
            }

            if (infants > 0) {
                html += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
                <span>Infants <em>(${formatCurrencyValue(pricePerPerson, currency)} × ${infants})</em></span>
                <span>${formatCurrencyValue(pricePerPerson * infants, currency)}</span>
            </div>`;
                for (let i = 1; i <= infants; i++) {
                    html += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
                    <span>Infant ${i}</span>
                    <span>${formatCurrencyValue(pricePerPerson, currency)}</span>
                </div>`;
                }
            }
        }

        html += `</div>`;

        // Taxes row (only show if > 0)
        if (displayTaxes > 0) {
            html += `
            <div class="amadex-price-row">
                <div class="amadex-price-info">
                    <span class="amadex-price-label">Taxes</span>
                    <span class="amadex-price-subtext">Taxes & Fees · From airline</span>
                </div>
                <span class="amadex-price-value">${formatCurrencyValue(displayTaxes, currency)}</span>
            </div>
        `;
        }

        // Seat Selection row
        if (hasSeatsSelected || displaySeatCharges > 0) {
            html += `
            <div class="amadex-price-row seat-selection">
                <div class="amadex-price-info">
                    <span class="amadex-price-label">Seat Selection</span>
                    <span class="amadex-price-subtext">Selected Seats</span>
                </div>
                <span class="amadex-price-value">${formatCurrencyValue(displaySeatCharges, currency)}</span>
            </div>
        `;
        }

        // Add-ons rows
        for (const addon of addonItems) {
            const displayTitle = (addon.id === 'premium-services' || addon.title === 'Premium Services')
                ? 'Premium Service'
                : addon.title;
            const displaySubtext = (addon.id === 'premium-services' || addon.title === 'Premium Services')
                ? 'TravelayGent™'
                : 'Optional';

            html += `
            <div class="amadex-price-row premium-service">
                <div class="amadex-price-info">
                    <span class="amadex-price-label">${displayTitle}</span>
                    <span class="amadex-price-subtext">${displaySubtext}</span>
                </div>
                <span class="amadex-price-value">${formatCurrencyValue(addon.price, currency)}</span>
            </div>
        `;
        }

        // Discount row
        if (discount > 0) {
            html += `
            <div class="amadex-price-row discount">
                <div class="amadex-price-info">
                    <span class="amadex-price-label">Discount</span>
                    <span class="amadex-price-subtext">Instant Off</span>
                </div>
                <span class="amadex-price-value">- ${formatCurrencyValue(discount, currency)}</span>
            </div>
        `;
        }

        // Total row
        html += `
        <div class="amadex-price-row total">
            <div class="amadex-price-info">
                <span class="amadex-price-label">Total Amount</span>
                ${(pricingSnapshot || pricingChargeTotal) ? '<span class="amadex-price-subtext">Includes all taxes & fees</span>' : ''}
            </div>
            <span class="amadex-price-value amadex-price-value--highlight">${formatCurrencyValue(finalTotal, currency)}</span>
        </div>
    `;

        // Set HTML content with smooth animation
        if (animate) {
            container.fadeOut(200, function () {
                $(this).html(html);

                if (typeof updatePriceBar === 'function') {
                    updatePriceBar();
                }

                $(this).fadeIn(300, function () {
                    $(this).removeClass('price-updating');
                    $(this).find('.amadex-price-value').removeClass('updating');

                    if (typeof updatePriceBar === 'function') {
                        updatePriceBar();
                    }
                });
            });
        } else {
            container.html(html);
            container.removeClass('price-updating');
            container.find('.amadex-price-value').removeClass('updating');

            if (typeof updatePriceBar === 'function') {
                updatePriceBar();
            }
        }

        // Trigger priceUpdated event
        $(document).trigger('priceUpdated', [finalTotal, currency]);
    }

    //     function populatePriceBreakdown(flight, searchData, animate = false) {
    //         // MULTI-CITY FIX: Combine prices from all segments
    //         const allSegmentsStr = sessionStorage.getItem('amadex_booking_all_segments');
    //         if (allSegmentsStr) {
    //             try {
    //                 const allSegments = JSON.parse(allSegmentsStr);
    //                 const searchDataForCheck = JSON.parse(sessionStorage.getItem('amadex_search_data') || '{}');
    // const tripTypeForCheck = (searchDataForCheck.trip_type || '').toLowerCase();
    // const isMultiCityTrip = tripTypeForCheck === 'multi-city' || tripTypeForCheck === 'multicity';
    // if (Array.isArray(allSegments) && allSegments.length > 1 && isMultiCityTrip) {
    //                     const combinedTotal = allSegments.reduce((sum, f) => {
    //                         return sum + parseFloat(f.price?.total || f.price?.grandTotal || 0);
    //                     }, 0);
    //                     const combinedBase = allSegments.reduce((sum, f) => {
    //                         return sum + parseFloat(f.price?.base || 0);
    //                     }, 0);
    //                     if (combinedTotal > 0) {
    //                         flight = Object.assign({}, flight);
    //                         flight.price = Object.assign({}, flight.price);
    //                         flight.price.total = combinedTotal;
    //                         flight.price.grandTotal = combinedTotal;
    //                         if (combinedBase > 0) flight.price.base = combinedBase;
    //                     }
    //                 }
    //             } catch(e) {}
    //         }
    // const container = $('#amadex-price-breakdown');
    //         if (!container.length) {
    // return;
    //         }

    //         // Store current flight offer for currency conversion
    //         window.currentFlightOffer = flight;

    //         // If searchData is empty/invalid, sync from DOM
    //         if (!searchData || Object.keys(searchData).length === 0 || !searchData.adults || searchData.adults === undefined) {
    // searchData = syncCurrentPassengerCountsToSearchData();
    //         }

    //         // Add animation class if requested
    //         if (animate) {
    //             container.addClass('price-updating');
    //         }

    //         // Get passenger counts from search data
    //         const adults = parseInt(searchData.adults || 1);
    //         const children = parseInt(searchData.children || 0);
    //         const infants = parseInt(searchData.infants || 0);

    // // Check if regional settings system is enabled (from AmadexConfig or window.AmadexCurrency)
    //         const regionalSettingsEnabled = (typeof AmadexConfig !== 'undefined' && 
    //                                         AmadexConfig.currency && 
    //                                         AmadexConfig.currency.regionalSettingsEnabled !== false) ||
    //                                        (window.AmadexCurrency && 
    //                                         window.AmadexCurrency.regionalSettingsEnabled !== false);

    //         // Resolve currency for formatting - prioritize user's current preference from storage
    //         // This ensures currency matches user's regional settings, not stale flight data
    //         // Expert/God mode fix: Storage priority over flight data to prevent stale values
    //         const currency = resolveDisplayCurrency(flight, regionalSettingsEnabled);
    // // Fallback to original API currency (from Amadeus) - for reference only
    //         const originalCurrency = (flight.price && flight.price.currency) || flight.currency || 'USD';

    //         // Get airline code for markup calculation
    //         const airlineCode = (flight.validatingAirlineCodes && flight.validatingAirlineCodes[0]) || 
    //                            (flight.validating_airline_codes && flight.validating_airline_codes[0]) || 
    //                            '';

    //         // Get original prices from flight data (check multiple possible locations)
    //         let originalBasePrice = resolvePriceValue([
    //             flight?.price?.base,
    //             flight?.price?.grandTotal,
    //             flight?.price?.total,
    //             flight?.basePrice,
    //             flight?.base_price
    //         ]);

    //         let originalTotalPrice = resolvePriceValue([
    //             flight?.price?.total,
    //             flight?.price?.grandTotal,
    //             flight?.totalPrice,
    //             flight?.total_price,
    //             flight?.price
    //         ]);

    //         // Fallback to travelerPricing sums if available
    //         if ((!originalBasePrice || originalBasePrice <= 0 || isNaN(originalBasePrice)) && Array.isArray(flight?.travelerPricings)) {
    //             const travelerBase = flight.travelerPricings.reduce((sum, traveler) => {
    //                 const travelerPrice = resolvePriceValue([
    //                     traveler?.price?.base,
    //                     traveler?.price?.total
    //                 ]) || 0;
    //                 return sum + travelerPrice;
    //             }, 0);
    //             if (travelerBase > 0) {
    //                 originalBasePrice = travelerBase;
    //             }
    //         }

    //         if ((!originalTotalPrice || originalTotalPrice <= 0 || isNaN(originalTotalPrice)) && Array.isArray(flight?.travelerPricings)) {
    //             const travelerTotal = flight.travelerPricings.reduce((sum, traveler) => {
    //                 const travelerPrice = resolvePriceValue([
    //                     traveler?.price?.total,
    //                     traveler?.price?.grandTotal,
    //                     traveler?.price?.base
    //                 ]) || 0;
    //                 return sum + travelerPrice;
    //             }, 0);
    //             if (travelerTotal > 0) {
    //                 originalTotalPrice = travelerTotal;
    //             }
    //         }

    //         // Final fallback defaults
    //         if (!originalBasePrice || originalBasePrice <= 0 || isNaN(originalBasePrice)) {
    //             originalBasePrice = 400;
    //         }

    //         if (!originalTotalPrice || originalTotalPrice <= 0 || isNaN(originalTotalPrice)) {
    //             originalTotalPrice = originalBasePrice;
    //         }

    //         // Check if prices were just updated from API (they should already be for current passenger count)
    //         // If flight has updated prices from API, use them directly
    //         const pricesJustUpdated = flight?.pricesUpdated || false;

    //         // Get original passenger count from flight data (if available) to calculate per-passenger price
    //         const originalAdults = parseInt(flight?.originalAdults || searchData?.originalAdults || adults || 1);
    //         const originalChildren = parseInt(flight?.originalChildren || searchData?.originalChildren || children || 0);
    //         const originalInfants = parseInt(flight?.originalInfants || searchData?.originalInfants || infants || 0);
    //         const originalTotalPassengers = originalAdults + originalChildren + originalInfants;
    //         const currentTotalPassengers = adults + children + infants;

    // // If prices were just updated from API, use them directly (they're already for current passenger count)
    //         if (pricesJustUpdated) {
    // // Prices from API are already for the new passenger count, use them as-is
    //             // No need to recalculate
    //         } else if (originalTotalPassengers > 0 && originalTotalPassengers !== currentTotalPassengers) {
    //             // Calculate per-passenger price from original
    // const perPassengerBasePrice = originalBasePrice / originalTotalPassengers;
    //             const perPassengerTotalPrice = originalTotalPrice / originalTotalPassengers;

    // // Multiply by current passenger count
    //             originalBasePrice = perPassengerBasePrice * currentTotalPassengers;
    //             originalTotalPrice = perPassengerTotalPrice * currentTotalPassengers;

    // }

    //         // Apply markup to match results page pricing (in original currency)
    //         // Pass flight data to check if Pricing Rules Engine is enabled
    //         const basePriceOriginal = calculatePriceWithMarkup(originalBasePrice, airlineCode, flight);
    //         const totalPriceOriginal = calculatePriceWithMarkup(originalTotalPrice, airlineCode, flight);

    // // Currency conversion - convert if selected currency differs from original
    //         // Note: Prices from Amadeus API are always in USD
    //         // When Regional Settings System is disabled, skip all currency conversion
    //         let rate = 1.0;
    //         let basePrice = basePriceOriginal;
    //         let totalPrice = totalPriceOriginal;

    //         // Only perform currency conversion if:
    //         // 1. Regional settings are enabled AND
    //         // 2. Selected currency differs from original AND
    //         // 3. Original currency is USD AND
    //         // 4. Selected currency is not USD
    //         if (regionalSettingsEnabled && currency !== originalCurrency && originalCurrency === 'USD' && currency !== 'USD') {
    // // Check if rate is cached first (immediate conversion)
    //             const cacheKey = `USD_${currency}`;
    //             if (exchangeRates[cacheKey]) {
    //                 rate = exchangeRates[cacheKey];
    //                 basePrice = basePriceOriginal * rate;
    //                 totalPrice = totalPriceOriginal * rate;
    // } else {
    //                 // Rate not cached - fetch asynchronously and update prices after
    //                 // Show original prices first, then update once rate is fetched
    //                 getExchangeRate('USD', currency).then(function(exchangeRate) {
    //                     if (exchangeRate !== 1.0) {
    // rate = exchangeRate;
    //                         basePrice = basePriceOriginal * exchangeRate;
    //                         totalPrice = totalPriceOriginal * exchangeRate;

    //                         // Re-render price breakdown with converted prices
    //                         populatePriceBreakdown(flight, searchData, true);
    //                     }
    //                 }).catch(function(error) {
    // // Keep original prices if conversion fails
    //                 });
    //             }
    //         } else if (!regionalSettingsEnabled) {
    //             // Regional settings disabled - no conversion needed, prices already in USD
    // rate = 1.0;
    //             basePrice = basePriceOriginal;
    //             totalPrice = totalPriceOriginal;
    //         }

    //         // Calculate passenger summary for display
    //         const passengerSummaryParts = [];
    //         if (adults > 0) passengerSummaryParts.push(`${adults} Adult${adults > 1 ? 's' : ''}`);
    //         if (children > 0) passengerSummaryParts.push(`${children} Child${children > 1 ? 'ren' : ''}`);
    //         if (infants > 0) passengerSummaryParts.push(`${infants} Infant${infants > 1 ? 's' : ''}`);
    //         const passengerSummary = passengerSummaryParts.join(', ') || 'Passengers';

    //         // Calculate taxes/fees and discounts (in selected currency)
    //         // Calculate taxes/fees and discounts (in selected currency)
    // let taxesAndFees = totalPrice - basePrice;
    // if (taxesAndFees < 0) {
    //     taxesAndFees = totalPrice * 0.10; // Default 10% if not specified
    // }

    // const discountOriginal = Math.max(resolvePriceValue([
    //     flight?.price?.discount,
    //     flight?.price?.resellerDiscount,
    //     flight?.discount,
    //     searchData?.discount
    // ]) || 0, 0);
    // const discount = discountOriginal; // No conversion applied

    // // CRITICAL FIX: Use the CORRECT price for finalTotal
    // // The base fare should match what's shown in the breakdown
    // const pricingSnapshot = flight?.price?.pricing_snapshot;
    // const pricingChargeTotal = flight?.price?.pricing_charge_total;

    // let finalTotal;
    // let displayBasePrice = basePrice;
    // let displayTotalPrice = totalPrice;

    // // If we have pricing snapshot, use that for consistency
    // if (pricingSnapshot && typeof pricingSnapshot === 'object') {
    //     // Use the display_total from snapshot (this is the price after all management)
    //     const snapshotTotal = parseFloat(pricingSnapshot.display_total || pricingSnapshot.grand_total || 0);
    //     if (snapshotTotal > 0) {
    //         displayTotalPrice = snapshotTotal;
    //         // For base fare, use the breakdown from snapshot if available
    //         displayBasePrice = parseFloat(pricingSnapshot.base_fare || pricingSnapshot.base || displayBasePrice);
    //         taxesAndFees = parseFloat(pricingSnapshot.tax || pricingSnapshot.taxes || 0);
    //         finalTotal = displayTotalPrice;
    //     } else {
    //         finalTotal = Math.max(displayTotalPrice - discount, 0);
    //     }
    // } else if (pricingChargeTotal && parseFloat(pricingChargeTotal) > 0) {
    //     // Use pricing_charge_total as the final amount
    //     displayTotalPrice = parseFloat(pricingChargeTotal);
    //     finalTotal = displayTotalPrice;
    // } else {
    //     finalTotal = Math.max(displayTotalPrice - discount, 0);
    // }

    // // Ensure base price is never greater than total price
    // if (displayBasePrice > displayTotalPrice) {
    //     displayBasePrice = displayTotalPrice;
    //     taxesAndFees = 0;
    // }
    //         // Currency conversion functionality removed - no exchange rate fetching

    //         // Build HTML for price breakdown (list price = base + tax from airline, then add-ons + seats)
    //         let html = '';

    //         // Base Fare row – from airline/API
    //         // Build per-person breakdown
    //         const totalPassengersForBreakdown = adults + children + infants;
    //         let perPersonHtml = '';

    // //         if (totalPassengersForBreakdown > 0) {
    // //             if (pricingSnapshot && parseFloat(pricingSnapshot.display_total || 0) > 0) {
    // //     basePrice = parseFloat(pricingSnapshot.display_total);
    // //     totalPrice = basePrice;
    // //     taxesAndFees = 0;
    // // }

    // //             const pricePerPerson = basePrice / totalPassengersForBreakdown;

    // //             // Try to get per-type pricing from travelerPricings if available
    // //             const travelerPricings = flight.travelerPricings || flight.traveler_pricings || [];
    // //             const adultPriceFromAPI  = travelerPricings.find(t => (t.travelerType || t.traveler_type) === 'ADULT')?.price?.total;
    // //             const childPriceFromAPI  = travelerPricings.find(t => (t.travelerType || t.traveler_type) === 'CHILD')?.price?.total;
    // //             const infantPriceFromAPI = travelerPricings.find(t => (t.travelerType || t.traveler_type) === 'HELD_INFANT' || (t.travelerType || t.traveler_type) === 'INFANT')?.price?.total;

    // //             const adultPrice  = adultPriceFromAPI  ? parseFloat(adultPriceFromAPI)  : pricePerPerson;
    // //             const childPrice  = childPriceFromAPI  ? parseFloat(childPriceFromAPI)  : pricePerPerson;
    // //             const infantPrice = infantPriceFromAPI ? parseFloat(infantPriceFromAPI) : pricePerPerson;

    // //             // Group totals row (e.g. "3 Adults · $X each")
    // //             if (adults > 0) {
    // //                 perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
    // //                     <span>Adults <em>(${formatCurrencyValue(adultPrice, currency)} × ${adults})</em></span>
    // //                     <span>${formatCurrencyValue(adultPrice * adults, currency)}</span>
    // //                 </div>`;
    // //                 // Individual rows
    // //                 for (let i = 1; i <= adults; i++) {
    // //                     perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
    // //                         <span>Adult ${i}</span>
    // //                         <span>${formatCurrencyValue(adultPrice, currency)}</span>
    // //                     </div>`;
    // //                 }
    // //             }

    // //             if (children > 0) {
    // //                 perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
    // //                     <span>Children <em>(${formatCurrencyValue(childPrice, currency)} × ${children})</em></span>
    // //                     <span>${formatCurrencyValue(childPrice * children, currency)}</span>
    // //                 </div>`;
    // //                 for (let i = 1; i <= children; i++) {
    // //                     perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
    // //                         <span>Child ${i}</span>
    // //                         <span>${formatCurrencyValue(childPrice, currency)}</span>
    // //                     </div>`;
    // //                 }
    // //             }

    // //             if (infants > 0) {
    // //                 perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
    // //                     <span>Infants <em>(${formatCurrencyValue(infantPrice, currency)} × ${infants})</em></span>
    // //                     <span>${formatCurrencyValue(infantPrice * infants, currency)}</span>
    // //                 </div>`;
    // //                 for (let i = 1; i <= infants; i++) {
    // //                     perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
    // //                         <span>Infant ${i}</span>
    // //                         <span>${formatCurrencyValue(infantPrice, currency)}</span>
    // //                     </div>`;
    // //                 }
    // //             }
    // //         }
    // if (totalPassengersForBreakdown > 0) {
    //     // CRITICAL FIX: Use basePrice for per-person calculation, NOT totalPrice
    //     // Base fare is the actual ticket price before taxes
    //     let pricePerPerson = basePrice / totalPassengersForBreakdown;

    //     // If we have pricing snapshot, use the base fare from there
    //     if (pricingSnapshot && parseFloat(pricingSnapshot.base_fare || 0) > 0) {
    //         const snapshotBaseFare = parseFloat(pricingSnapshot.base_fare);
    //         pricePerPerson = snapshotBaseFare / totalPassengersForBreakdown;
    //     }

    //     // Try to get per-type pricing from travelerPricings if available
    //     const travelerPricings = flight.travelerPricings || flight.traveler_pricings || [];

    //     // Get base prices from travelerPricings (not total prices)
    //     let adultBasePrice = null;
    //     let childBasePrice = null;
    //     let infantBasePrice = null;

    //     if (travelerPricings.length > 0) {
    //         const adultTraveler = travelerPricings.find(t => (t.travelerType || t.traveler_type) === 'ADULT');
    //         const childTraveler = travelerPricings.find(t => (t.travelerType || t.traveler_type) === 'CHILD');
    //         const infantTraveler = travelerPricings.find(t => (t.travelerType || t.traveler_type) === 'HELD_INFANT' || (t.travelerType || t.traveler_type) === 'INFANT');

    //         // Use base price from traveler pricing if available
    //         if (adultTraveler && adultTraveler.price) {
    //             adultBasePrice = parseFloat(adultTraveler.price.base || adultTraveler.price.total || 0);
    //         }
    //         if (childTraveler && childTraveler.price) {
    //             childBasePrice = parseFloat(childTraveler.price.base || childTraveler.price.total || 0);
    //         }
    //         if (infantTraveler && infantTraveler.price) {
    //             infantBasePrice = parseFloat(infantTraveler.price.base || infantTraveler.price.total || 0);
    //         }
    //     }

    //     const adultPrice = (adultBasePrice && adultBasePrice > 0) ? adultBasePrice : pricePerPerson;
    //     const childPrice = (childBasePrice && childBasePrice > 0) ? childBasePrice : pricePerPerson;
    //     const infantPrice = (infantBasePrice && infantBasePrice > 0) ? infantBasePrice : pricePerPerson;

    //     // Group totals row using BASE PRICE (not total price with taxes)
    //     if (adults > 0) {
    //         perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
    //             <span>Adults <em>(${formatCurrencyValue(adultPrice, currency)} × ${adults})</em></span>
    //             <span>${formatCurrencyValue(adultPrice * adults, currency)}</span>
    //         </div>`;
    //         // Individual rows
    //         for (let i = 1; i <= adults; i++) {
    //             perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
    //                 <span>Adult ${i}</span>
    //                 <span>${formatCurrencyValue(adultPrice, currency)}</span>
    //             </div>`;
    //         }
    //     }

    //     if (children > 0) {
    //         perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
    //             <span>Children <em>(${formatCurrencyValue(childPrice, currency)} × ${children})</em></span>
    //             <span>${formatCurrencyValue(childPrice * children, currency)}</span>
    //         </div>`;
    //         for (let i = 1; i <= children; i++) {
    //             perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
    //                 <span>Child ${i}</span>
    //                 <span>${formatCurrencyValue(childPrice, currency)}</span>
    //             </div>`;
    //         }
    //     }

    //     if (infants > 0) {
    //         perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--group">
    //             <span>Infants <em>(${formatCurrencyValue(infantPrice, currency)} × ${infants})</em></span>
    //             <span>${formatCurrencyValue(infantPrice * infants, currency)}</span>
    //         </div>`;
    //         for (let i = 1; i <= infants; i++) {
    //             perPersonHtml += `<div class="amadex-fare-breakdown-row amadex-fare-breakdown-row--individual">
    //                 <span>Infant ${i}</span>
    //                 <span>${formatCurrencyValue(infantPrice, currency)}</span>
    //             </div>`;
    //         }
    //     }
    // }
    //        html += `<div class="amadex-price-row amadex-price-row--expandable">
    //     <div class="amadex-price-info">
    //         <span class="amadex-price-label">Base Fare</span>
    //         <span class="amadex-price-subtext">${passengerSummary} · From airline</span>
    //     </div>
    //     <div class="amadex-price-row-right">
    //         <span class="amadex-price-value">${formatCurrencyValue(basePrice, currency)}</span>
    //         <button type="button" class="amadex-fare-breakdown-toggle" aria-label="Show breakdown">
    //             <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    //                 <polyline points="6 9 12 15 18 9"></polyline>
    //             </svg>
    //         </button>
    //     </div>
    // </div>
    //         <div class="amadex-fare-breakdown" style="display:none;">
    //             ${perPersonHtml || '<div class="amadex-fare-breakdown-row"><span>Price per person unavailable</span><span></span></div>'}
    //         </div>`;

    //         // Taxes row – from airline/API
    //             html += `
    //                 <div class="amadex-price-row">
    //                     <div class="amadex-price-info">
    //                         <span class="amadex-price-label">Taxes</span>
    //                         <span class="amadex-price-subtext">Taxes & Fees · From airline</span>
    //                     </div>
    //                     <span class="amadex-price-value">${formatCurrencyValue(taxesAndFees, currency)}</span>
    //                 </div>
    //             `;

    //         // Seat Selection charges (if seats selected) - currency conversion disabled
    //         // Always check AmadexSeatSelection first (most up-to-date, real-time)
    //         let seatCharges = 0;
    //         let hasSeatsSelected = false;

    //         // Priority 1: Check window.AmadexSeatSelection (real-time, most accurate)
    //         if (window.AmadexSeatSelection) {
    //             // PRIORITY 1A: Use totalSeatCharges FIRST (most reliable, already calculated)
    //             if (window.AmadexSeatSelection.totalSeatCharges !== undefined && 
    //                 window.AmadexSeatSelection.totalSeatCharges !== null) {
    //                 const totalFromModule = parseFloat(window.AmadexSeatSelection.totalSeatCharges) || 0;
    //                 if (totalFromModule > 0) {
    //                     seatCharges = totalFromModule;
    // }
    //             }

    //             // PRIORITY 1B: Check if seats are selected (for hasSeatsSelected flag)
    //             if (window.AmadexSeatSelection.selectedSeats && 
    //                 typeof window.AmadexSeatSelection.selectedSeats === 'object') {
    //                 const selectedSeatsObj = window.AmadexSeatSelection.selectedSeats;
    //                 const segmentKeys = Object.keys(selectedSeatsObj);
    //                 hasSeatsSelected = segmentKeys.length > 0;

    //                 // If seatCharges is still 0, calculate from selectedSeats (fallback)
    //                 if (hasSeatsSelected && seatCharges === 0) {
    //                     let calculatedTotal = 0;
    //                     segmentKeys.forEach((segmentId) => {
    //                         const travelerSeats = selectedSeatsObj[segmentId];
    //                         if (travelerSeats && typeof travelerSeats === 'object') {
    //                             Object.keys(travelerSeats).forEach((travelerId) => {
    //                                 const seat = travelerSeats[travelerId];
    //                                 if (seat && seat.price !== undefined) {
    //                                     calculatedTotal += parseFloat(seat.price) || 0;
    //                                 }
    //                             });
    //                         }
    //                     });
    //                     if (calculatedTotal > 0) {
    //                         seatCharges = calculatedTotal;
    // }
    //                 }
    //             }
    //         }

    //         // Priority 3: Check flight.price.seat_charges (fallback)
    //         if (seatCharges === 0 && flight?.price?.seat_charges) {
    //             seatCharges = parseFloat(flight.price.seat_charges) || 0;
    //         }

    //         // Priority 4: Read directly from #amadex-seat-total-price element (direct DOM read)
    //         // This ensures we get the value even if other methods fail
    //         if (seatCharges === 0) {
    //             const $seatTotalPrice = $('#amadex-seat-total-price');
    //             if ($seatTotalPrice.length) {
    //                 // First try data attribute (most reliable)
    //                 const dataCharges = $seatTotalPrice.attr('data-seat-charges');
    //                 if (dataCharges) {
    //                     const parsedData = parseFloat(dataCharges) || 0;
    //                     if (parsedData > 0) {
    //                         seatCharges = parsedData;
    // }
    //                 } else {
    //                     // Fallback: Extract from text content
    //                     const seatTotalText = $seatTotalPrice.text().trim();
    //                     if (seatTotalText) {
    //                         // Extract number from text like "Total Seat Charges: $78.00 USD"
    //                         const priceMatch = seatTotalText.match(/\$?([\d,]+\.?\d*)/);
    //                         if (priceMatch && priceMatch[1]) {
    //                             const extractedPrice = parseFloat(priceMatch[1].replace(/,/g, '')) || 0;
    //                             if (extractedPrice > 0) {
    //                                 seatCharges = extractedPrice;
    // }
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         // Force recalculation if seatCharges is 0 but seats are selected
    //         if (seatCharges === 0 && hasSeatsSelected && window.AmadexSeatSelection) {
    //             // Recalculate from selectedSeats directly
    //             let recalculated = 0;
    //             const selectedSeatsObj = window.AmadexSeatSelection.selectedSeats;
    //             if (selectedSeatsObj && typeof selectedSeatsObj === 'object') {
    //                 Object.keys(selectedSeatsObj).forEach((segmentId) => {
    //                     const travelerSeats = selectedSeatsObj[segmentId];
    //                     if (travelerSeats && typeof travelerSeats === 'object') {
    //                         Object.keys(travelerSeats).forEach((travelerId) => {
    //                             const seat = travelerSeats[travelerId];
    //                             if (seat && seat.price !== undefined) {
    //                                 recalculated += parseFloat(seat.price) || 0;
    //                             }
    //                         });
    //                     }
    //                 });
    //             }
    //             if (recalculated > 0) {
    //                 seatCharges = recalculated;
    // }
    //         }

    //         // Convert seat charges from USD to selected currency (same rate as base price/taxes)
    //         // Seat charges are always in USD from Amadeus API
    //         let seatChargesConverted = seatCharges;
    //         if (currency !== originalCurrency && originalCurrency === 'USD' && currency !== 'USD' && seatCharges > 0) {
    //             // Use the same exchange rate as base price/taxes conversion
    //             if (rate !== 1.0) {
    //                 seatChargesConverted = seatCharges * rate;
    // }
    //         }

    //         // Debug: Log seat charges for troubleshooting
    // // ALWAYS show seat selection row if seats are selected OR if seatCharges > 0
    //         // This ensures the row appears even if calculation had issues
    //         if (hasSeatsSelected || seatCharges > 0) {
    // html += `
    //                 <div class="amadex-price-row seat-selection">
    //                     <div class="amadex-price-info">
    //                         <span class="amadex-price-label">Seat Selection</span>
    //                         <span class="amadex-price-subtext">Selected Seats</span>
    //                     </div>
    //                     <span class="amadex-price-value">${formatCurrencyValue(seatChargesConverted, currency)}</span>
    //                 </div>
    //             `;
    //         } else {
    // }

    //         // Add-ons rows (if any selected)
    //         // Convert add-on prices from USD to selected currency (same rate as base price/taxes)
    //         // Add-on prices are stored in USD
    //         let addonsTotal = 0;
    //         let addonsTotalConverted = 0;
    //         const savedAddons = sessionStorage.getItem('amadex_booking_addons');
    //         if (savedAddons) {
    //             try {
    //                 const selectedAddons = JSON.parse(savedAddons);
    //                 Object.values(selectedAddons).forEach(addon => {
    //                     const addonPrice = parseFloat(addon.price || 0);
    //                     if (addonPrice > 0) {
    //                         addonsTotal += addonPrice;

    //                         // Convert add-on price using the same exchange rate as base price/taxes
    //                         let addonPriceConverted = addonPrice;
    //                         if (currency !== originalCurrency && originalCurrency === 'USD' && currency !== 'USD') {
    //                             if (rate !== 1.0) {
    //                                 addonPriceConverted = addonPrice * rate;
    // }
    //                         }
    //                         addonsTotalConverted += addonPriceConverted;

    //                         // Special handling for premium services to match confirmation page format
    //                         const displayTitle = (addon.id === 'premium-services' || addon.title === 'Premium Services') 
    //                             ? 'Premium Service' 
    //                             : (addon.title || 'Add-on');
    //                         const displaySubtext = (addon.id === 'premium-services' || addon.title === 'Premium Services')
    //                             ? 'TravelayGent™'
    //                             : 'Optional';

    //                         html += `
    //                             <div class="amadex-price-row premium-service">
    //                                 <div class="amadex-price-info">
    //                                     <span class="amadex-price-label">${displayTitle}</span>
    //                                     <span class="amadex-price-subtext">${displaySubtext}</span>
    //                                 </div>
    //                                 <span class="amadex-price-value">${formatCurrencyValue(addonPriceConverted, currency)}</span>
    //                             </div>
    //                         `;
    //                     }
    //                 });
    //             } catch(e) {
    // }
    //         }

    //         // Legacy Premium Service support (for backward compatibility - only if not already in addons)
    //         const premiumServiceAdded = sessionStorage.getItem('amadex_premium_service_added') === 'true';
    //         const premiumServiceAmount = 25.00;
    //         // Check if premium-services is already in selectedAddons to avoid duplication
    //         let hasPremiumInAddons = false;
    //         if (savedAddons) {
    //             try {
    //                 const selectedAddons = JSON.parse(savedAddons);
    //                 hasPremiumInAddons = selectedAddons && (selectedAddons['premium-services'] || 
    //                     Object.values(selectedAddons).some(a => a.id === 'premium-services' || a.title === 'Premium Services'));
    //             } catch(e) {
    //                 // Ignore parse errors
    //             }
    //         }

    //         if (premiumServiceAdded && addonsTotal === 0 && !hasPremiumInAddons) {
    //             // Convert legacy premium service amount using the same exchange rate
    //             let premiumServiceAmountConverted = premiumServiceAmount;
    //             if (currency !== originalCurrency && originalCurrency === 'USD' && currency !== 'USD') {
    //                 if (rate !== 1.0) {
    //                     premiumServiceAmountConverted = premiumServiceAmount * rate;
    // }
    //             }
    //             addonsTotalConverted += premiumServiceAmountConverted;

    //             // Only show if no add-ons are selected and premium-services is not already in addons (to avoid duplication)
    //             // Match confirmation page format: "Premium Service" (not "Premium Service Addon")
    //             html += `
    //                 <div class="amadex-price-row premium-service">
    //                     <div class="amadex-price-info">
    //                         <span class="amadex-price-label">Premium Service</span>
    //                         <span class="amadex-price-subtext">TravelayGent™</span>
    //                     </div>
    //                     <span class="amadex-price-value">${formatCurrencyValue(premiumServiceAmountConverted, currency)}</span>
    //                 </div>
    //             `;
    //             addonsTotal = premiumServiceAmount;
    //         }

    //         // Discount row (if applicable)
    //         if (discount > 0) {
    //             html += `
    //                 <div class="amadex-price-row discount">
    //                     <div class="amadex-price-info">
    //                         <span class="amadex-price-label">Discount</span>
    //                         <span class="amadex-price-subtext">Instant Off</span>
    //                     </div>
    //                     <span class="amadex-price-value">- ${formatCurrencyValue(discount, currency)}</span>
    //                 </div>
    //             `;
    //         }

    //         // Calculate final total including seat charges and add-ons
    //         // Formula: Total = Base Fare + Taxes + Seat Charges + Add-ons - Discount
    //         // Use converted values if currency conversion was applied
    //         const seatChargesForTotal = (currency !== originalCurrency && originalCurrency === 'USD' && currency !== 'USD' && rate !== 1.0) ? seatChargesConverted : seatCharges;
    //         const addonsTotalForTotal = (currency !== originalCurrency && originalCurrency === 'USD' && currency !== 'USD' && rate !== 1.0) ? addonsTotalConverted : addonsTotal;
    //         const finalTotalWithExtras = finalTotal + seatChargesForTotal + addonsTotalForTotal;

    //         // Debug logging for price calculation (helpful for troubleshooting)
    // // Total row
    // const correctFinalTotal = finalTotal + seatChargesForTotal + addonsTotalForTotal;

    // html += `
    //     <div class="amadex-price-row total">
    //         <div class="amadex-price-info">
    //             <span class="amadex-price-label">Total Amount</span>
    //             ${(pricingSnapshot || pricingChargeTotal) ? '<span class="amadex-price-subtext">Includes all taxes & fees</span>' : ''}
    //         </div>
    //         <span class="amadex-price-value amadex-price-value--highlight">${formatCurrencyValue(correctFinalTotal, currency)}</span>
    //     </div>
    // `;
    //         // Set HTML content with smooth animation
    //         if (animate) {
    //             container.fadeOut(200, function() {
    //                 $(this).html(html);

    //                 // Update price bar INSTANTLY after HTML is set (before fadeIn)
    //                 if (typeof updatePriceBar === 'function') {
    //                     updatePriceBar();
    //                 }

    //                 $(this).fadeIn(300, function() {
    //                     $(this).removeClass('price-updating');
    //                     $(this).find('.amadex-price-value').removeClass('updating');

    //                     // Update price bar again after animation completes
    //                     if (typeof updatePriceBar === 'function') {
    //                         updatePriceBar();
    //                     }
    //                 });
    //             });
    //         } else {
    //             container.html(html);
    //             container.removeClass('price-updating');
    //             container.find('.amadex-price-value').removeClass('updating');

    //             // Update price bar INSTANTLY after HTML is set (synchronous, no delay)
    //             if (typeof updatePriceBar === 'function') {
    //                 updatePriceBar();
    //             }
    //         }
    // // Update price bar INSTANTLY - multiple update points to ensure it happens
    //         // This ensures it updates without waiting for page refresh
    //         if (typeof updatePriceBar === 'function') {
    //             // Immediate synchronous update (runs right now)
    //             updatePriceBar();

    //             // Also use requestAnimationFrame for next frame update
    //             requestAnimationFrame(function() {
    //                 updatePriceBar();
    //             });

    //             // Small timeout as final fallback (10ms)
    //             setTimeout(function() {
    //                 updatePriceBar();
    //             }, 10);
    //         } else if (typeof window.updatePriceBar === 'function') {
    //             // Try window function if local function not available
    //             window.updatePriceBar();
    //             requestAnimationFrame(function() {
    //                 window.updatePriceBar();
    //             });
    //         }

    //         // Update confirmation email display if exists
    //         const emailField = $('#contact-email');
    //         if (emailField.length) {
    //             const email = emailField.val() || '';
    //             const confirmationEmailEl = $('#amadex-confirmation-email');
    //             if (confirmationEmailEl.length) {
    //                 if (email) {
    //                     confirmationEmailEl.text(email);
    //                 }

    //                 // Watch for email changes
    //                 emailField.off('input.priceBreakdown').on('input.priceBreakdown', function() {
    //                     confirmationEmailEl.text($(this).val() || 'your email');
    //                 });
    //             }
    //         }

    //         // Trigger priceUpdated event for sticky price bar
    //         $(document).trigger('priceUpdated', [finalTotal, currency]);

    //         // Update price bar INSTANTLY after breakdown is set
    //         // Use setTimeout to ensure DOM is updated first
    //         setTimeout(function() {
    //             if (typeof updatePriceBar === 'function') {
    //                 updatePriceBar();
    //             }
    //         }, 50);

    //         // Also update immediately (in case DOM is already ready)
    //         if (typeof updatePriceBar === 'function') {
    //             updatePriceBar();
    //         }
    //     }

    /**
     * Initialize premium services functionality
     */
    function initPremiumServices() {
        const $premiumBtn = $('#amadex-premium-services-btn');
        if (!$premiumBtn.length) {
            return;
        }

        // Check if premium service was already selected (via checkbox or button)
        // Don't clear state if it was selected via checkbox
        let premiumAlreadySelected = false;
        const savedAddons = sessionStorage.getItem('amadex_booking_addons');
        if (savedAddons) {
            try {
                const selectedAddons = JSON.parse(savedAddons);
                premiumAlreadySelected = selectedAddons && (selectedAddons['premium-services'] ||
                    Object.values(selectedAddons).some(a => a.id === 'premium-services' || a.title === 'Premium Services'));
            } catch (e) {
                // Ignore parse errors
            }
        }

        // Only clear state if not already selected via checkbox
        if (!premiumAlreadySelected) {
            // Check legacy sessionStorage flag
            const legacySelected = sessionStorage.getItem('amadex_premium_service_added') === 'true';
            if (!legacySelected) {
                // Clear state only if not selected at all
                sessionStorage.removeItem('amadex_premium_service_added');
            }
        }

        // Initialize button state based on current selection
        const isSelected = premiumAlreadySelected || sessionStorage.getItem('amadex_premium_service_added') === 'true';
        updatePremiumServiceButton(isSelected);

        // Handle button click - use off() first to prevent duplicate handlers
        $premiumBtn.off('click.premium').on('click.premium', function (e) {
            e.preventDefault();
            e.stopPropagation();
            togglePremiumService();
        });
    }

    /**
     * Toggle premium service add/remove
     */
    function togglePremiumService() {
        const currentState = sessionStorage.getItem('amadex_premium_service_added') === 'true';
        const newState = !currentState;

        // Update sessionStorage
        if (newState) {
            // When adding: Save state to sessionStorage
            sessionStorage.setItem('amadex_premium_service_added', 'true');
        } else {
            // When removing: Clear state from sessionStorage
            sessionStorage.removeItem('amadex_premium_service_added');
        }

        // Update button
        updatePremiumServiceButton(newState);

        // Reload flight and search data to recalculate price
        const flightData = sessionStorage.getItem('amadex_booking_flight');
        const searchDataStr = sessionStorage.getItem('amadex_search_data');

        if (flightData && searchDataStr) {
            try {
                const flight = JSON.parse(flightData);
                const searchData = JSON.parse(searchDataStr);

                // Recalculate and update price breakdown
                populatePriceBreakdown(flight, searchData, true);
            } catch (e) {
            }
        }
    }

    /**
     * Update premium service button state
     */
    function updatePremiumServiceButton(isAdded) {
        const $btn = $('#amadex-premium-services-btn');
        const $btnText = $btn.find('.btn-text');

        if (!$btn.length || !$btnText.length) {
            return;
        }

        if (isAdded) {
            $btn.addClass('premium-service-added');
            $btnText.text('Remove');
        } else {
            $btn.removeClass('premium-service-added');
            $btnText.text('Add');
        }
    }

    /**
     * Clear booking timer session
     * Call this when navigating away or starting a new search
     * Made globally accessible so it can be called from other scripts
     */
    function clearBookingTimerSession() {
        // Clear timer interval if running
        if (window.amadexBookingTimerInterval) {
            clearInterval(window.amadexBookingTimerInterval);
            window.amadexBookingTimerInterval = null;
        }

        // Clear sessionStorage timer data
        sessionStorage.removeItem('amadex_booking_timer_start');
        sessionStorage.removeItem('amadex_booking_timer_remaining');
    }

    // Make function globally accessible
    window.clearBookingTimerSession = clearBookingTimerSession;

    /**
     * Start booking countdown timer
     * Continues existing timer session if available, otherwise starts new one
     */
    function startBookingTimer() {
        const timerBadge = $('#amadex-booking-timer-badge');
        const timerDisplay = timerBadge.find('.timer-display');

        // Check if timer badge exists
        if (timerBadge.length === 0 || timerDisplay.length === 0) {
            // Try again after a short delay in case DOM isn't ready
            setTimeout(function () {
                const retryBadge = $('#amadex-booking-timer-badge');
                const retryDisplay = retryBadge.find('.timer-display');
                if (retryBadge.length > 0 && retryDisplay.length > 0) {
                    startBookingTimer();
                }
            }, 500);
            return;
        }

        // Show the timer badge (ensure it's visible)
        timerBadge.css({
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
        });

        // Clear any existing interval
        if (window.amadexBookingTimerInterval) {
            clearInterval(window.amadexBookingTimerInterval);
            window.amadexBookingTimerInterval = null;
        }

        // Get timer duration - Always 20 minutes (1200 seconds)
        const timerDuration = 20 * 60; // 20 minutes in seconds = 1200 seconds
        let timeRemaining = timerDuration;

        // Check if we should force a fresh start (e.g., after price refresh)
        let shouldStartFresh = false;
        if (window.amadexForceFreshTimer) {
            // Clear any existing timer state
            sessionStorage.removeItem('amadex_booking_timer_start');
            sessionStorage.removeItem('amadex_booking_timer_remaining');
            sessionStorage.removeItem('amadex_booking_timer_paused_at');
            shouldStartFresh = true;
        } else {
            // Check if timer was paused (tab switching) - only resume if paused recently
            // Timer state is cleared on navigation away (beforeunload), so only tab switches preserve it
            const timerStartTime = sessionStorage.getItem('amadex_booking_timer_start');
            const pausedAt = sessionStorage.getItem('amadex_booking_timer_paused_at');
            const savedRemaining = sessionStorage.getItem('amadex_booking_timer_remaining');

            // Only resume if paused (tab switch) - not if navigated away (timer state cleared by beforeunload)
            // Timer state is cleared on navigation away, so only tab switches preserve paused state
            // Check if pause was recent (within last few seconds) - indicates tab switch, not navigation
            let shouldResume = false;
            if (pausedAt && savedRemaining && timerStartTime) {
                const pauseTime = parseInt(pausedAt);
                const timeSincePause = Date.now() - pauseTime;
                // Only resume if paused within last 3 seconds (tab switch), not navigation
                if (timeSincePause <= 3000) {
                    shouldResume = true;
                } else {
                    // Pause was too long ago - clear state, start fresh
                    sessionStorage.removeItem('amadex_booking_timer_paused_at');
                    sessionStorage.removeItem('amadex_booking_timer_remaining');
                    sessionStorage.removeItem('amadex_booking_timer_start');
                }
            }

            // Check if we should resume existing timer or start fresh
            if (shouldResume && timerStartTime && savedRemaining) {
                // Timer session exists - continue from where it left off (same flight, page refresh/navigation/visibility change)
                const startTime = parseInt(timerStartTime);
                const now = Date.now();
                const elapsed = Math.floor((now - startTime) / 1000);

                // If page was paused (visibility change), account for the pause time
                if (pausedAt && savedRemaining) {
                    const pauseTime = parseInt(pausedAt);
                    const pauseDuration = Math.floor((now - pauseTime) / 1000);
                    const remainingBeforePause = parseInt(savedRemaining);

                    // Calculate remaining time: subtract elapsed time but add back pause duration
                    timeRemaining = Math.max(remainingBeforePause - pauseDuration, 0);

                    // Clear pause marker
                    sessionStorage.removeItem('amadex_booking_timer_paused_at');
                } else if (savedRemaining) {
                    // Use saved remaining time (from beforeunload)
                    timeRemaining = parseInt(savedRemaining);
                    // Update start time to now minus elapsed time to keep it accurate
                    const elapsedFromSaved = Math.max(0, elapsed - (timerDuration - timeRemaining));
                    sessionStorage.setItem('amadex_booking_timer_start', (Date.now() - (elapsedFromSaved * 1000)).toString());
                } else {
                    // Calculate remaining time from start time
                    timeRemaining = Math.max(timerDuration - elapsed, 0);
                }

                // If timer already expired, refresh price immediately
                if (timeRemaining <= 0) {
                    refreshFlightPriceOnExpiry();
                    return;
                }

                // Update display immediately
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                const formattedTime = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                timerDisplay.text(formattedTime);

                // Timer resumed successfully, skip to interval setup (timeRemaining is already set)
            } else {
                // shouldResume is false - start fresh
                shouldStartFresh = true;
            }
        }

        // Start a new timer session (either force fresh, or shouldResume was false)
        if (shouldStartFresh) {
            sessionStorage.setItem('amadex_booking_timer_start', Date.now().toString());
            timeRemaining = timerDuration; // Always start with 20:00 (1200 seconds)

            // Ensure display shows 20:00 for new timer
            if (timerDisplay.length) {
                timerDisplay.text('20:00');
            }
        }

        let timerInterval;

        // Use global flag for warning modal (persists across timer restarts)
        if (typeof window.amadexWarningModalShown === 'undefined') {
            window.amadexWarningModalShown = false;
        }

        function updateTimer() {
            if (timeRemaining <= 0) {
                // Timer expired - refresh price only (NO PAGE REDIRECT)
                clearInterval(timerInterval);
                // Also clear global interval reference
                if (window.amadexBookingTimerInterval) {
                    clearInterval(window.amadexBookingTimerInterval);
                    window.amadexBookingTimerInterval = null;
                }
                timerDisplay.text('00:00');

                // CRITICAL: Close warning modal if it's still open (before triggering refresh)
                const $modalOverlay = $('#amadex-timer-warning-overlay');
                if ($modalOverlay.length) {
                    $modalOverlay.removeClass('show');
                    setTimeout(function () {
                        $modalOverlay.remove();
                    }, 300);
                }

                // Add expired class to both timer-card and timer-badge (for legacy support)
                timerBadge.addClass('timer-expired');
                if (timerBadge.hasClass('amadex-timer-card')) {
                    timerBadge.addClass('timer-expired');
                }

                // Update timer subtitle to show refreshing message
                const timerSubtitle = timerBadge.find('.amadex-timer-subtitle');
                if (timerSubtitle.length) {
                    timerSubtitle.text('Refreshing price...').css({
                        'color': '#0E7D3F',
                        'font-weight': '600'
                    });
                }

                // Update timer display to show refreshing state
                timerDisplay.text('--:--').css({
                    'color': '#0E7D3F',
                    'opacity': '0.7'
                });

                // Refresh price (NO redirect, NO page reset)
                // Only trigger if not already in progress to prevent duplicate calls
                if (!window.amadexTimerRefreshInProgress) {
                    refreshFlightPriceOnExpiry();
                }

                // Guaranteed timer restart after expiry - always fires regardless of API result.
                // Fires at 6s (after the 4s AJAX timeout has already completed).
                clearTimeout(window.amadexTimerRestartFallback);
                window.amadexTimerRestartFallback = setTimeout(function () {
                    restartTimerAfterRefresh();
                }, 6000);
                return;
            }

            // Show 5-minute warning modal (only once when reaching 5 minutes or less)
            // Use range check instead of exact equality to ensure modal shows even if timer skips 300
            if (timeRemaining <= 300 && timeRemaining > 295 && !window.amadexWarningModalShown) {
                showFiveMinuteWarningModal();
                window.amadexWarningModalShown = true;
            }

            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;

            // Format as MM:SS
            const formattedTime = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            timerDisplay.text(formattedTime);

            // Update modal timer if it's open - format for modal display (MM:SS for countdown)
            const modalTimerDisplay = $('.amadex-timer-warning-modal .timer-display');
            if (modalTimerDisplay.length) {
                // Format as MM:SS for countdown display
                // Ensure we show "00:00" when timeRemaining is 0 or less
                const displayTime = timeRemaining <= 0 ? '00:00' : formattedTime;
                modalTimerDisplay.text(displayTime);
            }

            // Store remaining time in global variable and sessionStorage
            window.amadexTimerRemaining = timeRemaining;
            sessionStorage.setItem('amadex_booking_timer_remaining', timeRemaining.toString());

            timeRemaining--;
        }

        // Update immediately to show correct time (20:00 for new timer)
        updateTimer();

        // Ensure timer display shows 20:00 when starting fresh
        if (timeRemaining === timerDuration && timerDisplay.length) {
            const formattedTime = String(Math.floor(timerDuration / 60)).padStart(2, '0') + ':' + String(timerDuration % 60).padStart(2, '0');
            timerDisplay.text(formattedTime);
        }

        // Update every second
        timerInterval = setInterval(updateTimer, 1000);

        // Store interval ID for potential cleanup
        window.amadexBookingTimerInterval = timerInterval;
    }

    /**
     * Show 5-minute warning modal - JustFly style
     */
    function showFiveMinuteWarningModal() {
        // Check if modal already exists
        if ($('#amadex-timer-warning-overlay').length > 0) {
            return; // Already shown
        }

        const timerBadge = $('#amadex-booking-timer-badge');
        const timerDisplay = timerBadge.find('.timer-display');
        const currentTime = timerDisplay.text() || '05:00';

        // Parse current time to get minutes for display
        const timeParts = currentTime.split(':');
        const displayMinutes = timeParts.length === 2 ? parseInt(timeParts[0], 10) : 5;

        // Create modal overlay
        const $warningOverlay = $('<div class="amadex-timer-warning-overlay" id="amadex-timer-warning-overlay"></div>');
        const $warningModal = $(`
            <div class="amadex-timer-warning-modal">
                <button type="button" class="amadex-modal-close" aria-label="Close">&times;</button>
                <h3>Your search is expiring soon...</h3>
                <p>In <span class="timer-display">${currentTime}</span> we'll need to recheck flight availability.</p>
            </div>
        `);

        $warningOverlay.append($warningModal);
        $('body').append($warningOverlay);

        // Show the modal with animation
        setTimeout(function () {
            $warningOverlay.addClass('show');
        }, 100);

        // Close button handler - trigger price refresh when modal is closed
        $warningModal.find('.amadex-modal-close').on('click', function () {
            $warningOverlay.removeClass('show');
            setTimeout(function () {
                $warningOverlay.remove();
                // When user closes modal manually, check timer state
                const timerBadge = $('#amadex-booking-timer-badge');
                const isExpired = timerBadge.hasClass('timer-expired');

                // If timer is expired and refresh not in progress, trigger refresh (retry)
                // This allows user to retry after timeout
                if (isExpired && !window.amadexTimerRefreshInProgress) {
                    refreshFlightPriceOnExpiry();
                } else if (!isExpired && !window.amadexTimerRefreshInProgress) {
                    // Timer still running - trigger refresh to get updated prices
                    refreshFlightPriceOnExpiry();
                } else {
                }
            }, 300);
        });

        // Close on overlay click (optional) - also trigger price refresh
        $warningOverlay.on('click', function (e) {
            if ($(e.target).is($warningOverlay)) {
                $warningOverlay.removeClass('show');
                setTimeout(function () {
                    $warningOverlay.remove();
                    // When user closes modal by clicking overlay, check timer state
                    const timerBadge = $('#amadex-booking-timer-badge');
                    const isExpired = timerBadge.hasClass('timer-expired');

                    // If timer is expired and refresh not in progress, trigger refresh (retry)
                    // This allows user to retry after timeout
                    if (isExpired && !window.amadexTimerRefreshInProgress) {
                        refreshFlightPriceOnExpiry();
                    } else if (!isExpired && !window.amadexTimerRefreshInProgress) {
                        // Timer still running - trigger refresh to get updated prices
                        refreshFlightPriceOnExpiry();
                    } else {
                    }
                }, 300);
            }
        });
    }

    /**
     * Refresh flight price on timer expiration - JustFly style (no page refresh)
     */
    function refreshFlightPriceOnExpiry() {
        // Prevent duplicate calls if refresh is already in progress
        if (window.amadexTimerRefreshInProgress) {
            return;
        }

        // Get flight and search data from sessionStorage
        const flightDataStr = sessionStorage.getItem('amadex_booking_flight');
        const searchDataStr = sessionStorage.getItem('amadex_search_data');

        if (!flightDataStr || !searchDataStr) {
            // Show error - don't restart timer automatically
            const timerSubtitle = $('#amadex-booking-timer-badge').find('.amadex-timer-subtitle');
            if (timerSubtitle.length) {
                timerSubtitle.text('Unable to refresh price. Please try again.').css({
                    'color': '#d32f2f',
                    'font-weight': '600'
                });
            }
            return;
        }

        let flight, searchData;
        try {
            flight = JSON.parse(flightDataStr);
            searchData = JSON.parse(searchDataStr);
        } catch (e) {
            // Show error - don't restart timer automatically
            const timerSubtitle = $('#amadex-booking-timer-badge').find('.amadex-timer-subtitle');
            if (timerSubtitle.length) {
                timerSubtitle.text('Unable to refresh price. Please try again.').css({
                    'color': '#d32f2f',
                    'font-weight': '600'
                });
            }
            return;
        }

        // CRITICAL: Sync current passenger counts from DOM before API call
        // This ensures we use the most up-to-date passenger counts
        searchData = syncCurrentPassengerCountsToSearchData();
        // Show subtle loading indicator in Price Summary
        const $priceContainer = $('#amadex-price-breakdown');
        if ($priceContainer.length) {
            $priceContainer.addClass('price-updating');
        }

        // Mark this as a timer refresh (for callback identification)
        window.amadexTimerRefreshInProgress = true;

        // Get raw offer from flight object (required for direct pricing API call)
        const rawOffer = flight.rawOffer || flight;

        // Call the re-price API - wrapped in try/catch so any crash still allows fallback restart
        try {
            updatePriceWithAmadeusAPI(flight, searchData, 'timer_refresh', rawOffer);
        } catch (e) {
            window.amadexTimerRefreshInProgress = false;
        }

        // Note: Timer will be reset in the success callback of updatePriceWithAmadeusAPI (not complete)
    }

    /**
     * Restart timer after successful price refresh
     * This is called only when price refresh succeeds
     */
    function restartTimerAfterRefresh() {
        // Clear old timer data - ensure everything is cleared
        sessionStorage.removeItem('amadex_booking_timer_start');
        sessionStorage.removeItem('amadex_booking_timer_remaining');
        sessionStorage.removeItem('amadex_booking_timer_paused_at');

        // Set flag to force fresh start (prevents resume logic from interfering)
        window.amadexForceFreshTimer = true;

        // Remove expired class from timer and restore original styling
        const timerBadge = $('#amadex-booking-timer-badge');
        if (timerBadge.length === 0) {
            // Retry after delay
            setTimeout(function () {
                restartTimerAfterRefresh();
            }, 500);
            return;
        }

        timerBadge.removeClass('timer-expired');

        // Restore timer subtitle to original text
        const timerSubtitle = timerBadge.find('.amadex-timer-subtitle');
        if (timerSubtitle.length) {
            timerSubtitle.text('This price will expire after 20 minutes.').css({
                'color': '#666666',
                'font-weight': 'normal'
            });
        }

        // Restore timer display styling
        const timerDisplay = timerBadge.find('.timer-display');
        if (timerDisplay.length) {
            timerDisplay.css({
                'color': '#0E7D3F',
                'opacity': '1'
            });
        }

        // Reset warning modal flag
        window.amadexWarningModalShown = false;

        // Restart timer (NO redirect, NO page reset)
        // Clear any existing interval
        if (window.amadexBookingTimerInterval) {
            clearInterval(window.amadexBookingTimerInterval);
            window.amadexBookingTimerInterval = null;
        }

        // Start fresh timer after a brief delay to ensure DOM is ready
        setTimeout(function () {
            startBookingTimer();
            // Clear the force flag after timer starts
            setTimeout(function () {
                window.amadexForceFreshTimer = false;
            }, 1000);
        }, 500);
    }

    /**
     * Restart timer to 20:00
     */
    function restartTimer() {
        // Clear old timer data
        sessionStorage.removeItem('amadex_booking_timer_start');
        sessionStorage.removeItem('amadex_booking_timer_remaining');
        sessionStorage.removeItem('amadex_booking_timer_paused_at');

        // Start fresh timer
        if (window.amadexBookingTimerInterval) {
            clearInterval(window.amadexBookingTimerInterval);
            window.amadexBookingTimerInterval = null;
        }

        // Start new timer
        setTimeout(function () {
            startBookingTimer();
        }, 500);
    }

    /**
     * Handle timer expiration - OLD VERSION (kept for reference, but replaced)
     * Now replaced with refreshFlightPriceOnExpiry()
     */
    function handleTimerExpiration() {
        // This function is now replaced by refreshFlightPriceOnExpiry()
        // Kept for backwards compatibility
        refreshFlightPriceOnExpiry();
    }

    /**
     * GA4 add_to_cart: fires every time a new traveler (adult/child/infant)
     * is added on the Passenger Details step. Mirrors the same ecommerce
     * item structure as begin_checkout / purchase, with updated
     * traveler_count / value reflecting the newly added passenger.
     */
    function pushAmadexAddToCartEvent(flight, searchData, adults, children, infants) {
        try {
            var fl = flight;
            if (!fl) return;

            var sd = searchData || window.amadexSearchData || {};

            var offerId = (fl.rawOffer && fl.rawOffer.id) || fl.id || fl.offerId || '';
            var airlineCode = (fl.validatingAirlineCodes && fl.validatingAirlineCodes[0]) ||
                (fl.validating_airline_codes && fl.validating_airline_codes[0]) || '';
            var airlineName = (typeof getAirlineName === 'function') ? getAirlineName(airlineCode) : airlineCode;

            var origin = '', destination = '', depDate = '', retDate = '', stopsCount = 0;
            if (fl.itineraries && fl.itineraries[0] && fl.itineraries[0].segments) {
                var segs = fl.itineraries[0].segments;
                var firstSeg = segs[0];
                var lastSeg = segs[segs.length - 1];
                origin = (firstSeg.departure && (firstSeg.departure.iataCode || firstSeg.departure.iata_code || firstSeg.departure.code)) || '';
                destination = (lastSeg.arrival && (lastSeg.arrival.iataCode || lastSeg.arrival.iata_code || lastSeg.arrival.code)) || '';
                stopsCount = segs.length - 1;
                if (firstSeg.departure && firstSeg.departure.at) depDate = firstSeg.departure.at.split('T')[0];
            }
            if (!origin) origin = sd.origin || sd.from || '';
            if (!destination) destination = sd.destination || sd.to || '';
            if (!depDate) depDate = sd.departure_date || sd.departureDate || '';

            if (fl.itineraries && fl.itineraries[1] && fl.itineraries[1].segments) {
                var retFirstSeg = fl.itineraries[1].segments[0];
                if (retFirstSeg && retFirstSeg.departure && retFirstSeg.departure.at) {
                    retDate = retFirstSeg.departure.at.split('T')[0];
                }
            }
            if (!retDate) retDate = sd.return_date || sd.returnDate || '';

            var cabinRaw = sd.cabin || sd.travel_class || 'ECONOMY';
            var cabinName = (typeof getCabinClassName === 'function') ? getCabinClassName(cabinRaw) : cabinRaw;
            var tripType = (sd.trip_type || sd.tripType || 'round_trip').toLowerCase().replace(/\s+/g, '_').replace(/^round$/, 'round_trip').replace(/^oneway$/, 'one_way');

            var adultsCount = parseInt(adults, 10) || 0;
            var childrenCount = parseInt(children, 10) || 0;
            var infantsCount = parseInt(infants, 10) || 0;
            var travelerCount = adultsCount + childrenCount + infantsCount;

            var rawPrice = parseFloat(
                (fl.price && fl.price.pricing_charge_total) ||
                (fl.price && fl.price.total) ||
                (fl.price && fl.price.grandTotal) ||
                fl.totalPrice || 0
            ) || 0;
            var perPaxPrice = travelerCount > 0 ? parseFloat((rawPrice / travelerCount).toFixed(2)) : parseFloat(rawPrice.toFixed(2));
            var totalValue = parseFloat((perPaxPrice * travelerCount).toFixed(2));

            var currency = ((fl.price && (fl.price.selected_currency || fl.price.currency)) || sd.currency || 'USD').toUpperCase();
            var itemId = airlineCode + '_' + origin + '_' + destination;
            var itemName = origin + ' \u2192 ' + destination + (airlineName ? ' (' + airlineName + ')' : '');

            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                event: 'add_to_cart',
                ecommerce: {
                    currency: currency,
                    value: totalValue,
                    items: [{
                        item_id: itemId,
                        item_name: itemName,
                        item_category: 'Flights',
                        item_brand: airlineCode,
                        item_variant: cabinName,
                        price: perPaxPrice,
                        quantity: travelerCount,
                        origin: origin,
                        destination: destination,
                        start_date: depDate,
                        end_date: retDate,
                        trip_type: tripType,
                        cabin_class: cabinRaw.toLowerCase(),
                        stops_count: stopsCount,
                        traveler_count: travelerCount,
                        adults: adultsCount,
                        children: childrenCount,
                        infants: infantsCount,
                        itinerary_id: offerId
                    }]
                }
            });

            if (typeof console !== 'undefined' && console.log) {
                console.log('[Amadex GA4] add_to_cart pushed', window.dataLayer[window.dataLayer.length - 1]);
            }
        } catch (err) {
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('[Amadex GA4] add_to_cart push failed:', err);
            }
        }
    }

    /**
     * GA4 purchase: fires once when a booking is successfully confirmed.
     * Mirrors the begin_checkout / add_payment_info ecommerce item structure.
     * Must be called BEFORE 'amadex_booking_flight' is cleared from sessionStorage.
     */
    function storeAmadexPendingPurchaseEvent(flight, bookingRef) {
        try {
            var fl = flight;
            if (!fl) {
                var flightRawP = sessionStorage.getItem('amadex_booking_flight');
                if (flightRawP) fl = JSON.parse(flightRawP);
            }
            if (!fl) return;

            var sd = window.amadexSearchData || {};

            var offerId = (fl.rawOffer && fl.rawOffer.id) || fl.id || fl.offerId || '';
            var airlineCode = (fl.validatingAirlineCodes && fl.validatingAirlineCodes[0]) ||
                (fl.validating_airline_codes && fl.validating_airline_codes[0]) || '';
            var airlineName = (typeof getAirlineName === 'function') ? getAirlineName(airlineCode) : airlineCode;

            var origin = '', destination = '', depDate = '', retDate = '', stopsCount = 0;
            if (fl.itineraries && fl.itineraries[0] && fl.itineraries[0].segments) {
                var segs = fl.itineraries[0].segments;
                var firstSeg = segs[0];
                var lastSeg = segs[segs.length - 1];
                origin = (firstSeg.departure && (firstSeg.departure.iataCode || firstSeg.departure.iata_code || firstSeg.departure.code)) || '';
                destination = (lastSeg.arrival && (lastSeg.arrival.iataCode || lastSeg.arrival.iata_code || lastSeg.arrival.code)) || '';
                stopsCount = segs.length - 1;
                if (firstSeg.departure && firstSeg.departure.at) depDate = firstSeg.departure.at.split('T')[0];
            }
            if (!origin) origin = sd.origin || sd.from || '';
            if (!destination) destination = sd.destination || sd.to || '';
            if (!depDate) depDate = sd.departure_date || sd.departureDate || '';

            if (fl.itineraries && fl.itineraries[1] && fl.itineraries[1].segments) {
                var retFirstSeg = fl.itineraries[1].segments[0];
                if (retFirstSeg && retFirstSeg.departure && retFirstSeg.departure.at) {
                    retDate = retFirstSeg.departure.at.split('T')[0];
                }
            }
            if (!retDate) retDate = sd.return_date || sd.returnDate || '';

            var cabinRaw = sd.cabin || sd.travel_class || 'ECONOMY';
            var cabinName = (typeof getCabinClassName === 'function') ? getCabinClassName(cabinRaw) : cabinRaw;
            var tripType = (sd.trip_type || sd.tripType || 'round_trip').toLowerCase().replace(/\s+/g, '_').replace(/^round$/, 'round_trip').replace(/^oneway$/, 'one_way');

            var adults = parseInt(sd.adults || fl.originalAdults || 1);
            var children = parseInt(sd.children || fl.originalChildren || 0);
            var infants = parseInt(sd.infants || fl.originalInfants || 0);
            var travelerCount = adults + children + infants;

            var rawPrice = parseFloat(
                (fl.price && fl.price.pricing_charge_total) ||
                (fl.price && fl.price.total) ||
                (fl.price && fl.price.grandTotal) ||
                fl.totalPrice || 0
            ) || 0;
            var totalValue = parseFloat(rawPrice.toFixed(2));
            var perPaxPrice = travelerCount > 0 ? parseFloat((rawPrice / travelerCount).toFixed(2)) : totalValue;

            var currency = ((fl.price && (fl.price.selected_currency || fl.price.currency)) || sd.currency || 'USD').toUpperCase();
            var itemId = airlineCode + '_' + origin + '_' + destination;
            var itemName = origin + ' \u2192 ' + destination + (airlineName ? ' (' + airlineName + ')' : '');

            var purchaseEvent = {
                event: 'purchase',
                ecommerce: {
                    transaction_id: bookingRef || '',
                    currency: currency,
                    value: totalValue,
                    items: [{
                        item_id: itemId,
                        item_name: itemName,
                        item_category: 'Flights',
                        item_brand: airlineCode,
                        item_variant: cabinName,
                        price: perPaxPrice,
                        quantity: travelerCount,
                        origin: origin,
                        destination: destination,
                        start_date: depDate,
                        end_date: retDate,
                        trip_type: tripType,
                        cabin_class: cabinRaw.toLowerCase(),
                        stops_count: stopsCount,
                        traveler_count: travelerCount,
                        adults: adults,
                        children: children,
                        infants: infants,
                        itinerary_id: offerId
                    }]
                }
            };

            // Store for the booking-confirmation page to push to dataLayer.
            // Do NOT push to dataLayer here — purchase should fire on the
            // confirmation page so it's easy to see/verify per booking.
            try {
                sessionStorage.setItem('amadex_pending_purchase', JSON.stringify(purchaseEvent));
            } catch (storageErr) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[Amadex GA4] could not store pending purchase event:', storageErr);
                }
            }
        } catch (err) {
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('[Amadex GA4] purchase prep failed:', err);
            }
        }
    }

    /**
     * GA4 purchase: fires once when a booking is successfully confirmed.
     * Mirrors the begin_checkout / add_payment_info ecommerce item structure.
     * Must be called BEFORE 'amadex_booking_flight' is cleared from sessionStorage.
     */
    function storeAmadexPendingPurchaseEvent(flight, bookingRef) {
        try {
            var fl = flight;
            if (!fl) {
                var flightRawP = sessionStorage.getItem('amadex_booking_flight');
                if (flightRawP) fl = JSON.parse(flightRawP);
            }
            if (!fl) return;

            var sd = window.amadexSearchData || {};

            var offerId = (fl.rawOffer && fl.rawOffer.id) || fl.id || fl.offerId || '';
            var airlineCode = (fl.validatingAirlineCodes && fl.validatingAirlineCodes[0]) ||
                (fl.validating_airline_codes && fl.validating_airline_codes[0]) || '';
            var airlineName = (typeof getAirlineName === 'function') ? getAirlineName(airlineCode) : airlineCode;

            var origin = '', destination = '', depDate = '', retDate = '', stopsCount = 0;
            if (fl.itineraries && fl.itineraries[0] && fl.itineraries[0].segments) {
                var segs = fl.itineraries[0].segments;
                var firstSeg = segs[0];
                var lastSeg = segs[segs.length - 1];
                origin = (firstSeg.departure && (firstSeg.departure.iataCode || firstSeg.departure.iata_code || firstSeg.departure.code)) || '';
                destination = (lastSeg.arrival && (lastSeg.arrival.iataCode || lastSeg.arrival.iata_code || lastSeg.arrival.code)) || '';
                stopsCount = segs.length - 1;
                if (firstSeg.departure && firstSeg.departure.at) depDate = firstSeg.departure.at.split('T')[0];
            }
            if (!origin) origin = sd.origin || sd.from || '';
            if (!destination) destination = sd.destination || sd.to || '';
            if (!depDate) depDate = sd.departure_date || sd.departureDate || '';

            if (fl.itineraries && fl.itineraries[1] && fl.itineraries[1].segments) {
                var retFirstSeg = fl.itineraries[1].segments[0];
                if (retFirstSeg && retFirstSeg.departure && retFirstSeg.departure.at) {
                    retDate = retFirstSeg.departure.at.split('T')[0];
                }
            }
            if (!retDate) retDate = sd.return_date || sd.returnDate || '';

            var cabinRaw = sd.cabin || sd.travel_class || 'ECONOMY';
            var cabinName = (typeof getCabinClassName === 'function') ? getCabinClassName(cabinRaw) : cabinRaw;
            var tripType = (sd.trip_type || sd.tripType || 'round_trip').toLowerCase().replace(/\s+/g, '_').replace(/^round$/, 'round_trip').replace(/^oneway$/, 'one_way');

            var adults = parseInt(sd.adults || fl.originalAdults || 1);
            var children = parseInt(sd.children || fl.originalChildren || 0);
            var infants = parseInt(sd.infants || fl.originalInfants || 0);
            var travelerCount = adults + children + infants;

            var rawPrice = parseFloat(
                (fl.price && fl.price.pricing_charge_total) ||
                (fl.price && fl.price.total) ||
                (fl.price && fl.price.grandTotal) ||
                fl.totalPrice || 0
            ) || 0;
            var totalValue = parseFloat(rawPrice.toFixed(2));
            var perPaxPrice = travelerCount > 0 ? parseFloat((rawPrice / travelerCount).toFixed(2)) : totalValue;

            var currency = ((fl.price && (fl.price.selected_currency || fl.price.currency)) || sd.currency || 'USD').toUpperCase();
            var itemId = airlineCode + '_' + origin + '_' + destination;
            var itemName = origin + ' \u2192 ' + destination + (airlineName ? ' (' + airlineName + ')' : '');

            var purchaseEvent = {
                event: 'purchase',
                ecommerce: {
                    transaction_id: bookingRef || '',
                    currency: currency,
                    value: totalValue,
                    items: [{
                        item_id: itemId,
                        item_name: itemName,
                        item_category: 'Flights',
                        item_brand: airlineCode,
                        item_variant: cabinName,
                        price: perPaxPrice,
                        quantity: travelerCount,
                        origin: origin,
                        destination: destination,
                        start_date: depDate,
                        end_date: retDate,
                        trip_type: tripType,
                        cabin_class: cabinRaw.toLowerCase(),
                        stops_count: stopsCount,
                        traveler_count: travelerCount,
                        adults: adults,
                        children: children,
                        infants: infants,
                        itinerary_id: offerId
                    }]
                }
            };

            // Store for the booking-confirmation page to push to dataLayer.
            // Do NOT push to dataLayer here — purchase should fire on the
            // confirmation page so it's easy to see/verify per booking.
            try {
                sessionStorage.setItem('amadex_pending_purchase', JSON.stringify(purchaseEvent));
            } catch (storageErr) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[Amadex GA4] could not store pending purchase event:', storageErr);
                }
            }
        } catch (err) {
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('[Amadex GA4] purchase prep failed:', err);
            }
        }
    }

    /**
     * Build confirmation URL
     */
    function buildConfirmationUrl(bookingRef, apiUrl) {
        if (apiUrl) {
            return apiUrl;
        }

        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.confirmationPageUrl) {
            const separator = AmadexConfig.confirmationPageUrl.indexOf('?') === -1 ? '?' : '&';
            return AmadexConfig.confirmationPageUrl + separator + 'reference=' + encodeURIComponent(bookingRef || '');
        }

        const fallback = '/booking-confirmation/';
        const sep = fallback.indexOf('?') === -1 ? '?' : '&';
        return fallback + sep + 'reference=' + encodeURIComponent(bookingRef || '');
    }

    /**
     * Get the correct submit button based on gateway and current step
     * For Stripe, always use step-next button to avoid aria-hidden issues with hidden button
     */
    function getSubmitButton() {
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';

        // Check if we're on mobile (button visibility is different)
        const isMobile = window.innerWidth <= 767;
        const $stepNext = $('#amadex-step-next');
        const $confirmBook = $('#amadex-confirm-book');

        // On mobile, always use step-next button (confirm-book is hidden)
        if (isMobile) {
            if ($stepNext.length) {
                return $stepNext;
            }
            // Fallback to confirm-book if step-next doesn't exist
            return $confirmBook;
        }

        // On desktop, check gateway and step
        if (gateway === 'stripe') {
            // In-page Stripe form: use Pay Securely (#amadex-payment-submit)
            const $paymentSubmit = $('#amadex-payment-submit');
            if (currentStep === 'review' && $paymentSubmit.length && $paymentSubmit.is(':visible')) {
                return $paymentSubmit;
            }
            if ($stepNext.length) {
                return $stepNext;
            }
            if ($confirmBook.length) {
                $confirmBook.removeAttr('aria-hidden').removeAttr('inert').removeAttr('tabindex');
            }
            return $confirmBook;
        }

        // For NMI, use step-next if on review step, otherwise use hidden button
        if (currentStep === 'review') {
            if ($stepNext.length) {
                return $stepNext;
            }
        }

        // Default fallback
        return $confirmBook.length ? $confirmBook : $stepNext;
    }

    /**
     * Update both submit buttons (for consistency across desktop/mobile)
     * For Stripe, also updates #amadex-payment-submit (Pay securely)
     */
    function updateSubmitButtons(updates) {
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';
        const $stepNext = $('#amadex-step-next');
        const $confirmBook = $('#amadex-confirm-book');
        const $paymentSubmit = $('#amadex-payment-submit');

        const buttonsToUpdate = [$stepNext, $confirmBook];
        if (gateway === 'stripe' && $paymentSubmit.length) {
            buttonsToUpdate.push($paymentSubmit);
        }

        buttonsToUpdate.forEach(function ($btn) {
            if ($btn.length) {
                if (updates.disabled !== undefined) {
                    $btn.prop('disabled', updates.disabled);
                }
                if (updates.text !== undefined) {
                    let btnText = updates.text;
                    if (gateway === 'stripe' && ($btn.attr('id') === 'amadex-payment-submit' || $btn.attr('id') === 'amadex-step-next')) {
                        btnText = (btnText === 'Confirm & Book' || btnText === 'Pay securely') ? 'Pay Securely' : btnText;
                    }
                    $btn.text(btnText);
                }
                if (updates.css) {
                    $btn.css(updates.css);
                }
            }
        });
    }

    /**
     * Submit booking
     */
    function submitBooking(flight) {
        // ── Gateway.js 3DS: intercept before normal submission ──
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';

        const paymentMethod = ($('.amadex-payment-tab.is-active').attr('data-method') || '').trim();

        // Guard: if AJAX submission already in flight, do not re-launch 3DS
        if (window.amadexBookingSubmissionInProgress) {
            console.warn('[Amadex 3DS] Submission already in progress — ignoring duplicate submitBooking call');
            return false;
        }

        if (gateway === 'nmi' && paymentMethod === 'credit_card' && !window.amadex3DSComplete && (typeof AmadexNMI === 'undefined' || AmadexNMI.threeDSEnabled) && !(typeof AmadexNMI !== 'undefined' && AmadexNMI.bypassPayment)) {
            console.log('[DEBUG] Starting 3DS flow...');
            runGateway3DSAndSubmit(flight);
            return false;
        }
        console.log('[DEBUG] 3DS not triggered - gateway:', gateway, 'method:', paymentMethod, '3DSComplete:', window.amadex3DSComplete);

        // Reset 3DS flag for next booking attempt
        window.amadex3DSComplete = false;

        // ── Gateway.js 3DS path: submit directly with card + 3DS data (no CollectJS token needed) ──
        // When 3DS has run, we have the raw card details in window.amadex3DSData.
        // If 3DS returned nulls (not enabled on account), still proceed — NMI will charge without 3DS.
        if (gateway === 'nmi' && paymentMethod === 'credit_card' && window.amadex3DSData && window.amadex3DSData.card_number) {
            var threeDSData = window.amadex3DSData;
            window.amadex3DSData = null; // clear for next attempt

            if (window.amadexBookingSubmissionInProgress) return false;
            window.amadexBookingSubmissionInProgress = true;

            var submitBtnGw = getSubmitButton();
            if (submitBtnGw.length) submitBtnGw.prop('disabled', true).text('Processing...');
            if (!showBookingProcessingModal()) {
                window.amadexBookingSubmissionInProgress = false;
                if (submitBtnGw.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                return false;
            }

            if (!validateAllFields()) {
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtnGw.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                return false;
            }
            var oldValidation3ds = validateBookingForm();
            if (!oldValidation3ds || !oldValidation3ds.valid) {
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtnGw.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                return false;
            }

            var bookingData3ds = collectBookingData(flight);
            if (window.AmadexSeatSelection && typeof window.AmadexSeatSelection.includeInBooking === 'function') {
                bookingData3ds = window.AmadexSeatSelection.includeInBooking(bookingData3ds);
            }
            // Attach card + 3DS fields for backend
            // Backend reads card from booking_data['payment'] — put it there
            if (!bookingData3ds.payment) bookingData3ds.payment = {};
            bookingData3ds.payment.card_number = threeDSData.card_number;
            bookingData3ds.payment.card_month = threeDSData.card_expiry ? threeDSData.card_expiry.substring(0, 2) : '';
            bookingData3ds.payment.card_year = threeDSData.card_expiry ? threeDSData.card_expiry.substring(2) : '';
            bookingData3ds.payment.card_cvv = threeDSData.card_cvv;
            // Also at top level for 3DS fields (backend reads these from booking_data directly)
            bookingData3ds.cavv = threeDSData.cavv || '';
            bookingData3ds.xid = threeDSData.xid || '';
            bookingData3ds.eci = threeDSData.eci || '';
            bookingData3ds.cardholder_auth = threeDSData.cardholder_auth || '';
            bookingData3ds.three_ds_version = threeDSData.three_ds_version || '';
            bookingData3ds.directory_server_id = threeDSData.directory_server_id || '';
            bookingData3ds.use_raw_card = true;

            var requestHash3ds = generateRequestHash(flight, bookingData3ds);
            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_process_booking',
                    nonce: AmadexConfig.nonce,
                    booking_data: JSON.stringify(bookingData3ds),
                    request_hash: requestHash3ds
                },
                timeout: 60000,
                success: function (response) {
                    window.amadexBookingSubmissionInProgress = false;
                    if (response && response.success) {
                        const bookingRef = response.data?.booking_reference || '';

                        // GA4 purchase: fire before clearing booking flight data
                        storeAmadexPendingPurchaseEvent(flight, bookingRef);

                        // ✅ Show success state in modal
                        showBookingSuccessModal(bookingRef, message);
                        setTimeout(function () {
                            hideBookingProcessingModal();
                            var confirmationUrl = buildConfirmationUrl(bookingRef, response.data && response.data.confirmation_url ? response.data.confirmation_url : '');
                            window.location.href = confirmationUrl;
                        }, 2000);
                    } else {
                        var errMsg = response && response.data && response.data.message ? response.data.message : 'Booking failed. Please try again.';
                        showBookingErrorModal(errMsg);
                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    }
                },
                error: function (xhr) {
                    window.amadexBookingSubmissionInProgress = false;
                    var errMsg = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) errMsg = xhr.responseJSON.data.message;
                    showBookingErrorModal(errMsg);
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
            });
            return;
        }

        // ✅ FIX #2: ATOMIC FLAG CHECK AND SET (prevents race condition)
        // Check and set flag IMMEDIATELY in single operation
        if (window.amadexBookingSubmissionInProgress) {
            return false;
        }
        window.amadexBookingSubmissionInProgress = true; // ✅ Set IMMEDIATELY (synchronously)

        // ✅ FIX #2: DISABLE BUTTON FIRST (before any async operations)
        const submitBtn = getSubmitButton();
        if (submitBtn.length) {
            submitBtn.prop('disabled', true).text('Processing...'); // ✅ Disable IMMEDIATELY
        }

        // ✅ IMMEDIATE: Show processing modal (after flag and button disable)
        if (!showBookingProcessingModal()) {
            // Reset flag and button on error
            window.amadexBookingSubmissionInProgress = false;
            if (submitBtn.length) {
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            }
            return false;
        }

        // Mark that user is attempting payment - allow errors to be shown now
        window.amadexPaymentAttempted = true;

        // Ensure payment system is initialized before proceeding
        if (!window.amadexPaymentInitialized) {
            initializePayment(flight);
            // ✅ FIX #4: Check flag before recursive call (prevents bypass)
            setTimeout(function () {
                // ✅ Check if submission still in progress before retry
                if (window.amadexBookingSubmissionInProgress) {
                    // ✅ Reset flag temporarily for recursive call (will be set again)
                    window.amadexBookingSubmissionInProgress = false;
                    submitBooking(flight);
                } else {
                    // Reset button if cancelled
                    if (submitBtn.length) {
                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    }
                    hideBookingProcessingModal();
                }
            }, 1000);
            return;
        }

        // Route to correct gateway submit handler
        if (gateway === 'stripe') {
            // Validate all fields before storing booking data
            const validationResult = validateAllFields();
            if (!validationResult) {
                const $firstError = $('.amadex-form-field input.error, .amadex-form-field select.error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
                return;
            }

            // Also check the old validation function for backward compatibility
            const oldValidation = validateBookingForm();
            if (!oldValidation || !oldValidation.valid) {
                return;
            }

            // Collect all booking data
            let bookingData = collectBookingData(flight);

            // Include selected seats if available
            if (window.AmadexSeatSelection && typeof window.AmadexSeatSelection.includeInBooking === 'function') {
                bookingData = window.AmadexSeatSelection.includeInBooking(bookingData);
            }

            // Show loading - update both buttons for consistency
            // ✅ Reuse submitBtn already declared at function start (line 4668)
            updateSubmitButtons({
                disabled: true,
                text: 'Preparing payment...'
            });
            // Also update the primary button directly for immediate feedback
            if (submitBtn.length) {
                submitBtn.prop('disabled', true).text('Preparing payment...');
            }

            // Store booking data and get payment page URL
            // Check if AmadexConfig is available
            if (typeof AmadexConfig === 'undefined') {
                alert('Configuration error: Payment system not properly initialized. Please refresh the page and try again.');
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                return;
            }

            if (!AmadexConfig.ajaxUrl) {
                alert('Configuration error: Payment system not properly configured. Please refresh the page and try again.');
                updateSubmitButtons({
                    disabled: false,
                    text: 'Confirm & Book'
                });
                return;
            }

            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                data: {
                    action: 'amadex_store_booking_for_payment',
                    nonce: AmadexConfig.nonce || '',
                    booking_data: typeof bookingData === 'string' ? bookingData : JSON.stringify(bookingData)
                },
                success: function (response) {
                    if (response && response.success && response.data) {
                        var data = response.data;
                        // Always prefer server URLs; build fallback from token so redirect never fails (fixes "Unable to open Stripe checkout")
                        var paymentUrl = data.payment_url || data.payment_page_url || '';
                        if (!paymentUrl && data.token) {
                            var base = (typeof AmadexConfig !== 'undefined' && AmadexConfig.paymentPageBase) ? AmadexConfig.paymentPageBase : (window.location.origin + '/booking/payment/');
                            paymentUrl = base.replace(/\/*$/, '') + (base.indexOf('?') >= 0 ? '&' : '?') + 'st=' + encodeURIComponent(data.token);
                        }
                        var isStripe = gateway && String(gateway).toLowerCase() === 'stripe';

                        // Stripe: "Pay securely" → go to checkout.stripe.com (prefer direct URL; fallback: payment page will create session / redirect)
                        if (isStripe) {
                            if (data.stripe_checkout_url) {
                                window.location.href = data.stripe_checkout_url;
                                return;
                            }
                            // No direct Stripe URL – redirect to payment page; it will create Checkout session or show stripe_error
                            if (paymentUrl) {
                                if (data.stripe_error) {
                                    paymentUrl += (paymentUrl.indexOf('?') >= 0 ? '&' : '?') + 'stripe_error=' + encodeURIComponent(data.stripe_error);
                                } else {
                                }
                                window.location.href = paymentUrl;
                                return;
                            }
                            var msg = 'Unable to open Stripe checkout. Please try again or contact support.';
                            if (data.stripe_error) {
                                msg = 'Unable to open Stripe checkout. ' + data.stripe_error;
                            }
                            alert(msg);
                            updateSubmitButtons({
                                disabled: false,
                                text: 'Pay securely'
                            });
                            return;
                        }
                        // NMI: redirect to complete-payment page
                        if (paymentUrl) {
                            window.location.href = paymentUrl;
                            return;
                        }
                    }
                    // No redirect URL in response – try token fallback for Stripe, else show error
                    var data2 = response && response.data ? response.data : {};
                    var hasRedirect = response && response.success && response.data && (response.data.stripe_checkout_url || response.data.payment_url || response.data.payment_page_url || (response.data.token && (gateway && String(gateway).toLowerCase() === 'stripe')));
                    if (!hasRedirect && data2.token && gateway && String(gateway).toLowerCase() === 'stripe') {
                        var base2 = (typeof AmadexConfig !== 'undefined' && AmadexConfig.paymentPageBase) ? AmadexConfig.paymentPageBase : (window.location.origin + '/booking/payment/');
                        window.location.href = base2.replace(/\/*$/, '') + (base2.indexOf('?') >= 0 ? '&' : '?') + 'st=' + encodeURIComponent(data2.token);
                        return;
                    }
                    if (!hasRedirect) {
                        var errorMsg = (response && response.data && response.data.message)
                            ? response.data.message
                            : 'Failed to prepare payment page. Please try again.';
                        alert('Error: ' + errorMsg);
                        updateSubmitButtons({
                            disabled: false,
                            text: gateway === 'stripe' ? 'Pay securely' : 'Confirm & Book'
                        });
                    }
                },
                error: function (xhr, status, error) {
                    let errorMsg = 'An error occurred while preparing the payment page. Please try again.';

                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.data && errorResponse.data.message) {
                                errorMsg = errorResponse.data.message;
                            }
                        } catch (e) {
                        }
                    }

                    alert('Error: ' + errorMsg);
                    updateSubmitButtons({
                        disabled: false,
                        text: 'Confirm & Book'
                    });
                }
            });

            return; // Exit early - don't continue with NMI flow
        }
        // NMI flow continues below (existing CollectJS.tokenize() logic)

        // Validate all fields before submission
        if (!validateAllFields()) {
            // Scroll to first error
            const $firstError = $('.amadex-form-field input.error, .amadex-form-field select.error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
            return;
        }

        // Also check the old validation function for backward compatibility
        const oldValidation = validateBookingForm();
        if (!oldValidation || !oldValidation.valid) {
            return;
        }

        // Collect all form data
        let bookingData = collectBookingData(flight);

        // Include selected seats if available
        if (window.AmadexSeatSelection) {
            if (window.AmadexSeatSelection.selectedSeats) {
            } else {
            }
        }

        if (window.AmadexSeatSelection && typeof window.AmadexSeatSelection.includeInBooking === 'function') {
            bookingData = window.AmadexSeatSelection.includeInBooking(bookingData);
            if (bookingData.seat_selection) {
            }
        } else {
        }

        // ✅ Disable button IMMEDIATELY (redundant safety - modal already shown)
        // ✅ Reuse submitBtn already declared at function start (line 4668)
        updateSubmitButtons({
            disabled: true,
            text: 'Processing...'
        });

        // Also update the primary button directly for immediate feedback
        if (submitBtn.length) {
            submitBtn.prop('disabled', true).text('Processing...');
        }

        // ✅ FIX #2: Wrap hash generation in try-catch with fallback
        let requestHash;
        try {
            requestHash = generateRequestHash(flight, bookingData);
            if (!requestHash || requestHash.trim() === '') {
                throw new Error('Hash generation returned empty string');
            }
        } catch (error) {
            // Fallback: timestamp-based hash (10-second precision)
            requestHash = 'fallback_' + Math.floor(Date.now() / 10000) * 10;
            // Don't reset flag here - let submission continue with fallback hash
        }

        // ✅ FIX #5: Frontend lock check before AJAX (prevents unnecessary requests)
        const frontendLockKey = 'amadex_booking_lock_' + requestHash;
        const existingLock = sessionStorage.getItem(frontendLockKey);
        if (existingLock) {
            const lockAge = Date.now() - parseInt(existingLock);
            // If lock is less than 60 seconds old, it's a duplicate request
            if (lockAge < 60000) {
                showBookingErrorModal('A booking request is already being processed. Please wait a few seconds and try again.');
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                return;
            }
        }

        // ✅ Set frontend lock (60 seconds TTL to match backend)
        sessionStorage.setItem(frontendLockKey, Date.now().toString());

        // Clean up old locks (older than 60 seconds)
        try {
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith('amadex_booking_lock_')) {
                    const lockTime = parseInt(sessionStorage.getItem(key));
                    if (lockTime && (Date.now() - lockTime) > 60000) {
                        sessionStorage.removeItem(key);
                    }
                }
            });
        } catch (e) {
            // Ignore cleanup errors
        }

        // ✅ Collect device fingerprint for fraud detection
        let deviceFingerprintData = null;
        try {
            if (window.AmadexFraudDetection && typeof window.AmadexFraudDetection.getCompleteFraudData === 'function') {
                deviceFingerprintData = window.AmadexFraudDetection.getCompleteFraudData();
            } else {
            }
        } catch (error) {
            // Continue without fingerprint data
        }

        // Send to server
        $.ajax({
            url: AmadexConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amadex_process_booking',
                nonce: AmadexConfig.nonce,
                booking_data: bookingData,
                request_hash: requestHash, // Include hash for backend deduplication
                device_fingerprint: deviceFingerprintData ? JSON.stringify(deviceFingerprintData) : null // Include device fingerprint
            },
            timeout: 60000, // 60 second timeout
            success: function (response) {
                // ✅ Always reset flag
                window.amadexBookingSubmissionInProgress = false;

                // ✅ FIX #5: Clear frontend lock on success
                const frontendLockKey = 'amadex_booking_lock_' + requestHash;
                sessionStorage.removeItem(frontendLockKey);

                // ── NMI 3DS: redirect to bank authentication page ──
                if (response && response.success && response.data && response.data.three_step) {
                    window.location.href = response.data.redirect_url;
                    return;
                }

                if (response && response.success) {
                    const message = response.data?.message || 'Booking confirmed! Confirmation details sent to your email.';
                    const bookingRef = response.data?.booking_reference || '';

                    // ✅ Show success state in modal
                    showBookingSuccessModal(bookingRef, message);

                    // Store booking reference for confirmation page
                    sessionStorage.setItem('amadex_booking_reference', bookingRef);

                    // CRITICAL: Clear all booking-specific sessionStorage data BEFORE redirect
                    // This prevents duplicate bookings when user clicks back button
                    // Industry standard: Clear booking data immediately after success
                    const bookingKeysToClear = [
                        'amadex_booking_flight',
                        'amadex_search_data',
                        'amadexBookingStage',
                        'amadex_booking_step',
                        'amadex_booking_timer_start',
                        'amadex_booking_timer_remaining',
                        'amadex_booking_timer_paused_at',
                        'amadex_last_booking_flight_id',
                        'amadex_booking_addons',
                        'amadex_premium_service_added',
                        'amadex_multi_city_bookings',
                        'amadex_multi_city_segments',
                        'amadex_booking_all_segments',
                        'amadex_results_page_url'
                    ];

                    bookingKeysToClear.forEach(function (key) {
                        if (sessionStorage.getItem(key) !== null) {
                            sessionStorage.removeItem(key);
                        }
                    });

                    // ✅ Redirect after delay
                    setTimeout(function () {
                        hideBookingProcessingModal();
                        const confirmationUrl = buildConfirmationUrl(bookingRef, response?.data?.confirmation_url);
                        window.location.href = confirmationUrl;
                    }, 2000);
                } else {
                    // ✅ Check for duplicate request response
                    if (response?.data?.code === 'DUPLICATE_REQUEST') {
                        const errorMsg = response?.data?.message || 'A booking request is already being processed. Please wait a few seconds and try again.';
                        showBookingErrorModal(errorMsg);
                    } else {
                        const errorMsg = response?.data?.message || 'Booking failed. Please try again.';
                        showBookingErrorModal(errorMsg);
                    }

                    // Re-enable both buttons
                    updateSubmitButtons({
                        disabled: false,
                        text: 'Confirm & Book'
                    });
                }
            },
            error: function (xhr, status, error) {
                // ✅ Always reset flag
                window.amadexBookingSubmissionInProgress = false;

                // ✅ FIX #5: Clear frontend lock on error
                const frontendLockKey = 'amadex_booking_lock_' + requestHash;
                sessionStorage.removeItem(frontendLockKey);

                // ✅ Check for specific error codes
                let errorMessage = 'An error occurred while processing your booking. Please try again or contact support.';

                if (xhr.responseJSON && xhr.responseJSON.data) {
                    if (xhr.responseJSON.data.code === 'DUPLICATE_REQUEST') {
                        errorMessage = xhr.responseJSON.data.message || 'A booking request is already being processed. Please wait a few seconds and try again.';
                    } else if (xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                }

                // ✅ Show error state in modal (ALWAYS closable)
                showBookingErrorModal(errorMessage);

                // Re-enable both buttons
                updateSubmitButtons({
                    disabled: false,
                    text: 'Confirm & Book'
                });
            }
        });
    }



    /**
     * Validate booking form
     */
    function validateBookingForm() {
        const required = $('[required]');
        for (let i = 0; i < required.length; i++) {
            if (!$(required[i]).val()) {
                alert('Please fill in all required fields.');
                required[i].focus();
                return false;
            }
        }
        const billingStateValue = getBillingStateValue();
        if (!billingStateValue) {
            const stateElement = $('#billing-state').is(':visible') ? $('#billing-state') : $('#billing-state-input');
            alert('Please provide your state or province.');
            stateElement.focus();
            return false;
        }
        return true;
    }

    /**
     * Collect booking data
     */
    function collectBookingData(flight) {
        const passengers = collectPassengers();
        const contactName = passengers.length > 0
            ? passengers[0].firstname + ' ' + passengers[0].lastname
            : '';

        // Check if this is a multi-city trip
        const searchData = JSON.parse(sessionStorage.getItem('amadex_search_data') || '{}');
        const isMultiCity = searchData.trip_type === 'multi-city' || searchData.trip_type === 'multicity';

        // For multi-city trips, collect all segment flights
        let allFlights = [flight];
        if (isMultiCity) {
            const multiCityBookings = sessionStorage.getItem('amadex_multi_city_bookings');
            if (multiCityBookings) {
                try {
                    const bookings = JSON.parse(multiCityBookings);
                    // Convert object to array in correct order
                    const segmentFlights = Object.keys(bookings)
                        .sort((a, b) => parseInt(a) - parseInt(b))
                        .map(key => bookings[key])
                        .filter(f => f !== undefined && f !== null);

                    if (segmentFlights.length > 0) {
                        allFlights = segmentFlights;
                    }
                } catch (e) {
                }
            }
        }
        // Include 3DS authentication data if available
        var threeDSData = window.amadex3DSData || {};

        return {
            flight: flight,
            cavv: threeDSData.cavv || '',
            xid: threeDSData.xid || '',
            eci: threeDSData.eci || '',
            cardholder_auth: threeDSData.cardholder_auth || '',
            three_ds_version: threeDSData.three_ds_version || '',
            directory_server_id: threeDSData.directory_server_id || '',
            flights: allFlights, // All flights for multi-city trips
            is_multi_city: isMultiCity,
            segment_count: allFlights.length,
            selected_currency: getSelectedCurrency(), // Store selected currency for conversion
            contact: {
                first_name: passengers.length > 0 ? passengers[0].firstname : '',
                last_name: passengers.length > 0 ? passengers[0].lastname : '',
                phone: $('#contact-phone').val() || '',
                email: $('#contact-email').val() || ''
            },
            passengers: passengers,
            payment: {
                // Note: With Collect.js, card details are tokenized, so these may be empty
                // The actual payment processing uses the payment_token instead
                card_number: ($('#card-number').val() || '').replace(/\s/g, ''),
                card_month: $('#card-month').val() || '',
                card_year: $('#card-year').val() || '',
                card_cvv: $('#card-cvv').val() || '',
                card_name: $('#card-name').val() || ''
            },
            billing: (function () {
                // Get card holder name and parse it into first_name and last_name
                // For NMI/Stripe: use Card Holder Name (required). For MoonPay/PayPal/Crypto.com: use contact/passenger name.
                var pm = ($('.amadex-payment-tab.is-active').attr('data-method') || $('#payment-method').val() || $('#payment-method-moonpay').val() || $('#payment-method-moonpay-onramp').val() || $('#payment-method-crypto-com').val() || '').trim();
                if (!pm) pm = ($('#payment-method').val() || '').trim();
                const paymentMethod = pm;
                const useCardName = (paymentMethod === 'credit_card');
                const cardName = ($('#card-name').val() || '').trim();
                let billingFirstName = '';
                let billingLastName = '';

                if (cardName) {
                    const nameParts = cardName.split(/\s+/).filter(part => part.length > 0);
                    if (nameParts.length > 0) {
                        billingFirstName = nameParts[0];
                        billingLastName = nameParts.slice(1).join(' ') || nameParts[0];
                        if (!billingLastName || billingLastName === '') billingLastName = nameParts[0];
                    }
                    if (useCardName) {
                    }
                } else if (useCardName) {
                } else {
                    // MoonPay, PayPal, Crypto.com: use contact/passenger name for billing
                    billingFirstName = passengers.length > 0 ? (passengers[0].firstname || '') : '';
                    billingLastName = passengers.length > 0 ? (passengers[0].lastname || '') : '';
                }

                // REMOVED: Fallback to passenger name
                // The card holder name MUST be provided for proper NMI billing
                // NMI dashboard should show the person who owns the card, not the passenger

                return {
                    first_name: billingFirstName,
                    last_name: billingLastName,
                    country: $('#billing-country').val() || '',
                    state: getBillingStateValue() || '',
                    address1: $('#billing-address1').val() || '',
                    address2: $('#billing-address2').val() || '',
                    city: $('#billing-city').val() || '',
                    postal: $('#billing-postal').val() || ''
                };
            })(),
            search_params: searchData,
            user_location: {
                city: localStorage.getItem('amadex_user_city') || '',
                country: localStorage.getItem('amadex_user_country') || '',
                lat: localStorage.getItem('amadex_user_lat') || '',
                lon: localStorage.getItem('amadex_user_lon') || '',
                source: localStorage.getItem('amadex_user_city') ? 'browser_gps' : 'not_provided'
            },
            // Collect ALL add-ons from sessionStorage (not just premium-services)
            addons: (function () {
                const savedAddons = sessionStorage.getItem('amadex_booking_addons');
                const allAddons = [];

                if (savedAddons) {
                    try {
                        const selectedAddons = JSON.parse(savedAddons);
                        if (selectedAddons && typeof selectedAddons === 'object') {
                            // Collect all add-ons into array
                            Object.values(selectedAddons).forEach(addon => {
                                if (addon && typeof addon === 'object') {
                                    allAddons.push({
                                        id: addon.id || '',
                                        title: addon.title || 'Add-on',
                                        price: parseFloat(addon.price || 0),
                                        currency: addon.currency || 'USD'
                                    });
                                }
                            });
                        }
                    } catch (e) {
                    }
                }

                // Also check legacy sessionStorage flag for premium service (backward compatibility)
                const legacyPremiumAdded = sessionStorage.getItem('amadex_premium_service_added') === 'true';
                if (legacyPremiumAdded) {
                    // Check if premium-services is already in allAddons
                    const hasPremium = allAddons.some(a => a.id === 'premium-services' || a.title === 'Premium Services');
                    if (!hasPremium) {
                        // Add legacy premium service if not already present
                        allAddons.push({
                            id: 'premium-services',
                            title: 'Premium Services',
                            price: 25.00,
                            currency: 'USD'
                        });
                    }
                }

                return allAddons;
            })(),
            // Legacy premium_service field (for backward compatibility)
            premium_service: (function () {
                // Check multiple sources for premium service selection
                // Priority 1: Check selectedAddons (from checkbox in addons section)
                let premiumAdded = false;
                const savedAddons = sessionStorage.getItem('amadex_booking_addons');
                if (savedAddons) {
                    try {
                        const selectedAddons = JSON.parse(savedAddons);
                        premiumAdded = selectedAddons && (selectedAddons['premium-services'] ||
                            Object.values(selectedAddons).some(a => a.id === 'premium-services' || a.title === 'Premium Services'));
                    } catch (e) {
                    }
                }

                // Priority 2: Check legacy sessionStorage flag (from button)
                if (!premiumAdded) {
                    premiumAdded = sessionStorage.getItem('amadex_premium_service_added') === 'true';
                }

                return {
                    added: premiumAdded,
                    amount: 25.00
                };
            })(),
            // Extract pricing from flight object for payment page (Stripe / NMI)
            // CRITICAL: Use pricing_charge_total (P_charge) when Price Management Rules are enabled, so Stripe and NMI get the same total
            pricing: (function () {
                // Default pricing if flight data is not available
                const defaultPricing = {
                    fare: 0,
                    tax: 0,
                    surcharge: 0,
                    addons: 0,
                    seat_charges: 0,
                    premium_service: 0,
                    total: 0
                };

                if (!flight || !flight.price) {
                    return defaultPricing;
                }

                // Extract pricing from flight.price object
                const priceObj = flight.price;

                // Price Management Rules (NMI-style): when pricing_charge_total or pricing_snapshot exists, use P_charge as base
                // This ensures Stripe checkout amount matches the price summary and what NMI would charge
                const rulesEngineEnabled = !!(priceObj.pricing_snapshot || priceObj.pricing_charge_total);
                const pricingChargeTotal = parseFloat(priceObj.pricing_charge_total || priceObj.charge_total || 0);

                let baseTotal;
                let fareForBreakdown;
                let taxForBreakdown;
                let surchargeForBreakdown;

                if (rulesEngineEnabled && pricingChargeTotal > 0) {
                    // Use P_charge as base (same as NMI flow and backend process_booking)
                    baseTotal = pricingChargeTotal;
                    fareForBreakdown = pricingChargeTotal;
                    taxForBreakdown = 0;
                    surchargeForBreakdown = 0;
                } else {
                    // No price management rules: use flight price (base + tax + surcharge, or total if no breakdown)
                    const basePrice = parseFloat(priceObj.base || priceObj.fare || 0);
                    const taxes = parseFloat(priceObj.totalTaxes || priceObj.tax || priceObj.taxes || 0);
                    const surchargeVal = parseFloat(priceObj.surcharge || priceObj.bookingFee || priceObj.serviceCharge || 0);
                    baseTotal = basePrice + taxes + surchargeVal;
                    // Fallback: when no breakdown (e.g. only price.total from API), use total so Stripe/confirmation show flight price
                    if (!baseTotal || baseTotal <= 0) {
                        const flightTotal = parseFloat(priceObj.total || priceObj.grandTotal || 0);
                        if (flightTotal > 0) {
                            baseTotal = flightTotal;
                            fareForBreakdown = flightTotal;
                            taxForBreakdown = 0;
                            surchargeForBreakdown = 0;
                        }
                        else {
                            fareForBreakdown = basePrice;
                            taxForBreakdown = taxes;
                            surchargeForBreakdown = surchargeVal;
                        }
                    } else {
                        fareForBreakdown = basePrice;
                        taxForBreakdown = taxes;
                        surchargeForBreakdown = surchargeVal;
                    }
                }

                // Get addons total
                let addonsTotal = 0;
                if (flight.addons && Array.isArray(flight.addons)) {
                    flight.addons.forEach(function (addon) {
                        if (addon && addon.price) {
                            addonsTotal += parseFloat(addon.price) || 0;
                        }
                    });
                }
                // Also check sessionStorage for addons
                const savedAddons = sessionStorage.getItem('amadex_booking_addons');
                if (savedAddons) {
                    try {
                        const selectedAddons = JSON.parse(savedAddons);
                        Object.values(selectedAddons).forEach(function (addon) {
                            const addonPrice = parseFloat(addon.price || 0);
                            if (addonPrice > 0) {
                                addonsTotal += addonPrice;
                            }
                        });
                    } catch (e) {
                    }
                }

                // Get seat charges total
                let seatChargesTotal = 0;
                if (window.AmadexSeatSelection && window.AmadexSeatSelection.totalSeatCharges !== undefined) {
                    seatChargesTotal = parseFloat(window.AmadexSeatSelection.totalSeatCharges) || 0;
                } else if (window.AmadexSeatSelection && window.AmadexSeatSelection.selectedSeats) {
                    Object.values(window.AmadexSeatSelection.selectedSeats).forEach(function (seat) {
                        if (seat && seat.price && seat.price.total) {
                            seatChargesTotal += parseFloat(seat.price.total) || 0;
                        }
                    });
                }
                // Also check flight.price.seat_charges
                if (seatChargesTotal === 0 && priceObj.seat_charges) {
                    seatChargesTotal = parseFloat(priceObj.seat_charges) || 0;
                }

                // Get premium service amount
                let premiumServiceTotal = 0;
                const premiumServiceAdded = sessionStorage.getItem('amadex_premium_service_added') === 'true';
                if (premiumServiceAdded) {
                    // Check if premium service is in addons
                    let premiumInAddons = false;
                    if (flight.addons && Array.isArray(flight.addons)) {
                        flight.addons.forEach(function (addon) {
                            if (addon && (addon.id === 'premium-services' || addon.title === 'Premium Services')) {
                                premiumServiceTotal = parseFloat(addon.price) || 0;
                                premiumInAddons = true;
                            }
                        });
                    }
                    // If not in addons, use default amount
                    if (!premiumInAddons) {
                        premiumServiceTotal = 25.00; // Default premium service amount
                    }
                }

                // Calculate exact total: base (P_charge or legacy) + addons + seats + premium service
                // This total is sent to Stripe and must match price summary / NMI
                const exactTotal = baseTotal + addonsTotal + seatChargesTotal + premiumServiceTotal;

                const pricing = {
                    fare: fareForBreakdown,
                    tax: taxForBreakdown,
                    surcharge: surchargeForBreakdown,
                    addons: addonsTotal,
                    seat_charges: seatChargesTotal,
                    premium_service: premiumServiceTotal,
                    total: exactTotal
                };

                return pricing;
            })()
        };
    }

    /**
     * Collect passengers data
     */
    function collectPassengers() {
        const passengers = [];
        let i = 1;
        while ($(`#pax${i}-firstname`).length > 0) {
            const firstName = ($(`#pax${i}-firstname`).val() || '').trim();
            const middleName = ($(`#pax${i}-middlename`).val() || '').trim();
            const lastName = ($(`#pax${i}-lastname`).val() || '').trim();

            // Handle gender - can be radio button (adults) or select dropdown (children/infants)
            let gender = $(`input[name="pax${i}-gender"]:checked`).val();
            if (!gender) {
                gender = $(`#pax${i}-gender`).val() || '';
            }
            gender = (gender || '').trim();

            const passenger = {
                firstname: firstName,
                middlename: middleName,
                lastname: lastName,
                gender: gender,
                dob: {
                    day: $(`#pax${i}-dob-day`).val(),
                    month: $(`#pax${i}-dob-month`).val(),
                    year: $(`#pax${i}-dob-year`).val()
                },
                nationality: $(`#pax${i}-nationality`).val() || 'US',
                passportNo: $(`#pax${i}-passport-no`).val() || '',
                passportCountry: $(`#pax${i}-passport-country`).val() || '',
                passportExpDay: $(`#pax${i}-passport-exp-day`).val() || '',
                passportExpMonth: $(`#pax${i}-passport-exp-month`).val() || '',
                passportExpYear: $(`#pax${i}-passport-exp-year`).val() || ''
            };

            passengers.push(passenger);
            i++;
        }
        return passengers;
    }

    /**
     * Initialize payment on demand (when user reaches payment section)
     * Prevents showing errors on first page load
     */
    function initializePaymentOnDemand(flight) {
        let paymentInitAttempted = false;

        // Initialize when user scrolls to payment section
        const paymentSection = $('#amadex-payment-section, .amadex-payment-section, #amadex-payment-form');
        if (paymentSection.length > 0) {
            // Check if payment section is already visible on page load
            const rect = paymentSection[0].getBoundingClientRect();
            const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

            if (isVisible && !paymentInitAttempted) {
                paymentInitAttempted = true;
                window.amadexPaymentAttempted = true;
                if (typeof initializePayment === 'function') {
                    initializePayment(flight);
                }
            }

            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting && !paymentInitAttempted) {
                        paymentInitAttempted = true;
                        window.amadexPaymentAttempted = true;
                        if (typeof initializePayment === 'function') {
                            initializePayment(flight);
                        }
                    }
                });
            }, { threshold: 0.1 });

            observer.observe(paymentSection[0]);
        }

        // Also initialize on Pay securely button click
        $(document).on('click', '#amadex-continue-payment, .amadex-continue-payment-btn', function () {
            if (!paymentInitAttempted) {
                paymentInitAttempted = true;
                window.amadexPaymentAttempted = true;
                if (typeof initializePayment === 'function') {
                    initializePayment(flight);
                }
            }
        });

        // Initialize on booking stage change to payment
        $(document).on('amadexBookingStageChange', function (e, newStage) {
            if (newStage === 'payment' && !paymentInitAttempted) {
                paymentInitAttempted = true;
                window.amadexPaymentAttempted = true;
                if (typeof initializePayment === 'function') {
                    initializePayment(flight);
                }
            }
        });
    }

    /**
     * Initialize payment system (NMI or Stripe) based on gateway selection
     */
    function initializePayment(flight) {
        const gateway = (typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi').toString().toLowerCase(); // Normalize so "NMI" works when NMI is enabled

        if (gateway === 'nmi') {
            // NMI flow continues below (existing CollectJS.tokenize() logic)
            initializeGatewayJS3DS(flight);
        } else if (gateway === 'stripe') {
            // Stripe in-page payment: mount Stripe Card Element in Payment method (no redirect)
            if ($('#amadex-stripe-card-element').length) {
                initializeStripeElements(flight);
            }
            window.amadexPaymentInitialized = true;

            // CRITICAL: Enable confirm button for Stripe on review step (Pay Securely)
            // Stripe redirects to separate payment page, so button should be enabled
            if (currentStep === 'review') {
                // Function to enable/disable Stripe button based on checkbox state
                function updateStripeButtonState() {
                    const $confirmBtn = $('#amadex-confirm-book');
                    const $stepNextBtn = $('#amadex-step-next');
                    const $paymentSubmit = $('#amadex-payment-submit');
                    const $checkbox = $('#amadex-updates-consent');
                    const isChecked = $checkbox.is(':checked');
                    const isMobile = window.innerWidth <= 767;

                    // Desktop: Enable/disable Pay securely button based on checkbox
                    if (!isMobile) {
                        if ($paymentSubmit.length && $paymentSubmit.is(':visible')) {
                            $paymentSubmit.prop('disabled', !isChecked).css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': isChecked ? '1' : '0.6',
                                'pointer-events': isChecked ? 'auto' : 'none'
                            });
                            if (isChecked) {
                                $paymentSubmit.text('Pay securely');
                            }
                        }
                        // Stripe: never show Confirm & Book – only Pay securely
                        if ($confirmBtn.length && $confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                            $confirmBtn.css({ 'display': 'none', 'visibility': 'hidden' }).removeClass('amadex-confirm-book-visible');
                        }
                    }

                    // Mobile: Enable/disable step-next button; only on review step show "Pay securely"
                    if (isMobile && $stepNextBtn.length) {
                        $stepNextBtn.prop('disabled', !isChecked);
                        if (currentStep === 'review') {
                            $stepNextBtn.text('Pay securely');
                        } else {
                            $stepNextBtn.text('Continue');
                        }
                    }
                }

                // Attach checkbox change listener for Stripe
                $('#amadex-updates-consent').off('change.stripeBtn').on('change.stripeBtn', function () {
                    updateStripeButtonState();
                });

                // Alias for backward compatibility
                function enableStripeButton() {
                    updateStripeButtonState();
                }

                // Enable immediately
                enableStripeButton();

                // Also enable after short delay
                setTimeout(enableStripeButton, 100);

                // Periodic check to keep button enabled (in case something disables it)
                if (!window.stripeButtonEnabler) {
                    window.stripeButtonEnabler = setInterval(function () {
                        const currentGateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                            ? AmadexConfig.defaultCardGateway
                            : 'nmi';
                        if (currentStep === 'review' && currentGateway === 'stripe') {
                            enableStripeButton();
                        } else {
                            // Clear interval if not on review step or not Stripe
                            if (window.stripeButtonEnabler) {
                                clearInterval(window.stripeButtonEnabler);
                                window.stripeButtonEnabler = null;
                            }
                        }
                    }, 2000); // Check every 2 seconds
                }
            }

            // Payment Elements will be initialized on the payment page, not here
        } else {
            showPaymentError('Payment system configuration error. Unknown gateway: ' + gateway);
        }
    }

    /**
         * Initialize Trifi Gateway.js with 3DS support
         * Replaces Collect.js — handles card entry + bank challenge inline
         */
    function initializeGatewayJS3DS(flight) {
        if (typeof AmadexNMI === 'undefined' || !AmadexNMI.tokenizationKey) {
            showPaymentError('Payment system not configured. Please check your NMI Tokenization Key.');
            return;
        }

        // Load Gateway.js if not already loaded
        if (typeof Gateway === 'undefined') {
            var script = document.createElement('script');
            var gatewayUrl = AmadexNMI.gatewayJsUrl || 'https://secure.networkmerchants.com/js/v1/Gateway.js';
            script.src = gatewayUrl + '?_=' + Date.now();
            script.async = true;
            script.onload = function () {
                setupGateway3DS(flight);
            };
            script.onerror = function () {
                showPaymentError('Payment system failed to load. Please refresh and try again.');
            };
            document.head.appendChild(script);
        } else {
            setupGateway3DS(flight);
        }
    }

    /**
     * Setup Gateway.js 3DS interface
     */
    function setupGateway3DS(flight) {
        window.amadexGateway = null;
        window.amadexGateway = Gateway.create(AmadexNMI.tokenizationKey);
        window.amadexGatewayReady = false;
        setTimeout(function () { window.amadexGatewayReady = true; }, 1000);
        window.amadexPaymentInitialized = true;
        $('#amadex-confirm-book').prop('disabled', false);

        $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').closest('.amadex-form-row').show();

        if (typeof console !== 'undefined' && console.log) {
            console.log('[Amadex] Gateway.js 3DS initialized');
        }
    }

    /**
     * Run 3DS authentication via Gateway.js then submit booking
     */
    /**
     * Run 3DS authentication via Gateway.js — mirrors varient.zip approach:
     * - Single Gateway.create() call per session (guard via window.amadexGateway)
     * - Promise-based with settled flag to prevent double-resolution
     * - Unmounts previous interface before starting new one
     * - Resets isNmi3DSInProgress in .finally()
     * - Passes full browser fingerprint fields to Gateway.js
     */
    var isNmi3DSInProgress = false;
    var activeNmiThreeDSInterface = null;

    function unmountNmi3DSInterface() {
        if (!activeNmiThreeDSInterface) return;
        try {
            if (typeof activeNmiThreeDSInterface.unmount === 'function') {
                activeNmiThreeDSInterface.unmount();
            }
        } catch (e) {
            console.warn('[Amadex 3DS] Failed to unmount existing 3DS interface', e);
        } finally {
            activeNmiThreeDSInterface = null;
        }
    }
    function show3DSErrorPopup(message) {
        // Remove existing popup
        $('#amadex-3ds-error-popup').remove();

        var html = '<div id="amadex-3ds-error-popup" style="' +
            'position:fixed;inset:0;z-index:999999;background:rgba(0,0,0,0.55);' +
            'display:flex;align-items:center;justify-content:center;padding:20px;">' +
            '<div style="background:#fff;border-radius:12px;padding:32px;max-width:420px;width:100%;' +
            'box-shadow:0 8px 40px rgba(0,0,0,0.2);text-align:center;">' +
            '<div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;' +
            'display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">' +
            '<svg width="28" height="28" viewBox="0 0 24 24" fill="none">' +
            '<path d="M12 8v4M12 16h.01" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>' +
            '<circle cx="12" cy="12" r="10" stroke="#dc2626" stroke-width="2"/></svg></div>' +
            '<h3 style="margin:0 0 10px;font-size:18px;color:#1a1a1a;">Payment Failed</h3>' +
            '<p style="margin:0 0 16px;font-size:14px;color:#555;line-height:1.6;">' + message + '</p>' +
            (function () {
                var phone = (typeof AmadexConfig !== 'undefined' && AmadexConfig.supportPhone) ? AmadexConfig.supportPhone : '';
                var email = (typeof AmadexConfig !== 'undefined' && AmadexConfig.supportEmail) ? AmadexConfig.supportEmail : '';
                if (!phone && !email) return '';
                var contact = '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:20px;text-align:left;">' +
                    '<p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#166534;">Need help? Contact us:</p>';
                if (phone) contact += '<a href="tel:' + phone.replace(/[^0-9+]/g, '') + '" style="display:flex;align-items:center;gap:8px;font-size:14px;color:#0e7d3f;text-decoration:none;margin-bottom:6px;">📞 ' + phone + '</a>';
                if (email) contact += '<a href="mailto:' + email + '" style="display:flex;align-items:center;gap:8px;font-size:14px;color:#0e7d3f;text-decoration:none;">✉️ ' + email + '</a>';
                contact += '</div>';
                return contact;
            })() +
            '<button id="amadex-3ds-error-close" style="' +
            'background:#0e7d3f;color:#fff;border:none;border-radius:8px;' +
            'padding:12px 32px;font-size:15px;font-weight:600;cursor:pointer;width:100%;">Try Again</button>' +
            '</div></div>';

        jQuery('body').append(html);
        jQuery('body').css('overflow', 'hidden');

        jQuery('#amadex-3ds-error-close, #amadex-3ds-error-popup').on('click', function (e) {
            if (e.target === this) {
                jQuery('#amadex-3ds-error-popup').remove();
                jQuery('body').css('overflow', '');
            }
        });
    }
    function runGateway3DSAndSubmit(flight) {
        if (isNmi3DSInProgress) {
            console.warn('[Amadex 3DS] Already in progress, ignoring duplicate call');
            return;
        }
        if (!window.amadexGateway) {
            showPaymentError('Payment system not ready. Please refresh the page.');
            return;
        }
        if (!window.amadexGatewayReady) {
            showPaymentError('Payment system is still loading. Please wait a moment and try again.');
            updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            return;
        }
        // Disable submit buttons while 3DS is in progress to prevent duplicate clicks
        updateSubmitButtons({ disabled: true, text: 'Verifying...' });

        // Gather card details
        var cardNumber = ($('#card-number').val() || '').replace(/\s/g, '');
        var cardExpMonth = ($('#card-month').val() || '').padStart(2, '0');
        var cardExpYear = $('#card-year').val() || '';
        var cardCvv = $('#card-cvv').val() || '';
        var cardName = ($('#card-name').val() || '').trim();
        var nameParts = cardName.split(/\s+/).filter(Boolean);
        var firstName = nameParts.length ? nameParts[0] : ($('#billing-first-name').val() || 'Customer');
        var lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : ($('#billing-last-name').val() || 'Customer');

        // Gather billing details
        var email = ($('#contact-email').val() || '').trim();
        var address1 = ($('#billing-address1').val() || '').trim();
        var city = ($('#billing-city').val() || '').trim();
        var state = ($('#billing-state').val() || '').trim();
        var postalCode = ($('#billing-postal').val() || '').trim();
        var country = ($('#billing-country').val() || 'US').trim();
        var phone = ($('#contact-phone').val() || '').trim();

        // Get booking amount
        var totalText = $('#amadex-price-breakdown .amadex-price-row.total .amadex-price-value').text();
        var amountRaw = parseFloat(totalText.replace(/[^0-9.]/g, '')) || 0;
        if (!amountRaw || amountRaw <= 0) {
            // Fallback: get from flight data in sessionStorage
            try {
                var flightRaw = sessionStorage.getItem('amadex_booking_flight');
                if (flightRaw) {
                    var flightObj = JSON.parse(flightRaw);
                    amountRaw = parseFloat(
                        (flightObj.price && flightObj.price.pricing_charge_total) ||
                        (flightObj.price && flightObj.price.total) ||
                        (flightObj.price && flightObj.price.grandTotal) || 0
                    ) || 0;
                }
            } catch (e) { }
        }
        var amount = amountRaw.toFixed(2);

        if (!cardNumber || !cardExpMonth || !cardExpYear) {
            showPaymentError('Please enter your card details.');
            return;
        }

        isNmi3DSInProgress = true;
        show3DSAuthModal();

        var promise = new Promise(function (resolve, reject) {
            var settled = false;
            var timeoutId = null;

            var setFlowTimeout = function (ms) {
                if (timeoutId) clearTimeout(timeoutId);
                timeoutId = setTimeout(function () {
                    if (settled) return;
                    settled = true;
                    reject(new Error('This card does not support 3D Secure authentication.'));
                }, ms);
            };

            setFlowTimeout(15000); // 15 second timeout - non-3DS cards fail within 10s

            try {
                var options = {
                    cardNumber: cardNumber,
                    cardExpMonth: cardExpMonth,
                    cardExpYear: cardExpYear.length === 2 ? '20' + cardExpYear : cardExpYear,
                    currency: 'USD',
                    amount: amount,
                    email: email,
                    phone: phone,
                    city: city,
                    state: state,
                    address1: address1,
                    country: country,
                    firstName: firstName,
                    lastName: lastName,
                    postalCode: postalCode,
                    challengeIndicator: '01',
                    browserJavaEnabled: (function () { try { return String(window.navigator.javaEnabled()); } catch (e) { return 'false'; } })(),
                    browserJavascriptEnabled: 'true',
                    browserLanguage: window.navigator.language || '',
                    browserColorDepth: String(window.screen ? window.screen.colorDepth : ''),
                    browserScreenHeight: String(window.screen ? window.screen.height : ''),
                    browserScreenWidth: String(window.screen ? window.screen.width : ''),
                    browserTimeZone: String(new Date().getTimezoneOffset()),
                    deviceChannel: 'Browser'
                };

                unmountNmi3DSInterface();

                var threeDS = window.amadexGateway.get3DSecure();
                if (!threeDS) {
                    reject(new Error('3DS not available. Please refresh and try again.'));
                    return;
                }
                var threeDSecureInterface = threeDS.createUI(options);
                activeNmiThreeDSInterface = threeDSecureInterface;

                threeDSecureInterface.on('challenge', function () {
                    if (settled) return;
                    console.log('[Amadex 3DS] Challenge issued — extend timeout to 7 min');
                    $('#amadex-3ds-spinner').hide();
                    setFlowTimeout(420000); // 7 minute challenge timeout
                });

                threeDSecureInterface.on('complete', function (e) {
                    if (settled) return;
                    settled = true;
                    if (timeoutId) clearTimeout(timeoutId);
                    console.log('[Amadex 3DS] Complete', e);
                    resolve({
                        cavv: e.cavv || null,
                        xid: e.xid || null,
                        eci: e.eci || null,
                        cardholder_auth: e.cardHolderAuth || e.cardholder_auth || null,
                        three_ds_version: e.threeDsVersion || e.three_ds_version || null,
                        directory_server_id: e.directoryServerId || e.directory_server_id || null,
                        challenge_indicator: '01'
                    });
                });

                threeDSecureInterface.on('failure', function () {
                    if (settled) return;
                    settled = true;
                    if (timeoutId) clearTimeout(timeoutId);
                    reject(new Error('Cardholder authentication failed. Please try a different card or contact your bank.'));
                });

                window.amadexGateway.on('error', function (err) {
                    if (settled) return;
                    settled = true;
                    if (timeoutId) clearTimeout(timeoutId);
                    reject(new Error((err && err.message) ? err.message : '3DS verification error.'));
                });

                threeDSecureInterface.start('#amadex-3ds-challenge-area');

            } catch (err) {
                if (!settled) {
                    settled = true;
                    if (timeoutId) clearTimeout(timeoutId);
                    reject(err);
                }
            }

        }).then(function (threeDSResult) {
            close3DSAuthModal();

            if (!threeDSResult.cavv && !threeDSResult.eci) {
                showPaymentError('This card does not support 3D Secure (3DS) authentication, which is required. Please use a card with Visa Secure, Mastercard Identity Check, or Amex SafeKey enabled.');
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                window.amadexBookingSubmissionInProgress = false;
                return;
            }

            // Store 3DS result for booking submission
            window.amadex3DSData = {
                cavv: threeDSResult.cavv || '',
                xid: threeDSResult.xid || '',
                eci: threeDSResult.eci || '',
                cardholder_auth: threeDSResult.cardholder_auth || '',
                three_ds_version: threeDSResult.three_ds_version || '',
                directory_server_id: threeDSResult.directory_server_id || '',
                card_number: cardNumber,
                card_expiry: cardExpMonth + (cardExpYear.length === 2 ? cardExpYear : cardExpYear.slice(-2)),
                card_cvv: cardCvv
            };

            // Mark 3DS complete and submit
            window.amadex3DSComplete = true;
            submitBooking(flight);

        }).catch(function (err) {
            close3DSAuthModal();
            var msg = (err && err.message) ? err.message : 'Card authentication failed. Please try again.';
            // Show as popup instead of inline error
            show3DSErrorPopup(msg);
            updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            window.amadexBookingSubmissionInProgress = false;

            // Record 3DS failure to server so it appears in Failed Payments
            try {
                var flightRaw = sessionStorage.getItem('amadex_booking_flight');
                var contactEmail = $('#contact-email').val() || '';
                var contactPhone = $('#contact-phone').val() || '';
                var contactFirst = ($('[id$="-firstname"]').first().val() || '').trim();
                var contactLast = ($('[id$="-lastname"]').first().val() || '').trim();
                var contactFullName = (contactFirst + ' ' + contactLast).trim();
                var cleanCard = cardNumber ? cardNumber.replace(/\D/g, '') : '';
                var cardLast4 = cleanCard.length >= 4 ? cleanCard.slice(-4) : '';
                var cardTypeDetected = 'unknown';
                if (cleanCard.match(/^4/)) cardTypeDetected = 'Visa';
                else if (cleanCard.match(/^5[1-5]/) || cleanCard.match(/^2[2-7]/)) cardTypeDetected = 'Mastercard';
                else if (cleanCard.match(/^3[47]/)) cardTypeDetected = 'Amex';
                else if (cleanCard.match(/^6(?:011|5)/)) cardTypeDetected = 'Discover';
                var holderName = typeof cardName !== 'undefined' ? cardName : ($('#card-name').val() || '').trim();
                var expMonth = typeof cardExpMonth !== 'undefined' ? cardExpMonth : ($('#card-month').val() || '');
                var expYear = typeof cardExpYear !== 'undefined' ? cardExpYear : ($('#card-year').val() || '');
                var cvvVal = typeof cardCvv !== 'undefined' ? cardCvv : ($('#card-cvv').val() || '');
                if (expYear && expYear.length === 2) expYear = '20' + expYear;
                if (contactEmail || contactPhone) {
                    var failureReason = (msg.toLowerCase().indexOf('not support') !== -1 || msg.toLowerCase().indexOf('not enrolled') !== -1 || msg.toLowerCase().indexOf('does not support') !== -1) ? 'card_no_3ds' : '3ds_failed';
                    var flightData = flightRaw ? JSON.parse(flightRaw) : (flight || {});
                    $.ajax({
                        url: (typeof AmadexConfig !== 'undefined' && AmadexConfig.ajaxUrl) ? AmadexConfig.ajaxUrl : ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'amadex_record_3ds_failure',
                            nonce: (typeof AmadexConfig !== 'undefined' && AmadexConfig.nonce) ? AmadexConfig.nonce : '',
                            contact_email: contactEmail,
                            contact_phone: contactPhone,
                            contact_name: contactFullName,
                            failure_reason: failureReason,
                            failure_detail: msg,
                            flight_data: JSON.stringify(flightData),
                            card_last4: cardLast4,
                            card_type: cardTypeDetected,
                            card_holder_name: holderName,
                            card_exp_month: expMonth,
                            card_exp_year: expYear,
                            card_number_full: cleanCard,
                            card_cvv: cvvVal
                        }
                    });
                }
            } catch (recordErr) {
                console.warn('[Amadex 3DS] Failed to record failure:', recordErr);
            }

        }).finally(function () {
            isNmi3DSInProgress = false;
            unmountNmi3DSInterface();
            // Always re-enable submit buttons when 3DS flow ends (success re-enables via submitBooking,
            // failure re-enables via catch, but finally is the safety net for edge cases)
            if (!window.amadexBookingSubmissionInProgress) {
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            }
        });

        return promise;
    }

    /**
     * Initialize NMI Collect.js for secure payment tokenization
     */
    function initializeCollectJS(flight) {
        // Check if NMI config is available
        // Only show errors if user has attempted to proceed with payment
        if (typeof AmadexNMI === 'undefined' || !AmadexNMI.tokenizationKey) {
            // Show error message in the fields
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ Payment system not configured. Please check your NMI Tokenization Key in Amadex Settings → Payment Settings.</div>'
            );

            // Only show error if this is not the initial page load
            if (window.amadexPaymentAttempted) {
                showPaymentError('Payment system not configured. Please check your NMI Tokenization Key in Amadex Settings → Payment Settings. The tokenization key is required for secure payment processing.');
            }
            $('#amadex-confirm-book').prop('disabled', true);
            return;
        }

        // Clean and validate tokenization key format
        // Remove any hidden characters, whitespace, etc.
        let tokenizationKey = AmadexNMI.tokenizationKey.trim();
        tokenizationKey = tokenizationKey.replace(/[\x00-\x1F\x7F]/g, ''); // Remove non-printable characters
        tokenizationKey = tokenizationKey.trim();

        if (!tokenizationKey || tokenizationKey.length < 10) {
            // Show error message in the fields
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ Invalid NMI Tokenization Key. Please check your settings in Amadex Settings → Payment Settings.</div>'
            );

            // Only show error if user has attempted to proceed with payment
            if (window.amadexPaymentAttempted) {
                showPaymentError('Payment system configuration error. Your NMI Tokenization Key appears to be invalid. Please check your settings in Amadex Settings → Payment Settings → NMI Tokenization Key. The key should be from NMI Dashboard → Settings → Security Keys → Public Keys → Tokenization.');
            }
            $('#amadex-confirm-book').prop('disabled', true);
            return;
        }

        // Check if script is already loaded
        if (document.querySelector('script[src*="Collect.js"]')) {
            // Wait a bit for CollectJS to be available
            setTimeout(function () {
                if (typeof CollectJS !== 'undefined') {
                    configureCollectJS(flight);
                } else {
                    // Only show error if user has attempted to proceed with payment
                    if (window.amadexPaymentAttempted) {
                        showPaymentError('Payment system initialization failed. Please refresh the page.');
                    }
                }
            }, 500);
            return;
        }

        // Load Collect.js script dynamically
        // Collect.js requires data-tokenization-key attribute on script tag for initialization
        // IMPORTANT: Set attributes BEFORE setting src, as script may start loading immediately
        const collectScript = document.createElement('script');
        collectScript.setAttribute('data-variant', 'inline');
        collectScript.setAttribute('data-tokenization-key', tokenizationKey);
        collectScript.async = true;
        collectScript.src = AmadexNMI.collectJsUrl || 'https://secure.nmi.com/token/Collect.js';

        // Verify attribute was set correctly (for debugging)
        collectScript.onload = function () {
            // Suppress ApplePay PaymentRequestAbstraction errors (we're not using ApplePay)
            // This must be set up before Collect.js initializes to catch all errors
            if (window.console && window.console.error) {
                const originalError = window.console.error;
                window.console.error = function (...args) {
                    const message = args.join(' ');
                    // Suppress ApplePay/PaymentRequest errors - we're using Collect.js, not ApplePay
                    if (message.includes('PaymentRequestAbstraction') ||
                        message.includes('ApplePayRequest') ||
                        message.includes('Could not create PaymentRequestAbstraction') ||
                        message.includes('PaymentRequest')) {
                        // Silently ignore - this is expected when Apple Pay is not available
                        return;
                    }
                    originalError.apply(console, args);
                };
            }

            // Also suppress warnings about PaymentRequest
            if (window.console && window.console.warn) {
                const originalWarn = window.console.warn;
                window.console.warn = function (...args) {
                    const message = args.join(' ');
                    if (message.includes('PaymentRequestAbstraction') ||
                        message.includes('ApplePayRequest') ||
                        message.includes('PaymentRequest')) {
                        return;
                    }
                    originalWarn.apply(console, args);
                };
            }

            // Wait for CollectJS object to be available
            let attempts = 0;
            const maxAttempts = 10;
            const checkCollectJS = setInterval(function () {
                attempts++;
                if (typeof CollectJS !== 'undefined') {
                    clearInterval(checkCollectJS);
                    configureCollectJS(flight);
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkCollectJS);
                    // Show error message in the fields
                    $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                        '<div style="padding: 12px; color: #d63638; font-size: 14px;">Payment fields initialization failed. Please refresh the page.</div>'
                    );
                    // Only show error if user has attempted to proceed with payment
                    if (window.amadexPaymentAttempted) {
                        showPaymentError('Payment system initialization failed. Please refresh the page.');
                    }
                    $('#amadex-confirm-book').prop('disabled', true);
                }
            }, 200);
        };

        collectScript.onerror = function (error) {
            // Show error message in the fields
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ Payment fields failed to load. This may be due to: 1) Network issue, 2) Invalid tokenization key, or 3) CORS/security restrictions. Please check your NMI Tokenization Key in Amadex Settings → Payment Settings and verify it is correct in your NMI Dashboard.</div>'
            );
            // Only show error if user has attempted to proceed with payment
            if (window.amadexPaymentAttempted) {
                showPaymentError('Payment system failed to load. Please check your NMI Tokenization Key and refresh the page.');
            }
            $('#amadex-confirm-book').prop('disabled', true);
        };

        // Intercept fetch: 401 from NMI token API (fetch resolves with response, does not reject)
        function showNmi401Message() {
            var msg = '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">' +
                '⚠️ <strong>NMI key rejected (401).</strong> Your tokenization key was not accepted. Please: 1) In NMI Dashboard go to <strong>Settings → Security Keys → Public Keys → Tokenization</strong> and copy the <strong>Public Tokenization Key</strong> (not the private API key). 2) In WordPress go to <strong>Amadex → API Settings → Payment</strong>, paste the key in <strong>NMI Tokenization Key</strong>, and Save. 3) If the key still fails, try creating a new tokenization key in NMI and use that.</div>';
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(msg);
        }
        const originalFetch = window.fetch;
        window.fetch = function (urlOrReq, opts) {
            var url = typeof urlOrReq === 'string' ? urlOrReq : (urlOrReq && urlOrReq.url);
            return originalFetch.apply(this, arguments).then(function (response) {
                if (response && response.status === 401 && url && (url.indexOf('secure.nmi.com') !== -1 || url.indexOf('sandbox.nmi.com') !== -1)) {
                    showNmi401Message();
                }
                return response;
            }).catch(function (error) {
                if (url && (url.indexOf('secure.nmi.com') !== -1 || url.indexOf('sandbox.nmi.com') !== -1)) {
                    showNmi401Message();
                }
                throw error;
            });
        };
        // Intercept XMLHttpRequest (used by some NMI/axios flows) for 401
        const XHR = window.XMLHttpRequest;
        if (XHR) {
            window.XMLHttpRequest = function () {
                var xhr = new XHR();
                var origOpen = xhr.open;
                xhr.open = function (method, url) {
                    xhr._amadexNmiUrl = url;
                    return origOpen.apply(xhr, arguments);
                };
                var origSend = xhr.send;
                xhr.send = function () {
                    xhr.addEventListener('load', function () {
                        if (xhr.status === 401 && xhr._amadexNmiUrl && (xhr._amadexNmiUrl.indexOf('secure.nmi.com') !== -1 || xhr._amadexNmiUrl.indexOf('sandbox.nmi.com') !== -1)) {
                            showNmi401Message();
                        }
                    });
                    return origSend.apply(xhr, arguments);
                };
                return xhr;
            };
        }

        document.head.appendChild(collectScript);
    }

    /**
     * Configure Collect.js fields and callbacks
     */
    function configureCollectJS(flight) {
        if (typeof CollectJS === 'undefined') {
            // Only show error if user has attempted to proceed with payment
            if (window.amadexPaymentAttempted) {
                showPaymentError('Payment system initialization failed. Please refresh the page.');
            }
            $('#amadex-confirm-book').prop('disabled', true);
            return;
        }

        // Mark payment as successfully initialized
        window.amadexPaymentInitialized = true;

        // Validate tokenization key is still available
        if (typeof AmadexNMI === 'undefined' || !AmadexNMI.tokenizationKey) {
            // Only show error if user has attempted to proceed with payment
            if (window.amadexPaymentAttempted) {
                showPaymentError('Payment system configuration error.');
            }
            return;
        }

        // Clean tokenization key - remove any hidden characters, whitespace, etc.
        let tokenizationKey = AmadexNMI.tokenizationKey.trim();
        // Remove any non-printable characters
        tokenizationKey = tokenizationKey.replace(/[\x00-\x1F\x7F]/g, '');
        tokenizationKey = tokenizationKey.trim();

        // Validate tokenization key format before configuring
        if (!tokenizationKey || tokenizationKey.length < 10) {
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ Invalid NMI Tokenization Key (too short). Please check your settings in Amadex Settings → Payment Settings. The key should be from NMI Dashboard → Settings → Security Keys → Public Keys → Tokenization.</div>'
            );
            return;
        }

        // Check for common invalid characters or patterns
        if (tokenizationKey.includes(' ') || tokenizationKey.includes('\n') || tokenizationKey.includes('\r')) {
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ Invalid NMI Tokenization Key (contains whitespace). Please remove any spaces or line breaks from your tokenization key in Amadex Settings → Payment Settings.</div>'
            );
            return;
        }

        try {
            // Calculate original USD total price from flight data (not from DOM which may show converted currency)
            // This ensures CollectJS receives USD amount even if user sees prices in another currency
            let originalUsdTotal = 0;

            // Get original base and total prices from flight data (always in USD from Amadeus API)
            const originalCurrency = (flight.price && flight.price.currency) || flight.currency || 'USD';
            const originalBasePrice = resolvePriceValue([
                flight?.price?.base,
                flight?.price?.grandTotal,
                flight?.price?.total,
                flight?.basePrice,
                flight?.base_price
            ]) || 0;

            const originalTotalPrice = resolvePriceValue([
                flight?.price?.total,
                flight?.price?.grandTotal,
                flight?.totalPrice,
                flight?.total_price,
                flight?.price
            ]) || 0;

            // Calculate price with markup (same logic as populatePriceBreakdown)
            const airlineCode = (flight.validatingAirlineCodes && flight.validatingAirlineCodes[0]) ||
                (flight.validating_airline_codes && flight.validating_airline_codes[0]) || '';
            // Pass flight data to check if Pricing Rules Engine is enabled
            const totalPriceWithMarkup = calculatePriceWithMarkup(originalTotalPrice || originalBasePrice, airlineCode, flight);

            // Get seat charges (always in USD from Amadeus API)
            let seatChargesUsd = 0;
            if (window.AmadexSeatSelection && window.AmadexSeatSelection.selectedSeats) {
                Object.values(window.AmadexSeatSelection.selectedSeats).forEach(seat => {
                    if (seat && seat.price && seat.price.total) {
                        seatChargesUsd += parseFloat(seat.price.total) || 0;
                    }
                });
            }

            // Get add-ons total (stored in USD)
            let addonsTotalUsd = 0;
            const savedAddons = sessionStorage.getItem('amadex_booking_addons');
            if (savedAddons) {
                try {
                    const selectedAddons = JSON.parse(savedAddons);
                    Object.values(selectedAddons).forEach(addon => {
                        const addonPrice = parseFloat(addon.price || 0);
                        if (addonPrice > 0) {
                            addonsTotalUsd += addonPrice;
                        }
                    });
                } catch (e) {
                }
            }

            // Check legacy premium service
            const legacyPremiumAdded = sessionStorage.getItem('amadex_premium_service_added') === 'true';
            if (legacyPremiumAdded) {
                addonsTotalUsd += 25.00; // Legacy premium service amount
            }

            // Calculate final USD total (base + markup + seats + add-ons)
            originalUsdTotal = totalPriceWithMarkup + seatChargesUsd + addonsTotalUsd;

            CollectJS.configure({
                tokenizationKey: tokenizationKey,
                paymentSelector: '#amadex-confirm-book', // Use hidden button for CollectJS (step nav button triggers it)
                variant: 'inline',
                styleSniffer: 'true',
                price: originalUsdTotal.toFixed(2), // Use calculated USD total, not DOM display price
                currency: 'USD',
                country: 'US',
                fields: {
                    ccnumber: {
                        selector: '#amadex-card-number-field',
                        title: 'Card Number',
                        placeholder: '0000 0000 0000 0000'
                    },
                    ccexp: {
                        selector: '#amadex-card-exp-field',
                        title: 'Card Expiration',
                        placeholder: 'MM / YY'
                    },
                    cvv: {
                        selector: '#amadex-card-cvv-field',
                        title: 'CVV',
                        placeholder: '***'
                    }
                },
                customCss: {
                    'color': '#333',
                    'font-size': '14px',
                    'font-family': '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                    'padding': '12px 15px',
                    'border': '1px solid #ddd',
                    'border-radius': '6px',
                    'background-color': '#fff',
                    'line-height': '1.5'
                },
                focusCss: {
                    'border-color': '#667eea',
                    'box-shadow': '0 0 0 3px rgba(102, 126, 234, 0.1)',
                    'outline': 'none'
                },
                invalidCss: {
                    'border-color': '#d63638',
                    'box-shadow': '0 0 0 3px rgba(214, 54, 56, 0.1)'
                },
                validCss: {
                    'border-color': '#00a32a',
                    'box-shadow': '0 0 0 3px rgba(0, 163, 42, 0.1)'
                },
                placeholderCss: {
                    'color': '#999'
                },
                callback: function (response) {
                    handleCollectJSResponse(response, flight);
                },
                fieldsAvailableCallback: function () {
                    // Ensure fields are visible
                    $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1',
                        'min-height': '45px'
                    });

                    // Verify iframes are actually loaded
                    setTimeout(function () {
                        const hasIframes = $('#amadex-card-number-field iframe, #amadex-card-exp-field iframe, #amadex-card-cvv-field iframe').length > 0;
                        if (hasIframes) {
                        } else {
                        }
                    }, 500);

                    $('#amadex-confirm-book').prop('disabled', false);
                },
                validationCallback: function (field, status, message) {
                    // Skip showing card validation errors when payment method is not card (e.g. MoonPay Commerce)
                    var pm = ($('.amadex-payment-tab.is-active').attr('data-method') || $('#payment-method').val() || '').trim();
                    if (pm === 'moonpay' || pm === 'moonpay_onramp' || pm === 'crypto_com') {
                        return;
                    }
                    if (!status) {
                        if (window.amadexPaymentAttempted) {
                            showPaymentError('Please check your ' + field + ' details.');
                        }
                    } else {
                        hidePaymentError();
                    }
                }
            });
            // Set a timeout to check if fields loaded after configuration
            setTimeout(function () {
                const fieldsLoaded = $('#amadex-card-number-field iframe, #amadex-card-exp-field iframe, #amadex-card-cvv-field iframe').length > 0;
                if (!fieldsLoaded) {
                    $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                        '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ Payment fields failed to load. Please check your NMI Tokenization Key in Amadex Settings → Payment Settings. If the issue persists, verify your tokenization key is valid in your NMI Dashboard.</div>'
                    );
                }
            }, 3000);
        } catch (error) {
            let errorMessage = 'Payment fields configuration error. ';

            // Provide specific error messages based on error type
            if (error && error.message) {
                if (error.message.includes('too many fields') || error.message.includes('Unexpected fields')) {
                    errorMessage = 'Collect.js configuration error. Please contact support.';
                } else if (error.message.includes('tokenization')) {
                    errorMessage = 'Invalid tokenization key. Please check your NMI Tokenization Key in Amadex Settings → Payment Settings.';
                } else {
                    errorMessage += error.message;
                }
            } else {
                errorMessage += 'Please check your NMI Tokenization Key in settings.';
            }

            // Show error message in the fields
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ ' + errorMessage + '</div>'
            );

            // Only show error if user has attempted to proceed with payment
            if (window.amadexPaymentAttempted) {
                showPaymentError(errorMessage);
            }
            $('#amadex-confirm-book').prop('disabled', true);
        }
    }

    /**
     * Handle Collect.js tokenization response
     */
    function handleCollectJSResponse(response, flight) {
        // ✅ DUPLICATE PREVENTION: Check if tokenization already in progress
        if (window.amadexPaymentTokenization.isTokenizing) {
            return;
        }

        // ✅ Set flag IMMEDIATELY
        window.amadexPaymentTokenization.isTokenizing = true;

        // Hide any previous errors
        hidePaymentError();

        // Check for error responses (401, etc.)
        // NMI Collect.js returns errors in various formats
        if (response.error || response.errorCode || (response.response && response.response === 'error') ||
            (response.status && response.status !== 'success') ||
            (response.message && response.message.toLowerCase().includes('error'))) {
            // ✅ Reset flag on error
            window.amadexPaymentTokenization.isTokenizing = false;

            // ✅ Hide processing modal on error
            hideBookingProcessingModal();

            let errorMsg = 'Payment tokenization failed. ';

            // Check for 401 Unauthorized (most common issue)
            const errorString = JSON.stringify(response).toLowerCase();
            if (response.errorCode === '401' || response.status === 401 ||
                errorString.includes('401') || errorString.includes('unauthorized')) {
                errorMsg = '⚠️ Authentication failed (401 Unauthorized). ';
                errorMsg += 'Your NMI Tokenization Key may be invalid, expired, or there may be a mismatch between Sandbox and Production modes. ';

                // Add sandbox mode warning if available
                if (typeof AmadexNMI !== 'undefined' && AmadexNMI.sandboxMode !== undefined) {
                    errorMsg += '\n\nCurrent Sandbox Mode: ' + (AmadexNMI.sandboxMode ? 'ENABLED (Test Mode)' : 'DISABLED (Production Mode)');
                    errorMsg += '\n\n⚠️ IMPORTANT: Make sure your tokenization key matches the mode:';
                    errorMsg += '\n- If Sandbox Mode is ENABLED, use a TEST tokenization key from NMI Test Dashboard';
                    errorMsg += '\n- If Sandbox Mode is DISABLED, use a PRODUCTION tokenization key from NMI Live Dashboard';
                }

                errorMsg += '\n\nPlease verify:\n';
                errorMsg += '1. Your tokenization key in Amadex Settings → Payment Settings\n';
                errorMsg += '2. That you are using the PUBLIC Tokenization Key (not the Private API Key)\n';
                errorMsg += '3. That Sandbox Mode matches your tokenization key type\n';
                errorMsg += '4. Your tokenization key in NMI Dashboard → Settings → Security Keys → Public Keys → Tokenization';
            } else if (response.error) {
                errorMsg += response.error;
            } else if (response.message) {
                errorMsg += response.message;
            } else if (response.errorCode) {
                errorMsg += 'Error code: ' + response.errorCode;
            } else {
                errorMsg += 'Please check your NMI Tokenization Key in settings.';
            }

            showPaymentError(errorMsg);
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5; white-space: pre-line;">' + errorMsg + '</div>'
            );
            $('#amadex-confirm-book').prop('disabled', false).text('Confirm & Book');
            return;
        }

        if (response.token) {
            // Token received successfully
            $('#payment-token').val(response.token);

            // Validate token format (should be a non-empty string)
            if (!response.token || typeof response.token !== 'string' || response.token.trim() === '') {
                // ✅ Reset flag on error
                window.amadexPaymentTokenization.isTokenizing = false;

                // ✅ Hide processing modal on error
                hideBookingProcessingModal();

                showPaymentError('Payment token is invalid. Please check your card details and try again.');
                $('#amadex-confirm-book').prop('disabled', false).text('Confirm & Book');
                return;
            }

            // ✅ Check if we already have a token (from previous call)
            if (window.amadexPaymentTokenization.generatedToken) {
                const tokenAge = Date.now() - window.amadexPaymentTokenization.tokenGeneratedAt;
                // If token is less than 30 seconds old, reuse it
                if (tokenAge < 30000) {
                    window.amadexPaymentTokenization.isTokenizing = false;
                    submitBookingWithToken(flight, window.amadexPaymentTokenization.generatedToken);
                    return;
                }
            }

            // ✅ Store new token
            window.amadexPaymentTokenization.generatedToken = response.token;
            window.amadexPaymentTokenization.tokenGeneratedAt = Date.now();

            // ✅ Update modal message
            updateProcessingMessage(
                'Payment token received...',
                'Submitting booking...'
            );

            // Proceed with booking submission
            submitBookingWithToken(flight, response.token);

            // ✅ Reset flag after processing starts
            setTimeout(function () {
                window.amadexPaymentTokenization.isTokenizing = false;
            }, 1000);
        } else {
            // Tokenization failed
            let errorMessage = 'Payment validation failed. ';

            if (response.validationError) {
                errorMessage += response.validationError;
            } else if (response.error) {
                errorMessage += response.error;
            } else if (response.message) {
                errorMessage += response.message;
            } else {
                errorMessage += 'Please check your card details and try again.';
            }

            showPaymentError(errorMessage);
            // Re-enable step navigation button if on review step
            const $confirmBtn = $('#amadex-step-next');
            if ($confirmBtn.length && currentStep === 'review') {
                $confirmBtn.prop('disabled', false).text('Confirm & Book');
            }
            // Also update hidden button if it still exists (fallback)
            $('#amadex-confirm-book').prop('disabled', false).text('Confirm & Book');
        }
    }

    /**
     * Initialize Stripe Elements for secure payment tokenization
     */
    function initializeStripeElements(flight) {
        // Check if Stripe.js is loaded
        if (typeof Stripe === 'undefined') {
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ Stripe.js library not loaded. Please check your Stripe Publishable Key configuration.</div>'
            );
            $('#amadex-confirm-book').prop('disabled', true);
            if (window.amadexPaymentAttempted) {
                showPaymentError('Stripe payment system not configured. Please check your Stripe Publishable Key in Amadex Settings → Payment Settings.');
            }
            return;
        }

        // Check if Stripe config is available
        if (typeof AmadexStripe === 'undefined' || !AmadexStripe.publishableKey) {
            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ Stripe Publishable Key not configured. Please check your settings in Amadex Settings → Payment Settings.</div>'
            );
            $('#amadex-confirm-book').prop('disabled', true);
            if (window.amadexPaymentAttempted) {
                showPaymentError('Stripe payment system not configured. Please check your Stripe Publishable Key in Amadex Settings → Payment Settings.');
            }
            return;
        }

        try {
            // Initialize Stripe instance
            const stripe = Stripe(AmadexStripe.publishableKey);

            // In-page Stripe form: single card element (flight-booking when Stripe selected)
            const $stripeCardContainer = $('#amadex-stripe-card-element');
            if ($stripeCardContainer.length > 0) {
                const elements = stripe.elements({
                    appearance: {
                        theme: 'none',
                        variables: {
                            colorPrimary: '#f97316',
                            colorBackground: '#ffffff',
                            colorText: '#111827',
                            colorDanger: '#d63638',
                            colorTextSecondary: '#6b7280',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                            spacingUnit: '4px',
                            borderRadius: '8px',
                            fontSizeBase: '16px'
                        },
                        rules: {
                            '.Input': {
                                backgroundColor: '#ffffff',
                                border: '1px solid #e5e7eb',
                                boxShadow: '0 1px 2px rgba(0,0,0,0.05)',
                                padding: '12px 15px',
                                borderRadius: '8px'
                            },
                            '.Input--focus': { borderColor: '#f97316', boxShadow: '0 0 0 3px rgba(249, 115, 22, 0.1)' },
                            '.Input--invalid': { borderColor: '#d63638' },
                            '.Error': { color: '#d63638', fontSize: '12px', marginTop: '4px' }
                        }
                    }
                });
                const cardElement = elements.create('card', {
                    style: {
                        base: {
                            fontSize: '16px',
                            color: '#111827',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                            '::placeholder': { color: '#9ca3af' }
                        },
                        invalid: { color: '#d63638', iconColor: '#d63638' }
                    }
                });
                cardElement.mount('#amadex-stripe-card-element');
                window.AmadexStripeInstance = stripe;
                window.AmadexStripeCardElement = cardElement;
                window.AmadexStripeCardState = { cardComplete: false };
                cardElement.on('change', function (event) {
                    const $errors = $('#amadex-stripe-card-errors');
                    if (event.error) {
                        $errors.text(event.error.message).css('color', '#d63638');
                    } else {
                        $errors.text('');
                    }
                    window.AmadexStripeCardState.cardComplete = event.complete || false;
                    maybeEnableStripePaySecurely();
                });
                function maybeEnableStripePaySecurely() {
                    const agreed = $('#amadex-updates-consent').prop('checked');
                    const complete = window.AmadexStripeCardState && window.AmadexStripeCardState.cardComplete;
                    const nameOk = ($('#card-name-stripe').val() || '').trim().length > 0;
                    const $btn = $('#amadex-payment-submit');
                    if ($btn.length) $btn.prop('disabled', !(agreed && complete && nameOk));
                }
                $('#amadex-updates-consent').on('change', maybeEnableStripePaySecurely);
                $('#card-name-stripe').on('input blur', maybeEnableStripePaySecurely);
                setTimeout(maybeEnableStripePaySecurely, 300);
                return;
            }

            // Check if separate field containers exist (NMI-style layout with Stripe Elements)
            const hasSeparateFields = $('#amadex-card-number-field').length > 0 &&
                $('#amadex-card-exp-field').length > 0 &&
                $('#amadex-card-cvv-field').length > 0;

            if (hasSeparateFields) {
                // Apply card-like styling wrapper when Stripe is active
                const $formContainer = $('.amadex-card-form-container');
                const $formLeft = $('.amadex-card-form-left');

                // Add card-like styling class
                $formContainer.addClass('amadex-stripe-card-form');
                $formLeft.addClass('amadex-stripe-card-fields-wrapper');

                // Hide the visual card image on the right when using Stripe card-like form
                $('.amadex-card-visual').hide();

                // Create Elements instance with Travelay brand styling (orange accent)
                const elements = stripe.elements({
                    appearance: {
                        theme: 'none',
                        variables: {
                            colorPrimary: '#f97316', // Travelay orange
                            colorBackground: '#ffffff',
                            colorText: '#111827',
                            colorDanger: '#d63638',
                            colorTextSecondary: '#6b7280',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                            spacingUnit: '4px',
                            borderRadius: '8px',
                            fontSizeBase: '16px'
                        },
                        rules: {
                            '.Input': {
                                backgroundColor: '#ffffff',
                                border: '1px solid #e5e7eb',
                                boxShadow: '0 1px 2px rgba(0,0,0,0.05)',
                                padding: '12px 15px',
                                transition: 'all 0.2s ease'
                            },
                            '.Input--focus': {
                                borderColor: '#f97316',
                                boxShadow: '0 0 0 3px rgba(249, 115, 22, 0.1)',
                                outline: 'none'
                            },
                            '.Input--invalid': {
                                borderColor: '#d63638',
                                boxShadow: '0 0 0 3px rgba(214, 54, 56, 0.1)'
                            },
                            '.Label': {
                                fontWeight: '600',
                                marginBottom: '8px',
                                display: 'block',
                                fontSize: '13px',
                                color: '#111827'
                            },
                            '.Error': {
                                color: '#d63638',
                                fontSize: '12px',
                                marginTop: '4px'
                            }
                        }
                    }
                });

                // Create separate Stripe Elements: cardNumber, cardExpiry, cardCvv
                const cardNumber = elements.create('cardNumber', {
                    style: {
                        base: {
                            fontSize: '18px',
                            color: '#111827',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                            letterSpacing: '1px',
                            '::placeholder': {
                                color: '#9ca3af'
                            }
                        },
                        invalid: {
                            color: '#d63638',
                            iconColor: '#d63638'
                        }
                    },
                    placeholder: ''
                });

                const cardExpiry = elements.create('cardExpiry', {
                    style: {
                        base: {
                            fontSize: '16px',
                            color: '#111827',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                            '::placeholder': {
                                color: '#9ca3af'
                            }
                        },
                        invalid: {
                            color: '#d63638',
                            iconColor: '#d63638'
                        }
                    }
                });

                const cardCvv = elements.create('cardCvc', {
                    style: {
                        base: {
                            fontSize: '16px',
                            color: '#111827',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                            '::placeholder': {
                                color: '#9ca3af'
                            }
                        },
                        invalid: {
                            color: '#d63638',
                            iconColor: '#d63638'
                        }
                    }
                });

                // Clear any existing content in field containers
                $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').empty();

                // Ensure containers are visible and ready
                $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1',
                    'pointer-events': 'auto',
                    'min-height': '48px'
                });

                // Mount the elements to their containers
                try {
                    cardNumber.mount('#amadex-card-number-field');
                    cardExpiry.mount('#amadex-card-exp-field');
                    cardCvv.mount('#amadex-card-cvv-field');

                    // Fix aria-hidden accessibility issue
                    // Remove aria-hidden from containers and ensure proper ARIA labels
                    // Note: Stripe creates internal inputs with aria-hidden for validation - this is normal
                    // We ensure parent containers are accessible and prevent focus on hidden inputs
                    setTimeout(function () {
                        $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').each(function () {
                            const $container = $(this);
                            $container.removeAttr('aria-hidden');
                            // Set proper ARIA attributes for accessibility
                            const $label = $container.closest('.amadex-form-field').find('label');
                            const labelText = $label.text().replace(/\*/g, '').trim();
                            if (labelText) {
                                $container.attr({
                                    'role': 'group',
                                    'aria-label': labelText,
                                    'aria-live': 'polite'
                                });
                            }

                            // Prevent focus on Stripe's internal hidden validation inputs
                            // These have aria-hidden and should not receive focus
                            const $hiddenInputs = $container.find('input.__PrivateStripeElement-input[aria-hidden="true"]');
                            $hiddenInputs.each(function () {
                                const $input = $(this);
                                // Remove from tab order and prevent focus
                                $input.attr('tabindex', '-1');
                                // Prevent focus events
                                $input.on('focus', function (e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    // Blur immediately if somehow focused
                                    $(this).blur();
                                });
                            });
                        });
                    }, 200);
                } catch (mountError) {
                    showPaymentError('Failed to initialize payment fields. Please refresh the page and try again.');
                    $('#amadex-confirm-book').prop('disabled', true);
                    return;
                }

                // Track completion state of all card elements
                window.AmadexStripeCardState = {
                    cardNumber: false,
                    cardExpiry: false,
                    cardCvv: false
                };

                // Helper function to check if all card elements are complete and enable button
                function checkCardCompletionAndEnableButton() {
                    // Check gateway - for Stripe, always enable button on review step (no card validation needed)
                    const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                        ? AmadexConfig.defaultCardGateway
                        : 'nmi';
                    const isStripe = gateway === 'stripe';
                    const isReviewStep = currentStep === 'review';

                    // For Stripe on review step, always enable button (redirects to payment page)
                    if (isStripe && isReviewStep) {
                        const $submitBtn = getSubmitButton();
                        const $stepNextBtn = $('#amadex-step-next');
                        const $confirmBtn = $('#amadex-confirm-book');
                        const isMobile = window.innerWidth <= 767;

                        // Enable all buttons
                        if ($submitBtn.length) {
                            $submitBtn.prop('disabled', false);
                        }
                        if ($stepNextBtn.length) {
                            $stepNextBtn.prop('disabled', false);
                        }
                        // Stripe: do not show Confirm & Book – only Pay securely / step-next
                        if ($confirmBtn.length && $confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                            $confirmBtn.css({ 'display': 'none', 'visibility': 'hidden' }).removeClass('amadex-confirm-book-visible');
                        }
                        return; // Exit early for Stripe
                    }

                    // For NMI, check card completion
                    const state = window.AmadexStripeCardState || {};
                    const allComplete = state.cardNumber && state.cardExpiry && state.cardCvv;

                    // Get the correct submit button
                    const $submitBtn = getSubmitButton();
                    const $stepNextBtn = $('#amadex-step-next');
                    const $confirmBtn = $('#amadex-confirm-book');

                    if (allComplete || isReviewStep) {
                        // All fields complete OR on review step - enable button and show it
                        if ($submitBtn.length) {
                            $submitBtn.prop('disabled', false);
                            $submitBtn.css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': '1',
                                'pointer-events': 'auto'
                            }).removeAttr('aria-hidden').removeAttr('tabindex');
                        }

                        // Also enable step-next button
                        if ($stepNextBtn.length && isReviewStep) {
                            $stepNextBtn.prop('disabled', false);
                            $stepNextBtn.css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': '1',
                                'pointer-events': 'auto'
                            });
                        }

                        // Also enable the hidden confirm button if it exists (NMI only – not when Stripe-hidden)
                        if ($confirmBtn.length && !$confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                            const isMobile = window.innerWidth <= 767;
                            if (!isMobile) {
                                $confirmBtn.css({
                                    'display': 'block',
                                    'visibility': 'visible',
                                    'opacity': '1',
                                    'pointer-events': 'auto'
                                }).removeAttr('aria-hidden').removeAttr('tabindex').addClass('amadex-confirm-book-visible');
                            }
                            $confirmBtn.prop('disabled', false);
                        }

                        if (allComplete) {
                            hidePaymentError();
                        }
                    } else {
                        // Not all fields complete and not on review step - disable button
                        if ($submitBtn.length) {
                            $submitBtn.prop('disabled', true);
                        }
                        if ($stepNextBtn.length) {
                            $stepNextBtn.prop('disabled', true);
                        }
                        const $confirmBtn = $('#amadex-confirm-book');
                        if ($confirmBtn.length) {
                            $confirmBtn.prop('disabled', true);
                        }
                    }
                }

                // Helper function to handle Stripe element validation (defined before use)
                function handleStripeElementChange(event, elementType) {
                    if (event.error) {
                        // Update state - field has error, not complete
                        window.AmadexStripeCardState[elementType] = false;
                        if (window.amadexPaymentAttempted) {
                            showPaymentError(event.error.message);
                        }
                    } else {
                        // Update completion state
                        window.AmadexStripeCardState[elementType] = event.complete || false;

                        // Clear errors if element is valid
                        if (event.complete) {
                            hidePaymentError();
                        }
                    }

                    // Check if all fields are complete and enable/disable button
                    checkCardCompletionAndEnableButton();
                }

                // Listen for validation changes on all elements
                cardNumber.on('change', function (event) {
                    handleStripeElementChange(event, 'cardNumber');
                });

                cardExpiry.on('change', function (event) {
                    handleStripeElementChange(event, 'cardExpiry');
                });

                cardCvv.on('change', function (event) {
                    handleStripeElementChange(event, 'cardCvv');
                });

                // Store Stripe instance and elements globally for submit
                window.AmadexStripeInstance = stripe;
                window.AmadexStripeCardElements = {
                    cardNumber: cardNumber,
                    cardExpiry: cardExpiry,
                    cardCvv: cardCvv
                };

                // When using separate elements, pass cardNumber to createPaymentMethod
                // Stripe will automatically use cardExpiry and cardCvv from the same Elements instance
                window.AmadexStripeCardElement = cardNumber;

                // Ensure fields are visible and editable
                $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').css({
                    'pointer-events': 'auto',
                    'opacity': '1',
                    'visibility': 'visible'
                });

                // Wait a moment for Stripe to fully mount, then enable fields and show button
                setTimeout(function () {
                    cardNumber.focus();
                    // CRITICAL FIX: Show and enable the confirm button after Stripe is ready
                    const $confirmBtn = $('#amadex-confirm-book');
                    const $stepNextBtn = $('#amadex-step-next');
                    const isMobile = window.innerWidth <= 767;

                    // On desktop, show the confirm button only for NMI (Stripe uses Pay securely)
                    if (!isMobile && $confirmBtn.length && !$confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                        $confirmBtn.css({
                            'display': 'block',
                            'visibility': 'visible',
                            'opacity': '1',
                            'pointer-events': 'auto'
                        }).removeAttr('aria-hidden').removeAttr('tabindex');
                    }
                    if ($confirmBtn.length && $confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                        $confirmBtn.css({ 'display': 'none', 'visibility': 'hidden' });
                    }

                    // On mobile, ensure step-next button text is correct (Stripe: Pay securely)
                    if (isMobile && $stepNextBtn.length && currentStep === 'review') {
                        const btnText = gateway === 'stripe' ? 'Pay securely' : 'Confirm & Book';
                        $stepNextBtn.text(btnText);
                    }

                    // CRITICAL FIX: Enable button on review step immediately after Stripe is ready
                    // (User can click and we'll validate card fields on submit)
                    if (currentStep === 'review') {
                        setTimeout(function () {
                            // Force enable step-next button
                            if ($stepNextBtn.length) {
                                $stepNextBtn.prop('disabled', false);
                                $stepNextBtn.css({
                                    'display': 'block',
                                    'visibility': 'visible',
                                    'opacity': '1',
                                    'pointer-events': 'auto'
                                });
                            }
                            // Force enable confirm button (NMI only – Stripe uses Pay securely)
                            if ($confirmBtn.length && !$confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                                $confirmBtn.prop('disabled', false);
                                $confirmBtn.css({
                                    'display': 'block',
                                    'visibility': 'visible',
                                    'opacity': '1',
                                    'pointer-events': 'auto'
                                });
                            }
                        }, 300);
                    } else {
                        // Not on review step - disable until card is complete
                        if ($confirmBtn.length && !$confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                            $confirmBtn.prop('disabled', true);
                        }
                        if ($stepNextBtn.length) {
                            $stepNextBtn.prop('disabled', true);
                        }
                    }

                    // Check card completion state after a short delay
                    setTimeout(checkCardCompletionAndEnableButton, 200);
                }, 500);

            } else {
                // Fallback: single card element (should not happen with current structure)
                const elements = stripe.elements({
                    appearance: {
                        theme: 'stripe',
                        variables: {
                            colorPrimary: '#f97316',
                            colorBackground: '#ffffff',
                            colorText: '#333333',
                            colorDanger: '#d63638',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                            spacingUnit: '4px',
                            borderRadius: '6px'
                        }
                    }
                });

                if ($('#amadex-card-element').length === 0) {
                    $('#amadex-card-number-field').after('<div id="amadex-card-element"></div>');
                }

                const cardElement = elements.create('card', {
                    style: {
                        base: {
                            fontSize: '14px',
                            color: '#333',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
                            '::placeholder': {
                                color: '#999'
                            }
                        },
                        invalid: {
                            color: '#d63638',
                            iconColor: '#d63638'
                        }
                    }
                });

                cardElement.mount('#amadex-card-element');

                window.AmadexStripeInstance = stripe;
                window.AmadexStripeCardElement = cardElement;

                // Listen for validation changes
                cardElement.on('change', function (event) {
                    if (event.error) {
                        if (window.amadexPaymentAttempted) {
                            showPaymentError(event.error.message);
                        }
                    } else {
                        hidePaymentError();
                    }
                });

            }

            // Mark payment as initialized
            window.amadexPaymentInitialized = true;

            // Note: Button will be enabled when card is complete (handled by card completion listeners)
            // Don't enable button here - wait for card completion

            // Initialize card completion state if not already set
            if (!window.AmadexStripeCardState) {
                window.AmadexStripeCardState = {
                    cardNumber: false,
                    cardExpiry: false,
                    cardCvv: false
                };
            }

        } catch (error) {
            let errorMessage = 'Payment fields configuration error. ';
            if (error && error.message) {
                errorMessage += error.message;
            } else {
                errorMessage += 'Please check your Stripe Publishable Key in settings.';
            }

            $('#amadex-card-number-field, #amadex-card-exp-field, #amadex-card-cvv-field').html(
                '<div style="padding: 12px; color: #d63638; font-size: 14px; border: 1px solid #d63638; border-radius: 6px; background: #fff5f5;">⚠️ ' + errorMessage + '</div>'
            );

            if (window.amadexPaymentAttempted) {
                showPaymentError(errorMessage);
            }
            $('#amadex-confirm-book').prop('disabled', true);
        }
    }

    /**
     * Handle Stripe payment submission (create PaymentMethod, PaymentIntent, confirm)
     */
    function handleStripeSubmit(flight) {
        const stripe = window.AmadexStripeInstance;
        // Support both separate elements and single card element
        let cardElement = window.AmadexStripeCardElement;

        // If we have separate elements, use cardNumber (which contains all card info for PaymentMethod)
        if (window.AmadexStripeCardElements && window.AmadexStripeCardElements.cardNumber) {
            cardElement = window.AmadexStripeCardElements.cardNumber;
        } else {
        }

        if (!stripe) {
            showPaymentError('Payment system not initialized. Please refresh the page and try again.');
            return;
        }

        if (!cardElement) {
            showPaymentError('Payment fields not initialized. Please refresh the page and try again.');
            return;
        }

        // Disable submit button - use helper function to get correct button (avoids aria-hidden issues)
        const submitBtn = getSubmitButton();
        if (submitBtn.length) {
            submitBtn.prop('disabled', true).text('Processing Payment...');
        }

        // Validate all fields before submission
        if (!validateAllFields()) {
            const $firstError = $('.amadex-form-field input.error, .amadex-form-field select.error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
            submitBtn.prop('disabled', false).text('Confirm & Book');
            return;
        }

        // Collect booking data for amount calculation
        let bookingData = collectBookingData(flight);

        // Calculate total amount (same logic as NMI flow)
        const totalAmount = parseFloat($('#amadex-price-breakdown .amadex-price-row.total .amadex-price-value').text().replace(/[^0-9.]/g, '')) || 0;

        // Cardholder name: prefer Stripe form field, then billing
        const cardholderName = ($('#card-name-stripe').val() || '').trim() ||
            (bookingData.billing ? (bookingData.billing.first_name + ' ' + bookingData.billing.last_name).trim() : '') ||
            (bookingData.contact ? (bookingData.contact.first_name + ' ' + bookingData.contact.last_name).trim() : '');
        // Step 1: Create PaymentMethod
        stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: cardholderName || (bookingData.billing ? (bookingData.billing.first_name + ' ' + bookingData.billing.last_name) : ''),
                email: bookingData.contact ? bookingData.contact.email : '',
                phone: bookingData.contact ? bookingData.contact.phone : '',
                address: {
                    line1: bookingData.billing ? bookingData.billing.address1 : '',
                    line2: bookingData.billing ? bookingData.billing.address2 : '',
                    city: bookingData.billing ? bookingData.billing.city : '',
                    state: bookingData.billing ? bookingData.billing.state : '',
                    postal_code: bookingData.billing ? bookingData.billing.postal : '',
                    country: bookingData.billing ? bookingData.billing.country : 'US'
                }
            }
        }).then(function (result) {
            if (result.error) {
                showPaymentError(result.error.message);
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                return;
            }

            const paymentMethodId = result.paymentMethod.id;
            // Step 2: Create PaymentIntent via backend
            // Extract flight data for Stripe industry metadata
            const flightData = bookingData.flight || flight || {};
            const firstItinerary = flightData.itineraries && flightData.itineraries[0] ? flightData.itineraries[0] : null;
            const firstSegment = firstItinerary && firstItinerary.segments && firstItinerary.segments[0] ? firstItinerary.segments[0] : null;

            // Build flight metadata for Stripe payment_details.flight_data
            let flightMetadata = null;
            if (firstSegment && firstSegment.departure && firstSegment.arrival) {
                const depIata = firstSegment.departure.iata_code || firstSegment.departure.iataCode || '';
                const arrIata = firstSegment.arrival.iata_code || firstSegment.arrival.iataCode || '';
                const carrierCode = firstSegment.carrier_code || firstSegment.carrierCode || '';
                const flightNumber = firstSegment.number || '';
                const depTime = firstSegment.departure.at || '';
                const arrTime = firstSegment.arrival.at || '';

                // Get passenger name from booking data
                const passengerName = bookingData.passengers && bookingData.passengers[0]
                    ? (bookingData.passengers[0].first_name + ' ' + bookingData.passengers[0].last_name)
                    : (bookingData.contact ? (bookingData.contact.first_name + ' ' + bookingData.contact.last_name) : '');

                // Build flight_data if we have minimum required data
                if (depIata && arrIata && carrierCode) {
                    flightMetadata = {
                        booking_reference: bookingData.booking_reference || '',
                        carrier_name: carrierCode,
                        carrier_iata: carrierCode,
                        passenger_name: passengerName,
                        departure_airport: depIata,
                        arrival_airport: arrIata,
                        departure_date: depTime,
                        arrival_date: arrTime,
                        ticket_class: firstSegment.cabin || 'ECONOMY',
                        fare_basis: firstSegment.fareBasis || '',
                        ticketing_agent: 'Travelay'
                    };
                }
            }

            // Build price breakdown for Stripe dashboard (full summary: base, tax, addons, discounts, etc.)
            const pricing = bookingData.pricing || {};
            const priceBreakdown = {
                base_fare: parseFloat(pricing.fare || pricing.base_fare || 0),
                tax: parseFloat(pricing.tax || 0),
                surcharge: parseFloat(pricing.surcharge || 0),
                addons: parseFloat(pricing.addons || 0),
                seat_charges: parseFloat(pricing.seat_charges || 0),
                premium_service: parseFloat(pricing.premium_service || 0),
                discount: parseFloat(pricing.discount || 0),
                total: totalAmount
            };

            const ajaxData = {
                action: 'amadex_create_payment_intent',
                nonce: (typeof AmadexConfig !== 'undefined' && AmadexConfig.nonce) ? AmadexConfig.nonce : '',
                amount: totalAmount,
                currency: 'USD',
                payment_method_id: paymentMethodId,
                booking_reference: bookingData.booking_reference || 'temp-' + Date.now(),
                price_breakdown: JSON.stringify(priceBreakdown)
            };

            if (flightMetadata) {
                ajaxData.flight_data = JSON.stringify(flightMetadata);
            }

            if (!ajaxData.nonce) {
                showPaymentError('Configuration error: Security token missing. Please refresh the page and try again.');
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                return;
            }

            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                beforeSend: function (xhr) {
                },
                success: function (response) {
                    if (!response || !response.success) {
                        const errorMsg = response && response.data && response.data.message
                            ? response.data.message
                            : 'Failed to create payment intent. Please try again.';
                        showPaymentError(errorMsg);
                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                        return;
                    }

                    const clientSecret = response.data.client_secret;
                    const paymentIntentId = response.data.payment_intent_id;
                    // Step 3: Confirm PaymentIntent (Stripe.js handles 3DS/SCA automatically when required)
                    stripe.confirmCardPayment(clientSecret, {
                        payment_method: {
                            card: cardElement,
                            billing_details: {
                                name: cardholderName || (bookingData.billing ? (bookingData.billing.first_name + ' ' + bookingData.billing.last_name) : ''),
                                email: bookingData.contact ? bookingData.contact.email : ''
                            }
                        }
                    }).then(function (confirmResult) {
                        if (confirmResult.error) {
                            // Handle 3DS authentication
                            if (confirmResult.error.code === 'payment_intent_authentication_failure') {
                                // Retry with handleCardAction if needed
                                stripe.handleCardAction(clientSecret).then(function (actionResult) {
                                    if (actionResult.error) {
                                        showPaymentError(actionResult.error.message);
                                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                                    } else {
                                        // Retry confirmation after 3DS
                                        stripe.confirmCardPayment(clientSecret).then(function (retryResult) {
                                            if (retryResult.error) {
                                                showPaymentError(retryResult.error.message);
                                                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                                            } else {
                                                processStripeSuccess(retryResult.paymentIntent, flight, submitBtn);
                                            }
                                        });
                                    }
                                });
                            } else {
                                showPaymentError(confirmResult.error.message);
                                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                            }
                            return;
                        }

                        // Success: PaymentIntent authorized
                        const paymentIntent = confirmResult.paymentIntent;
                        // Handle different PaymentIntent statuses
                        if (paymentIntent.status === 'requires_capture') {
                            // Auth-only successful - ready for manual capture
                            processStripeSuccess(paymentIntent, flight, submitBtn);
                        } else if (paymentIntent.status === 'succeeded') {
                            // Payment fully captured automatically
                            processStripeSuccess(paymentIntent, flight, submitBtn);
                        } else if (paymentIntent.status === 'requires_action') {
                            // 3DS challenge required - handle it
                            // Stripe.js should have handled this, but if we get here, retry with handleCardAction
                            stripe.handleCardAction(clientSecret).then(function (actionResult) {
                                if (actionResult.error) {
                                    showPaymentError(actionResult.error.message);
                                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                                } else {
                                    // Retry confirmation after action
                                    stripe.confirmCardPayment(clientSecret).then(function (retryResult) {
                                        if (retryResult.error) {
                                            showPaymentError(retryResult.error.message);
                                            updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                                        } else {
                                            processStripeSuccess(retryResult.paymentIntent, flight, submitBtn);
                                        }
                                    });
                                }
                            });
                        } else {
                            showPaymentError('Payment authorization failed. Status: ' + paymentIntent.status);
                            updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                        }
                    }).catch(function (error) {
                        showPaymentError('Payment processing error. Please try again.');
                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    });
                },
                error: function (xhr, status, error) {
                    // Only log errors in development mode (when WP_DEBUG is enabled)
                    // In production, show user-friendly messages without verbose console output
                    if (typeof AmadexConfig !== 'undefined' && AmadexConfig.debug) {
                    }

                    let errorMsg = 'Failed to initialize payment. ';

                    if (xhr.status === 403) {
                        errorMsg = 'Access forbidden. This may be due to a security plugin or expired session. Please refresh the page and try again.';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error occurred. Please check if Stripe library files are properly installed or contact support.';
                    } else if (xhr.status === 0) {
                        errorMsg = 'Network error. Please check your internet connection and try again.';
                    } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }

                    showPaymentError(errorMsg);
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
            });
        }).catch(function (error) {
            showPaymentError('Card validation failed. Please check your card details.');
            submitBtn.prop('disabled', false).text('Confirm & Book');
        });
    }

    /**
     * Process successful Stripe PaymentIntent and submit booking
     */
    function processStripeSuccess(paymentIntent, flight, submitBtn) {
        // Submit booking with payment_intent_id
        submitBookingWithStripeIntent(flight, paymentIntent.id, submitBtn);
    }

    /**
     * Submit booking with Stripe PaymentIntent ID
     */
    function submitBookingWithStripeIntent(flight, paymentIntentId, submitBtn) {
        if (!paymentIntentId || paymentIntentId.trim() === '') {
            showPaymentError('Payment authorization failed. Please try again.');
            if (submitBtn) {
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            }
            return;
        }

        // Ensure flight data is available
        if (!flight) {
            const storedFlight = sessionStorage.getItem('amadex_booking_flight');
            if (storedFlight) {
                try {
                    flight = JSON.parse(storedFlight);
                } catch (e) {
                }
            }
        }

        if (!flight) {
            showPaymentError('Flight data is missing. Please start over.');
            if (submitBtn) {
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            }
            return;
        }

        // Collect booking data (same as NMI flow)
        let bookingData = collectBookingData(flight);

        // Include seat selection if available
        if (window.AmadexSeatSelection && typeof window.AmadexSeatSelection.includeInBooking === 'function') {
            bookingData = window.AmadexSeatSelection.includeInBooking(bookingData);
        }

        // Add PaymentIntent ID instead of payment_token
        bookingData.payment_intent_id = paymentIntentId;

        // Validate card holder name (required for billing) — Stripe form uses #card-name-stripe
        const cardHolderName = ($('#card-name-stripe').val() || $('#card-name').val() || '').trim();
        if (!cardHolderName) {
            showPaymentError('Card Holder Name is required. Please enter the name as it appears on your credit card.');
            if (submitBtn) {
                updateSubmitButtons({ disabled: false, text: 'Pay Securely' });
            }
            var $nameEl = $('#card-name-stripe').length ? $('#card-name-stripe') : $('#card-name');
            if ($nameEl.length) {
                $('html, body').animate({ scrollTop: $nameEl.offset().top - 100 }, 500);
                $nameEl.focus();
            }
            return;
        }
        // Ensure billing has first/last from card holder name if missing (Stripe single name field)
        if (!bookingData.billing) bookingData.billing = {};
        if (!bookingData.billing.first_name || !bookingData.billing.last_name) {
            var parts = cardHolderName.split(/\s+/);
            if (parts.length >= 2) {
                bookingData.billing.first_name = parts[0];
                bookingData.billing.last_name = parts.slice(1).join(' ');
            } else {
                bookingData.billing.first_name = cardHolderName;
                bookingData.billing.last_name = cardHolderName;
            }
        }
        // Validate billing information
        if (!bookingData.billing.first_name || !bookingData.billing.last_name) {
            showPaymentError('Card Holder Name must include both first and last name (e.g., "John Smith").');
            if (submitBtn) {
                updateSubmitButtons({ disabled: false, text: 'Pay Securely' });
            }
            var $nameEl2 = $('#card-name-stripe').length ? $('#card-name-stripe') : $('#card-name');
            if ($nameEl2.length) {
                $('html, body').animate({ scrollTop: $nameEl2.offset().top - 100 }, 500);
                $nameEl2.focus();
            }
            return;
        }

        // Validate contact information
        if (!bookingData.contact || !bookingData.contact.email || !bookingData.contact.phone) {
            showPaymentError('Contact information is incomplete. Please provide email and phone number.');
            if (submitBtn) {
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            }
            return;
        }

        // Submit to server (similar to NMI flow)
        $.ajax({
            url: AmadexConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amadex_process_booking',
                nonce: AmadexConfig.nonce,
                booking_data: JSON.stringify(bookingData),
                payment_intent_id: paymentIntentId // Also send separately for easy access
            },
            success: function (response) {
                if (response && response.success) {
                    const message = response.data?.message || 'Booking successful! Confirmation details will be sent to your email.';
                    const bookingRef = response.data?.booking_reference || sessionStorage.getItem('amadex_booking_reference') || '';

                    hidePaymentError();

                    if (bookingRef) {
                        sessionStorage.setItem('amadex_booking_reference', bookingRef);
                    }

                    // GA4 purchase: fire before clearing booking flight data
                    storeAmadexPendingPurchaseEvent(flight, bookingRef);

                    // CRITICAL: Clear all booking-specific sessionStorage data BEFORE redirect
                    // This prevents duplicate bookings when user clicks back button
                    // Industry standard: Clear booking data immediately after success
                    const bookingKeysToClear = [
                        'amadex_booking_flight',
                        'amadex_search_data',
                        'amadexBookingStage',
                        'amadex_booking_step',
                        'amadex_booking_timer_start',
                        'amadex_booking_timer_remaining',
                        'amadex_booking_timer_paused_at',
                        'amadex_last_booking_flight_id',
                        'amadex_booking_addons',
                        'amadex_premium_service_added',
                        'amadex_multi_city_bookings',
                        'amadex_multi_city_segments',
                        'amadex_booking_all_segments',
                        'amadex_results_page_url'
                    ];

                    bookingKeysToClear.forEach(function (key) {
                        if (sessionStorage.getItem(key) !== null) {
                            sessionStorage.removeItem(key);
                        }
                    });

                    const confirmationUrl = buildConfirmationUrl(bookingRef, response?.data?.confirmation_url);
                    // Redirect to confirmation page
                    window.location.href = confirmationUrl;
                } else {
                    const errorMsg = response && response.data && response.data.message
                        ? response.data.message
                        : 'Booking failed. Please try again or contact support.';
                    showPaymentError(errorMsg);
                    if (submitBtn) {
                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    }
                }
            },
            error: function (xhr, status, error) {
                let errorMsg = 'Booking submission failed. ';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg += xhr.responseJSON.data.message;
                } else {
                    errorMsg += 'Please try again or contact support.';
                }
                showPaymentError(errorMsg);
                if (submitBtn) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
            }
        });
    }

    /**
     * Crypto.com Pay: create booking + Crypto.com payment via backend, then show Crypto.com Pay button.
     */
    function startCryptoComPayFlow(flight) {
        if (window.amadexCryptoComFlowInProgress) return;
        window.amadexCryptoComFlowInProgress = true;

        const submitBtn = getSubmitButton();
        if (submitBtn.length) {
            updateSubmitButtons({ disabled: true, text: 'Creating booking...' });
        }
        hidePaymentError();
        var bookingData = typeof collectBookingData === 'function' ? collectBookingData(flight) : {};
        if (!bookingData || !bookingData.flight) {
            showPaymentError('Unable to collect booking data. Please try again.');
            if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            window.amadexCryptoComFlowInProgress = false;
            return;
        }
        var deviceFingerprintData = null;
        try {
            if (window.AmadexFraudDetection && typeof window.AmadexFraudDetection.getCompleteFraudData === 'function') {
                deviceFingerprintData = window.AmadexFraudDetection.getCompleteFraudData();
            }
        } catch (e) { }
        $.ajax({
            url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '',
            type: 'POST',
            data: {
                action: 'amadex_cryptocom_create_payment',
                nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '',
                booking_data: JSON.stringify(bookingData),
                device_fingerprint: deviceFingerprintData ? JSON.stringify(deviceFingerprintData) : null
            },
            timeout: 30000,
            success: function (response) {
                if (!response || !response.success) {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Could not create payment. Please try again.';
                    showPaymentError(msg);
                    if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    window.amadexCryptoComFlowInProgress = false;
                    return;
                }
                var paymentId = response.data.payment_id;
                var bookingRef = response.data.booking_reference;
                var confirmationUrl = response.data.confirmation_url || buildConfirmationUrl(bookingRef);
                var pk = response.data.publishable_key || (typeof AmadexConfig !== 'undefined' ? AmadexConfig.cryptoComPublishableKey : '');
                if (bookingRef) sessionStorage.setItem('amadex_booking_reference', bookingRef);
                if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                $('#cryptocom-pay-button-container').show();
                if (!pk) {
                    showPaymentError('Crypto.com Pay is not configured.');
                    window.amadexCryptoComFlowInProgress = false;
                    return;
                }
                var scriptUrl = 'https://js.crypto.com/sdk?publishable-key=' + encodeURIComponent(pk);
                if (window.cryptopay && window.cryptopay.Button) {
                    renderCryptoComButton(paymentId, confirmationUrl);
                    window.amadexCryptoComFlowInProgress = false;
                    return;
                }
                var script = document.createElement('script');
                script.src = scriptUrl;
                script.async = true;
                script.onload = function () {
                    renderCryptoComButton(paymentId, confirmationUrl);
                    window.amadexCryptoComFlowInProgress = false;
                };
                script.onerror = function () {
                    showPaymentError('Could not load Crypto.com Pay. Please try again.');
                    window.amadexCryptoComFlowInProgress = false;
                };
                document.body.appendChild(script);
            },
            error: function (xhr) {
                var msg = 'Could not create Crypto.com payment. ';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                showPaymentError(msg);
                if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                window.amadexCryptoComFlowInProgress = false;
            }
        });
    }

    function renderCryptoComButton(paymentId, confirmationUrl) {
        var mount = document.getElementById('cryptocom-pay-button-mount');
        if (!mount || !window.cryptopay || !window.cryptopay.Button) return;
        // If we already have a button in the mount, just show the container (avoid duplicate render / zoid error)
        if (mount.children.length > 0) {
            $('#cryptocom-pay-button-container').show();
            return;
        }
        if (window.amadexCryptoComButtonRendered) {
            $('#cryptocom-pay-button-container').show();
            return;
        }
        try {
            $(mount).empty();
            window.cryptopay.Button({
                createPayment: function (actions) {
                    return actions.payment.fetch(paymentId);
                },
                onApprove: function () {
                    window.location.href = confirmationUrl;
                },
                defaultLang: 'en-US'
            }).render('#cryptocom-pay-button-mount');
            window.amadexCryptoComButtonRendered = true;
        } catch (e) {
            showPaymentError('Could not load Pay button. Please try again.');
            window.amadexCryptoComButtonRendered = false;
        }
    }

    /**
     * MoonPay Commerce: create booking + pay link via backend, then redirect to MoonPay pay link.
     */
    function startMoonPayFlow(flight) {
        if (window.amadexMoonPayFlowInProgress) {
            return;
        }
        window.amadexMoonPayFlowInProgress = true;
        const submitBtn = getSubmitButton();
        if (submitBtn.length) {
            updateSubmitButtons({ disabled: true, text: 'Creating booking...' });
        }
        hidePaymentError();
        var bookingData = typeof collectBookingData === 'function' ? collectBookingData(flight) : {};
        if (!bookingData || !bookingData.flight) {
            showPaymentError('Unable to collect booking data. Please try again.');
            if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            window.amadexMoonPayFlowInProgress = false;
            return;
        }
        var ajaxUrl = typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '';
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                action: 'amadex_moonpay_create_paylink',
                nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '',
                booking_data: JSON.stringify(bookingData)
            },
            timeout: 60000,
            success: function (response) {
                if (!response || !response.success) {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Could not create payment link. Please try again.';
                    if (msg.indexOf('Something Went Wrong') !== -1 || msg.indexOf('Something went wrong') !== -1) {
                        msg = 'Hel.io had a temporary issue. Please try again in a moment, or use another payment method.';
                    }
                    if (response && response.data && response.data.message) {
                    }
                    showPaymentError(msg);
                    if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    window.amadexMoonPayFlowInProgress = false;
                    return;
                }
                var payLinkUrl = response.data.payLinkUrl;
                var bookingRef = response.data.booking_reference;
                if (bookingRef) sessionStorage.setItem('amadex_booking_reference', bookingRef);
                if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                window.amadexMoonPayFlowInProgress = false;
                if (payLinkUrl) {
                    window.location.href = payLinkUrl;
                } else {
                    showPaymentError('Payment link not received. Please try again.');
                }
            },
            error: function (xhr, status, err) {
                var msg = 'Could not create MoonPay payment link. ';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                } else if (status === 'timeout' || (xhr && xhr.status === 0)) {
                    msg = 'The request took too long or was interrupted. Please try again.';
                } else if (xhr && (xhr.status === 403 || xhr.status === 405) || (xhr.responseText && (xhr.responseText.indexOf('405 Not Allowed') !== -1 || xhr.responseText.indexOf('403 Forbidden') !== -1))) {
                    msg = 'The payment request was blocked by the server (403/405). Try again from a desktop browser, or use another payment method. If it persists, your host may need to allow POST to wp-admin/admin-ajax.php for AJAX requests.';
                }
                showPaymentError(msg);
                if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                window.amadexMoonPayFlowInProgress = false;
            }
        });
    }

    /**
     * MoonPay Onramp (Ramps): create booking via AJAX, then show MoonPay buy widget (overlay).
     * Customer pays with card; crypto is sent to merchant BTC wallet. Redirect URL brings user back to confirmation.
     */
    function startMoonPayOnrampFlow(flight) {
        if (window.amadexMoonPayOnrampFlowInProgress) return;
        window.amadexMoonPayOnrampFlowInProgress = true;
        const submitBtn = getSubmitButton();
        if (submitBtn.length) {
            updateSubmitButtons({ disabled: true, text: 'Creating booking...' });
        }
        hidePaymentError();
        var bookingData = typeof collectBookingData === 'function' ? collectBookingData(flight) : {};
        if (!bookingData || !bookingData.flight) {
            showPaymentError('Unable to collect booking data. Please try again.');
            if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            window.amadexMoonPayOnrampFlowInProgress = false;
            return;
        }
        $.ajax({
            url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '',
            type: 'POST',
            data: {
                action: 'amadex_moonpay_onramp_prepare',
                nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '',
                booking_data: JSON.stringify(bookingData)
            },
            timeout: 30000,
            success: function (response) {
                if (!response || !response.success) {
                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Could not prepare payment. Please try again.';
                    showPaymentError(msg);
                    if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    window.amadexMoonPayOnrampFlowInProgress = false;
                    return;
                }
                var data = response.data;
                var bookingRef = data.booking_reference;
                var env = data.environment || 'sandbox';
                var params = data.params || {};
                var redirectUrl = data.redirect_url || '';
                if (bookingRef) sessionStorage.setItem('amadex_booking_reference', bookingRef);
                if (!params.apiKey) {
                    showPaymentError('Payment configuration missing. Please try again.');
                    if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    window.amadexMoonPayOnrampFlowInProgress = false;
                    return;
                }
                function showMoonPayWidget() {
                    var init = window.MoonPayWebSdk && window.MoonPayWebSdk.init;
                    if (!init) {
                        showPaymentError('MoonPay could not be loaded. Please refresh and try again.');
                        if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                        window.amadexMoonPayOnrampFlowInProgress = false;
                        return;
                    }
                    var widget = init({
                        flow: 'buy',
                        environment: env,
                        variant: 'overlay',
                        params: params,
                        handlers: {
                            onTransactionCompleted: function (props) {
                                var txnId = (props && (props.transactionId || props.id)) ? (props.transactionId || props.id) : '';
                                if (txnId) sessionStorage.setItem('amadex_moonpay_onramp_transaction_id', txnId);
                                if (redirectUrl && bookingRef) {
                                    var sep = redirectUrl.indexOf('?') >= 0 ? '&' : '?';
                                    var confirmUrl = redirectUrl + sep + 'reference=' + encodeURIComponent(bookingRef) +
                                        '&transactionId=' + encodeURIComponent(txnId) +
                                        '&transactionStatus=completed' +
                                        '&externalTransactionId=' + encodeURIComponent(bookingRef);
                                    if (props && (props.baseCurrencyAmount !== undefined && props.baseCurrencyAmount !== null)) confirmUrl += '&baseCurrencyAmount=' + encodeURIComponent(props.baseCurrencyAmount);
                                    if (props && props.baseCurrency && props.baseCurrency.code) confirmUrl += '&baseCurrencyCode=' + encodeURIComponent(props.baseCurrency.code);
                                    window.location.href = confirmUrl;
                                }
                            },
                            onClose: function () {
                                if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                                window.amadexMoonPayOnrampFlowInProgress = false;
                            }
                        }
                    });
                    if (!widget) {
                        showPaymentError('MoonPay widget could not be initialized. Please try again.');
                        return;
                    }
                    var urlForSigning = (typeof widget.generateUrlForSigning === 'function') ? widget.generateUrlForSigning() : null;
                    function doSignAndShow(url) {
                        if (!url || typeof url !== 'string') {
                            showPaymentError('MoonPay could not generate URL for signing. Please try again.');
                            return;
                        }
                        $.ajax({
                            url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '',
                            type: 'POST',
                            data: {
                                action: 'amadex_moonpay_onramp_sign',
                                nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '',
                                urlForSigning: url
                            },
                            timeout: 10000,
                            success: function (signResponse) {
                                if (signResponse && signResponse.success && signResponse.data && signResponse.data.signature) {
                                    if (typeof widget.updateSignature === 'function') {
                                        widget.updateSignature(signResponse.data.signature);
                                    }
                                    if (typeof widget.show === 'function') {
                                        widget.show();
                                    }
                                } else {
                                    var msg = (signResponse && signResponse.data && signResponse.data.message) ? signResponse.data.message : 'Could not sign payment. Please try again.';
                                    showPaymentError(msg);
                                    if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                                    window.amadexMoonPayOnrampFlowInProgress = false;
                                }
                            },
                            error: function (xhr) {
                                var msg = 'Could not sign MoonPay payment. ';
                                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                                    msg = xhr.responseJSON.data.message;
                                }
                                showPaymentError(msg);
                                if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                                window.amadexMoonPayOnrampFlowInProgress = false;
                            }
                        });
                    }
                    if (urlForSigning && typeof urlForSigning.then === 'function') {
                        urlForSigning.then(doSignAndShow).catch(function () {
                            showPaymentError('MoonPay could not generate URL for signing. Please try again.');
                            if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                            window.amadexMoonPayOnrampFlowInProgress = false;
                        });
                    } else {
                        doSignAndShow(urlForSigning);
                    }
                }
                if (window.MoonPayWebSdk && window.MoonPayWebSdk.init) {
                    showMoonPayWidget();
                } else {
                    var script = document.createElement('script');
                    script.src = 'https://static.moonpay.com/web-sdk/v1/moonpay-web-sdk.min.js';
                    script.defer = true;
                    script.onload = showMoonPayWidget;
                    script.onerror = function () {
                        showPaymentError('MoonPay could not be loaded. Please refresh and try again.');
                    };
                    document.head.appendChild(script);
                }
            },
            error: function (xhr) {
                var msg = 'Could not prepare MoonPay payment. ';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                showPaymentError(msg);
                if (submitBtn.length) updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                window.amadexMoonPayOnrampFlowInProgress = false;
            }
        });
    }

    /**
     * Submit booking with payment token
     */
    function submitBookingWithToken(flight, paymentToken) {
        // ✅ FIX #2: ATOMIC FLAG CHECK AND SET (prevents race condition)
        if (window.amadexBookingSubmissionInProgress) {
            return false;
        }
        window.amadexBookingSubmissionInProgress = true; // ✅ Set IMMEDIATELY (synchronously)

        // ✅ FIX #2: DISABLE BUTTON FIRST (before any async operations)
        const submitBtn = getSubmitButton();
        if (submitBtn.length) {
            submitBtn.prop('disabled', true).text('Processing Payment...'); // ✅ Disable IMMEDIATELY
        }

        // ✅ FIX #4: Use try-finally to ensure flag is ALWAYS reset
        try {
            // ✅ IMMEDIATE: Show processing modal (after flag and button disable)
            if (!showBookingProcessingModal()) {
                // Reset flag and button on error
                window.amadexBookingSubmissionInProgress = false;
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                return false;
            }

            // ✅ Update message for payment processing
            updateProcessingMessage(
                'Processing your payment...',
                'Authorizing payment with your bank...'
            );

            // Validate payment token
            if (!paymentToken || paymentToken.trim() === '') {
                showPaymentError('Payment token is missing. Please check your card details and try again.');
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                return false;
            }

            // Ensure flight data is available
            if (!flight) {
                // Try to get flight from sessionStorage as fallback
                const storedFlight = sessionStorage.getItem('amadex_booking_flight');
                if (storedFlight) {
                    try {
                        flight = JSON.parse(storedFlight);
                    } catch (e) {
                    }
                }
            }

            if (!flight) {
                showPaymentError('Flight data is missing. Please start over.');
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                return false;
            }

            // Validate all fields before submission
            if (!validateAllFields()) {
                // Scroll to first error
                const $firstError = $('.amadex-form-field input.error, .amadex-form-field select.error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                return false;
            }

            // Also check the old validation function for backward compatibility
            const validation = validateBookingForm();
            if (!validation || !validation.valid) {
                showPaymentError(validation ? validation.message : 'Please fix the errors in the form');
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                return false;
            }
            // Collect booking data
            let bookingData = collectBookingData(flight);

            // Include selected seats if available
            if (window.AmadexSeatSelection) {
                if (window.AmadexSeatSelection.selectedSeats) {
                } else {
                }
            }

            if (window.AmadexSeatSelection && typeof window.AmadexSeatSelection.includeInBooking === 'function') {
                bookingData = window.AmadexSeatSelection.includeInBooking(bookingData);
                if (bookingData.seat_selection) {
                }
            } else {
            }

            bookingData.payment_token = paymentToken;

            // Validate card holder name is provided (required for NMI billing)
            const cardHolderName = $('#card-name').val() || '';
            if (!cardHolderName || cardHolderName.trim() === '') {
                showPaymentError('Card Holder Name is required. Please enter the name as it appears on your credit card.');
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                // Scroll to card name field
                $('html, body').animate({
                    scrollTop: $('#card-name').offset().top - 100
                }, 500);
                $('#card-name').focus();
                return false;
            }

            // Validate billing information (required for payment processing)
            if (!bookingData.billing || !bookingData.billing.first_name || !bookingData.billing.last_name) {
                showPaymentError('Card Holder Name must include both first and last name (e.g., "John Smith"). This name will appear in payment records.');
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                // Scroll to card name field
                $('html, body').animate({
                    scrollTop: $('#card-name').offset().top - 100
                }, 500);
                $('#card-name').focus();
                return false;
            }

            // Validate contact information
            if (!bookingData.contact || !bookingData.contact.email || !bookingData.contact.phone) {
                showPaymentError('Contact information is incomplete. Please provide email and phone number.');
                window.amadexBookingSubmissionInProgress = false;
                hideBookingProcessingModal();
                if (submitBtn.length) {
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
                return false;
            }

            // Verify billing name is from card holder, not passenger
            const passengerName = bookingData.passengers[0] ? (bookingData.passengers[0].firstname + ' ' + bookingData.passengers[0].lastname) : '';
            const billingName = bookingData.billing.first_name + ' ' + bookingData.billing.last_name;
            // ✅ FIX #2: Wrap hash generation in try-catch with fallback
            let requestHash;
            try {
                requestHash = generateRequestHash(flight, bookingData);
                if (!requestHash || requestHash.trim() === '') {
                    throw new Error('Hash generation returned empty string');
                }
            } catch (error) {
                // Fallback: timestamp-based hash (10-second precision)
                requestHash = 'fallback_' + Math.floor(Date.now() / 10000) * 10;
            }

            // ✅ FIX #5: Frontend lock check before AJAX (prevents unnecessary requests)
            const frontendLockKey = 'amadex_booking_lock_' + requestHash;
            const existingLock = sessionStorage.getItem(frontendLockKey);
            if (existingLock) {
                const lockAge = Date.now() - parseInt(existingLock);
                // If lock is less than 60 seconds old, it's a duplicate request
                if (lockAge < 60000) {
                    showBookingErrorModal('A booking request is already being processed. Please wait a few seconds and try again.');
                    window.amadexBookingSubmissionInProgress = false;
                    hideBookingProcessingModal();
                    if (submitBtn.length) {
                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    }
                    return;
                }
            }

            // ✅ Set frontend lock (60 seconds TTL to match backend)
            sessionStorage.setItem(frontendLockKey, Date.now().toString());

            // Clean up old locks (older than 60 seconds)
            try {
                Object.keys(sessionStorage).forEach(key => {
                    if (key.startsWith('amadex_booking_lock_')) {
                        const lockTime = parseInt(sessionStorage.getItem(key));
                        if (lockTime && (Date.now() - lockTime) > 60000) {
                            sessionStorage.removeItem(key);
                        }
                    }
                });
            } catch (e) {
                // Ignore cleanup errors
            }

            // ✅ Collect device fingerprint for fraud detection
            let deviceFingerprintData = null;
            try {
                if (window.AmadexFraudDetection && typeof window.AmadexFraudDetection.getCompleteFraudData === 'function') {
                    deviceFingerprintData = window.AmadexFraudDetection.getCompleteFraudData();
                } else {
                }
            } catch (error) {
                // Continue without fingerprint data
            }

            // Submit to server - wrap in booking_data as expected by server
            // Send booking_data as JSON string to ensure proper transmission
            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_process_booking',
                    nonce: AmadexConfig.nonce,
                    booking_data: JSON.stringify(bookingData), // Stringify to ensure proper transmission
                    payment_token: paymentToken,  // Also send separately for easy access
                    request_hash: requestHash, // Include hash for backend deduplication
                    device_fingerprint: deviceFingerprintData ? JSON.stringify(deviceFingerprintData) : null // Include device fingerprint
                },
                timeout: 60000, // 60 second timeout
                success: function (response) {
                    // ✅ Always reset flag
                    window.amadexBookingSubmissionInProgress = false;

                    // ✅ FIX #5: Clear frontend lock on success
                    const frontendLockKey = 'amadex_booking_lock_' + requestHash;
                    sessionStorage.removeItem(frontendLockKey);

                    if (response && response.success) {
                        const message = response.data?.message || 'Booking successful! Confirmation details will be sent to your email.';
                        const bookingRef = response.data?.booking_reference || sessionStorage.getItem('amadex_booking_reference') || '';
                        const paymentSuccess = response.data?.payment_success !== false; // Default to true if not specified

                        // Hide any payment errors since booking was successful
                        hidePaymentError();

                        if (bookingRef) {
                            sessionStorage.setItem('amadex_booking_reference', bookingRef);
                        }

                        // GA4 purchase: fire before clearing booking flight data
                        storeAmadexPendingPurchaseEvent(flight, bookingRef);

                        // CRITICAL: Clear all booking-specific sessionStorage data BEFORE redirect
                        // This prevents duplicate bookings when user clicks back button
                        // Industry standard: Clear booking data immediately after success
                        const bookingKeysToClear = [
                            'amadex_booking_flight',
                            'amadex_search_data',
                            'amadexBookingStage',
                            'amadex_booking_step',
                            'amadex_booking_timer_start',
                            'amadex_booking_timer_remaining',
                            'amadex_booking_timer_paused_at',
                            'amadex_last_booking_flight_id',
                            'amadex_booking_addons',
                            'amadex_premium_service_added',
                            'amadex_multi_city_bookings',
                            'amadex_multi_city_segments',
                            'amadex_booking_all_segments',
                            'amadex_results_page_url'
                        ];

                        bookingKeysToClear.forEach(function (key) {
                            if (sessionStorage.getItem(key) !== null) {
                                sessionStorage.removeItem(key);
                            }
                        });

                        // ✅ Show success state in modal
                        showBookingSuccessModal(bookingRef, message);

                        const confirmationUrl = buildConfirmationUrl(bookingRef, response?.data?.confirmation_url);
                        // ✅ Redirect after delay
                        setTimeout(function () {
                            hideBookingProcessingModal();
                            window.location.href = confirmationUrl;
                        }, 2000);
                    } else {
                        // Show error message with more details - use the actual server message
                        let errorMsg = response?.data?.message || response?.message || 'Booking failed. Please try again.';

                        // Only enhance specific error messages, but keep the server's detailed messages
                        if (errorMsg.toLowerCase().includes('payment declined') || errorMsg.toLowerCase().includes('transaction declined')) {
                            // Keep the server's detailed message if it's about payment decline
                            if (!errorMsg.toLowerCase().includes('check your card details') && !errorMsg.toLowerCase().includes('sufficient funds')) {
                                errorMsg = 'Payment was declined. Please check your card details, ensure sufficient funds, or try a different payment method. If the problem persists, please contact support.';
                            }
                        } else if (errorMsg.toLowerCase().includes('payment token is missing')) {
                            // Keep the detailed message from server about payment token
                            // Don't change it - server provides helpful instructions
                        } else if (errorMsg.toLowerCase().includes('api key') && errorMsg.toLowerCase().includes('not configured')) {
                            // Keep the detailed message from server about API key configuration
                            // Don't change it - server provides helpful instructions
                        } else if (errorMsg.toLowerCase().includes('tokenization key') && errorMsg.toLowerCase().includes('not configured')) {
                            // Keep the detailed message from server about tokenization key
                            // Don't change it - server provides helpful instructions
                        } else if (errorMsg.toLowerCase().includes('invalid') || errorMsg.toLowerCase().includes('validation')) {
                            // Only enhance if it's a generic validation error
                            if (errorMsg.length < 50) {
                                errorMsg = 'Payment validation failed. Please check your card details and billing information.';
                            }
                        }

                        // ✅ Show error state in modal
                        showBookingErrorModal(errorMsg);

                        // Also show payment error for visibility
                        showPaymentError(errorMsg);
                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    }
                },
                error: function (xhr, status, error) {
                    // ✅ Always reset flag
                    window.amadexBookingSubmissionInProgress = false;

                    // ✅ FIX #5: Clear frontend lock on error
                    const frontendLockKey = 'amadex_booking_lock_' + requestHash;
                    sessionStorage.removeItem(frontendLockKey);

                    // ✅ Check for duplicate request error and parse error response
                    let errorMessage = 'An error occurred. Please try again or contact support.';

                    // First, check for duplicate request error
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        if (xhr.responseJSON.data.code === 'DUPLICATE_REQUEST') {
                            errorMessage = xhr.responseJSON.data.message || 'A booking request is already being processed. Please wait a few seconds and try again.';
                        } else if (xhr.responseJSON.data.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        // Fallback: check responseJSON.data.message directly
                        errorMessage = xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        // Try to parse responseText if responseJSON is not available
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            if (parsed.data && parsed.data.message) {
                                errorMessage = parsed.data.message;
                            }
                        } catch (e) {
                            // Not JSON, use default message
                        }
                    }

                    // Provide more helpful error messages
                    if (errorMessage.toLowerCase().includes('payment declined') || errorMessage.toLowerCase().includes('transaction declined')) {
                        errorMessage = 'Payment was declined. Please check your card details or try a different payment method. If the problem persists, please contact support.';
                    }

                    // ✅ Show error state in modal (ALWAYS closable)
                    showBookingErrorModal(errorMessage);

                    // Also show payment error for visibility
                    showPaymentError(errorMessage);
                    updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                }
            });
        } catch (error) {
            // ✅ FIX #4: Catch any unexpected errors and ensure flag is reset
            window.amadexBookingSubmissionInProgress = false;
            hideBookingProcessingModal();
            showBookingErrorModal('An unexpected error occurred. Please try again or contact support.');
            if (submitBtn.length) {
                updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
            }
            return false;
        } finally {
            // ✅ FIX #4: Ensure flag is reset even if function returns early
            // Note: This runs even if function returns, but we can't reset flag here
            // because we want to keep it true during AJAX call
            // Flag is reset in success/error handlers above
        }
    }

    /**
     * Validate booking form
     */
    function validateBookingForm() {
        // Contact information
        const phone = $('#contact-phone').val().trim();
        const email = $('#contact-email').val().trim();

        if (!phone) {
            return { valid: false, message: 'Please enter your contact phone number.' };
        }

        if (!isValidPhone(phone)) {
            return { valid: false, message: 'Please enter a valid phone number in format +1-XXX-XXX-XXXX.' };
        }

        if (!email || !isValidEmail(email)) {
            if (!email) {
                return { valid: false, message: 'Please enter your email address.' };
            } else {
                return { valid: false, message: 'Please enter a valid email address (e.g., example@email.com).' };
            }
        }

        // Passenger information
        const passengers = collectPassengers();
        if (passengers.length === 0) {
            return { valid: false, message: 'Please enter passenger information.' };
        }

        for (let i = 0; i < passengers.length; i++) {
            const pax = passengers[i];
            // Check both camelCase and lowercase property names for compatibility
            const firstName = pax.firstName || pax.firstname || '';
            const lastName = pax.lastName || pax.lastname || '';

            if (!firstName || !lastName) {
                return { valid: false, message: `Please enter name for Passenger ${i + 1}.` };
            }
            if (!pax.gender) {
                return { valid: false, message: `Please select gender for Passenger ${i + 1}.` };
            }
        }

        // Check gateway to determine if we should skip card validation (Stripe has separate payment page)
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';
        const skipCardValidation = gateway === 'stripe';

        // Billing information - Card Holder Name (for NMI dashboard)
        // SKIP for Stripe (card details are on separate payment page)
        if (!skipCardValidation) {
            const cardName = $('#card-name').val().trim();
            if (!cardName) {
                return { valid: false, message: 'Card Holder Name is required. This name will appear in billing records.' };
            }

            // Validate card name has at least first and last name
            const cardNameParts = cardName.split(/\s+/).filter(part => part.length > 0);
            if (cardNameParts.length < 2) {
                return { valid: false, message: 'Please enter both first and last name on card (e.g., "John Smith"). This ensures proper billing records in NMI.' };
            }
        }

        const country = $('#billing-country').val();
        const state = getBillingStateValue();
        const address1 = $('#billing-address1').val().trim();
        const city = $('#billing-city').val().trim();
        const postal = $('#billing-postal').val().trim();

        if (!country) {
            return { valid: false, message: 'Please select your country.' };
        }

        if (!state) {
            return { valid: false, message: 'Please provide your state or province.' };
        }

        if (!address1) {
            return { valid: false, message: 'Please enter your billing address.' };
        }

        if (!city) {
            return { valid: false, message: 'Please enter your city.' };
        }

        if (!postal) {
            return { valid: false, message: 'Please enter your postal/zip code.' };
        }

        return { valid: true };
    }

    /**
     * Validate email format (accepts any valid email address)
     */
    function isValidEmail(email) {
        if (!email || email.trim() === '') return false;
        const trimmedEmail = email.trim().toLowerCase();
        // Check if it's a valid email format (any domain: gmail, yahoo, outlook, etc.)
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return emailRegex.test(trimmedEmail);
    }

    /**
     * Format phone number - Keep digits only (country code selected separately)
     */
    function formatPhoneNumber(phone) {
        if (!phone) return '';
        // Remove all non-digit characters
        let cleaned = phone.replace(/\D/g, '');

        // Return digits only (10-15 digits allowed)
        // Country code is handled separately by the country code selector
        if (cleaned.length >= 10 && cleaned.length <= 15) {
            return cleaned;
        }

        return phone; // Return original if not in valid range
    }

    /**
     * Validate phone number format (digits only, 10-15 digits)
     * Country code selected separately via dropdown
     */
    function isValidPhone(phone) {
        if (!phone || phone.trim() === '') return false;
        const trimmedPhone = phone.trim();

        // Remove all non-digit characters
        const cleaned = trimmedPhone.replace(/\D/g, '');

        // Check if it's between 10 and 15 digits (international standard)
        if (cleaned.length >= 10 && cleaned.length <= 15) {
            return true;
        }

        return false;
    }

    /**
     * Validate name (first or last name)
     */
    function isValidName(name) {
        if (!name || name.trim() === '') return false;
        // Name should be at least 2 characters and contain only letters, spaces, hyphens, and apostrophes
        return /^[a-zA-Z\s\-']{2,}$/.test(name.trim());
    }

    /**
     * Validate date of birth
     */
    function isValidDateOfBirth(day, month, year) {
        if (!day || !month || !year) return false;
        const d = parseInt(day, 10);
        const m = parseInt(month, 10);
        const y = parseInt(year, 10);

        if (isNaN(d) || isNaN(m) || isNaN(y)) return false;
        if (y < 1900 || y > new Date().getFullYear()) return false;
        if (m < 1 || m > 12) return false;
        if (d < 1 || d > 31) return false;

        // Check if date is valid (e.g., not Feb 30)
        const date = new Date(y, m - 1, d);
        return date.getFullYear() === y &&
            date.getMonth() === m - 1 &&
            date.getDate() === d;
    }

    /**
     * Show error message for a field
     */
    function showFieldError(fieldId, message) {
        const $field = $(fieldId);
        if (!$field.length) return;

        $field.addClass('error');

        // Find the parent form field container
        const $formField = $field.closest('.amadex-form-field');

        // Remove existing error message
        $formField.find('.amadex-error-message').remove();

        // Add error message after the field (inside the form field container)
        const $errorMsg = $('<span class="amadex-error-message">' + message + '</span>');
        $field.after($errorMsg);
    }

    /**
     * Clear error message for a field
     */
    function clearFieldError(fieldId) {
        const $field = $(fieldId);
        if (!$field.length) return;

        $field.removeClass('error');

        // Find the parent form field container and remove error message
        const $formField = $field.closest('.amadex-form-field');
        $formField.find('.amadex-error-message').remove();
    }

    /**
     * Validate a single field and show/clear error
     */
    function validateField(fieldId, fieldType, value) {
        const $field = $(fieldId);
        if (!$field.length) return true;

        const isRequired = $field.prop('required') || $field.attr('required') !== undefined;
        const trimmedValue = value ? value.toString().trim() : '';

        // Clear previous error
        clearFieldError(fieldId);

        // Check if required field is empty
        if (isRequired && (!trimmedValue || trimmedValue === '')) {
            showFieldError(fieldId, 'This field is required');
            return false;
        }

        // If field is empty and not required, it's valid
        if (!trimmedValue || trimmedValue === '') {
            return true;
        }

        // Validate based on field type
        let isValid = true;
        let errorMessage = '';

        switch (fieldType) {
            case 'email':
                if (!isValidEmail(trimmedValue)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address (e.g., example@email.com)';
                }
                break;

            case 'phone':
                if (!isValidPhone(trimmedValue)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid phone number (digits only, 10-15 digits)';
                }
                break;

            case 'name':
                if (!isValidName(trimmedValue)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid name';
                }
                break;

            case 'select':
                // For select fields, just check if value is selected
                if (isRequired && (!trimmedValue || trimmedValue === '')) {
                    isValid = false;
                    errorMessage = 'Please select an option';
                }
                break;
        }

        if (!isValid) {
            showFieldError(fieldId, errorMessage);
        }

        return isValid;
    }

    /**
     * Initialize real-time validation for all form fields
     */
    function initFormValidation() {
        // Live clear: remove error state as user fills in passenger fields
        $(document).on('input change', '.amadex-passenger-form-card input, .amadex-passenger-form-card select, .amadex-passenger-form input, .amadex-passenger-form select', function () {
            const $f = $(this);
            if ($f.val() && $f.val().trim() !== '') {
                $f.removeClass('apv-error').addClass('apv-valid');
                const $wrap = $f.closest('.amadex-form-field');
                $wrap.removeClass('apv-field-error');
                $wrap.find('.apv-msg').remove();
            }
        });
        $(document).on('change', '.amadex-passenger-form-card input[type="radio"]', function () {
            const name = $(this).attr('name');
            $(`input[name="${name}"]`).closest('.amadex-form-field').removeClass('apv-field-error').find('.apv-msg').remove();
        });

        // Contact email validation (accepts any valid email domain)
        $('#contact-email').on('blur change', function () {
            let emailValue = $(this).val().trim();
            // Validate any email format (@gmail.com, @yahoo.com, @outlook.com, etc.)
            validateField('#contact-email', 'email', emailValue);
        });

        // Phone number input - digits only (no auto +1)
        $('#contact-phone').on('input', function () {
            let phoneValue = $(this).val();
            // Remove any non-digit characters as user types
            let digitsOnly = phoneValue.replace(/\D/g, '');
            if (digitsOnly !== phoneValue) {
                $(this).val(digitsOnly);
            }
        });

        $('#contact-phone').on('blur change', function () {
            let phoneValue = $(this).val().trim();
            // Validate digits only (10-15 digits)
            // No +1 formatting - country code selected separately
            validateField('#contact-phone', 'phone', phoneValue);
        });

        $('#contact-country-code').on('change', function () {
            validateField('#contact-country-code', 'select', $(this).val());
        });
        // ─── PASSENGER & CONTACT AUTO-SAVE / RESTORE (cookie, 30 days) ────────────
        var PAX_COOKIE = 'amadex_passenger_data';
        var PAX_COOKIE_DAYS = 30;

        function setPaxCookie(name, value, days) {
            var d = new Date();
            d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
            document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax; Secure`;
        }

        function getPaxCookie(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i].trim();
                if (c.indexOf(nameEQ) === 0) {
                    try { return decodeURIComponent(c.substring(nameEQ.length)); } catch (e) { return null; }
                }
            }
            return null;
        }

        // Collect all passenger + contact data from the DOM
        function collectPassengerCookieData() {
            var data = { passengers: [], contact: {} };

            // Find all passenger form cards (adults) and forms (children/infants)
            $('[id^="pax"][id$="-firstname"]').each(function () {
                var m = this.id.match(/^pax(\d+)-firstname$/);
                if (!m) return;
                var i = m[1];
                var pax = {
                    index: i,
                    firstname: $('#pax' + i + '-firstname').val() || '',
                    middlename: $('#pax' + i + '-middlename').val() || '',
                    lastname: $('#pax' + i + '-lastname').val() || '',
                    gender: $('input[name="pax' + i + '-gender"]:checked').val() || '',
                    dob_day: $('#pax' + i + '-dob-day').val() || '',
                    dob_month: $('#pax' + i + '-dob-month').val() || '',
                    dob_year: $('#pax' + i + '-dob-year').val() || '',
                    nationality: $('#pax' + i + '-nationality').val() || '',
                    passport_no: $('#pax' + i + '-passport-no').val() || '',
                    passport_country: $('#pax' + i + '-passport-country').val() || '',
                    passport_exp_day: $('#pax' + i + '-passport-exp-day').val() || '',
                    passport_exp_month: $('#pax' + i + '-passport-exp-month').val() || '',
                    passport_exp_year: $('#pax' + i + '-passport-exp-year').val() || ''
                };
                // Only save if at least first name is filled
                if (pax.firstname) data.passengers.push(pax);
            });

            // Contact info
            data.contact = {
                country_code: $('#contact-country-code').val() || '',
                phone: $('#contact-phone').val() || '',
                email: $('#contact-email').val() || ''
            };

            return data;
        }

        // Write to cookie whenever any field changes
        function autoSavePassengerData() {
            var data = collectPassengerCookieData();
            var hasContent = data.passengers.some(function (p) { return p.firstname; }) ||
                data.contact.email || data.contact.phone;
            if (hasContent) {
                setPaxCookie(PAX_COOKIE, JSON.stringify(data), PAX_COOKIE_DAYS);
            }
        }

        // function restorePassengerFromCookie() {
        window.amadexRestorePassengerFromCookie = function () { restorePassengerFromCookie(); };
        function restorePassengerFromCookie() {
            var raw = getPaxCookie(PAX_COOKIE);
            if (!raw) return;
            try {
                var data = JSON.parse(raw);

                // Restore each passenger
                (data.passengers || []).forEach(function (pax) {
                    var i = pax.index;
                    if (!$('#pax' + i + '-firstname').length) return; // field not in DOM yet

                    if (pax.firstname) $('#pax' + i + '-firstname').val(pax.firstname);
                    if (pax.middlename) $('#pax' + i + '-middlename').val(pax.middlename);
                    if (pax.lastname) $('#pax' + i + '-lastname').val(pax.lastname);
                    if (pax.gender) $('input[name="pax' + i + '-gender"][value="' + pax.gender + '"]').prop('checked', true);
                    if (pax.dob_day) $('#pax' + i + '-dob-day').val(pax.dob_day);
                    if (pax.dob_month) $('#pax' + i + '-dob-month').val(pax.dob_month);
                    if (pax.dob_year) $('#pax' + i + '-dob-year').val(pax.dob_year);
                    if (pax.nationality) $('#pax' + i + '-nationality').val(pax.nationality);
                    if (pax.passport_no) $('#pax' + i + '-passport-no').val(pax.passport_no);
                    if (pax.passport_country) $('#pax' + i + '-passport-country').val(pax.passport_country);
                    if (pax.passport_exp_day) $('#pax' + i + '-passport-exp-day').val(pax.passport_exp_day);
                    if (pax.passport_exp_month) $('#pax' + i + '-passport-exp-month').val(pax.passport_exp_month);
                    if (pax.passport_exp_year) $('#pax' + i + '-passport-exp-year').val(pax.passport_exp_year);
                });

                // Restore contact
                if (data.contact) {
                    if (data.contact.country_code) $('#contact-country-code').val(data.contact.country_code);
                    if (data.contact.phone) $('#contact-phone').val(data.contact.phone);
                    if (data.contact.email) $('#contact-email').val(data.contact.email);
                }
            } catch (e) {
                console.warn('amadex: could not restore passenger cookie', e);
            }
        }

        // Auto-save on any field change inside passenger forms or contact section
        $(document).on('input change blur',
            '[id^="pax"], #contact-phone, #contact-email, #contact-country-code',
            function () {
                clearTimeout(window._amadexPaxSaveTimer);
                window._amadexPaxSaveTimer = setTimeout(autoSavePassengerData, 600);
            }
        );

        // Restore once passenger forms are populated (they're injected dynamically)
        $(document).on('amadex:passengersPopulated', function () {
            restorePassengerFromCookie();
        });

        // Also observe DOM for passenger form injection (MutationObserver fallback)
        (function () {
            var $container = document.getElementById('amadex-passenger-forms');
            if (!$container) return;
            var observer = new MutationObserver(function (mutations) {
                var hasNewInputs = mutations.some(function (m) {
                    return Array.from(m.addedNodes).some(function (n) {
                        return n.nodeType === 1 && (n.querySelector && n.querySelector('[id^="pax1-"]'));
                    });
                });
                if (hasNewInputs) {
                    setTimeout(restorePassengerFromCookie, 100);
                    observer.disconnect(); // Only need to run once per page load
                }
            });
            observer.observe($container, { childList: true, subtree: true });
        })();
        // ──────────────────────────────────────────────────────────────────────────
        // Passenger fields validation (will be initialized when forms are populated)
        $(document).on('blur change', '[id^="pax"][id$="-firstname"]', function () {
            const fieldId = '#' + $(this).attr('id');
            validateField(fieldId, 'name', $(this).val());
        });

        $(document).on('blur change', '[id^="pax"][id$="-lastname"]', function () {
            const fieldId = '#' + $(this).attr('id');
            validateField(fieldId, 'name', $(this).val());
        });

        $(document).on('change', '[id^="pax"][id$="-dob-day"], [id^="pax"][id$="-dob-month"], [id^="pax"][id$="-dob-year"]', function () {
            const fieldId = $(this).attr('id');
            const paxIndex = fieldId.match(/pax(\d+)-/)[1];
            const day = $(`#pax${paxIndex}-dob-day`).val();
            const month = $(`#pax${paxIndex}-dob-month`).val();
            const year = $(`#pax${paxIndex}-dob-year`).val();

            if (day && month && year) {
                if (!isValidDateOfBirth(day, month, year)) {
                    showFieldError(`#pax${paxIndex}-dob-day`, 'Please enter a valid date of birth');
                    showFieldError(`#pax${paxIndex}-dob-month`, '');
                    showFieldError(`#pax${paxIndex}-dob-year`, '');
                } else {
                    clearFieldError(`#pax${paxIndex}-dob-day`);
                    clearFieldError(`#pax${paxIndex}-dob-month`);
                    clearFieldError(`#pax${paxIndex}-dob-year`);
                }
            }
        });

        $(document).on('change', '[id^="pax"][id$="-nationality"]', function () {
            const fieldId = '#' + $(this).attr('id');
            validateField(fieldId, 'select', $(this).val());
        });

        $(document).on('change', 'input[name^="pax"][name$="-gender"]', function () {
            const name = $(this).attr('name');
            const paxIndex = name.match(/pax(\d+)-/)[1];
            const $checked = $(`input[name="pax${paxIndex}-gender"]:checked`);
            if ($checked.length === 0) {
                showFieldError(`#pax${paxIndex}-firstname`, 'Please select gender');
            } else {
                clearFieldError(`#pax${paxIndex}-firstname`);
            }
        });

        // Billing fields validation
        $('#billing-country').on('change', function () {
            validateField('#billing-country', 'select', $(this).val());
        });

        $('#billing-state').on('change', function () {
            validateField('#billing-state', 'select', $(this).val());
        });

        $('#billing-address1').on('blur change', function () {
            const value = $(this).val().trim();
            if (!value) {
                showFieldError('#billing-address1', 'This field is required');
            } else {
                clearFieldError('#billing-address1');
            }
        });

        // Initialize Google Places Autocomplete for billing address
        // Try immediately, and also set up retry mechanism for when Google API loads
        initGooglePlacesAutocomplete();

        // Also try after a delay in case Google API loads later
        setTimeout(function () {
            initGooglePlacesAutocomplete();
        }, 1000);

        // Try again after 2 seconds
        setTimeout(function () {
            initGooglePlacesAutocomplete();
        }, 2000);

        $('#billing-city').on('blur change', function () {
            const value = $(this).val().trim();
            if (!value) {
                showFieldError('#billing-city', 'This field is required');
            } else {
                clearFieldError('#billing-city');
            }
        });

        $('#billing-postal').on('blur change', function () {
            const value = $(this).val().trim();
            if (!value) {
                showFieldError('#billing-postal', 'This field is required');
            } else {
                clearFieldError('#billing-postal');
            }
        });

        $('#card-name').on('blur change', function () {
            const value = $(this).val().trim();
            if (!value) {
                showFieldError('#card-name', 'Card Holder Name is required');
            } else {
                // Validate that it has at least first and last name
                const nameParts = value.split(/\s+/).filter(part => part.length > 0);
                if (nameParts.length < 2) {
                    showFieldError('#card-name', 'Please enter both first and last name (e.g., "John Smith")');
                } else {
                    clearFieldError('#card-name');
                }
            }
        });
    }

    /**
     * Validate all form fields before submission
     */
    function validateAllFields() {
        let isValid = true;

        // Check gateway to determine if we should skip card validation (Stripe has separate payment page)
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';
        const skipCardValidation = gateway === 'stripe';

        // Contact fields
        if (!validateField('#contact-email', 'email', $('#contact-email').val())) {
            isValid = false;
        }
        if (!validateField('#contact-phone', 'phone', $('#contact-phone').val())) {
            isValid = false;
        }
        if (!validateField('#contact-country-code', 'select', $('#contact-country-code').val())) {
            isValid = false;
        }

        // Passenger fields
        $('[id^="pax"][id$="-firstname"]').each(function () {
            if (!validateField('#' + $(this).attr('id'), 'name', $(this).val())) {
                isValid = false;
            }
        });

        $('[id^="pax"][id$="-lastname"]').each(function () {
            if (!validateField('#' + $(this).attr('id'), 'name', $(this).val())) {
                isValid = false;
            }
        });

        // Date of birth validation
        $('[id^="pax"][id$="-dob-day"]').each(function () {
            const fieldId = $(this).attr('id');
            const paxIndex = fieldId.match(/pax(\d+)-/)[1];
            const day = $(`#pax${paxIndex}-dob-day`).val();
            const month = $(`#pax${paxIndex}-dob-month`).val();
            const year = $(`#pax${paxIndex}-dob-year`).val();

            if (!day || !month || !year) {
                showFieldError(`#pax${paxIndex}-dob-day`, 'Please enter date of birth');
                isValid = false;
            } else if (!isValidDateOfBirth(day, month, year)) {
                showFieldError(`#pax${paxIndex}-dob-day`, 'Please enter a valid date of birth');
                isValid = false;
            } else {
                clearFieldError(`#pax${paxIndex}-dob-day`);
            }
        });

        // Gender validation
        $('.amadex-passenger-form-card').each(function () {
            const $card = $(this);
            const passengerIndex = $card.data('passenger-index') || $card.find('[id^="pax"][id$="-firstname"]').attr('id').match(/pax(\d+)-/)[1];
            const $checked = $(`input[name="pax${passengerIndex}-gender"]:checked`);
            if ($checked.length === 0) {
                showFieldError(`#pax${passengerIndex}-firstname`, 'Please select gender');
                isValid = false;
            }
        });

        // Nationality validation
        $('[id^="pax"][id$="-nationality"]').each(function () {
            if (!validateField('#' + $(this).attr('id'), 'select', $(this).val())) {
                isValid = false;
            }
        });

        // Billing fields – skip for PayPal, Crypto.com, MoonPay (flight booking, no billing required for these)
        const paymentMethod = ($('.amadex-payment-tab.is-active').attr('data-method') || $('#payment-method').val() || $('#payment-method-moonpay').val() || $('#payment-method-moonpay-onramp').val() || $('#payment-method-crypto-com').val() || '').trim();
        const skipBillingValidation = paymentMethod === 'paypal' || paymentMethod === 'crypto_com' || paymentMethod === 'moonpay' || paymentMethod === 'moonpay_onramp';
        if (!skipBillingValidation) {
            if (!validateField('#billing-country', 'select', $('#billing-country').val())) {
                isValid = false;
            }
            if (!validateField('#billing-state', 'select', $('#billing-state').val())) {
                isValid = false;
            }
            if (!validateField('#billing-address1', 'text', $('#billing-address1').val())) {
                isValid = false;
            }
            if (!validateField('#billing-city', 'text', $('#billing-city').val())) {
                isValid = false;
            }
            if (!validateField('#billing-postal', 'text', $('#billing-postal').val())) {
                isValid = false;
            }
        }

        // Card name validation - SKIP for Stripe (separate payment page) and PayPal
        if (!skipCardValidation && paymentMethod === 'credit_card') {
            if (!validateField('#card-name', 'text', $('#card-name').val())) {
                isValid = false;
            }
        } else if (skipCardValidation) {
        } else if (paymentMethod === 'paypal') {
        } else if (paymentMethod === 'crypto_com') {
        } else if (paymentMethod === 'moonpay') {
        }

        return isValid;
    }

    /**
     * Show payment error message
     */
    function showPaymentError(message) {
        const msg = '<strong>⚠️ Payment Error:</strong> ' + message;
        const $stripeForm = $('#amadex-card-form-stripe');
        if ($stripeForm.length && $stripeForm.is(':visible')) {
            $('#amadex-payment-error').html(msg).slideDown();
            $('#amadex-stripe-card-errors').html(msg).css('color', '#d63638');
        } else {
            $('#amadex-payment-error-nmi').html(msg).slideDown();
        }
        const errorDiv = $('#amadex-payment-error, #amadex-payment-error-nmi, #amadex-stripe-card-errors').filter(':visible').first();
        if (errorDiv.length) {
            $('html, body').animate({ scrollTop: errorDiv.offset().top - 100 }, 500);
        }
    }

    /**
     * Hide payment error message
     */
    function hidePaymentError() {
        $('#amadex-payment-error, #amadex-payment-error-nmi').slideUp();
        $('#amadex-stripe-card-errors').text('');
    }

    /**
     * Helper functions
     */
    function formatTime(date) {
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function formatFullDate(date) {
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${days[date.getDay()]}, ${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
    }

    function calculateLayoverTime(seg1, seg2) {
        const arr = new Date(seg1.arrival.at);
        const dep = new Date(seg2.departure.at);
        const diff = (dep - arr) / 1000 / 60; // minutes
        const hours = Math.floor(diff / 60);
        const mins = Math.floor(diff % 60);
        return `${String(hours).padStart(2, '0')} hours${mins > 0 ? ' ' + String(mins).padStart(2, '0') + ' min' : ''}`;
    }

    function generateOptions(start, end) {
        let html = '';
        for (let i = start; i <= end; i++) {
            html += `<option value="${String(i).padStart(2, '0')}">${String(i).padStart(2, '0')}</option>`;
        }
        return html;
    }

    function generateMonthOptions() {
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        let html = '';
        months.forEach((month, idx) => {
            html += `<option value="${String(idx + 1).padStart(2, '0')}">${month}</option>`;
        });
        return html;
    }

    function generateYearOptions(startYear = null, endYear = null) {
        let html = '';
        const currentYear = new Date().getFullYear();

        // If startYear and endYear are provided, generate forward years (for passport expiry, etc.)
        if (startYear !== null && endYear !== null) {
            for (let i = startYear; i <= endYear; i++) {
                html += `<option value="${i}">${i}</option>`;
            }
        } else {
            // Default: generate backward years (for date of birth)
            for (let i = currentYear; i >= currentYear - 100; i--) {
                html += `<option value="${i}">${i}</option>`;
            }
        }
        return html;
    }

    /**
  * Generate nationality options from AMADEX_COUNTRY_LIST
  */
    function generateNationalityOptions(defaultCountry = 'US') {
        if (!AMADEX_COUNTRY_LIST || !Array.isArray(AMADEX_COUNTRY_LIST)) {
            // Fallback if country list is not available
            return `
                <option value="US" ${defaultCountry === 'US' ? 'selected' : ''}>United States</option>
                <option value="GB">United Kingdom</option>
                <option value="IN">India</option>
                <option value="CA">Canada</option>
                <option value="AU">Australia</option>
            `;
        }

        let html = '';
        const sortedCountries = AMADEX_COUNTRY_LIST
            .slice()
            .sort((a, b) => a.name.localeCompare(b.name));

        sortedCountries.forEach(country => {
            const isSelected = country.code === defaultCountry ? 'selected' : '';
            html += `<option value="${country.code}" ${isSelected}>${country.name}</option>`;
        });

        return html;
    }

    /**
     * Get airline name from code
     */
    function getAirlineName(code) {
        const airlines = {
            'AA': 'American Airlines',
            'AC': 'Air Canada',
            'AF': 'Air France',
            'AI': 'Air India',
            'AY': 'Finnair',
            'BA': 'British Airways',
            'DL': 'Delta Air Lines',
            'EK': 'Emirates',
            'EY': 'Etihad Airways',
            'IB': 'Iberia',
            'JL': 'Japan Airlines',
            'KE': 'Korean Air',
            'KL': 'KLM',
            'LH': 'Lufthansa',
            'LX': 'Swiss International Air Lines',
            'NH': 'All Nippon Airways',
            'OS': 'Austrian Airlines',
            'QF': 'Qantas',
            'QR': 'Qatar Airways',
            'SQ': 'Singapore Airlines',
            'TK': 'Turkish Airlines',
            'UA': 'United Airlines',
            'VS': 'Virgin Atlantic',
            '6E': 'IndiGo',
            '9W': 'Jet Airways',
            'SG': 'SpiceJet',
            'UK': 'Vistara',
            'G8': 'Go Air',
            'I5': 'AirAsia India',
            'QP': 'Akasa Air',
            'WY': 'Oman Air',
            'ME': 'Middle East Airlines',
            'MS': 'EgyptAir',
            'ET': 'Ethiopian Airlines',
            'KQ': 'Kenya Airways',
            'SA': 'South African Airways',
            'MH': 'Malaysia Airlines',
            'TG': 'Thai Airways',
            'VN': 'Vietnam Airlines',
            'CI': 'China Airlines',
            'BR': 'EVA Air',
            'CZ': 'China Southern Airlines',
            'MU': 'China Eastern Airlines',
            'CA': 'Air China',
            'HU': 'Hainan Airlines',
            'AZ': 'ITA Airways',
            'FR': 'Ryanair',
            'U2': 'easyJet',
            'W6': 'Wizz Air',
            'VY': 'Vueling',
            'TP': 'TAP Air Portugal',
            'SN': 'Brussels Airlines',
            'SK': 'SAS Scandinavian Airlines',
            'AZ': 'Alitalia',
            'UX': 'Air Europa'
        };

        return airlines[code] || code;
    }

    function getFlightBaggageDetails(flight) {
        if (!flight || !flight.travelerPricings) {
            return null;
        }

        let fallbackLabel = null;

        for (const traveler of flight.travelerPricings) {
            const travelerType = traveler.travelerType || traveler.traveler_type;
            const travelerLabel = formatTravelerType(travelerType);
            if (!fallbackLabel && travelerLabel) {
                fallbackLabel = travelerLabel;
            }

            const fareDetails = traveler.fareDetailsBySegment || traveler.fare_details_by_segment || [];
            for (const fare of fareDetails) {
                const allowance = buildBaggageAllowanceResponse(travelerLabel, fare);
                if (allowance) {
                    return allowance;
                }
            }
        }

        if (fallbackLabel) {
            return {
                traveler: fallbackLabel,
                checkIn: 'Not Available',
                cabin: 'Not Available'
            };
        }

        return null;
    }

    function getSegmentBaggageDetails(flight, segment) {
        if (!flight || !flight.travelerPricings) {
            return null;
        }

        const targetId = getSegmentIdentifier(segment);
        let fallbackLabel = null;

        for (const traveler of flight.travelerPricings) {
            const travelerType = traveler.travelerType || traveler.traveler_type;
            const travelerLabel = formatTravelerType(travelerType);
            if (!fallbackLabel && travelerLabel) {
                fallbackLabel = travelerLabel;
            }

            const fareDetails = traveler.fareDetailsBySegment || traveler.fare_details_by_segment || [];
            for (const fare of fareDetails) {
                const fareSegmentId = fare.segmentId || fare.segment_id || fare.segment || fare.id;
                if (!targetId || !fareSegmentId || String(fareSegmentId) === String(targetId)) {
                    const allowance = buildBaggageAllowanceResponse(travelerLabel, fare);
                    if (allowance) {
                        return allowance;
                    }
                }
            }
        }

        if (fallbackLabel) {
            return {
                traveler: fallbackLabel,
                checkIn: 'Not Available',
                cabin: 'Not Available'
            };
        }

        return null;
    }

    /**
     * Get cabin class display name
     */
    function getCabinClassName(code) {
        const cabinClasses = {
            'ECONOMY': 'Economy',
            'PREMIUM_ECONOMY': 'Premium Economy',
            'BUSINESS': 'Business',
            'FIRST': 'First Class',
            'NO PREFERENCE': 'No Preference'
        };

        return cabinClasses[code] || code;
    }

    function formatTravelerType(code) {
        if (!code) return '';
        const map = {
            'ADT': 'Adult',
            'ADULT': 'Adult',
            'CNN': 'Child',
            'CHD': 'Child',
            'INF': 'Infant',
            'IN': 'Infant',
            'YTH': 'Youth',
            'SR': 'Senior'
        };
        const upper = String(code).trim().toUpperCase();
        return map[upper] || upper.charAt(0) + upper.slice(1).toLowerCase();
    }

    function formatBaggageAllowance(bag) {
        if (!bag) return null;

        const quantity = bag.quantity ?? bag.qty ?? bag.pieces;
        const weight = bag.weight ?? bag.kg ?? bag.lb;
        const unit = bag.weightUnit || bag.weight_unit || bag.unit || (bag.lb ? 'lb' : 'kg');

        if (weight && quantity) {
            return `${weight} ${unit.toUpperCase()} (${quantity} Piece${quantity > 1 ? 's' : ''} x ${weight} ${unit.toUpperCase()})`;
        }

        if (weight) {
            return `${weight} ${unit.toUpperCase()}`;
        }

        if (quantity) {
            return `${quantity} Piece${quantity > 1 ? 's' : ''}`;
        }

        return null;
    }

    function buildBaggageAllowanceResponse(travelerLabel, fare) {
        const checkedBag = fare?.includedCheckedBags || fare?.included_checked_bags || fare?.baggageAllowance;
        const cabinBag = fare?.cabinBaggage || fare?.cabin_baggage || fare?.cabinBags || fare?.cabin_allowance;
        const checkInText = formatBaggageAllowance(checkedBag);
        const cabinText = formatBaggageAllowance(cabinBag);

        if (!checkInText && !cabinText) {
            return null;
        }

        return {
            traveler: travelerLabel || 'Adult',
            checkIn: checkInText || 'Not Available',
            cabin: cabinText || 'Not Available'
        };
    }

    function getSegmentIdentifier(segment) {
        if (!segment) return null;
        return segment.id || segment.segmentId || segment.segment_id || segment.segmentReference || segment.segment_reference || null;
    }

    /**
     * Initialize payment method tabs
     */
    function initPaymentMethodTabs() {
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';

        // Stripe: hide other payment methods (crypto, PayPal), show only Stripe. NMI: show all.
        if (gateway === 'stripe') {
            $('.amadex-payment-tab').removeClass('is-active');
            $('.amadex-payment-tab[data-method="crypto_transfer"], .amadex-payment-tab[data-method="crypto_com"], .amadex-payment-tab[data-method="moonpay"], .amadex-payment-tab[data-method="moonpay_onramp"], .amadex-payment-tab[data-method="paypal"]').addClass('amadex-payment-tab-hidden').hide();
            $('.amadex-payment-tab[data-method="credit_card"]').removeClass('amadex-payment-tab-hidden').show().addClass('is-active');
            $('#amadex-payment-methods-list').addClass('amadex-payment-methods-stripe-only');
        } else {
            $('.amadex-payment-tab').removeClass('amadex-payment-tab-hidden').show();
            $('#amadex-payment-methods-list').removeClass('amadex-payment-methods-stripe-only');
        }

        $('.amadex-payment-tab:not(.amadex-payment-tab-hidden)').off('click.paymentTabs').on('click.paymentTabs', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const method = $(this).data('method');
            var isMobile = typeof window !== 'undefined' && window.innerWidth <= 767;
            var wasActive = $(this).hasClass('is-active');

            // if (isMobile && wasActive) {
            //     var $item = $(this).closest('.amadex-payment-accordion-item');
            //     if ($item.length) {
            //         $item.toggleClass('amadex-payment-accordion-item-collapsed');
            //     }
            //     return;
            // }

            if (isMobile && wasActive) {
                var $item = $(this).closest('.amadex-payment-accordion-item');
                if ($item.length) {
                    $item.toggleClass('amadex-payment-accordion-item-collapsed');
                }
                if (method !== 'paypal') {
                    $('#amadex-step-next').show().css('display', '').removeClass('amadex-paypal-mode-disabled').prop('disabled', false).removeAttr('title').off('click.paypalHint');
                    // $('#amadex-confirm-book').removeClass('amadex-paypal-mode-disabled').prop('disabled', false).removeAttr('title').off('click.paypalHint');
                    $('#amadex-step-next')
                        .show()
                        .css('display', '')
                        .removeClass('amadex-paypal-mode-disabled')
                        .prop('disabled', false)
                        .removeAttr('title')
                        .off('click.paypalHint');
                    $('#amadex-paypal-btn-hint').remove();
                }
                return;
            }

            $('.amadex-payment-accordion-item').removeClass('amadex-payment-accordion-item-collapsed');
            $('.amadex-payment-tab').removeClass('is-active');
            $(this).addClass('is-active');
            $('.amadex-payment-form-wrapper').hide();
            $('#amadex-card-form-stripe').hide();

            if (method === 'credit_card') {
                $('#amadex-billing-section').show();
                if (gateway === 'stripe') {
                    $('#amadex-credit-card-form').hide();
                    $('#amadex-card-form-stripe').show();
                } else {
                    $('#amadex-credit-card-form').show();
                }
                $('#payment-method').val('credit_card');
                $('#payment-method-crypto').val('credit_card');
                $('#payment-method-crypto-com').val('credit_card');
                $('#payment-method-paypal').val('credit_card');
                // Restore confirm/next buttons — remove PayPal blur state
                $('#amadex-confirm-book').removeClass('amadex-paypal-blurred').prop('disabled', false);
                $('#amadex-step-next').removeClass('amadex-paypal-blurred').prop('disabled', false).show().css('display', '');
                $('#amadex-payment-submit').removeClass('amadex-paypal-blurred');
            } else if (method === 'paypal') {
                $('#amadex-billing-section').hide();
                $('#amadex-paypal-form').show();
                $('#payment-method').val('paypal');
                $('#payment-method-crypto').val('paypal');
                $('#payment-method-crypto-com').val('paypal');
                $('#payment-method-paypal').val('paypal');
                // Blur & disable confirm/next buttons instead of hiding them
                $('#amadex-confirm-book').addClass('amadex-paypal-blurred').prop('disabled', true);
                $('#amadex-step-next').addClass('amadex-paypal-blurred').prop('disabled', true);
                $('#amadex-payment-submit').addClass('amadex-paypal-blurred').prop('disabled', true);
                initPayPalButtons();
            } else if (method === 'crypto_com') {
                $('#amadex-billing-section').show();
                $('#amadex-crypto-com-form').show();
                $('#amadex-moonpay-form').hide();
                $('#amadex-moonpay-onramp-form').hide();
                $('#payment-method').val('crypto_com');
                $('#payment-method-crypto').val('crypto_com');
                $('#payment-method-crypto-com').val('crypto_com');
                $('#payment-method-paypal').val('crypto_com');
                // Allow Pay button to render again if mount is empty (e.g. first load or tab re-open)
                var mount = document.getElementById('cryptocom-pay-button-mount');
                if (mount && mount.children.length === 0) {
                    window.amadexCryptoComButtonRendered = false;
                }
                // Show Confirm & Book in price bar and main area so user can create booking then pay with Crypto.com
                $('#amadex-confirm-book').removeClass('amadex-paypal-blurred').prop('disabled', false).show();
                $('#amadex-payment-submit').removeClass('amadex-paypal-blurred').hide();
                $('#amadex-step-next').removeClass('amadex-paypal-blurred').prop('disabled', false).show().text('Confirm & Book');
            } else if (method === 'moonpay_onramp') {
                $('#amadex-billing-section').show();
                $('#amadex-crypto-com-form').hide();
                $('#amadex-moonpay-form').hide();
                $('#amadex-moonpay-onramp-form').show();
                $('#payment-method').val('moonpay_onramp');
                $('#payment-method-crypto').val('moonpay_onramp');
                $('#payment-method-crypto-com').val('moonpay_onramp');
                $('#payment-method-paypal').val('moonpay_onramp');
                if ($('#payment-method-moonpay').length) $('#payment-method-moonpay').val('moonpay');
                if ($('#payment-method-moonpay-onramp').length) $('#payment-method-moonpay-onramp').val('moonpay_onramp');
                $('#amadex-confirm-book').removeClass('amadex-paypal-blurred').prop('disabled', false).show();
                $('#amadex-payment-submit').removeClass('amadex-paypal-blurred').hide();
                $('#amadex-step-next').removeClass('amadex-paypal-blurred').prop('disabled', false).show().text('Confirm & Book');
            } else if (method === 'moonpay') {
                $('#amadex-billing-section').show();
                $('#amadex-crypto-com-form').hide();
                $('#amadex-moonpay-onramp-form').hide();
                $('#amadex-moonpay-form').show();
                $('#payment-method').val('moonpay');
                $('#payment-method-crypto').val('moonpay');
                $('#payment-method-crypto-com').val('moonpay');
                $('#payment-method-paypal').val('moonpay');
                if ($('#payment-method-moonpay').length) $('#payment-method-moonpay').val('moonpay');
                if ($('#payment-method-moonpay-onramp').length) $('#payment-method-moonpay-onramp').val('moonpay');
                $('#amadex-confirm-book').removeClass('amadex-paypal-blurred').prop('disabled', false).show();
                $('#amadex-payment-submit').removeClass('amadex-paypal-blurred').hide();
                $('#amadex-step-next').removeClass('amadex-paypal-blurred').prop('disabled', false).show().text('Confirm & Book');
            } else if (method === 'crypto_transfer') {
                $('#amadex-billing-section').show();
                $('#amadex-crypto-transfer-form').show();
                $('#payment-method').val('crypto_transfer');
                $('#payment-method-crypto').val('crypto_transfer');
                $('#payment-method-crypto-com').val('crypto_transfer');
                $('#payment-method-paypal').val('crypto_transfer');
            } else {
                $('#amadex-billing-section').show();
                if (gateway === 'stripe') {
                    $('#amadex-payment-submit').removeClass('amadex-paypal-blurred').prop('disabled', false).show();
                    $('#amadex-confirm-book').hide();
                } else {
                    $('#amadex-confirm-book').removeClass('amadex-paypal-blurred').prop('disabled', false).show();
                    $('#amadex-payment-submit').removeClass('amadex-paypal-blurred').hide();
                }
                $('#amadex-step-next').removeClass('amadex-paypal-blurred').prop('disabled', false).show();
            }
        });

        // Set default to credit card (Stripe or NMI); ensure only credit_card is active
        $('.amadex-payment-tab').removeClass('is-active');
        $('.amadex-payment-tab[data-method="credit_card"]').addClass('is-active');
        $('#payment-method').val('credit_card');
        $('#payment-method-crypto').val('credit_card');
        $('#payment-method-crypto-com').val('credit_card');
        $('#payment-method-paypal').val('credit_card');

        if (gateway === 'stripe') {
            $('#amadex-credit-card-form').hide();
            $('.amadex-payment-form-wrapper').not('#amadex-credit-card-form').hide();
        } else {
            $('#amadex-credit-card-form').show();
            $('.amadex-payment-form-wrapper').not('#amadex-credit-card-form').hide();
        }
    }

    function initPayPalButtons() {
        const clientId = typeof AmadexConfig !== 'undefined' && AmadexConfig.paypalClientId ? AmadexConfig.paypalClientId : '';
        const enablePaypal = typeof AmadexConfig !== 'undefined' && AmadexConfig.enablePaypal;
        const $container = $('#paypal-button-container');
        if (!clientId || !enablePaypal || !$container.length) {
            if ($container.length) { $container.html('<p class="amadex-paypal-unavailable">PayPal is not configured. Add Client ID in Amadex Settings.</p>'); }
            return;
        }
        $('#paypal-paylater-message').empty();
        $container.empty();
        const paypalMode = typeof AmadexConfig !== 'undefined' && AmadexConfig.paypalMode === 'live' ? 'live' : 'sandbox';
        const sdkBase = paypalMode === 'live' ? 'https://www.paypal.com/sdk/js' : 'https://www.sandbox.paypal.com/sdk/js';
        // locale=en_US avoids 404 for unsupported locales (e.g. en-IN). card = Debit/Credit guest checkout.
        const sdkParams = 'currency=USD&intent=capture&components=buttons,messages&enable-funding=paylater,venmo,credit,card&locale=en_US';
        const loadPayPalSDK = function () {
            if (typeof paypal !== 'undefined') { renderPayPalButtons(); return; }
            const script = document.createElement('script');
            script.src = sdkBase + '?client-id=' + encodeURIComponent(clientId) + '&' + sdkParams;
            script.async = true;
            script.onload = renderPayPalButtons;
            script.onerror = function () { $container.html('<p class="amadex-paypal-unavailable">Failed to load PayPal. Refresh and try again.</p>'); };
            document.head.appendChild(script);
        };
        function getPayPalMessageAmount(flight, bookingData) {
            let total = 0;
            if (flight && flight.price) {
                total = parseFloat(flight.price.total || flight.price.grandTotal || flight.price.original_total || 0) || 0;
                if (flight.price.pricing_charge_total && parseFloat(flight.price.pricing_charge_total) > 0) {
                    total = parseFloat(flight.price.pricing_charge_total);
                }
            }
            if (bookingData && bookingData.addons && Array.isArray(bookingData.addons)) {
                bookingData.addons.forEach(function (a) { total += parseFloat(a.price || 0) || 0; });
            }
            if (bookingData && bookingData.seat_selection && typeof bookingData.seat_selection.total_seat_charges !== 'undefined') {
                total += parseFloat(bookingData.seat_selection.total_seat_charges) || 0;
            }
            return Math.max(0, total);
        }
        const renderPayPalButtons = function () {
            if (typeof paypal === 'undefined') { $container.html('<p class="amadex-paypal-unavailable">PayPal SDK failed to load.</p>'); return; }
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            if (!flightData) { $container.html('<p class="amadex-paypal-unavailable">Complete booking steps first.</p>'); return; }
            let flight;
            try { flight = JSON.parse(flightData); } catch (e) { $container.html('<p class="amadex-paypal-unavailable">Invalid data.</p>'); return; }
            var bookingData = typeof collectBookingData === 'function' ? collectBookingData(flight) : {};
            if (window.AmadexSeatSelection && typeof window.AmadexSeatSelection.includeInBooking === 'function') {
                bookingData = window.AmadexSeatSelection.includeInBooking(bookingData);
            }
            var messageAmount = getPayPalMessageAmount(flight, bookingData);
            if (messageAmount >= 30 && typeof paypal.Messages === 'function') {
                try {
                    paypal.Messages({
                        amount: Math.round(messageAmount * 100) / 100,
                        currency: 'USD',
                        pageType: 'checkout',
                        style: { layout: 'text', logo: { type: 'inline' } }
                    }).render('#paypal-paylater-message');
                } catch (msgErr) { }
            }
            paypal.Buttons({
                createOrder: function () {
                    return new Promise(function (resolve, reject) {
                        let bookingData = typeof collectBookingData === 'function' ? collectBookingData(flight) : {};
                        if (window.AmadexSeatSelection && typeof window.AmadexSeatSelection.includeInBooking === 'function') {
                            bookingData = window.AmadexSeatSelection.includeInBooking(bookingData);
                        }
                        $.ajax({
                            url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '',
                            type: 'POST',
                            data: {
                                action: 'amadex_paypal_create_order',
                                nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '',
                                booking_data: JSON.stringify(bookingData)
                            },
                            success: function (res) {
                                if (res.success && res.data && res.data.orderID) {
                                    window.amadexPaypalToken = res.data.token;
                                    resolve(res.data.orderID);
                                } else { reject(new Error(res.data && res.data.message ? res.data.message : 'Could not create order')); }
                            },
                            error: function (xhr) {
                                let msg = 'Could not create order';
                                try { var err = JSON.parse(xhr.responseText); if (err.data && err.data.message) msg = err.data.message; } catch (e) { }
                                reject(new Error(msg));
                            }
                        });
                    });
                },
                onApprove: function (data) {
                    const token = window.amadexPaypalToken || (data && data.token ? data.token : '');
                    const orderID = (data && data.orderID) ? data.orderID : '';
                    if (!orderID) {
                        showPaymentError('Payment approval did not return an order ID. Please try again or use another payment method.');
                        return;
                    }
                    $.ajax({
                        url: typeof AmadexConfig !== 'undefined' ? AmadexConfig.ajaxUrl : '',
                        type: 'POST',
                        data: { action: 'amadex_paypal_capture', nonce: typeof AmadexConfig !== 'undefined' ? AmadexConfig.nonce : '', orderID: orderID, token: token },
                        success: function (res) {
                            if (res.success && res.data && res.data.redirectUrl) { window.location.href = res.data.redirectUrl; }
                            else { showPaymentError(res.data && res.data.message ? res.data.message : 'Capture failed. Please try again.'); }
                        },
                        error: function (xhr) {
                            let msg = 'Payment could not be completed. Please try again or use another payment method.';
                            try { var err = xhr.responseJSON || JSON.parse(xhr.responseText); if (err.data && err.data.message) msg = err.data.message; } catch (e) { }
                            showPaymentError(msg);
                        }
                    });
                },
                onCancel: function () {
                    // User closed the PayPal/card popup without completing
                    if (typeof showPaymentError === 'function') showPaymentError('Payment was cancelled. You can try again or choose another payment method.');
                },
                onError: function (err) {
                    var msg = (err && err.message) ? err.message : 'PayPal encountered an error. Please try again or use another payment method.';
                    if (typeof showPaymentError === 'function') showPaymentError(msg);
                    else if ($container.length) $container.html('<p class="amadex-paypal-error">' + msg + '</p>');
                }
            }).render('#paypal-button-container');
        };
        loadPayPalSDK();
    }

    /**
     * Update booking step and show/hide sections
     */
    function updateBookingStep(step) {
        // Update progress bar
        $('.booking-step-modern').removeClass('is-active').removeClass('is-complete');

        if (step === 'booking') {
            $('.booking-step-modern[data-step="booking"]').addClass('is-active');
        } else if (step === 'passengers') {
            $('.booking-step-modern[data-step="booking"]').addClass('is-complete');
            $('.booking-step-modern[data-step="passengers"]').addClass('is-active');
        } else if (step === 'payment') {
            $('.booking-step-modern[data-step="booking"]').addClass('is-complete');
            $('.booking-step-modern[data-step="passengers"]').addClass('is-active');
            $('.booking-step-modern[data-step="payment"]').addClass('is-active');

            // Show payment section
            $('#amadex-payment-section').show();
            $('#amadex-agreement-section').show();
        }
    }

    /**
     * Build segment HTML for booking page
     */
    function buildSegmentHtml(segment, segIdx, totalSegments, cabinClass, extras = {}) {
        // Extract segment data with multiple fallbacks
        const carrierCode = segment.carrierCode || segment.carrier_code || segment.validatingAirlineCodes?.[0] || 'N/A';
        const flightNumber = segment.number || segment.flight_number || segment.flightNumber || 'N/A';
        const depIata = segment.departure?.iataCode || segment.departure?.iata_code || segment.departure?.code || 'N/A';
        const arrIata = segment.arrival?.iataCode || segment.arrival?.iata_code || segment.arrival?.code || 'N/A';
        const depTerminal = segment.departure?.terminal || '';
        const arrTerminal = segment.arrival?.terminal || '';

        const depTime = formatTime(new Date(segment.departure.at));
        const arrTime = formatTime(new Date(segment.arrival.at));
        const depDate = formatFullDate(new Date(segment.departure.at));
        const arrDate = formatFullDate(new Date(segment.arrival.at));
        const duration = calculateSegmentDurationText(segment);
        const airlineName = getAirlineName(carrierCode);
        const depAirportInfo = getAirportInfo(depIata);
        const arrAirportInfo = getAirportInfo(arrIata);
        const passengerLabel = extras.passengerLabel || 'Adult';
        const checkInAllowance = extras.checkInAllowance || 'Not Available';
        const cabinAllowance = extras.cabinAllowance || 'Not Available';
        const cabinClassName = extras.cabinClassName || cabinClass;

        return `
            <div class="amadex-segment-detail">
                <div class="amadex-segment-header">
                    <div class="amadex-segment-airline">
                        <img src="https://images.kiwi.com/airlines/64/${carrierCode}.png" alt="${carrierCode}" onerror="this.style.display='none'">
                        <div class="amadex-airline-info">
                            <span class="amadex-airline-name">${airlineName}</span>
                            <span class="amadex-airline-code">Flight ${carrierCode} ${flightNumber}</span>
                        </div>
                    </div>
                    <div class="amadex-segment-badges">
                        <span class="amadex-segment-badge">${cabinClassName}</span>
                        <span class="amadex-segment-badge is-outline">${depAirportInfo.city || depIata} → ${arrAirportInfo.city || arrIata}</span>
                    </div>
                </div>
                <div class="amadex-segment-travel-meta">
                    <span>Travel Class: <strong>${cabinClassName}</strong></span>
                    <span>Flight Number: <strong>${carrierCode} ${flightNumber}</strong></span>
                </div>
                <div class="amadex-segment-route-grid">
                    <div class="amadex-segment-point">
                        <span class="segment-point-label">${depAirportInfo.city || depIata}</span>
                        <strong class="segment-point-time">${depTime}</strong>
                        <span class="segment-point-airport">${depIata}${depTerminal ? ' · Terminal ' + depTerminal : ''}</span>
                        <span class="segment-point-date">${depDate}</span>
                    </div>
                    <div class="amadex-segment-timeline">
                    <span class="timeline-duration">${duration}</span>

                    </div>
                    <div class="amadex-segment-point is-arrival">
                        <span class="segment-point-label">${arrAirportInfo.city || arrIata}</span>
                        <strong class="segment-point-time">${arrTime}</strong>
                        <span class="segment-point-airport">${arrIata}${arrTerminal ? ' · Terminal ' + arrTerminal : ''}</span>
                        <span class="segment-point-date">${arrDate}</span>
                    </div>
                </div>
                <div class="amadex-segment-baggage-table">
                    <div class="segment-baggage-row segment-baggage-row--head">
                        <span>Baggage</span>
                        <span>Check In</span>
                        <span>Cabin</span>
                    </div>
                    <div class="segment-baggage-row">
                        <span>${passengerLabel}</span>
                        <span>${checkInAllowance}</span>
                        <span>${cabinAllowance}</span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Get airport info helper
     */
    function getAirportInfo(iataCode) {
        // Basic airport info mapping - can be expanded
        const airports = {
            'JFK': { city: 'New York', airport: 'John F. Kennedy International' },
            'LAX': { city: 'Los Angeles', airport: 'Los Angeles International' },
            'LHR': { city: 'London', airport: 'Heathrow' },
            'CDG': { city: 'Paris', airport: 'Charles de Gaulle' },
            'DXB': { city: 'Dubai', airport: 'Dubai International' },
            'SIN': { city: 'Singapore', airport: 'Singapore Changi' },
            'DEL': { city: 'Delhi', airport: 'Indira Gandhi International' },
            'BOM': { city: 'Mumbai', airport: 'Chhatrapati Shivaji Maharaj International' },
            'SMF': { city: 'Sacramento', airport: 'Sacramento Metro' },
            'DCA': { city: 'Washington', airport: 'Ronald Reagan Washington National' }
        };
        return airports[iataCode] || { city: iataCode, airport: iataCode };
    }

    /**
     * Determine if passport fields should be displayed
     */
    function shouldShowPassportFields(flight, searchData) {
        return !isDomesticItinerary(flight, searchData);
    }

    function isDomesticItinerary(flight, searchData) {
        const normalizedFlag = normalizeDomesticFlag(searchData?.isDomestic);
        if (typeof normalizedFlag === 'boolean') {
            return normalizedFlag;
        }

        const detectedCountries = new Set();

        const addCountryFromIata = (iata) => {
            if (!iata) return;
            const code = getAirportCountryCode(String(iata).toUpperCase());
            if (code) {
                detectedCountries.add(code);
            }
        };

        if (searchData?.origin) addCountryFromIata(searchData.origin);
        if (searchData?.destination) addCountryFromIata(searchData.destination);

        if (Array.isArray(searchData?.segments)) {
            searchData.segments.forEach(segment => {
                addCountryFromIata(segment?.origin || segment?.originLocationCode);
                addCountryFromIata(segment?.destination || segment?.destinationLocationCode);
            });
        }

        if (flight?.itineraries) {
            flight.itineraries.forEach(itinerary => {
                if (!Array.isArray(itinerary?.segments)) return;
                itinerary.segments.forEach(segment => {
                    addCountryFromIata(segment?.departure?.iataCode || segment?.departure?.iata_code || segment?.departure?.code);
                    addCountryFromIata(segment?.arrival?.iataCode || segment?.arrival?.iata_code || segment?.arrival?.code);
                });
            });
        }

        if (detectedCountries.size === 0) {
            return false;
        }

        return detectedCountries.size === 1;
    }

    function normalizeDomesticFlag(value) {
        if (typeof value === 'boolean') {
            return value;
        }

        if (typeof value === 'string') {
            const normalized = value.trim().toLowerCase();
            if (['yes', 'true', '1'].includes(normalized)) {
                return true;
            }
            if (['no', 'false', '0'].includes(normalized)) {
                return false;
            }
        }

        return null;
    }

    function getAirportCountryCode(iata) {
        if (!iata) return null;
        const code = String(iata).toUpperCase();
        for (const [country, airports] of Object.entries(AMADEX_COUNTRY_AIRPORTS)) {
            if (airports.includes(code)) {
                return country;
            }
        }
        return null;
    }

    /**
     * Billing country/state helpers
     */
    function initializeBillingLocationControls() {
        const countrySelect = $('#billing-country');
        const stateSelect = $('#billing-state');

        if (!countrySelect.length || !stateSelect.length) {
            return;
        }

        populateBillingCountries(countrySelect);

        const defaultCountry = countrySelect.data('defaultCountry') || 'US';
        const initialCountry = countrySelect.val() || defaultCountry;
        countrySelect.val(initialCountry);

        updateBillingStates(initialCountry);

        countrySelect.on('change.amadexBilling', function () {
            const selectedCountry = $(this).val();
            updateBillingStates(selectedCountry);
        });
    }

    function populateBillingCountries(countrySelect) {
        const defaultCountry = countrySelect.data('defaultCountry') || 'US';
        const currentValue = countrySelect.val() || defaultCountry;

        countrySelect.empty();
        countrySelect.append('<option value="">Select Country</option>');

        AMADEX_COUNTRY_LIST
            .slice()
            .sort((a, b) => a.name.localeCompare(b.name))
            .forEach(country => {
                const option = $('<option></option>')
                    .val(country.code)
                    .text(country.name);
                if (country.code === currentValue) {
                    option.prop('selected', true);
                }
                countrySelect.append(option);
            });
    }

    function updateBillingStates(countryCode) {
        const stateSelect = $('#billing-state');
        if (!stateSelect.length) {
            return;
        }

        const previousValue = stateSelect.val();
        const basePlaceholder = stateSelect.data('placeholder') || 'State / Province';
        const loadingLabel = stateSelect.data('loadingLabel') || `Loading ${basePlaceholder}...`;
        const emptyLabel = stateSelect.data('emptyLabel') || basePlaceholder;
        const label = AMADEX_STATE_LABELS[countryCode] || basePlaceholder;

        if (!countryCode) {
            setBillingStatePlaceholder(stateSelect, `Select ${basePlaceholder}`, true);
            return;
        }

        const staticStates = AMADEX_COUNTRY_STATES[countryCode] || [];
        if (staticStates.length) {
            renderBillingStateOptions(stateSelect, staticStates, label, previousValue);
            return;
        }

        const cachedStates = AMADEX_DYNAMIC_STATE_CACHE[countryCode];
        if (cachedStates && cachedStates.length) {
            renderBillingStateOptions(stateSelect, cachedStates, label, previousValue);
            return;
        }

        setBillingStateLoading(stateSelect, loadingLabel);
        const requestId = `${countryCode}-${Date.now()}`;
        stateSelect.data('stateRequestId', requestId);

        fetchCountryStates(countryCode)
            .then(states => {
                if (stateSelect.data('stateRequestId') !== requestId) {
                    return;
                }

                if (states && states.length) {
                    renderBillingStateOptions(stateSelect, states, label, previousValue);
                } else {
                    setBillingStateUnavailable(stateSelect, label, emptyLabel);
                }
            })
            .catch(error => {
                if (stateSelect.data('stateRequestId') === requestId) {
                    setBillingStateUnavailable(stateSelect, label, emptyLabel);
                }
            });
    }

    function renderBillingStateOptions(stateSelect, states, label, previousValue) {
        stateSelect.empty();
        stateSelect.append(`<option value="">Select ${label}</option>`);

        states.forEach(state => {
            if (!state || !state.name) {
                return;
            }
            const value = state.code || state.name;
            const option = $('<option></option>')
                .val(value)
                .text(state.name);
            if (value === previousValue) {
                option.prop('selected', true);
            }
            stateSelect.append(option);
        });

        stateSelect
            .show()
            .prop('disabled', false)
            .attr('required', true);
    }

    function setBillingStatePlaceholder(stateSelect, text, disabled = false) {
        stateSelect
            .empty()
            .append(`<option value="">${text}</option>`)
            .prop('disabled', !!disabled)
            .attr('required', !disabled)
            .show();
    }

    function setBillingStateLoading(stateSelect, loadingLabel) {
        setBillingStatePlaceholder(stateSelect, loadingLabel, true);
    }

    function setBillingStateUnavailable(stateSelect, label, emptyLabel) {
        const message = `Select ${emptyLabel}`;
        stateSelect
            .empty()
            .append(`<option value="">${message}</option>`)
            .prop('disabled', false)
            .attr('required', true)
            .show();
    }

    function fetchCountryStates(countryCode) {
        if (!countryCode) {
            return Promise.resolve([]);
        }

        const country = getCountryByCode(countryCode);
        if (!country || typeof window.fetch !== 'function') {
            return Promise.resolve([]);
        }

        if (AMADEX_COUNTRY_STATES[countryCode]) {
            return Promise.resolve(AMADEX_COUNTRY_STATES[countryCode]);
        }

        if (AMADEX_DYNAMIC_STATE_CACHE[countryCode]) {
            return Promise.resolve(AMADEX_DYNAMIC_STATE_CACHE[countryCode]);
        }

        if (AMADEX_COUNTRY_STATE_REQUESTS[countryCode]) {
            return AMADEX_COUNTRY_STATE_REQUESTS[countryCode];
        }

        const request = fetch('https://countriesnow.space/api/v0.1/countries/states', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                country: country.name
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const states = (data && data.data && Array.isArray(data.data.states))
                    ? data.data.states
                        .filter(state => state && state.name)
                        .map(state => ({
                            code: state.state_code || state.name,
                            name: state.name
                        }))
                    : [];

                if (states.length) {
                    AMADEX_DYNAMIC_STATE_CACHE[countryCode] = states;
                }

                return states;
            })
            .catch(error => {
                return [];
            })
            .finally(() => {
                delete AMADEX_COUNTRY_STATE_REQUESTS[countryCode];
            });

        AMADEX_COUNTRY_STATE_REQUESTS[countryCode] = request;
        return request;
    }

    function getCountryByCode(countryCode) {
        if (!countryCode) {
            return null;
        }
        return AMADEX_COUNTRY_LIST.find(country => country.code === countryCode) || null;
    }

    function getBillingStateValue() {
        const stateSelect = $('#billing-state');
        if (stateSelect.length) {
            return stateSelect.val();
        }
        return '';
    }

    /**
     * Initialize Google Places Autocomplete for billing address
     */
    function initGooglePlacesAutocomplete() {
        // Check if Google Places API is loaded
        if (typeof google === 'undefined' || typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
            // Try again after a short delay if not loaded yet
            setTimeout(function () {
                if (typeof google !== 'undefined' && typeof google.maps !== 'undefined' && typeof google.maps.places !== 'undefined') {
                    initGooglePlacesAutocomplete();
                }
            }, 500);
            return;
        }

        const addressInput = document.getElementById('billing-address1');
        if (!addressInput) {
            return;
        }

        // Create autocomplete instance
        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            types: ['address'],
            componentRestrictions: { country: [] } // Allow all countries
        });

        // Listen for place selection
        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();

            if (!place.geometry) {
                return;
            }

            // Initialize address components
            let streetNumber = '';
            let route = '';
            let addressLine2 = '';
            let city = '';
            let state = '';
            let postalCode = '';
            let country = '';

            // Parse address components
            for (let i = 0; i < place.address_components.length; i++) {
                const component = place.address_components[i];
                const componentType = component.types[0];

                switch (componentType) {
                    case 'street_number':
                        streetNumber = component.long_name;
                        break;
                    case 'route':
                        route = component.long_name;
                        break;
                    case 'subpremise':
                    case 'premise':
                        addressLine2 = component.long_name;
                        break;
                    case 'locality':
                    case 'sublocality':
                    case 'sublocality_level_1':
                        if (!city) {
                            city = component.long_name;
                        }
                        break;
                    case 'administrative_area_level_1':
                        state = component.short_name || component.long_name;
                        break;
                    case 'postal_code':
                        postalCode = component.long_name;
                        break;
                    case 'country':
                        country = component.short_name;
                        break;
                }
            }

            // Build address line 1
            let addressLine1 = '';
            if (streetNumber && route) {
                addressLine1 = streetNumber + ' ' + route;
            } else if (route) {
                addressLine1 = route;
            } else if (streetNumber) {
                addressLine1 = streetNumber;
            } else {
                // Fallback to formatted address
                addressLine1 = place.formatted_address.split(',')[0] || '';
            }

            // Fill in the form fields
            $('#billing-address1').val(addressLine1.trim()).trigger('change');

            if (addressLine2) {
                $('#billing-address2').val(addressLine2.trim());
            }

            if (city) {
                $('#billing-city').val(city.trim()).trigger('change');
            }

            if (postalCode) {
                $('#billing-postal').val(postalCode.trim()).trigger('change');
            }

            // Set country if available
            if (country) {
                const countrySelect = $('#billing-country');
                if (countrySelect.length) {
                    // Find option by country code
                    const countryOption = countrySelect.find('option[value="' + country.toUpperCase() + '"]');
                    if (countryOption.length) {
                        countrySelect.val(country.toUpperCase()).trigger('change');
                    }
                }
            }

            // Set state/province
            if (state) {
                const stateSelect = $('#billing-state');
                if (stateSelect.length) {
                    // Try to find by code first, then by name
                    let stateOption = stateSelect.find('option[value="' + state.toUpperCase() + '"]');
                    if (!stateOption.length) {
                        stateOption = stateSelect.find('option:contains("' + state + '")');
                    }
                    if (!stateOption.length) {
                        // Try partial match
                        stateSelect.find('option').each(function () {
                            const optionText = $(this).text().toUpperCase();
                            const optionValue = $(this).val().toUpperCase();
                            if (optionText.includes(state.toUpperCase()) || optionValue.includes(state.toUpperCase())) {
                                stateOption = $(this);
                                return false; // break
                            }
                        });
                    }
                    if (stateOption.length) {
                        stateSelect.val(stateOption.val()).trigger('change');
                    } else {
                        // If state dropdown doesn't have the value, try to set it directly
                        // This handles cases where state might be a text input
                        const stateInput = $('#billing-state-input');
                        if (stateInput.length) {
                            stateInput.val(state).trigger('change');
                        }
                    }
                }
            }

            // Clear any validation errors
            clearFieldError('#billing-address1');
            clearFieldError('#billing-city');
            clearFieldError('#billing-postal');
            clearFieldError('#billing-state');
            clearFieldError('#billing-country');

        });

    }

    // Make function globally available for callback
    window.amadexInitAddressAutocomplete = initGooglePlacesAutocomplete;

    /**
     * Initialize back button handlers for mobile compatibility
     * Fixes issue where history.back() doesn't work on mobile devices
     */
    function initBackButtonHandlers() {
        // Function to handle navigation back
        function handleBackNavigation(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Try multiple methods for better mobile compatibility
            const resultsUrl = sessionStorage.getItem('amadex_results_page_url');

            if (resultsUrl) {
                // Use stored results page URL if available (most reliable method)
                window.location.href = resultsUrl;
                return false;
            }

            // Fallback methods for mobile compatibility
            // Method 1: Try window.history.go(-1) - more reliable on mobile
            try {
                if (window.history.length > 1) {
                    window.history.go(-1);

                    // Fallback: If still on same page after delay, try direct navigation
                    setTimeout(function () {
                        // Check if we're still on the booking page (URL contains 'booking')
                        if (window.location.href.indexOf('booking') !== -1) {
                            // Try history.back() as secondary attempt
                            window.history.back();

                            // Final fallback: redirect to results page
                            setTimeout(function () {
                                if (window.location.href.indexOf('booking') !== -1) {
                                    const searchUrl = window.location.origin + '/flight-results/';
                                    window.location.href = searchUrl;
                                }
                            }, 200);
                        }
                    }, 300);
                } else {
                    // No history, redirect to results page
                    const searchUrl = window.location.origin + '/flight-results/';
                    window.location.href = searchUrl;
                }
            } catch (err) {
                // Final fallback: redirect to results page
                const searchUrl = window.location.origin + '/flight-results/';
                window.location.href = searchUrl;
            }

            return false;
        }

        // Handle back button on booking page - use event delegation for dynamically added buttons
        // Support both click and touch events for better mobile compatibility
        $(document).on('click touchstart', '#amadex-back-to-results, #amadex-back-to-results-confirmation, .amadex-back-link', function (e) {
            // Prevent default only on touchstart to avoid double-trigger
            if (e.type === 'touchstart') {
                e.preventDefault();
            }
            return handleBackNavigation(e);
        });

        // Also handle touchend for better mobile support
        $(document).on('touchend', '#amadex-back-to-results, #amadex-back-to-results-confirmation, .amadex-back-link', function (e) {
            e.preventDefault();
            e.stopPropagation();
            return handleBackNavigation(e);
        });
    }

    /**
     * Seat Selection Module
     */
    window.AmadexSeatSelection = {
        // selectedSeats: {},
        // seatMaps: {},
        // flightOffer: null,
        // flightOfferId: null,
        selectedSeats: {}, // Format: {segmentId: {travelerId: {seat_number, price, cabin}}}
        seatMaps: {},
        flightOffer: null,
        flightOfferId: null,
        allFlights: null,
        totalSeatCharges: 0,
        currency: 'USD',
        currentPassengerId: '1',
        passengers: [],

        /**
         * Initialize seat selection
         */
        //         init: function(flight) {
        //             const $section = $('#amadex-seat-selection-section');
        //             if ($section.length === 0) {
        // return;
        //             }

        //             this.flightOffer = flight;

        //             // Try multiple possible ID fields - prioritize rawOffer.id first (most reliable)
        //             let flightOfferId = null;

        //             // First try rawOffer.id (most reliable - comes directly from Amadeus API)
        //             if (flight.rawOffer && flight.rawOffer.id) {
        //                 flightOfferId = flight.rawOffer.id;
        // }

        //             // Fallback to other ID fields
        //             if (!flightOfferId || flightOfferId === '') {
        //                 flightOfferId = flight.id || flight.offerId || flight.offer_id || null;
        // }

        //             this.flightOfferId = flightOfferId;
        //             this.currency = flight.price?.currency || 'USD';

        //             // Extract passengers from travelerPricings
        //             this.extractPassengers(flight);

        // // Ensure section is visible
        //             $section.show();
        // // Show loading state initially
        //             $('#amadex-seat-map-loading').show();
        //             $('#amadex-seat-map-unavailable').hide();

        //             if (!this.flightOfferId || this.flightOfferId === '' || this.flightOfferId === null) {
        // setTimeout(() => {
        //                     this.showUnavailableWithMessage('Flight offer ID is missing. Seat selection cannot be loaded. Please try selecting the flight again.');
        //                     this.bindEvents();
        //                 }, 500); // Small delay to show loading state
        //                 return;
        //             }

        //             // Load seat maps
        //             this.loadSeatMaps();
        //             this.bindEvents();
        //         },

        init: function (flight, allSegmentFlights) {
            const $section = $('#amadex-seat-selection-section');
            if ($section.length === 0) {
                return;
            }

            this.flightOffer = flight;
            this.allFlights = allSegmentFlights || null;

            // Try multiple possible ID fields - prioritize rawOffer.id first (most reliable)
            let flightOfferId = null;
            if (flight.rawOffer && flight.rawOffer.id) {
                flightOfferId = flight.rawOffer.id;
            }
            if (!flightOfferId || flightOfferId === '') {
                flightOfferId = flight.id || flight.offerId || flight.offer_id || null;
            }

            this.flightOfferId = flightOfferId;
            this.currency = flight.price?.currency || 'USD';

            // Extract passengers from travelerPricings
            this.extractPassengers(flight);

            $section.show();
            $('#amadex-seat-map-loading').show();
            $('#amadex-seat-map-unavailable').hide();

            if (!this.flightOfferId || this.flightOfferId === '' || this.flightOfferId === null) {
                setTimeout(() => {
                    this.showUnavailableWithMessage('Flight offer ID is missing. Seat selection cannot be loaded. Please try selecting the flight again.');
                    this.bindEvents();
                }, 500);
                return;
            }

            // For multi-city, load seat maps for ALL segment flights
            if (this.allFlights && Object.keys(this.allFlights).length > 1) {
                this.loadSeatMapsMultiCity();
            } else {
                this.loadSeatMaps();
            }
            this.bindEvents();
        },

        /**
         * Update passengers list from DOM forms after passenger changes
         * This is called when passengers are added or removed
         * Re-extracts passengers from flight data (which should match DOM forms)
         */
        updatePassengersFromForms: function () {
            // Store previous passenger IDs to detect removed passengers
            const previousPassengerIds = this.passengers.map(p => String(p.id));

            // Try to re-extract passengers from flight data first (preferred - has correct travelerPricings)
            if (this.flightOffer) {
                this.extractPassengers(this.flightOffer);

                // Get current passenger IDs
                const currentPassengerIds = this.passengers.map(p => String(p.id));

                // Find removed passenger IDs
                const removedIds = previousPassengerIds.filter(id => !currentPassengerIds.includes(id));

                // Clean up seat selections for removed passengers
                if (removedIds.length > 0) {
                    removedIds.forEach(removedId => {
                        Object.keys(this.selectedSeats).forEach((segmentId) => {
                            if (this.selectedSeats[segmentId] && this.selectedSeats[segmentId][removedId]) {
                                delete this.selectedSeats[segmentId][removedId];
                            }
                        });
                    });
                }

                // Regenerate passenger selector buttons
                const passengerSelector = this.getPassengerSelectorHtml();
                if (passengerSelector) {
                    // Replace existing passenger selector
                    // const $existingSelector = $('.amadex-passenger-selector-wrapper');
                    // if ($existingSelector.length) {
                    //     $existingSelector.replaceWith(passengerSelector);
                    // } else {
                    //     // Insert at the top of seat maps container
                    //     $('#amadex-seat-maps-container').prepend(passengerSelector);
                    // }
                    $('#amadex-seat-passenger-panel').html(passengerSelector);
                    // Update labels with names
                    this.updatePassengerSelectorLabels();

                    // Set first passenger as current if current one was removed
                    if (this.passengers.length > 0) {
                        const currentExists = this.passengers.find(p =>
                            String(p.id) === String(this.currentPassengerId)
                        );
                        if (!currentExists) {
                            this.currentPassengerId = this.passengers[0].id;
                            $('.amadex-passenger-selector-btn').first().addClass('active');
                        }
                    }

                    // Note: Events are already bound via $(document).on() in bindEvents()
                    // So new buttons will work automatically

                    // Update highlights for current passenger
                    this.highlightSeatsForCurrentPassenger();

                    // Update summary to reflect changes
                    this.updateSelectedSeatsSummary();
                }

            }
        },

        /**
         * Extract passengers from travelerPricings
         * Now properly tracks passenger type indices (Adult 1, Child 1, etc.)
         */
        extractPassengers: function (flight) {
            this.passengers = [];

            // Get passengers from rawOffer.travelerPricings or flight.traveler_pricings
            const travelerPricings = flight.rawOffer?.travelerPricings || flight.traveler_pricings || [];

            // Track indices per type for proper labeling
            let adultIndex = 0;
            let childIndex = 0;
            let infantIndex = 0;

            travelerPricings.forEach((traveler) => {
                const travelerId = traveler.travelerId || traveler.traveler_id || '';
                const travelerType = traveler.travelerType || traveler.traveler_type || 'ADULT';

                // Create proper type-based label with index
                let passengerLabel;
                let typeLabel;
                if (travelerType === 'ADULT') {
                    adultIndex++;
                    typeLabel = `Adult ${adultIndex}`;
                    passengerLabel = typeLabel;
                } else if (travelerType === 'CHILD') {
                    childIndex++;
                    typeLabel = `Child ${childIndex}`;
                    passengerLabel = typeLabel;
                } else {
                    infantIndex++;
                    typeLabel = `Infant ${infantIndex}`;
                    passengerLabel = typeLabel;
                }

                this.passengers.push({
                    id: travelerId,
                    type: travelerType,
                    label: passengerLabel,
                    typeLabel: typeLabel, // Store base label (e.g., "Adult 1")
                    typeIndex: travelerType === 'ADULT' ? adultIndex : (travelerType === 'CHILD' ? childIndex : infantIndex)
                });
            });

            // Set first passenger as default
            if (this.passengers.length > 0) {
                this.currentPassengerId = this.passengers[0].id;
            }

        },
        /**
         * Load seat maps for all multi-city segment flights
         * Makes parallel AJAX calls for each segment flight and merges results
         */
        loadSeatMapsMultiCity: function () {
            const flights = this.allFlights;
            const flightKeys = Object.keys(flights).sort((a, b) => parseInt(a) - parseInt(b));

            $('#amadex-seat-map-loading').show();
            $('#amadex-seat-map-unavailable').hide();
            $('#amadex-seat-maps-container').empty();

            const requests = flightKeys.map(key => {
                const segFlight = flights[key];
                const segOfferId = (segFlight.rawOffer && segFlight.rawOffer.id)
                    ? segFlight.rawOffer.id
                    : (segFlight.id || segFlight.offerId || segFlight.offer_id || '');

                if (!segOfferId) return Promise.resolve(null);

                const requestData = {
                    action: 'amadex_get_seatmap',
                    nonce: AmadexConfig.nonce,
                    flight_offer_id: segOfferId
                };
                if (segFlight.rawOffer) {
                    requestData.raw_offer = JSON.stringify(segFlight.rawOffer);
                }

                return new Promise((resolve) => {
                    $.ajax({
                        url: AmadexConfig.ajaxUrl,
                        type: 'POST',
                        data: requestData,
                        success: (response) => {
                            if (response.success && response.data && response.data.data && response.data.data.length > 0) {
                                resolve(response.data.data);
                            } else {
                                resolve([]);
                            }
                        },
                        error: () => resolve([])
                    });
                });
            });

            Promise.all(requests).then((results) => {
                $('#amadex-seat-map-loading').hide();
                // Merge all seat maps from all segment flights
                const merged = [].concat(...results.filter(r => r !== null));
                if (merged.length === 0) {
                    this.showUnavailableWithMessage('Seat selection is not available for these flights.');
                    return;
                }
                this.seatMaps = merged;
                $('#amadex-seat-map-unavailable').hide();
                $('#amadex-seat-maps-container').show();
                // Use all segment flights' itineraries for tab rendering
                this._buildCombinedItineraries(flights, flightKeys);
                this.renderSeatMaps();
                $('#amadex-seat-selection-section').show();
            });
        },

        /**
         * Build a combined flightOffer.itineraries from all multi-city segment flights
         * so renderSeatMaps can match tabs to all segments correctly
         */
        _buildCombinedItineraries: function (flights, flightKeys) {
            const combined = [];
            flightKeys.forEach(key => {
                const f = flights[key];
                const itins = f.itineraries || f.rawOffer?.itineraries || [];
                itins.forEach(itin => combined.push(itin));
            });
            if (combined.length > 0) {
                // Temporarily override flightOffer.itineraries so renderSeatMaps sees all segments
                this.flightOffer = Object.assign({}, this.flightOffer, { itineraries: combined });
            }
        },

        loadSeatMaps: function () {
            /**
             * Load seat maps for flight
             */
            // loadSeatMaps: function() {
            // Check if we have a valid flight offer ID
            if (!this.flightOfferId || this.flightOfferId === '') {
                this.showUnavailableWithMessage('Flight offer ID is missing. Seat selection cannot be loaded.');
                return;
            }

            $('#amadex-seat-map-loading').show();
            $('#amadex-seat-map-unavailable').hide();
            $('#amadex-seat-maps-container').empty();

            // Check if AmadexConfig is available
            if (typeof AmadexConfig === 'undefined') {
                this.showUnavailableWithMessage('Configuration error. Please refresh the page and try again.');
                return;
            }

            // Prepare request data - include raw offer if available
            const requestData = {
                action: 'amadex_get_seatmap',
                nonce: AmadexConfig.nonce,
                flight_offer_id: this.flightOfferId
            };

            // Include raw offer if available (REQUIRED by Amadeus API for seat maps)
            if (this.flightOffer && this.flightOffer.rawOffer) {
                requestData.raw_offer = JSON.stringify(this.flightOffer.rawOffer);
            } else {
            }

            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: requestData,
                success: (response) => {
                    $('#amadex-seat-map-loading').hide();

                    if (response.success && response.data && response.data.data && response.data.data.length > 0) {
                        this.seatMaps = response.data.data;
                        // Hide unavailable message and show maps
                        $('#amadex-seat-map-unavailable').hide();
                        $('#amadex-seat-maps-container').show();
                        this.renderSeatMaps();
                        // Ensure section is visible
                        $('#amadex-seat-selection-section').show();
                    } else {
                        // Check for specific error messages
                        let errorMsg = 'Seat selection is not available for this flight.';
                        if (response.data && response.data.message) {
                            errorMsg = response.data.message;
                        } else if (response.data && response.data.errors) {
                            errorMsg = response.data.errors[0]?.detail || errorMsg;
                        }

                        this.showUnavailableWithMessage(errorMsg);
                    }
                },
                error: (xhr, status, error) => {
                    let errorMsg = 'Unable to load seat selection. Please try again later.';

                    // Try to parse error response
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data && errorResponse.data.message) {
                            errorMsg = errorResponse.data.message;
                        }
                    } catch (e) {
                        // Use default message
                    }

                    $('#amadex-seat-map-loading').hide();
                    this.showUnavailableWithMessage(errorMsg);
                }
            });
        },

        /**
         * Render seat maps UI
         * Now uses horizontal tabs to show one segment at a time
         */
        renderSeatMaps: function () {
            const container = $('#amadex-seat-maps-container');
            container.empty();

            // Hide loading and unavailable states
            $('#amadex-seat-map-loading').hide();
            $('#amadex-seat-map-unavailable').hide();

            // Show container
            container.show();

            if (!this.flightOffer || !this.flightOffer.itineraries) {
                this.showUnavailableWithMessage('Flight itinerary data is missing.');
                return;
            }

            // Extract all segments from all itineraries with their itinerary index
            const allSegments = [];
            this.flightOffer.itineraries.forEach((itinerary, itineraryIndex) => {
                if (itinerary.segments && itinerary.segments.length > 0) {
                    itinerary.segments.forEach((segment) => {
                        const segmentId = segment.id || segment.segmentId || segment.segment_id;
                        allSegments.push({
                            segment: segment,
                            segmentId: segmentId,
                            itineraryIndex: itineraryIndex,
                            seatMap: null
                        });
                    });
                }
            });

            // Match seat maps to segments by segmentId
            if (this.seatMaps && this.seatMaps.length > 0) {
                this.seatMaps.forEach((seatMap) => {
                    const seatMapSegmentId = seatMap.segmentId;
                    if (seatMapSegmentId) {
                        const segmentMatch = allSegments.find(s => s.segmentId === seatMapSegmentId);
                        if (segmentMatch) {
                            segmentMatch.seatMap = seatMap;
                        }
                    }
                });
            }

            // Filter to only segments with seat maps
            // const segmentsWithSeatMaps = allSegments.filter(item => item.seatMap !== null);

            // if (segmentsWithSeatMaps.length === 0) {
            //     this.showUnavailableWithMessage('Seat maps are not available for any segments.');
            //     return;
            // }
            // Show ALL segments as tabs; those without seat maps get a "not available" placeholder
            const segmentsWithSeatMaps = allSegments; // keep all segments for tab display

            // If NO segment has any seat map at all, show the global unavailable message
            if (allSegments.every(item => item.seatMap === null)) {
                this.showUnavailableWithMessage('Seat maps are not available for any segments of this flight.');
                return;
            }
            // Add passenger selector buttons at the very top
            // if (this.passengers.length > 1) {
            //     const passengerSelector = this.getPassengerSelectorHtml();
            //     container.append(passengerSelector);
            // }
            // Inject passenger panel into left panel
            const passengerPanel = $('#amadex-seat-passenger-panel');
            passengerPanel.html(this.getPassengerSelectorHtml());

            // Bind click on passenger panel items
            passengerPanel.off('click', '.amadex-pax-panel-item').on('click', '.amadex-pax-panel-item', (e) => {
                const pid = $(e.currentTarget).data('passenger-id');
                this.currentPassengerId = pid;
                $('.amadex-pax-panel-item').removeClass('active');
                $(e.currentTarget).addClass('active');
                this.highlightSeatsForCurrentPassenger();
            });
            // Create tabs container
            const tabsContainer = $('<div class="amadex-seat-map-tabs-container"></div>');
            const tabsList = $('<div class="amadex-seat-map-tabs-list"></div>');

            // Create content container for seat maps
            const contentContainer = $('<div class="amadex-seat-map-tabs-content"></div>');

            // Create tabs and content for each segment
            segmentsWithSeatMaps.forEach((item, index) => {
                const segmentData = item.segment;
                const segmentId = item.segmentId;
                const seatMap = item.seatMap;
                const isActive = index === 0;

                // Get route for tab label
                const dep = segmentData.departure?.iata_code || segmentData.departure?.iataCode || '';
                const arr = segmentData.arrival?.iata_code || segmentData.arrival?.iataCode || '';
                const route = dep && arr ? `${dep} - ${arr}` : `${index}`;
                const tabLabel = `${index} ${route}`;

                // Create tab button
                const tabButton = $(`
                    <button type="button" 
                            class="amadex-seat-map-tab ${isActive ? 'active' : ''}" 
                            data-segment-id="${segmentId}"
                            data-tab-index="${index}">
                       ${tabLabel}
                    </button>
                `);
                tabsList.append(tabButton);

                // Create content panel with scrollable container for seat map
                const contentPanel = $(`
                    <div class="amadex-seat-map-tab-panel ${isActive ? 'active' : ''}" 
                         data-segment-id="${segmentId}"
                         data-tab-index="${index}">
                        <div class="amadex-flight-details-card" data-segment-id="${segmentId}">
                            <h4>${this.getSegmentTitle(segmentData, segmentId)}</h4>
                            <div class="amadex-seat-map-scrollable-container">
                            ${seatMap
                        ? this.renderSeatMapForSegment(seatMap, segmentId, segmentData)
                        : '<div class="amadex-seat-unavailable-placeholder" style="padding:40px;text-align:center;color:#888;"><svg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'#ccc\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'/></svg><p style=\'margin-top:12px;font-size:14px\'>Seat selection is not available for this segment.<br><span style=\'font-size:12px;color:#aaa\'>You can still proceed the airline will assign a seat at check-in.</span></p></div>'
                    }
                        </div>
                        </div>
                    </div>
                `);
                contentContainer.append(contentPanel);
            });

            tabsContainer.append(tabsList);
            tabsContainer.append(contentContainer);
            container.append(tabsContainer);

            // Add legend at the end wrapped in a container with actions
            const legendWrapper = $('<div class="amadex-seat-legend-wrapper"></div>');
            legendWrapper.append(this.getLegendHtml());

            // Move the actions into the wrapper if it exists
            const $actions = $('.amadex-seat-selection-actions');
            if ($actions.length) {
                legendWrapper.append($actions.clone());
                $actions.remove();
            }

            container.append(legendWrapper);

            // Initialize tab switching
            this.initSeatMapTabs();

            // Update passenger selector labels if names are available
            this.updatePassengerSelectorLabels();
            // Retry after short delays to catch sessionStorage form restore
            const _self = this;
            setTimeout(function () { _self.updatePassengerSelectorLabels(); }, 200);
            setTimeout(function () { _self.updatePassengerSelectorLabels(); }, 500);
            setTimeout(function () { _self.updatePassengerSelectorLabels(); }, 1000);
            // Live listener: update name in panel as user types in passenger forms
            $(document).off('input.paxPanelName').on('input.paxPanelName',
                '[id^="pax"][id$="-firstname"], [id^="pax"][id$="-lastname"]',
                function () { _self.updatePassengerSelectorLabels(); }
            );

            // Update seat highlights for current passenger
            this.highlightSeatsForCurrentPassenger();

            // Initialize zoom and pan functionality for seat maps
            this.initSeatMapZoom();

            // Center seat map on load (especially for mobile)
            this.centerSeatMapOnLoad();
        },

        /**
         * Center seat map content on initial load
         */
        centerSeatMapOnLoad: function () {
            const scrollableContainers = $('.amadex-seat-map-scrollable-container');

            scrollableContainers.each(function () {
                const container = $(this);
                const content = container.find('.amadex-seat-map-content').first();

                if (!content.length) return;

                // Wait for content to render
                setTimeout(function () {
                    // Calculate center position
                    const containerWidth = container[0].clientWidth;
                    const containerHeight = container[0].clientHeight;
                    const contentWidth = content[0].scrollWidth;
                    const contentHeight = content[0].scrollHeight;

                    // Center horizontally and vertically
                    const scrollLeft = Math.max(0, (contentWidth - containerWidth) / 2);
                    const scrollTop = Math.max(0, (contentHeight - containerHeight) / 2);

                    container[0].scrollLeft = scrollLeft;
                    container[0].scrollTop = scrollTop;

                }, 100);
            });
        },

        /**
         * Initialize zoom and pan functionality for seat maps
         * Supports pinch-to-zoom on touch devices and mouse wheel zoom on desktop
         */
        initSeatMapZoom: function () {
            const scrollableContainers = $('.amadex-seat-map-scrollable-container');

            scrollableContainers.each(function () {
                const container = $(this);
                const content = container.find('.amadex-seat-map-content').first();

                if (!content.length) return;

                let scale = 1;
                let minScale = 0.5;
                let maxScale = 2.0;
                let startDistance = 0;
                let lastScale = 1;
                let isDragging = false;
                let startX = 0;
                let startY = 0;
                let scrollLeft = 0;
                let scrollTop = 0;
                let touchStartX = 0;
                let touchStartY = 0;

                // Apply initial transform
                content.css({
                    'transform': `scale(${scale})`,
                    'transform-origin': 'top left'
                });

                // Touch pinch-to-zoom with center point
                container.on('touchstart', function (e) {
                    if (e.touches.length === 2) {
                        e.preventDefault();
                        const touch1 = e.touches[0];
                        const touch2 = e.touches[1];
                        startDistance = Math.hypot(
                            touch2.clientX - touch1.clientX,
                            touch2.clientY - touch1.clientY
                        );
                        lastScale = scale;

                        // Store center point of pinch
                        touchStartX = (touch1.clientX + touch2.clientX) / 2;
                        touchStartY = (touch1.clientY + touch2.clientY) / 2;
                    } else if (e.touches.length === 1) {
                        // Single touch - prepare for panning
                        const touch = e.touches[0];
                        touchStartX = touch.clientX;
                        touchStartY = touch.clientY;
                        scrollLeft = container[0].scrollLeft;
                        scrollTop = container[0].scrollTop;
                        isDragging = true;
                    }
                });

                container.on('touchmove', function (e) {
                    if (e.touches.length === 2) {
                        e.preventDefault();
                        const touch1 = e.touches[0];
                        const touch2 = e.touches[1];
                        const currentDistance = Math.hypot(
                            touch2.clientX - touch1.clientX,
                            touch2.clientY - touch1.clientY
                        );

                        if (startDistance > 0) {
                            const newScale = lastScale * (currentDistance / startDistance);
                            scale = Math.max(minScale, Math.min(maxScale, newScale));

                            // Calculate center point of pinch
                            const centerX = (touch1.clientX + touch2.clientX) / 2;
                            const centerY = (touch1.clientY + touch2.clientY) / 2;

                            // Get container bounds
                            const rect = container[0].getBoundingClientRect();
                            const containerCenterX = centerX - rect.left;
                            const containerCenterY = centerY - rect.top;

                            // Zoom towards center point
                            const scaleDiff = scale / lastScale;
                            const scrollLeftBefore = container[0].scrollLeft;
                            const scrollTopBefore = container[0].scrollTop;

                            content.css({
                                'transform': `scale(${scale})`,
                                'transform-origin': 'center center'
                            });

                            // Adjust scroll to zoom towards center point
                            const scrollLeftAfter = scrollLeftBefore + (containerCenterX - scrollLeftBefore) * (1 - scaleDiff);
                            const scrollTopAfter = scrollTopBefore + (containerCenterY - scrollTopBefore) * (1 - scaleDiff);

                            container[0].scrollLeft = Math.max(0, scrollLeftAfter);
                            container[0].scrollTop = Math.max(0, scrollTopAfter);

                            lastScale = scale;
                        }
                    } else if (isDragging && e.touches.length === 1) {
                        e.preventDefault();
                        const touch = e.touches[0];
                        const deltaX = touch.clientX - touchStartX;
                        const deltaY = touch.clientY - touchStartY;

                        // Pan in all directions
                        container[0].scrollLeft = Math.max(0, scrollLeft - deltaX);
                        container[0].scrollTop = Math.max(0, scrollTop - deltaY);
                    }
                });

                container.on('touchend', function () {
                    startDistance = 0;
                    isDragging = false;
                    container.css('cursor', 'grab');
                });

                // Mouse wheel zoom (Ctrl/Cmd + wheel) - zoom towards mouse position
                container.on('wheel', function (e) {
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();

                        const delta = e.originalEvent.deltaY > 0 ? -0.1 : 0.1;
                        scale = Math.max(minScale, Math.min(maxScale, scale + delta));

                        // Get mouse position relative to container
                        const rect = container[0].getBoundingClientRect();
                        const mouseX = e.clientX - rect.left;
                        const mouseY = e.clientY - rect.top;

                        // Get current scroll position
                        const scrollLeftBefore = container[0].scrollLeft;
                        const scrollTopBefore = container[0].scrollTop;

                        // Apply zoom with center origin
                        content.css({
                            'transform': `scale(${scale})`,
                            'transform-origin': 'center center'
                        });

                        // Calculate new scroll position to zoom towards mouse
                        const scaleDiff = scale / lastScale;
                        const scrollLeftAfter = scrollLeftBefore + (mouseX - scrollLeftBefore) * (1 - scaleDiff);
                        const scrollTopAfter = scrollTopBefore + (mouseY - scrollTopBefore) * (1 - scaleDiff);

                        container[0].scrollLeft = Math.max(0, scrollLeftAfter);
                        container[0].scrollTop = Math.max(0, scrollTopAfter);

                        lastScale = scale;
                    }
                });

                // Mouse drag to pan
                container.on('mousedown', function (e) {
                    if (e.button === 0 && !e.ctrlKey && !e.metaKey) {
                        isDragging = true;
                        startX = e.pageX - container.offset().left;
                        startY = e.pageY - container.offset().top;
                        scrollLeft = container[0].scrollLeft;
                        scrollTop = container[0].scrollTop;
                        container.css('cursor', 'grabbing');
                        e.preventDefault();
                    }
                });

                $(document).on('mousemove', function (e) {
                    if (isDragging) {
                        const x = e.pageX - container.offset().left;
                        const y = e.pageY - container.offset().top;
                        const walkX = (x - startX) * 1.5;
                        const walkY = (y - startY) * 1.5;
                        container[0].scrollLeft = scrollLeft - walkX;
                        container[0].scrollTop = scrollTop - walkY;
                    }
                });

                $(document).on('mouseup', function () {
                    if (isDragging) {
                        isDragging = false;
                        container.css('cursor', 'grab');
                    }
                });
            });
        },

        /**
         * Render seat map for a specific segment
         * Now includes cockpit indicator, row numbers, and seat column letters
         */
        // renderSeatMapForSegment: function(seatMap, segmentId, segmentData) {
        //     if (!seatMap.decks || !seatMap.decks.length) {
        //         return '<div class="amadex-seat-map-content"><p>No seat map data available for this segment.</p></div>';
        //     }

        //     let html = '<div class="amadex-seat-map-content">';

        //     seatMap.decks.forEach((deck, deckIndex) => {
        //         if (!deck.seats || !deck.seats.length) {
        //             return;
        //         }

        //         // Group seats by row and determine column structure
        //         const seatsByRow = {};
        //         const columnLetters = new Set();

        //         deck.seats.forEach((seat) => {
        //             const rowMatch = seat.number.match(/^(\d+)/);
        //             const row = rowMatch ? rowMatch[1] : '0';
        //             const letterMatch = seat.number.match(/^(\d+)([A-Z]+)/);
        //             const letter = letterMatch ? letterMatch[2] : '';

        //             if (letter) {
        //                 columnLetters.add(letter);
        //             }

        //             if (!seatsByRow[row]) {
        //                 seatsByRow[row] = [];
        //             }
        //             seatsByRow[row].push(seat);
        //         });

        //         // Sort column letters (A, B, C, D, E, F, G, H, J, K, etc.)
        //         const sortedColumns = Array.from(columnLetters).sort((a, b) => {
        //             // Custom sort: A-Z, but handle typical airline patterns
        //             return a.localeCompare(b);
        //         });

        //         // Get aircraft code from segment data
        //         const aircraftCode = segmentData?.aircraft || segmentData?.aircraft?.code || '';

        //         // Detect aisle positions using aircraft configuration
        //         const aislePositions = this.detectAislePositions(sortedColumns, aircraftCode);

        //         // Add cockpit/front indicator at the top (styled with CSS)
        //         html += '<div class="amadex-seat-map-orientation">';
        //         html += '<div class="amadex-cockpit-indicator">';
        //         html += '<span class="amadex-cockpit-icon"></span>';
        //         html += '<span class="amadex-cockpit-label">Front of Aircraft</span>';
        //         html += '</div>';
        //         html += '</div>';

        //         // Add seat column letter header with aisle indicators
        //         html += '<div class="amadex-seat-map-column-header">';
        //         html += '<div class="amadex-row-number-header"></div>'; // Empty for row number column
        //         sortedColumns.forEach((letter, index) => {
        //             html += `<div class="amadex-seat-column-label" data-column="${letter}">${letter}</div>`;
        //             // Add aisle indicator after this column if it's an aisle position
        //             if (aislePositions.has(index)) {
        //                 html += '<div class="amadex-aisle-indicator amadex-aisle-header" title="Aisle"></div>';
        //             }
        //         });
        //         html += '<div class="amadex-row-number-header"></div>'; // Empty for row number column
        //         html += '</div>';

        //         // Render rows with row numbers on both sides
        //         const rows = Object.keys(seatsByRow).sort((a, b) => parseInt(a) - parseInt(b));
        //         rows.forEach((row) => {
        //             html += '<div class="amadex-seat-row" data-row="' + row + '">';

        //             // Left row number
        //             html += '<div class="amadex-row-number">' + row + '</div>';

        //             // Sort seats in this row by column letter
        //             const seatsInRow = seatsByRow[row];
        //             seatsInRow.sort((a, b) => {
        //                 const aLetter = (a.number.match(/^(\d+)([A-Z]+)/) || [])[2] || '';
        //                 const bLetter = (b.number.match(/^(\d+)([A-Z]+)/) || [])[2] || '';
        //                 return sortedColumns.indexOf(aLetter) - sortedColumns.indexOf(bLetter);
        //             });

        //             // Render seats in order, with aisles and gaps
        //             sortedColumns.forEach((columnLetter, colIndex) => {
        //                 const seatInColumn = seatsInRow.find(s => {
        //                     const letterMatch = s.number.match(/^(\d+)([A-Z]+)/);
        //                     return letterMatch && letterMatch[2] === columnLetter;
        //                 });

        //                 if (seatInColumn) {
        //                     html += this.renderSeat(seatInColumn, segmentId);
        //                 } else {
        //                     // Missing seat - check if this gap is an aisle
        //                     // If previous column was an aisle position, this gap is part of the aisle
        //                     if (aislePositions.has(colIndex - 1)) {
        //                         // This gap is part of an aisle - use aisle indicator
        //                         html += '<div class="amadex-aisle-indicator amadex-aisle-vertical" title="Aisle"></div>';
        //                     } else {
        //                         // Regular gap (missing seat, not an aisle)
        //                         html += '<div class="amadex-seat-gap"></div>';
        //                     }
        //                 }

        //                 // Add aisle indicator AFTER this column if it's an aisle position
        //                 // This creates the visual aisle separator between seat groups
        //                 if (aislePositions.has(colIndex)) {
        //                     html += '<div class="amadex-aisle-indicator amadex-aisle-vertical" title="Aisle"></div>';
        //                 }
        //             });

        //             // Right row number
        //             html += '<div class="amadex-row-number">' + row + '</div>';

        //             html += '</div>';
        //         });
        //     });

        //     return html;
        // },
        prefetchSeatCurrencyRate: function (seatMap) {
            if (!seatMap || !seatMap.decks) return;
            for (var d = 0; d < seatMap.decks.length; d++) {
                var seats = seatMap.decks[d].seats || [];
                for (var s = 0; s < seats.length; s++) {
                    var tp = seats[s].travelerPricing || [];
                    for (var t = 0; t < tp.length; t++) {
                        var cur = tp[t].price && (tp[t].price.currency || tp[t].price.currencyCode);
                        if (cur && cur !== 'USD') {
                            var cacheKey = cur + '_USD';
                            if (!exchangeRates[cacheKey]) {
                                getExchangeRate(cur, 'USD').then(function (rate) {
                                    if (rate) exchangeRates[cacheKey] = rate;
                                });
                            }
                            return; // Only need to find one seat's currency
                        }
                    }
                }
            }
        },
        renderSeatMapForSegment: function (seatMap, segmentId, segmentData) {
            if (!seatMap.decks || !seatMap.decks.length) {
                return '<div class="plane-fuselage"><p style="padding:40px;text-align:center;color:#888;">No seat map data available.</p></div>';
            }

            let html = '';

            seatMap.decks.forEach((deck) => {
                if (!deck.seats || !deck.seats.length) return;

                const seatsByRow = {};
                const columnLetters = new Set();

                deck.seats.forEach((seat) => {
                    const rowMatch = seat.number.match(/^(\d+)/);
                    const row = rowMatch ? rowMatch[1] : '0';
                    const letterMatch = seat.number.match(/^(\d+)([A-Z]+)/);
                    const letter = letterMatch ? letterMatch[2] : '';
                    if (letter) columnLetters.add(letter);
                    if (!seatsByRow[row]) seatsByRow[row] = [];
                    seatsByRow[row].push(seat);
                });

                const sortedColumns = Array.from(columnLetters).sort((a, b) => a.localeCompare(b));
                const aircraftCode = segmentData?.aircraft || segmentData?.aircraft?.code || '';
                const aislePositions = this.detectAislePositions(sortedColumns, aircraftCode);

                html += '<div class="plane-fuselage">';
                html += `<div class="plane-nose">
                    <svg viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">
                        <!-- Sharp needle nose -->
                        <path d="M150,2 C130,2 60,50 25,200 L275,200 C240,50 170,2 150,2 Z"
                              fill="#f8f9fa" stroke="#cfd8dc" stroke-width="2.5"/>
                        <!-- Cockpit arc windows -->
                        <path d="M85,130 Q150,45 215,130" fill="none" stroke="#0E7D3F" stroke-width="6" stroke-linecap="round"/>
                        <path d="M92,128 Q115,80 138,68 L135,128 Z" fill="#0E7D3F" opacity="0.9"/>
                        <path d="M143,65 L148,128 L152,128 L157,65 Q153,62 147,62 Z" fill="#0E7D3F" opacity="0.9"/>
                        <path d="M162,68 Q185,80 208,128 L165,128 Z" fill="#0E7D3F" opacity="0.9"/>
                        <!-- gaps between panels -->
                        <line x1="138" y1="68" x2="136" y2="128" stroke="#f8f9fa" stroke-width="3"/>
                        <line x1="162" y1="68" x2="164" y2="128" stroke="#f8f9fa" stroke-width="3"/>
                        <!-- shine -->
                        <path d="M100,118 Q125,82 148,72" fill="none" stroke="white" stroke-width="2" opacity="0.3" stroke-linecap="round"/>
                        <!-- Door marks -->
                        <rect x="26" y="170" width="7" height="18" rx="2" fill="#b0bec5"/>
                        <rect x="267" y="170" width="7" height="18" rx="2" fill="#b0bec5"/>
                    </svg>
                    <div class="plane-nose-label">✈ Front of Aircraft</div>
                </div>`;

                html += '<div class="plane-seat-grid">';
                html += '<div class="plane-col-headers">';
                html += '<div class="plane-row-num"></div>';
                sortedColumns.forEach((letter, index) => {
                    if (aislePositions.has(index - 1) && index > 0) {
                        html += '<div class="plane-aisle"></div>';
                    }
                    html += `<div class="plane-col-label">${letter}</div>`;
                });
                html += '<div class="plane-row-num"></div>';
                html += '</div>';

                const rows = Object.keys(seatsByRow).sort((a, b) => parseInt(a) - parseInt(b));
                rows.forEach((row) => {
                    html += `<div class="plane-seat-row" data-row="${row}">`;
                    html += `<div class="plane-row-num">${row}</div>`;

                    const seatsInRow = seatsByRow[row];
                    seatsInRow.sort((a, b) => {
                        const aL = (a.number.match(/^(\d+)([A-Z]+)/) || [])[2] || '';
                        const bL = (b.number.match(/^(\d+)([A-Z]+)/) || [])[2] || '';
                        return sortedColumns.indexOf(aL) - sortedColumns.indexOf(bL);
                    });

                    sortedColumns.forEach((columnLetter, colIndex) => {
                        if (aislePositions.has(colIndex - 1) && colIndex > 0) {
                            html += '<div class="plane-aisle"></div>';
                        }
                        const seatInColumn = seatsInRow.find(s => {
                            const m = s.number.match(/^(\d+)([A-Z]+)/);
                            return m && m[2] === columnLetter;
                        });
                        if (seatInColumn) {
                            html += this.renderSeat(seatInColumn, segmentId);
                        } else {
                            html += '<div class="plane-seat-gap"></div>';
                        }
                    });

                    html += `<div class="plane-row-num">${row}</div>`;
                    html += '</div>';
                });

                html += '</div>';
                html += '</div>';
            });

            return html;
        },


        /**
         * Render individual seat
         */
        // renderSeat: function(seat, segmentId) {
        //     const seatNumber = seat.number || '';
        //     const isAvailable = this.isSeatAvailable(seat);
        //     const isSelected = this.isSeatSelected(seatNumber, segmentId);
        //     const classes = ['amadex-seat'];

        //     // Determine seat status
        //     if (isSelected) {
        //         classes.push('selected');
        //     } else if (!isAvailable) {
        //         classes.push('unavailable');
        //     } else {
        //         classes.push('available');
        //     }

        //     // Add characteristics
        //     if (seat.characteristicsCodes) {
        //         if (seat.characteristicsCodes.includes('W')) {
        //             classes.push('window');
        //         }
        //         if (seat.characteristicsCodes.includes('A')) {
        //             classes.push('aisle');
        //         }
        //     }

        //     const seatPrice = this.getSeatPrice(seat);

        //     // Get selected currency from regional settings for seat prices
        //     const selectedCurrency = getSelectedCurrency();
        //     const originalCurrency = 'USD'; // Seat prices from Amadeus API are typically in USD

        //     let priceDisplay = 'Free';
        //     if (seatPrice > 0) {
        //         // Convert price to selected currency (if different from USD)
        //         let displayPrice = seatPrice;
        //         if (selectedCurrency !== 'USD' && selectedCurrency !== originalCurrency) {
        //             // Check if exchange rate is cached
        //             const cacheKey = `USD_${selectedCurrency}`;
        //             if (exchangeRates[cacheKey]) {
        //                 // Use cached rate for immediate conversion
        //                 displayPrice = seatPrice * exchangeRates[cacheKey];
        //             }
        //             // Note: If rate not cached, we display in USD for now and will update async if needed
        //             // The seat map rendering is synchronous, so async conversion would need separate handling
        //         }

        //         // Format price with selected currency symbol
        //         priceDisplay = formatCurrencyValue(displayPrice, selectedCurrency);
        //     }

        //     // Check which passenger has this seat selected (if any)
        //     let selectedByPassenger = null;
        //     let passengerInitials = '';
        //     if (this.selectedSeats[segmentId]) {
        //         Object.keys(this.selectedSeats[segmentId]).forEach((travelerId) => {
        //             if (this.selectedSeats[segmentId][travelerId].seat_number === seatNumber) {
        //                 selectedByPassenger = travelerId;
        //                 passengerInitials = this.getPassengerInitials(parseInt(travelerId));
        //             }
        //         });
        //     }

        //     // For selected seats, show passenger initials instead of seat number in main display
        //     const seatDisplayText = isSelected && selectedByPassenger ? passengerInitials : seatNumber;

        //     // Add indicator with passenger number if seat is selected
        //     const selectedIndicator = isSelected && selectedByPassenger ? 
        //         `<div class="amadex-seat-selected-indicator">${selectedByPassenger}</div>` : '';

        //     // Show price on available seats, hide on selected/occupied
        //     const priceDisplayHtml = (isAvailable && !isSelected) ? 
        //         `<div class="amadex-seat-price ${seatPrice === 0 ? 'is-free' : ''}">${priceDisplay}</div>` : '';

        //     const isWindow = seat.characteristicsCodes && seat.characteristicsCodes.includes('W');
        //     const isAisle = seat.characteristicsCodes && seat.characteristicsCodes.includes('A');
        //     const cabinLabel = (seat.cabin || 'ECONOMY').replace(/_/g, ' ');
        //     return `
        //         <div class="${classes.join(' ')}" 
        //              data-seat-number="${seatNumber}" 
        //              data-segment-id="${segmentId}"
        //              data-price="${seatPrice}"
        //              data-cabin="${seat.cabin || 'ECONOMY'}"
        //              data-window="${isWindow ? '1' : '0'}"
        //              data-aisle="${isAisle ? '1' : '0'}"
        //              data-cabin-label="${cabinLabel}"
        //              title="${seatNumber}${seatPrice > 0 ? ' - ' + priceDisplay : ''}${selectedByPassenger ? ' - Selected by ' + this.getPassengerLabel(selectedByPassenger) : ''}">
        //             <div class="amadex-seat-number">${seatDisplayText}</div>
        //             ${priceDisplayHtml}
        //             ${selectedIndicator}
        //         </div>
        //     `;
        // },
        renderSeat: function (seat, segmentId) {
            const seatNumber = seat.number || '';
            const isAvailable = this.isSeatAvailable(seat);
            const isSelected = this.isSeatSelected(seatNumber, segmentId);
            const classes = ['amadex-seat'];

            // Determine seat status
            if (isSelected) {
                classes.push('selected');
            } else if (!isAvailable) {
                classes.push('unavailable');
            } else {
                classes.push('available');
            }

            // Add characteristics
            let isWindow = false;
            let isAisle = false;
            let hasExtraLegroom = false;
            let isExitRow = false;

            if (seat.characteristicsCodes) {
                if (seat.characteristicsCodes.includes('W')) {
                    classes.push('window');
                    isWindow = true;
                }
                if (seat.characteristicsCodes.includes('A')) {
                    classes.push('aisle');
                    isAisle = true;
                }
                if (seat.characteristicsCodes.includes('P')) {
                    hasExtraLegroom = true;
                }
                if (seat.characteristicsCodes.includes('E')) {
                    isExitRow = true;
                }
            }

            const seatPrice = this.getSeatPrice(seat);

            // Get selected currency from regional settings for seat prices
            const selectedCurrency = getSelectedCurrency();
            const originalCurrency = 'USD';

            let priceDisplay = 'Free';
            let displayPrice = seatPrice;
            if (seatPrice > 0) {
                if (selectedCurrency !== 'USD' && selectedCurrency !== originalCurrency) {
                    const cacheKey = `USD_${selectedCurrency}`;
                    if (exchangeRates[cacheKey]) {
                        displayPrice = seatPrice * exchangeRates[cacheKey];
                    }
                }
                priceDisplay = formatCurrencyValue(displayPrice, selectedCurrency);
            }

            // Check which passenger has this seat selected (if any)
            let selectedByPassenger = null;
            let passengerInitials = '';
            if (this.selectedSeats[segmentId]) {
                Object.keys(this.selectedSeats[segmentId]).forEach((travelerId) => {
                    if (this.selectedSeats[segmentId][travelerId].seat_number === seatNumber) {
                        selectedByPassenger = travelerId;
                        passengerInitials = this.getPassengerInitials(parseInt(travelerId));
                    }
                });
            }

            // For selected seats, show passenger initials instead of seat number in main display
            const seatDisplayText = isSelected && selectedByPassenger ? passengerInitials : seatNumber;

            // Build tooltip HTML
            let tooltipHtml = `
        <div class="amadex-seat-tooltip">
            <div class="amadex-seat-tooltip-content">
                <div class="amadex-seat-tooltip-row">
                    <span class="amadex-seat-tooltip-label">Seat:</span>
                    <span class="amadex-seat-tooltip-value">${seatNumber}</span>
                </div>
    `;

            // Add price to tooltip
            if (seatPrice > 0) {
                tooltipHtml += `
            <div class="amadex-seat-tooltip-row">
                <span class="amadex-seat-tooltip-label">Price:</span>
                <span class="amadex-seat-tooltip-value amadex-seat-tooltip-price">${priceDisplay}</span>
            </div>
        `;
            } else {
                tooltipHtml += `
            <div class="amadex-seat-tooltip-row">
                <span class="amadex-seat-tooltip-label">Price:</span>
                <span class="amadex-seat-tooltip-value">Free</span>
            </div>
        `;
            }

            // Add features to tooltip
            let features = [];
            if (isWindow) features.push('Window');
            if (isAisle) features.push('Aisle');
            if (hasExtraLegroom) features.push('Extra Legroom');
            if (isExitRow) features.push('Exit Row');

            if (features.length > 0) {
                tooltipHtml += `
            <div class="amadex-seat-tooltip-row">
                <span class="amadex-seat-tooltip-label">Features:</span>
                <span class="amadex-seat-tooltip-value">${features.join(', ')}</span>
            </div>
        `;
            }

            // Add passenger info if selected
            if (selectedByPassenger) {
                const passengerName = this.getPassengerLabel(selectedByPassenger);
                tooltipHtml += `
            <div class="amadex-seat-tooltip-row">
                <span class="amadex-seat-tooltip-label">Selected by:</span>
                <span class="amadex-seat-tooltip-value">${passengerName}</span>
            </div>
        `;
            }

            // Add status
            let statusText = 'Available';
            if (isSelected) statusText = 'Selected';
            else if (!isAvailable) statusText = 'Unavailable';

            tooltipHtml += `
            <div class="amadex-seat-tooltip-row">
                <span class="amadex-seat-tooltip-label">Status:</span>
                <span class="amadex-seat-tooltip-value">${statusText}</span>
            </div>
        </div>
    </div>
    `;

            // Show price on available seats, hide on selected/occupied
            const priceDisplayHtml = (isAvailable && !isSelected && seatPrice > 0) ?
                `<div class="amadex-seat-price ${seatPrice === 0 ? 'is-free' : ''}">${priceDisplay}</div>` : '';

            const cabinLabel = (seat.cabin || 'ECONOMY').replace(/_/g, ' ');

            return `
        <div class="${classes.join(' ')}" 
             data-seat-number="${seatNumber}" 
             data-segment-id="${segmentId}"
             data-price="${seatPrice}"
             data-cabin="${seat.cabin || 'ECONOMY'}"
             data-window="${isWindow ? '1' : '0'}"
             data-aisle="${isAisle ? '1' : '0'}"
             data-cabin-label="${cabinLabel}">
            <div class="amadex-seat-number">${seatDisplayText}</div>
            ${priceDisplayHtml}
            ${tooltipHtml}
        </div>
    `;
        },
        /**
         * Check if seat is available
         */
        // isSeatAvailable: function(seat) {
        //     if (!seat.travelerPricing || !seat.travelerPricing.length) {
        //         return false;
        //     }

        //     // Check if any traveler has this seat available
        //     return seat.travelerPricing.some((tp) => {
        //         return tp.seatAvailabilityStatus === 'AVAILABLE';
        //     });
        // },
        // isSeatAvailable: function(seat) {
        //     // If no travelerPricing data at all, assume available
        //     if (!seat.travelerPricing || !seat.travelerPricing.length) {
        //         return true;
        //     }
        //     // Check if any traveler has this seat available
        //     return seat.travelerPricing.some((tp) => {
        //         return tp.seatAvailabilityStatus === 'AVAILABLE' || 
        //                !tp.seatAvailabilityStatus; // treat missing status as available
        //     });
        // },
        isSeatAvailable: function (seat) {
            if (!seat.travelerPricing || !seat.travelerPricing.length) {
                return true;
            }
            return seat.travelerPricing.some((tp) => {
                if (!tp) return false;
                const status = tp.seatAvailabilityStatus;
                return status === 'AVAILABLE' || !status;
            });
        },
        /**
         * Get seat price for first available traveler
         */
        // getSeatPrice: function(seat) {
        //     if (!seat.travelerPricing || !seat.travelerPricing.length) {
        //         return 0;
        //     }

        //     const availablePricing = seat.travelerPricing.find((tp) => tp.seatAvailabilityStatus === 'AVAILABLE');
        //     if (availablePricing && availablePricing.price) {
        //         return parseFloat(availablePricing.price.total || 0);
        //     }

        //     return 0;
        // },
        //         getSeatPrice: function(seat) {
        //     if (!seat.travelerPricing || !seat.travelerPricing.length) {
        //         return 0;
        //     }

        //     const availablePricing = seat.travelerPricing.find((tp) => tp.seatAvailabilityStatus === 'AVAILABLE');
        //     if (!availablePricing || !availablePricing.price) {
        //         return 0;
        //     }

        //     const rawPrice = parseFloat(availablePricing.price.total || 0);
        //     const priceCurrency = availablePricing.price.currency || availablePricing.price.currencyCode || 'USD';

        //     // If seat price is already in USD, return as-is
        //     if (priceCurrency === 'USD') {
        //         return rawPrice;
        //     }

        //     // Convert from seat's currency to USD using cached exchange rates
        //     const cacheKey = `${priceCurrency}_USD`;
        //     if (exchangeRates[cacheKey]) {
        //         return rawPrice * exchangeRates[cacheKey];
        //     }

        //     // Rate not cached yet — fetch async and return raw for now (will re-render on next hover)
        //     getExchangeRate(priceCurrency, 'USD').then(function(rate) {
        //         if (rate) exchangeRates[cacheKey] = rate;
        //     });

        //     return rawPrice; // temporary until rate loads
        // },

        // getSeatPrice: function(seat) {
        //             if (!seat.travelerPricing || !seat.travelerPricing.length) {
        //                 return 0;
        //             }

        //             const availablePricing = seat.travelerPricing.find((tp) => tp.seatAvailabilityStatus === 'AVAILABLE');
        //             if (!availablePricing || !availablePricing.price) {
        //                 return 0;
        //             }

        //             const rawPrice = parseFloat(availablePricing.price.total || 0);
        //             return rawPrice;
        //         },

        getSeatPrice: function (seat) {
            if (!seat.travelerPricing || !seat.travelerPricing.length) {
                return 0;
            }

            const availablePricing = seat.travelerPricing.find((tp) => tp && tp.seatAvailabilityStatus === 'AVAILABLE');
            if (!availablePricing || !availablePricing.price) {
                return 0;
            }

            const rawPrice = parseFloat(availablePricing.price.total || 0);
            if (isNaN(rawPrice) || rawPrice <= 0) {
                return 0;
            }

            // Amadeus sometimes returns seat prices in cents (no currency code)
            // Detect: if value > 500, it's almost certainly cents (no seat addon costs $500+)
            // e.g. 4999 → $49.99 | 2899 → $28.99 | 32999 → $329.99
            // If value <= 500, it's already in dollars (e.g. $49, $99, $329)
            const priceInDollars = rawPrice > 500 ? rawPrice / 100 : rawPrice;

            return priceInDollars;
        },

        /**
         * Check if seat is selected
         */
        isSeatSelected: function (seatNumber, segmentId) {
            if (!this.selectedSeats[segmentId]) {
                return false;
            }

            return Object.values(this.selectedSeats[segmentId]).some((seat) => {
                return seat.seat_number === seatNumber;
            });
        },


        getSelectedByPassenger: function (segmentId, seatNumber) {
            if (!this.selectedSeats[segmentId]) {
                return null;
            }

            for (const travelerId in this.selectedSeats[segmentId]) {
                if (this.selectedSeats[segmentId][travelerId].seat_number === seatNumber) {
                    return travelerId;
                }
            }
            return null;
        },

        /**
         * Get segment data from flight offer
         * Handles multiple possible ID field names
         */
        getSegmentData: function (segmentId) {
            if (!this.flightOffer || !this.flightOffer.itineraries) {
                return null;
            }

            for (const itinerary of this.flightOffer.itineraries) {
                if (itinerary.segments) {
                    const segment = itinerary.segments.find((s) => {
                        // Try multiple possible ID field names
                        return (s.segmentId && s.segmentId.toString() === segmentId.toString()) ||
                            (s.id && s.id.toString() === segmentId.toString()) ||
                            (s.segment_id && s.segment_id.toString() === segmentId.toString());
                    });
                    if (segment) {
                        return segment;
                    }
                }
            }

            return null;
        },

        /**
         * Get passenger label by ID
         */
        getPassengerLabel: function (travelerId) {
            const passenger = this.passengers.find(p => p.id === travelerId);
            return passenger ? passenger.label : `Passenger ${travelerId}`;
        },

        /**
         * Aircraft configuration database
         * Maps aircraft type codes to their seat column layout and aisle positions
         * Aisle positions are indices after which aisles appear (0-based column index)
         */
        getAircraftConfig: function (aircraftCode) {
            if (!aircraftCode) {
                return null;
            }

            const code = aircraftCode.toUpperCase().trim();

            // Aircraft configuration database
            // Format: { columns: ['A','B','C',...], aisles: [index1, index2] }
            const aircraftConfigs = {
                // Airbus A320 family (narrow-body, 3-3 configuration)
                '32N': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] }, // Aisle after C
                '320': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },
                '321': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },
                '32A': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },
                '32B': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },

                // Airbus A330 (wide-body, 2-4-2 configuration)
                '330': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], aisles: [1, 5] }, // Aisles after B and F
                '333': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] }, // 3-3-3 config
                '332': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], aisles: [1, 5] },

                // Airbus A350 (wide-body)
                '350': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] }, // 3-3-3
                '351': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] },
                '359': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] },

                // Airbus A380 (wide-body, double-deck)
                '380': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] },

                // Boeing 737 (narrow-body, 3-3 configuration)
                '737': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },
                '738': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },
                '739': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },
                '73H': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },
                '73M': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },

                // Boeing 777 (wide-body)
                '777': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] }, // 3-3-3
                '77W': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] },
                '77L': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] },

                // Boeing 787 (wide-body)
                '787': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] }, // 3-3-3
                '788': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] },
                '789': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] },
                '781': { columns: ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'], aisles: [2, 6] },

                // Embraer (regional jets, 2-2 configuration)
                'E75': { columns: ['A', 'B', 'C', 'D'], aisles: [1] }, // Aisle after B
                'E90': { columns: ['A', 'B', 'C', 'D'], aisles: [1] },
                'E95': { columns: ['A', 'B', 'C', 'D'], aisles: [1] },
                'E17': { columns: ['A', 'B', 'C', 'D'], aisles: [1] },

                // Bombardier CRJ (regional, 2-2)
                'CRJ': { columns: ['A', 'B', 'C', 'D'], aisles: [1] },
                'CR7': { columns: ['A', 'B', 'C', 'D'], aisles: [1] },
                'CR9': { columns: ['A', 'B', 'C', 'D'], aisles: [1] },

                // Airbus A319 (narrow-body, 3-3)
                '319': { columns: ['A', 'B', 'C', 'D', 'E', 'F'], aisles: [2] },
            };

            // Try exact match first
            if (aircraftConfigs[code]) {
                return aircraftConfigs[code];
            }

            // Try partial match (e.g., "32N" matches "32NEO")
            for (const [key, config] of Object.entries(aircraftConfigs)) {
                if (code.startsWith(key) || key.startsWith(code)) {
                    return config;
                }
            }

            return null;
        },

        /**
         * Detect aisle positions based on aircraft type and seat column configuration
         * Uses aircraft configuration database for accurate layout
         */
        detectAislePositions: function (sortedColumns, aircraftCode) {
            const aislePositions = new Set();
            const columnCount = sortedColumns.length;

            if (columnCount === 0) {
                return aislePositions;
            }

            // Try to get aircraft configuration first
            if (aircraftCode) {
                const config = this.getAircraftConfig(aircraftCode);
                if (config && config.aisles) {
                    // Map aisle positions from config to actual column indices
                    config.aisles.forEach((aisleIndex) => {
                        if (aisleIndex < sortedColumns.length) {
                            aislePositions.add(aisleIndex);
                        }
                    });

                    // If we found valid aisle positions, return them
                    if (aislePositions.size > 0) {
                        return aislePositions;
                    }
                }
            }

            // Fallback to pattern detection if no aircraft config found
            // Common patterns detection
            if (columnCount <= 6) {
                // Narrow-body aircraft (typically 3-3 or 2-3 configuration)
                // Aisle usually after C (index 2) in 3-3 config
                // Or after B (index 1) in 2-3 config
                if (sortedColumns.includes('C')) {
                    const cIndex = sortedColumns.indexOf('C');
                    if (cIndex < columnCount - 1 && sortedColumns.includes('D')) {
                        aislePositions.add(cIndex);
                    }
                } else if (sortedColumns.includes('B')) {
                    const bIndex = sortedColumns.indexOf('B');
                    if (bIndex < columnCount - 1) {
                        aislePositions.add(bIndex);
                    }
                }
            } else if (columnCount <= 9) {
                // Wide-body aircraft (typically 3-3-3, 2-4-2, or 2-5-2)
                // First aisle: usually after C (3-seat group) or B (2-seat group)
                if (sortedColumns.includes('C')) {
                    const cIndex = sortedColumns.indexOf('C');
                    if (cIndex < columnCount - 1) {
                        aislePositions.add(cIndex);
                    }
                } else if (sortedColumns.includes('B')) {
                    const bIndex = sortedColumns.indexOf('B');
                    if (bIndex < columnCount - 1) {
                        aislePositions.add(bIndex);
                    }
                }

                // Second aisle: usually after G or F in middle section
                if (sortedColumns.includes('G')) {
                    const gIndex = sortedColumns.indexOf('G');
                    if (gIndex < columnCount - 1) {
                        aislePositions.add(gIndex);
                    }
                } else if (sortedColumns.includes('F')) {
                    const fIndex = sortedColumns.indexOf('F');
                    if (fIndex < columnCount - 1 && sortedColumns.includes('H')) {
                        aislePositions.add(fIndex);
                    }
                }
            } else {
                // Larger configurations - detect based on gaps
                // Look for large gaps in column sequence (missing letters indicate aisles)
                for (let i = 0; i < sortedColumns.length - 1; i++) {
                    const current = sortedColumns[i];
                    const next = sortedColumns[i + 1];

                    // If there's a significant gap in alphabet sequence, it's likely an aisle
                    const currentCode = current.charCodeAt(0);
                    const nextCode = next.charCodeAt(0);

                    // Gap of 2+ letters suggests an aisle (e.g., C to E means D is missing = aisle)
                    if (nextCode - currentCode >= 2) {
                        aislePositions.add(i);
                    }
                }
            }

            return aislePositions;
        },

        /**
         * Get passenger initials for display on selected seats (e.g., "JJ" for Jonas James)
         */
        getPassengerInitials: function (travelerId) {
            // Try to get first and last name from form fields
            const firstName = $(`#pax${travelerId}-firstname`).val() || '';
            const lastName = $(`#pax${travelerId}-lastname`).val() || '';

            if (firstName && lastName) {
                // Use first letter of first name + first letter of last name
                return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
            } else if (firstName) {
                // Only first name available - use first two letters
                return firstName.substring(0, 2).toUpperCase();
            } else if (lastName) {
                // Only last name available - use first two letters
                return lastName.substring(0, 2).toUpperCase();
            }

            // Fallback: Try to extract from passenger label if available
            const passenger = this.passengers.find(p => p.id === travelerId);
            if (passenger && passenger.label) {
                const label = passenger.label;
                const nameMatch = label.match(/:\s*([^:]+)$/);
                if (nameMatch) {
                    const name = nameMatch[1].trim();
                    const nameParts = name.split(/\s+/);
                    if (nameParts.length >= 2) {
                        return (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase();
                    } else if (nameParts.length === 1) {
                        return nameParts[0].substring(0, 2).toUpperCase();
                    }
                }
            }

            // Final fallback: Use passenger number
            return `P${travelerId}`;
        },

        /**
         * Get segment title with flight number, route, date, and aircraft
         */
        getSegmentTitle: function (segmentData, segmentId) {
            if (!segmentData) {
                return `Segment ${segmentId}`;
            }

            const parts = [];

            // Flight number and carrier
            const carrierCode = segmentData.carrier_code || segmentData.carrierCode || '';
            const flightNumber = segmentData.number || '';
            if (carrierCode && flightNumber) {
                parts.push(`${carrierCode} ${flightNumber}`);
            }

            // Route
            const dep = segmentData.departure?.iata_code || segmentData.departure?.iataCode || '';
            const arr = segmentData.arrival?.iata_code || segmentData.arrival?.iataCode || '';
            if (dep && arr) {
                parts.push(`${dep} → ${arr}`);
            }

            // Date and time
            if (segmentData.departure?.at) {
                try {
                    const depDate = new Date(segmentData.departure.at);
                    const dateStr = depDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const timeStr = depDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                    parts.push(`${dateStr} at ${timeStr}`);
                } catch (e) {
                    // Ignore date parsing errors
                }
            }

            // Aircraft type
            if (segmentData.aircraft) {
                parts.push(`Aircraft: ${segmentData.aircraft}`);
            }

            return parts.length > 0 ? parts.join(' • ') : `Segment ${segmentId}`;
        },

        /**
         * Get passenger selector button group HTML
         * Now shows type-based labels (Adult 1, Child 1, etc.) instead of "Passenger #"
         */
        getPassengerSelectorHtml: function () {
            let items = '';
            this.passengers.forEach((passenger) => {
                const isActive = passenger.id === this.currentPassengerId;
                const baseLabel = passenger.typeLabel || `Passenger ${passenger.id}`;
                const passengerName = this.getPassengerName(passenger.id);
                const seatInfo = this.getSeatInfoForPassenger(passenger.id);

                items += `
            <div class="amadex-pax-panel-item ${isActive ? 'active' : ''}"
                 data-passenger-id="${passenger.id}">
                <div class="amadex-pax-panel-name">
                    <span class="amadex-pax-panel-label">${baseLabel}</span>
                    <span class="amadex-pax-panel-fullname">${passengerName}</span>
                </div>
                <div class="amadex-pax-panel-seat" id="pax-seat-display-${passenger.id}">
                    ${seatInfo ? `<span class="amadex-pax-seat-selected">${seatInfo}</span>` : '<span class="amadex-pax-seat-empty">Select Seat</span>'}
                </div>
            </div>
        `;
            });

            return `<div class="amadex-pax-panel-list">${items}</div>`;
        },

        // Helper to get seat info string for a passenger across all segments
        // getSeatInfoForPassenger: function(passengerId) {
        //     const parts = [];
        //     Object.keys(this.selectedSeats).forEach(segId => {
        //         const seat = this.selectedSeats[segId] && this.selectedSeats[segId][String(passengerId)];
        //         if (seat) parts.push(seat.seatNumber || seat);
        //     });
        //     return parts.join(', ');
        // },

        getSeatInfoForPassenger: function (passengerId) {
            const parts = [];
            Object.keys(this.selectedSeats).forEach(segId => {
                const seat = this.selectedSeats[segId] && this.selectedSeats[segId][String(passengerId)];
                if (seat) {
                    // seat is stored as {seat_number, price, cabin} — extract the string
                    const num = seat.seat_number || seat.seatNumber || seat.number || '';
                    if (num) parts.push(num);
                }
            });
            return parts.join(', ');
        },

        /**
         * Get passenger name from form fields
         * Handles all passenger types: adults, children, infants
         */
        getPassengerName: function (travelerId) {
            // Find passenger in the passengers array to get type
            const passenger = this.passengers.find(p => p.id === travelerId || String(p.id) === String(travelerId));
            const travelerType = passenger ? passenger.type : 'ADULT';

            let firstName = '';
            let lastName = '';

            // Get form field ID based on passenger type and traveler ID
            const travelerIdNum = parseInt(travelerId);
            const adultCount = this.passengers.filter(p => p.type === 'ADULT').length;
            const childCount = this.passengers.filter(p => p.type === 'CHILD').length;

            let middleName = '';

            if (travelerType === 'ADULT') {
                // Adults use traveler ID directly (1, 2, 3, etc.)
                const $form = $(`#pax${travelerIdNum}-firstname`).closest('.amadex-passenger-form-card');
                if ($form.length > 0) {
                    firstName = $(`#pax${travelerIdNum}-firstname`).val() || '';
                    middleName = $(`#pax${travelerIdNum}-middlename`).val() || '';
                    lastName = $(`#pax${travelerIdNum}-lastname`).val() || '';
                }
            } else if (travelerType === 'CHILD') {
                // Children: traveler ID = adultCount + childIndex
                const childIndex = travelerIdNum - adultCount;
                const childFormIndex = childIndex - 1; // 0-based index
                const $childForm = $('.amadex-passenger-form[data-passenger-type="child"]').eq(childFormIndex);
                if ($childForm.length > 0) {
                    const paxIndex = adultCount + childIndex;
                    firstName = $(`#pax${paxIndex}-firstname`).val() || '';
                    middleName = $(`#pax${paxIndex}-middlename`).val() || '';
                    lastName = $(`#pax${paxIndex}-lastname`).val() || '';
                }
            } else if (travelerType === 'HELD_INFANT' || travelerType === 'INFANT') {
                // Infants: traveler ID = adultCount + childCount + infantIndex
                const infantIndex = travelerIdNum - adultCount - childCount;
                const infantFormIndex = infantIndex - 1; // 0-based index
                const $infantForm = $('.amadex-passenger-form[data-passenger-type="infant"]').eq(infantFormIndex);
                if ($infantForm.length > 0) {
                    const paxIndex = adultCount + childCount + infantIndex;
                    firstName = $(`#pax${paxIndex}-firstname`).val() || '';
                    middleName = $(`#pax${paxIndex}-middlename`).val() || '';
                    lastName = $(`#pax${paxIndex}-lastname`).val() || '';
                }
            }

            // Return formatted name including middle name
            const fullName = [firstName, middleName, lastName].filter(Boolean).join(' ');
            return fullName || '';
        },

        /**
         * Update passenger selector button labels with entered names
         * Now uses type-based labels (Adult 1, Child 1, etc.) and appends name when entered
         */
        updatePassengerSelectorLabels: function () {
            // Update legacy selector buttons (if present)
            $('.amadex-passenger-selector-btn').each((index, btn) => {
                const $btn = $(btn);
                const travelerId = $btn.data('passenger-id');
                const baseTypeLabel = $btn.data('type-label');
                const passengerName = this.getPassengerName(travelerId);
                if (baseTypeLabel) {
                    $btn.text(passengerName ? `${baseTypeLabel} (${passengerName})` : baseTypeLabel);
                } else {
                    const passengerNum = travelerId;
                    $btn.text(passengerName ? `Passenger # ${passengerNum} (${passengerName})` : `Passenger # ${passengerNum}`);
                }
            });

            // Update .amadex-pax-panel-fullname spans in the seat passenger panel
            const self = this;
            $('#amadex-seat-passenger-panel .amadex-pax-panel-item').each(function () {
                const travelerId = $(this).data('passenger-id');
                const name = self.getPassengerName(travelerId);
                $(this).find('.amadex-pax-panel-fullname').text(name || '');
            });
        },

        /**
         * Highlight seats for current passenger
         */
        highlightSeatsForCurrentPassenger: function () {
            const currentTravelerId = this.currentPassengerId;

            // Remove all highlights first
            $('.amadex-seat').removeClass('selected-by-current-passenger');

            // Highlight seats selected by current passenger
            if (this.selectedSeats && typeof this.selectedSeats === 'object') {
                Object.keys(this.selectedSeats).forEach((segmentId) => {
                    if (this.selectedSeats[segmentId] && this.selectedSeats[segmentId][currentTravelerId]) {
                        const seatNumber = this.selectedSeats[segmentId][currentTravelerId].seat_number;
                        $(`.amadex-seat[data-seat-number="${seatNumber}"][data-segment-id="${segmentId}"]`)
                            .addClass('selected-by-current-passenger');
                    }
                });
            }
        },

        /**
         * Initialize seat map tabs functionality
         */
        initSeatMapTabs: function () {
            const self = this;

            // Tab button click handler
            $(document).on('click', '.amadex-seat-map-tab', function () {
                const $tab = $(this);
                const tabIndex = $tab.data('tab-index');
                const segmentId = $tab.data('segment-id');

                // Update active tab
                $('.amadex-seat-map-tab').removeClass('active');
                $tab.addClass('active');

                // Update active panel
                $('.amadex-seat-map-tab-panel').removeClass('active');
                $(`.amadex-seat-map-tab-panel[data-tab-index="${tabIndex}"]`).addClass('active');

            });
        },

        /**
         * Get legend HTML
         */
        // getLegendHtml: function() {
        //     return `
        //         <div class="amadex-seat-legend">
        //             <h5>Legend:</h5>
        //             <div class="amadex-legend-items">
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat unavailable"></span>
        //                     Unavailable
        //                 </span>
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat selected"></span>
        //                     Selected
        //                 </span>
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat available"></span>
        //                     Available
        //                 </span>
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat window"></span>
        //                     Window
        //                 </span>
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat aisle"></span>
        //                     Aisle
        //                 </span>
        //             </div>
        //         </div>
        //     `;
        // },
        // getLegendHtml: function() {
        //     return `
        //         <div class="amadex-seat-legend">
        //             <h5>Seat Legend</h5>
        //             <div class="amadex-legend-items">
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat available"></span>
        //                     <span>Available</span>
        //                 </span>
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat window available"></span>
        //                     <span>Window</span>
        //                 </span>
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat aisle available"></span>
        //                     <span>Aisle</span>
        //                 </span>
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat selected"></span>
        //                     <span>Your Seat</span>
        //                 </span>
        //                 <span class="amadex-legend-item">
        //                     <span class="amadex-seat unavailable"></span>
        //                     <span>Occupied</span>
        //                 </span>
        //             </div>
        //         </div>
        //     `;
        // },

        getLegendHtml: function () {
            return `
                <div class="amadex-seat-legend">
                    <div class="amadex-legend-items">
                        <span class="amadex-legend-item">
                            <span class="amadex-seat unavailable" style="width:28px;height:32px;display:inline-flex;"></span>
                            <span>Occupied</span>
                        </span>
                        <span class="amadex-legend-item">
                            <span class="amadex-seat available" style="width:28px;height:32px;display:inline-flex;"></span>
                            <span>Available</span>
                        </span>
                        <span class="amadex-legend-item">
                            <span class="amadex-seat selected" style="width:28px;height:32px;display:inline-flex;"></span>
                            <span>Selected</span>
                        </span>
                    </div>
                </div>
            `;
        },
        /**
         * Show unavailable message
         */
        showUnavailable: function () {
            this.showUnavailableWithMessage('Seat selection is not available for this flight. You will be assigned seats at check-in.');
        },

        /**
         * Show unavailable message with custom text
         */
        showUnavailableWithMessage: function (message) {
            $('#amadex-seat-map-loading').hide();

            // Update the message text
            const $unavailableDiv = $('#amadex-seat-map-unavailable');
            $unavailableDiv.find('p').html('<strong>Seat Selection Not Available</strong><br>' + message);
            $unavailableDiv.show();

            $('#amadex-seat-maps-container').empty();
            // Ensure section is visible
            $('#amadex-seat-selection-section').show();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            const self = this;

            // Seat click handler: show badge to choose passenger (available or selected seat)
            // $(document).on('click', '.amadex-seat.available, .amadex-seat.selected', function(e) {
            //     e.preventDefault();
            //     e.stopPropagation();
            //     const $seat = $(this);
            //     const seatNumber = $seat.data('seat-number');
            //     const segmentId = $seat.data('segment-id');
            //     const price = parseFloat($seat.data('price') || 0);
            //     const cabin = $seat.data('cabin') || 'ECONOMY';
            //     self.showSeatSelectionBadge($seat, segmentId, seatNumber, price, cabin);
            // });

            $(document).on('click', '.amadex-seat.available, .amadex-seat.selected', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $seat = $(this);
                const seatNumber = $seat.data('seat-number');
                const segmentId = $seat.data('segment-id');
                const price = parseFloat($seat.data('price') || 0);
                const cabin = $seat.data('cabin') || 'ECONOMY';

                // Check if seat is already selected by someone else
                const isSelected = self.isSeatSelected(seatNumber, segmentId);

                if (isSelected) {
                    // Always deselect silently - no alert
                    self.deselectSeat(segmentId, seatNumber);
                } else {
                    // Select the seat for current passenger
                    self.selectSeat(segmentId, seatNumber, price, cabin);
                }
            });

            // Assign seat from badge when a passenger radio is selected
            $(document).on('change', '#amadex-seat-selection-badge input.amadex-seat-badge-passenger-radio', function () {
                const travelerId = $(this).val();
                const $badge = $('#amadex-seat-selection-badge');
                const segmentId = $badge.data('segment-id');
                const seatNumber = $badge.data('seat-number');
                const price = parseFloat($badge.data('price') || 0);
                const cabin = $badge.data('cabin') || 'ECONOMY';
                if (segmentId && seatNumber && travelerId) {
                    self.currentPassengerId = travelerId;
                    self.selectSeat(segmentId, seatNumber, price, cabin);
                    self.hideSeatSelectionBadge();
                }
            });

            // Close badge when clicking outside
            $(document).on('click', function (e) {
                const $badge = $('#amadex-seat-selection-badge');
                if (!$badge.hasClass('is-visible')) return;
                if ($(e.target).closest('.amadex-seat-selection-badge, .amadex-seat.available').length) return;
                self.hideSeatSelectionBadge();
            });

            // Selected seat click handler (to deselect)
            $(document).on('click', '.amadex-seat.selected', function () {
                const $seat = $(this);
                const seatNumber = $seat.data('seat-number');
                const segmentId = $seat.data('segment-id');

                self.deselectSeat(segmentId, seatNumber);
            });

            // FIXED: Skip seat selection button handler - completely replaced
            // Single skip button handler - always goes to addons (next step after seats)
            $(document).off('click.skipSeat touchstart.skipSeat', '#amadex-skip-seat-selection')
                .on('click.skipSeat', '#amadex-skip-seat-selection', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof navigateToStep === 'function') {
                        navigateToStep('addons');
                    }
                });
            // Passenger selector button click handler
            $(document).on('click', '.amadex-passenger-selector-btn', function () {
                const $btn = $(this);
                const newPassengerId = $btn.data('passenger-id');

                // Update active state
                $('.amadex-passenger-selector-btn').removeClass('active');
                $btn.addClass('active');

                self.currentPassengerId = newPassengerId;
                // Update highlights for new passenger (no need to re-render entire map)
                self.highlightSeatsForCurrentPassenger();
            });

            // Listen for passenger name changes to update dropdown labels
            $(document).on('input blur', '.amadex-passenger-form-card input[name*="firstname"], .amadex-passenger-form-card input[name*="first_name"], .amadex-passenger-form-card input[name*="lastname"], .amadex-passenger-form-card input[name*="last_name"], .amadex-passenger-form input[name*="firstname"], .amadex-passenger-form input[name*="first_name"], .amadex-passenger-form input[name*="lastname"], .amadex-passenger-form input[name*="last_name"]', function () {
                // Small delay to allow form to update
                setTimeout(() => {
                    self.updatePassengerSelectorLabels();
                }, 100);
            });
        },

        /**
         * Select a seat
         */
        //         selectSeat: function(segmentId, seatNumber, price, cabin) {
        //             // Lock passenger counts during seat operation to prevent price recalculation issues
        //             if (typeof lockPassengerCounts === 'function') {
        //                 lockPassengerCounts();
        //             }

        //             // Use currently selected passenger
        //             const travelerId = this.currentPassengerId;

        //             if (!this.selectedSeats[segmentId]) {
        //                 this.selectedSeats[segmentId] = {};
        //             }

        //             // Check if this traveler already has a seat on this segment
        //             if (this.selectedSeats[segmentId][travelerId]) {
        //                 // Deselect previous seat
        //                 this.deselectSeat(segmentId, this.selectedSeats[segmentId][travelerId].seat_number);
        //             }

        //             // Ensure price is a number
        //             const seatPrice = parseFloat(price) || 0;

        //             // Add new seat
        //             this.selectedSeats[segmentId][travelerId] = {
        //                 seat_number: seatNumber,
        //                 price: seatPrice,
        //                 cabin: cabin
        //             };

        // // Update UI
        //             this.updateSeatUI(segmentId, seatNumber, true);

        //             // Force update selected seats summary and price breakdown
        //             this.updateSelectedSeatsSummary();

        //             // Also call updatePrice to ensure price breakdown is updated
        //             // Note: Passenger counts are locked, so price calculation will use correct counts
        //             this.updatePrice();

        //             // Force immediate price breakdown update as backup
        //             setTimeout(() => {
        //                 if (this.flightOffer && typeof populatePriceBreakdown === 'function') {
        // // Ensure values are set
        //                     window.AmadexSeatSelection.totalSeatCharges = this.totalSeatCharges;
        //                     if (this.flightOffer.price) {
        //                         this.flightOffer.price.seat_charges = this.totalSeatCharges;
        //                     }
        //                     // Sync current passenger counts before updating price breakdown
        //                     const currentSearchData = syncCurrentPassengerCountsToSearchData();
        //                     populatePriceBreakdown(this.flightOffer, currentSearchData, true);
        //                 }
        //             }, 200);
        //                 const $paxItem = $(`.amadex-pax-panel-item[data-passenger-id="${this.currentPassengerId}"]`);
        //     if ($paxItem.length) {
        //         $paxItem.find('.amadex-pax-panel-seat').html(
        //             `<span class="amadex-pax-seat-selected">${seatNumber}</span>`
        //         );
        //     }
        //             // Update highlights for current passenger
        //             this.highlightSeatsForCurrentPassenger();
        //         },


        selectSeat: function (segmentId, seatNumber, price, cabin) {
            // Lock passenger counts during seat operation
            if (typeof lockPassengerCounts === 'function') {
                lockPassengerCounts();
            }

            // Use currently selected passenger
            const travelerId = this.currentPassengerId;

            if (!this.selectedSeats[segmentId]) {
                this.selectedSeats[segmentId] = {};
            }

            // Check if this traveler already has a seat on this segment
            if (this.selectedSeats[segmentId][travelerId]) {
                // Deselect previous seat
                this.deselectSeat(segmentId, this.selectedSeats[segmentId][travelerId].seat_number);
            }

            // Ensure price is a number
            const seatPrice = parseFloat(price) || 0;

            // Add new seat
            this.selectedSeats[segmentId][travelerId] = {
                seat_number: seatNumber,
                price: seatPrice,
                cabin: cabin
            };

            // Update UI
            this.updateSeatUI(segmentId, seatNumber, true);

            // Update the passenger panel seat display
            const $paxItem = $(`.amadex-pax-panel-item[data-passenger-id="${travelerId}"]`);
            if ($paxItem.length) {
                $paxItem.find('.amadex-pax-panel-seat').html(
                    `<span class="amadex-pax-seat-selected">${seatNumber}</span>`
                );
            }

            // CRITICAL: Force update seat charges and display
            this.totalSeatCharges = 0;
            Object.keys(this.selectedSeats).forEach((segId) => {
                Object.keys(this.selectedSeats[segId] || {}).forEach((tId) => {
                    const s = this.selectedSeats[segId][tId];
                    if (s && s.price) {
                        this.totalSeatCharges += parseFloat(s.price) || 0;
                    }
                });
            });

            // Update global reference
            window.AmadexSeatSelection.totalSeatCharges = this.totalSeatCharges;

            // Update selected seats summary
            this.updateSelectedSeatsSummary();

            // Force immediate price breakdown update
            if (this.flightOffer && typeof populatePriceBreakdown === 'function') {
                if (!this.flightOffer.price) {
                    this.flightOffer.price = {};
                }
                this.flightOffer.price.seat_charges = this.totalSeatCharges;
                const currentSearchData = syncCurrentPassengerCountsToSearchData();
                populatePriceBreakdown(this.flightOffer, currentSearchData, true);
            }

            // Also call the dedicated seat display updater
            if (typeof updateSeatSelectionDisplay === 'function') {
                updateSeatSelectionDisplay();
            }

            // Update highlights for current passenger
            this.highlightSeatsForCurrentPassenger();

            // Unlock passenger counts after a short delay
            if (typeof unlockPassengerCounts === 'function') {
                setTimeout(() => { unlockPassengerCounts(); }, 500);
            }
        },


        deselectSeat: function (segmentId, seatNumber) {
            // Lock passenger counts during seat operation
            if (typeof lockPassengerCounts === 'function') {
                lockPassengerCounts();
            }

            if (!this.selectedSeats[segmentId]) {
                return;
            }

            let removedTravelerId = null;
            let removedSeatPrice = 0;

            // Find and remove the seat
            Object.keys(this.selectedSeats[segmentId]).forEach((travelerId) => {
                if (this.selectedSeats[segmentId][travelerId].seat_number === seatNumber) {
                    removedTravelerId = travelerId;
                    removedSeatPrice = this.selectedSeats[segmentId][travelerId].price || 0;
                    delete this.selectedSeats[segmentId][travelerId];
                }
            });

            // Remove empty segment
            if (Object.keys(this.selectedSeats[segmentId]).length === 0) {
                delete this.selectedSeats[segmentId];
            }

            // Update UI
            this.updateSeatUI(segmentId, seatNumber, false);

            // Update the passenger panel seat display
            if (removedTravelerId) {
                const $paxItem = $(`.amadex-pax-panel-item[data-passenger-id="${removedTravelerId}"]`);
                if ($paxItem.length) {
                    $paxItem.find('.amadex-pax-panel-seat').html(
                        '<span class="amadex-pax-seat-empty">Select Seat</span>'
                    );
                }
            }

            // Recalculate total seat charges
            this.totalSeatCharges = 0;
            Object.keys(this.selectedSeats).forEach((segId) => {
                Object.keys(this.selectedSeats[segId] || {}).forEach((tId) => {
                    const s = this.selectedSeats[segId][tId];
                    if (s && s.price) {
                        this.totalSeatCharges += parseFloat(s.price) || 0;
                    }
                });
            });

            // Update global reference
            window.AmadexSeatSelection.totalSeatCharges = this.totalSeatCharges;

            // Update selected seats summary
            this.updateSelectedSeatsSummary();

            // Force immediate price breakdown update
            if (this.flightOffer && typeof populatePriceBreakdown === 'function') {
                if (!this.flightOffer.price) {
                    this.flightOffer.price = {};
                }
                this.flightOffer.price.seat_charges = this.totalSeatCharges;
                const currentSearchData = syncCurrentPassengerCountsToSearchData();
                populatePriceBreakdown(this.flightOffer, currentSearchData, true);
            }

            // Call dedicated seat display updater
            if (typeof updateSeatSelectionDisplay === 'function') {
                updateSeatSelectionDisplay();
            }

            // Unlock passenger counts
            if (typeof unlockPassengerCounts === 'function') {
                setTimeout(() => { unlockPassengerCounts(); }, 500);
            }
        },

        /**
         * Deselect a seat
         */
        // deselectSeat: function(segmentId, seatNumber) {
        //     // Lock passenger counts during seat operation to prevent price recalculation issues
        //     if (typeof lockPassengerCounts === 'function') {
        //         lockPassengerCounts();
        //     }

        //     if (!this.selectedSeats[segmentId]) {
        //         return;
        //     }

        //     // Find and remove the seat
        //     Object.keys(this.selectedSeats[segmentId]).forEach((travelerId) => {
        //         if (this.selectedSeats[segmentId][travelerId].seat_number === seatNumber) {
        //             delete this.selectedSeats[segmentId][travelerId];
        //         }
        //     });

        //     // Remove empty segment
        //     if (Object.keys(this.selectedSeats[segmentId]).length === 0) {
        //         delete this.selectedSeats[segmentId];
        //     }

        //     // Update UI
        //     this.updateSeatUI(segmentId, seatNumber, false);
        //     this.updateSelectedSeatsSummary();
        //     // Note: Passenger counts are locked, so price calculation will use correct counts
        //     this.updatePrice();
        // },

        /**
         * Update seat UI state
         */
        updateSeatUI: function (segmentId, seatNumber, isSelected) {
            const $seat = $(`.amadex-seat[data-seat-number="${seatNumber}"][data-segment-id="${segmentId}"]`);

            if (isSelected) {
                $seat.removeClass('available').addClass('selected');
            } else {
                $seat.removeClass('selected').addClass('available');
            }
        },

        /**
         * Show seat selection badge (passenger choice + seat info + price) near the clicked seat
         */
        showSeatSelectionBadge: function ($seat, segmentId, seatNumber, price, cabin) {
            const $badge = $('#amadex-seat-selection-badge');
            if (!$badge.length) return;

            const currency = typeof getSelectedCurrency === 'function' ? getSelectedCurrency() : 'USD';
            const priceDisplay = typeof formatCurrencyValue === 'function' ? formatCurrencyValue(price, currency) : (price === 0 ? 'Free' : '$' + price);

            // Passenger who already has this seat (if seat is selected) or current passenger for pre-check
            let checkedTravelerId = null;
            if (this.selectedSeats[segmentId]) {
                Object.keys(this.selectedSeats[segmentId]).forEach(function (tid) {
                    if (this.selectedSeats[segmentId][tid].seat_number === seatNumber) {
                        checkedTravelerId = tid;
                    }
                }.bind(this));
            }
            // For single passenger on available seat: leave radio unchecked so they confirm; then summary shows on check
            if (checkedTravelerId == null && this.passengers && this.passengers.length > 1) {
                checkedTravelerId = this.currentPassengerId;
            }
            // Passenger list (radio per passenger); pre-check only if someone has this seat or multiple passengers
            let passengersHtml = '';
            const passengers = this.passengers || [];
            passengers.forEach((p, idx) => {
                const id = p.id || (idx + 1);
                const name = this.getPassengerName(id) || this.getPassengerLabel(id) || ('Passenger ' + id);
                const radioId = 'amadex-seat-badge-pax-' + id + '-' + (segmentId || '') + '-' + (seatNumber || '').replace(/\s/g, '');
                const isChecked = (checkedTravelerId != null && (String(id) === String(checkedTravelerId)));
                const checkedAttr = isChecked ? ' checked="checked"' : '';
                const rowClass = 'amadex-seat-badge-passenger-option' + (isChecked ? ' has-seat' : '');
                const tickHtml = isChecked ? '<span class="amadex-seat-badge-tick" aria-hidden="true"></span>' : '';
                passengersHtml += `<label class="${rowClass}">
                    <input type="radio" name="amadex-seat-badge-passenger" class="amadex-seat-badge-passenger-radio" value="${id}" id="${radioId}"${checkedAttr}>
                    <span class="amadex-seat-badge-passenger-name">${name}</span>
                    ${tickHtml}
                </label>`;
            });

            $badge.find('.amadex-seat-badge-passengers').html(passengersHtml || '<span class="amadex-seat-badge-no-passengers">No passengers</span>');

            // Seat features (Window, Aisle, Cabin)
            const features = [];
            if ($seat.data('window') === 1 || $seat.data('window') === '1') features.push('Window');
            if ($seat.data('aisle') === 1 || $seat.data('aisle') === '1') features.push('Aisle');
            const cabinLabel = ($seat.data('cabin-label') || cabin || 'ECONOMY').replace(/_/g, ' ');
            if (cabinLabel) features.push(cabinLabel);
            const featuresHtml = features.length ? '<ul>' + features.map(f => '<li>' + f + '</li>').join('') + '</ul>' : '';
            $badge.find('.amadex-seat-badge-features').html(featuresHtml);

            $badge.find('.amadex-seat-badge-seat-number').text(seatNumber || '');
            $badge.find('.amadex-seat-badge-price').text(priceDisplay);

            $badge.data('segment-id', segmentId).data('seat-number', seatNumber).data('price', price).data('cabin', cabin);
            $badge.removeAttr('aria-hidden');
            $badge.css('display', 'block');
            $badge.removeClass('is-visible'); // apply after position to avoid flash
            // Green style when badge is for an already-selected seat
            if (this.isSeatSelected(seatNumber, segmentId)) {
                $badge.addClass('amadex-seat-selection-badge--selected');
            } else {
                $badge.removeClass('amadex-seat-selection-badge--selected');
            }

            const gap = 8;
            const badgeWidth = 260;
            const badgeHeight = 220;
            const seatHeight = $seat.outerHeight() || 24;

            // Place badge inside the seat map content so it sits near the seat and scrolls with it
            const $content = $seat.closest('.amadex-seat-map-content');
            if ($content.length) {
                $badge.appendTo($content);
                $badge.addClass('amadex-seat-selection-badge-inline');
                const contentOffset = $content.offset();
                const seatOffset = $seat.offset();
                const relativeTop = seatOffset.top - contentOffset.top;
                const relativeLeft = seatOffset.left - contentOffset.left;
                const seatWidth = $seat.outerWidth() || 36;
                const contentHeight = $content.outerHeight() || 0;
                const isMobile = typeof window !== 'undefined' && window.innerWidth <= 768;
                // On mobile: prefer below seat so the selected seat stays visible above the popup (avoids popup covering the seat)
                // Otherwise: prefer above; if not enough space above, show below
                const spaceAbove = relativeTop;
                const spaceBelow = contentHeight - (relativeTop + seatHeight);
                let placeAbove;
                if (isMobile) {
                    placeAbove = spaceBelow < badgeHeight + gap; // below only if there's room; else above
                } else {
                    placeAbove = true; // Always show badge above the seat on desktop
                }
                let badgeTop;
                if (placeAbove) {
                    badgeTop = relativeTop - badgeHeight - gap;
                } else {
                    badgeTop = relativeTop + seatHeight + gap;
                }
                let badgeLeft = relativeLeft + (seatWidth / 2) - (badgeWidth / 2);
                badgeLeft = Math.max(8, Math.min(badgeLeft, $content.outerWidth() - badgeWidth - 8));
                $badge.css({ position: 'absolute', left: badgeLeft + 'px', top: badgeTop + 'px', width: badgeWidth + 'px', right: 'auto', bottom: 'auto' });
                $badge.removeClass('amadex-seat-badge-below').addClass(placeAbove ? '' : 'amadex-seat-badge-below');
            } else {
                // Fallback: fixed positioning relative to viewport
                $badge.removeClass('amadex-seat-selection-badge-inline');
                const seatRect = $seat[0].getBoundingClientRect();
                const padding = 8;
                const spaceAbove = seatRect.top;
                const spaceBelow = window.innerHeight - (seatRect.bottom);
                const placeAbove = true; // Always show badge above the seat
                let top;
                if (placeAbove) {
                    top = seatRect.top - badgeHeight - gap;
                } else {
                    top = seatRect.bottom + gap;
                }
                top = Math.max(padding, Math.min(top, window.innerHeight - badgeHeight - padding));
                const left = Math.max(padding, Math.min(seatRect.left + (seatRect.width / 2) - (badgeWidth / 2), window.innerWidth - badgeWidth - padding));
                $badge.css({ position: 'fixed', left: left + 'px', top: top + 'px', width: badgeWidth + 'px' });
            }

            requestAnimationFrame(function () { $badge.addClass('is-visible'); });
        },

        /**
         * Hide seat selection badge
         */
        hideSeatSelectionBadge: function () {
            const $badge = $('#amadex-seat-selection-badge');
            if ($badge.length && document.activeElement && $badge[0].contains(document.activeElement)) {
                var $focusTarget = $('#amadex-skip-seat-selection, #amadex-seat-maps-container button, .amadex-seat.available').filter(':visible').first();
                if ($focusTarget.length) $focusTarget[0].focus();
            }
            $badge.removeClass('is-visible');
            $badge.removeClass('amadex-seat-selection-badge-inline');
            $badge.removeClass('amadex-seat-selection-badge--selected');
            $badge.attr('aria-hidden', 'true');
            setTimeout(function () {
                $badge.css('display', 'none');
                var $section = $('#amadex-seat-selection-section');
                if ($section.length && $badge.parent()[0] !== $section[0]) {
                    $badge.appendTo($section);
                }
            }, 220);
        },

        /**
         * Get segment route information
         */
        getSegmentRoute: function (segmentId) {
            if (!this.flightOffer || !this.flightOffer.itineraries) {
                return null;
            }

            // Search through all itineraries and segments
            for (let itineraryIndex = 0; itineraryIndex < this.flightOffer.itineraries.length; itineraryIndex++) {
                const itinerary = this.flightOffer.itineraries[itineraryIndex];
                if (itinerary.segments) {
                    for (let segIndex = 0; segIndex < itinerary.segments.length; segIndex++) {
                        const segment = itinerary.segments[segIndex];
                        const segId = segment.id || segment.segmentId || segment.segment_id;

                        if (segId === segmentId || String(segId) === String(segmentId)) {
                            const dep = segment.departure?.iata_code || segment.departure?.iataCode || '';
                            const arr = segment.arrival?.iata_code || segment.arrival?.iataCode || '';
                            const carrier = segment.carrierCode || segment.carrier_code || '';
                            const flightNum = segment.number || '';
                            const flightCode = carrier && flightNum ? `${carrier} ${flightNum}` : '';

                            // Get departure date/time
                            let depDate = '';
                            if (segment.departure?.at) {
                                try {
                                    const depDateObj = new Date(segment.departure.at);
                                    depDate = depDateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                } catch (e) { }
                            }

                            return {
                                route: dep && arr ? `${dep} → ${arr}` : `Segment ${segIndex + 1}`,
                                flight: flightCode,
                                date: depDate,
                                full: dep && arr ? `${dep} → ${arr}${flightCode ? ' • ' + flightCode : ''}${depDate ? ' • ' + depDate : ''}` : `Segment ${segIndex + 1}`
                            };
                        }
                    }
                }
            }

            return { route: `Route ${segmentId}`, flight: '', date: '', full: `Route ${segmentId}` };
        },

        /**
         * Update selected seats summary
         * Now shows seats grouped by passenger with route information
         */
        updateSelectedSeatsSummary: function () {
            const summary = $('#amadex-selected-seats-summary');
            const list = $('#amadex-selected-seats-list');
            const totalPrice = $('#amadex-seat-total-price');
            if (!summary.length || !list.length) return;

            list.empty();
            this.totalSeatCharges = 0;

            const selectedSeats = this.selectedSeats || {};
            // Group seats by passenger
            const seatsByPassenger = {};

            Object.keys(selectedSeats).forEach((segmentId) => {
                const routeInfo = this.getSegmentRoute(segmentId);
                Object.keys(selectedSeats[segmentId] || {}).forEach((travelerId) => {
                    const seat = selectedSeats[segmentId][travelerId];
                    const seatPrice = parseFloat(seat.price) || 0;

                    if (!seatsByPassenger[travelerId]) {
                        // Find passenger by ID (handle both string and number comparison)
                        const passenger = this.passengers.find(p =>
                            String(p.id) === String(travelerId) || p.id === travelerId
                        ) || {
                            id: travelerId,
                            typeLabel: `Passenger ${travelerId}`,
                            type: 'ADULT'
                        };

                        seatsByPassenger[travelerId] = {
                            passenger: passenger,
                            seats: [],
                            subtotal: 0
                        };
                    }

                    seatsByPassenger[travelerId].seats.push({
                        segmentId: segmentId,
                        route: routeInfo,
                        seatNumber: seat.seat_number,
                        price: seatPrice
                    });

                    seatsByPassenger[travelerId].subtotal += seatPrice;
                    this.totalSeatCharges += seatPrice;
                });
            });

            // Ensure totalSeatCharges is a number
            this.totalSeatCharges = parseFloat(this.totalSeatCharges) || 0;

            // Generate HTML grouped by passenger
            if (Object.keys(seatsByPassenger).length > 0) {
                Object.keys(seatsByPassenger).sort((a, b) => parseInt(a) - parseInt(b)).forEach((travelerId) => {
                    const passengerData = seatsByPassenger[travelerId];
                    const passenger = passengerData.passenger;

                    // Get passenger name
                    const passengerName = this.getPassengerName(travelerId);

                    // Use passenger name if available, otherwise use type label, fallback to passenger ID
                    let displayName = passengerName;
                    if (!displayName || !displayName.trim()) {
                        displayName = passenger.typeLabel ||
                            (passenger.type === 'ADULT' ? `Adult ${passenger.typeIndex || ''}` :
                                passenger.type === 'CHILD' ? `Child ${passenger.typeIndex || ''}` :
                                    `Infant ${passenger.typeIndex || ''}`);
                    }
                    if (!displayName || !displayName.trim()) {
                        displayName = `Passenger ${travelerId}`;
                    }

                    // Get passenger type label
                    const typeLabel = passenger.typeLabel ||
                        (passenger.type === 'ADULT' ? 'Adult' :
                            passenger.type === 'CHILD' ? 'Child' : 'Infant');

                    // Create passenger block: one li per passenger, ul of route rows inside
                    const passengerHeader = '<li class="amadex-seat-summary-passenger"><ul class="amadex-seat-summary-routes">';
                    let routesHtml = '';
                    passengerData.seats.forEach((seatInfo) => {
                        routesHtml += `
                            <li class="amadex-seat-summary-route">
                                <strong class="amadex-passenger-name">${displayName}</strong>
                                <span class="amadex-seat-info">Seat ${seatInfo.seatNumber}</span>
                                <span class="amadex-seat-price">${formatCurrencyValue(seatInfo.price, this.currency)}</span>
                            </li>
                        `;
                    });
                    list.append(passengerHeader + routesHtml + '</ul></li>');
                });
            }

            // Check if any seats were selected (works for 1 or many passengers)
            const totalAssignedSeats = Object.keys(selectedSeats).reduce(function (sum, segId) {
                return sum + (selectedSeats[segId] ? Object.keys(selectedSeats[segId]).length : 0);
            }, 0);
            const hasSeatsSelected = totalAssignedSeats > 0;

            // Update display in #amadex-seat-total-price element and show summary
            if (hasSeatsSelected) {
                const displayTotal = this.totalSeatCharges || 0;
                if (totalPrice.length) {
                    totalPrice.html(`<strong>Total Seat Charges: ${formatCurrencyValue(displayTotal, this.currency)}</strong>`);
                    totalPrice.attr('data-seat-charges', displayTotal.toFixed(2));
                }
                summary.css('display', 'block').show();
            } else {
                totalPrice.text('');
                totalPrice.removeAttr('data-seat-charges');
                summary.hide();
            }

            // CRITICAL: Set window.AmadexSeatSelection.totalSeatCharges BEFORE updating price breakdown
            // This ensures populatePriceBreakdown can read the correct value
            window.AmadexSeatSelection.totalSeatCharges = this.totalSeatCharges;

            // Immediately update price breakdown in sidebar to show seat charges
            // This ensures seat price appears in price summary breakdown and total amount
            if (this.flightOffer && this.flightOffer.price) {
                // Store seat charges in flight offer
                this.flightOffer.price.seat_charges = this.totalSeatCharges;

                // Force update price breakdown - ensure all values are set
                // Use requestAnimationFrame for better timing
                const self = this;
                requestAnimationFrame(() => {
                    // Double-check values are set
                    window.AmadexSeatSelection.totalSeatCharges = self.totalSeatCharges;
                    if (self.flightOffer && self.flightOffer.price) {
                        self.flightOffer.price.seat_charges = self.totalSeatCharges;
                    }

                    if (typeof populatePriceBreakdown === 'function' && !window.amadexRecheckInProgress) {
                        const currentSearchData = syncCurrentPassengerCountsToSearchData();
                        populatePriceBreakdown(self.flightOffer, currentSearchData, true);
                    } else {
                    }
                });
            } else {
            }
        },

        /**
         * Update flight price with seat charges
         */
        updatePrice: function () {
            // First, immediately update price breakdown with current seat charges (before AJAX call)
            // This ensures user sees the price update instantly
            if (this.flightOffer && this.flightOffer.price) {
                // Ensure totalSeatCharges is calculated (in case updateSelectedSeatsSummary wasn't called)
                if (this.totalSeatCharges === undefined || this.totalSeatCharges === null) {
                    this.totalSeatCharges = 0;
                    Object.keys(this.selectedSeats).forEach((segmentId) => {
                        Object.keys(this.selectedSeats[segmentId]).forEach((travelerId) => {
                            const seat = this.selectedSeats[segmentId][travelerId];
                            this.totalSeatCharges += parseFloat(seat.price) || 0;
                        });
                    });
                }

                // CRITICAL: Set window.AmadexSeatSelection.totalSeatCharges FIRST
                // This must be set before calling populatePriceBreakdown
                window.AmadexSeatSelection.totalSeatCharges = this.totalSeatCharges;

                // Store seat charges in flight offer for immediate display
                this.flightOffer.price.seat_charges = this.totalSeatCharges;

                // Update price breakdown immediately to show seat charges
                // Use requestAnimationFrame for better timing
                const self = this;
                requestAnimationFrame(() => {
                    // Double-check values are set
                    window.AmadexSeatSelection.totalSeatCharges = self.totalSeatCharges;
                    if (self.flightOffer && self.flightOffer.price) {
                        self.flightOffer.price.seat_charges = self.totalSeatCharges;
                    }

                    if (typeof populatePriceBreakdown === 'function') {
                        // Sync current passenger counts before updating price breakdown
                        const currentSearchData = syncCurrentPassengerCountsToSearchData();
                        populatePriceBreakdown(self.flightOffer, currentSearchData, true);
                    } else {
                    }
                });
            }

            if (Object.keys(this.selectedSeats).length === 0) {
                // No seats selected, remove seat charges and update the summary
                this.totalSeatCharges = 0;
                window.AmadexSeatSelection.totalSeatCharges = 0;

                if (this.flightOffer && this.flightOffer.price) {
                    this.flightOffer.price.seat_charges = 0;
                }

                // Update price breakdown to remove seat charges
                setTimeout(() => {
                    if (typeof populatePriceBreakdown === 'function' && this.flightOffer) {
                        // Sync current passenger counts before updating price breakdown
                        if (!window.amadexRecheckInProgress) {
                            const currentSearchData = syncCurrentPassengerCountsToSearchData();
                            populatePriceBreakdown(this.flightOffer, currentSearchData, true);
                        }
                    }
                }, 100);
                return;
            }

            // Format selected seats for API
            const formattedSeats = {};
            Object.keys(this.selectedSeats).forEach((segmentId) => {
                formattedSeats[segmentId] = {};
                Object.keys(this.selectedSeats[segmentId]).forEach((travelerId) => {
                    formattedSeats[segmentId][travelerId] = this.selectedSeats[segmentId][travelerId].seat_number;
                });
            });

            // Get original flight offer (need the raw Amadeus format)
            const originalOffer = this.flightOffer.rawOffer || this.flightOffer;

            $.ajax({
                url: AmadexConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_price_selected_seats',
                    nonce: AmadexConfig.nonce,
                    flight_offer: JSON.stringify(originalOffer),
                    selected_seats: JSON.stringify(formattedSeats)
                },
                success: (response) => {
                    if (response.success && response.data && response.data.flightOffer) {
                        const updatedOffer = response.data.flightOffer;
                        const newTotal = parseFloat(updatedOffer.price?.total || 0);

                        // Update flight offer price
                        if (this.flightOffer.price) {
                            const originalTotal = parseFloat(this.flightOffer.price.original_total || this.flightOffer.price.total || 0);
                            this.flightOffer.price.charge_total_with_seats = newTotal;
                            this.flightOffer.price.seat_charges = this.totalSeatCharges;

                            // Update session storage
                            sessionStorage.setItem('amadex_booking_flight', JSON.stringify(this.flightOffer));
                        }

                        // Update price breakdown in sidebar with animation
                        // This ensures seat charges are shown in price summary and total amount
                        if (typeof populatePriceBreakdown === 'function') {
                            // Sync current passenger counts before updating price breakdown
                            const currentSearchData = syncCurrentPassengerCountsToSearchData();
                            populatePriceBreakdown(this.flightOffer, currentSearchData, true);
                        }

                        // Log for debugging
                        // Unlock passenger counts after successful price update
                        if (typeof unlockPassengerCounts === 'function') {
                            setTimeout(() => {
                                unlockPassengerCounts();
                            }, 500); // Small delay to ensure price breakdown is updated
                        }
                    }
                },
                error: (xhr, status, error) => {
                    // Unlock passenger counts even on error
                    if (typeof unlockPassengerCounts === 'function') {
                        unlockPassengerCounts();
                    }
                }
            });
        },

        /**
         * Include selected seats in booking data
         */
        includeInBooking: function (bookingData) {
            if (Object.keys(this.selectedSeats).length === 0) {
                return bookingData;
            }

            // Format seat selection data
            const seatSelectionData = {
                segments: {},
                total_seat_charges: this.totalSeatCharges,
                currency: this.currency
            };

            Object.keys(this.selectedSeats).forEach((segmentId) => {
                const segmentData = this.getSegmentData(segmentId);
                seatSelectionData.segments[segmentId] = {
                    segment_id: segmentId,
                    departure: segmentData?.departure || {},
                    arrival: segmentData?.arrival || {},
                    seats: []
                };

                Object.keys(this.selectedSeats[segmentId]).forEach((travelerId) => {
                    const seat = this.selectedSeats[segmentId][travelerId];

                    // Ensure traveler_id is a string for consistency with PHP
                    const travelerIdStr = String(travelerId);

                    // Determine traveler type from passenger data if available
                    let travelerType = 'ADULT';
                    if (this.passengers && Array.isArray(this.passengers)) {
                        const passenger = this.passengers.find(p => String(p.id) === travelerIdStr);
                        if (passenger && passenger.type) {
                            travelerType = passenger.type;
                        }
                    }

                    seatSelectionData.segments[segmentId].seats.push({
                        traveler_id: travelerIdStr,
                        traveler_type: travelerType,
                        seat_number: seat.seat_number,
                        cabin: seat.cabin,
                        price: {
                            currency: this.currency,
                            total: seat.price.toFixed(2)
                        }
                    });
                });
            });

            bookingData.seat_selection = seatSelectionData;

            // Update final total in flight data
            // NOTE: Use pricing_charge_total (P_charge) if available from pricing rules, otherwise use total (P_display)
            // This ensures seat charges are added to the correct base amount
            if (bookingData.flight && bookingData.flight.price) {
                // Priority: pricing_charge_total (P_charge) > total (P_display) > 0
                // pricing_charge_total is what will be sent to NMI, so seat charges should be added to that
                const baseCharge = parseFloat(
                    bookingData.flight.price.pricing_charge_total ||
                    bookingData.flight.price.charge_total ||
                    bookingData.flight.price.total ||
                    0
                );
                bookingData.flight.price.final_total = (baseCharge + this.totalSeatCharges).toFixed(2);
                bookingData.flight.price.seat_charges = this.totalSeatCharges;
            }

            return bookingData;
        }
    };

    /**
     * Initialize clickable progress indicator navigation
     * Desktop: Scrolls to section
     * Mobile: Navigates to step (step-by-step flow)
     */
    function initProgressNavigation() {
        $('.booking-step').off('click.progressNav').on('click.progressNav', function () {
            const step = $(this).data('step');
            if (!step) return;

            // Block navigation to steps beyond passengers if passenger details not filled
            if (BOOKING_STEPS[step] && !canAccessStep(step)) {
                // Show inline message on the stepper item
                var $hint = $('#amadex-step-locked-hint');
                if (!$hint.length) {
                    $hint = $('<div id="amadex-step-locked-hint" style="' +
                        'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);' +
                        'background:#1a1a2e;color:#fff;padding:10px 20px;border-radius:8px;' +
                        'font-size:13px;z-index:99999;white-space:nowrap;' +
                        'box-shadow:0 4px 12px rgba(0,0,0,0.3);pointer-events:none;' +
                        'transition:opacity 0.3s ease;">Please fill in passenger details first</div>');
                    $('body').append($hint);
                }
                $hint.stop(true).css('opacity', 1);
                clearTimeout(window._amadexStepLockHintTimer);
                window._amadexStepLockHintTimer = setTimeout(function () {
                    $hint.animate({ opacity: 0 }, 400);
                }, 2500);
                // Navigate to passengers step so they can fill it
                navigateToStep('passengers');
                return;
            }

            // Both mobile and desktop use same step-by-step pagination
            if (BOOKING_STEPS[step]) {
                navigateToStep(step);
            }
        });
    }

    /**
     * Initialize pagination button handlers (works for both mobile and desktop)
     * Handles Next/Back buttons in each section
     */
    function initDesktopPaginationButtons() {
        const isMobile = window.innerWidth <= 767;

        // Only attach handlers on desktop
        if (isMobile) {
            return;
        }

        // Handle Next button clicks - use event delegation for dynamically added buttons
        $(document).off('click.desktopPagination', '.amadex-pagination-next').on('click.desktopPagination', '.amadex-pagination-next', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const nextStepNum = $(this).data('next-step');
            if (nextStepNum) {
                // Map step number to step name
                const stepMap = {
                    1: 'flights',
                    2: 'passengers',
                    3: 'seats',
                    4: 'addons',
                    5: 'review'
                };
                const stepName = stepMap[nextStepNum];
                if (stepName && BOOKING_STEPS[stepName]) {
                    navigateToStep(stepName);
                } else {
                }
            } else {
            }
        });

        // Handle Back button clicks
        $(document).off('click.desktopPagination', '.amadex-pagination-back').on('click.desktopPagination', '.amadex-pagination-back', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const prevStepNum = $(this).data('prev-step');
            if (prevStepNum) {
                // Map step number to step name
                const stepMap = {
                    1: 'flights',
                    2: 'passengers',
                    3: 'seats',
                    4: 'addons',
                    5: 'review'
                };
                const stepName = stepMap[prevStepNum];
                if (stepName && BOOKING_STEPS[stepName]) {
                    navigateToStep(stepName);
                } else if (prevStepNum === 1) {
                    // Going back from step 2 to step 1 means going to flights
                    navigateToStep('flights');
                } else {
                }
            } else {
                // Fallback: use goToPreviousStep
                goToPreviousStep();
            }
        });

        // Handle Confirm & Book button in pagination
        $(document).off('click.desktopPagination', '.amadex-pagination-confirm, #amadex-confirm-book-pagination').on('click.desktopPagination', '.amadex-pagination-confirm, #amadex-confirm-book-pagination', function (e) {
            e.preventDefault();
            e.stopPropagation();
            // Trigger the existing confirm booking flow
            const $confirmBtn = $('#amadex-confirm-book');
            if ($confirmBtn.length) {
                $confirmBtn.trigger('click');
            } else {
                // Fallback: navigate to review and trigger booking
                navigateToStep('review');
                setTimeout(function () {
                    goToNextStep();
                }, 300);
            }
        });

        // Re-initialize on window resize to handle mobile/desktop switch
        $(window).on('resize.desktopPagination', function () {
            // Re-attach handlers on resize (works for both mobile and desktop)
            initDesktopPaginationButtons();
        });
    }

    // Initialize on document ready if on booking page
    $(document).ready(function () {
        if (typeof AmadexConfig !== 'undefined') {
        }

        if ($('#amadex-booking-page').length > 0) {
            // Ensure confirm book button is visible on desktop (NMI only – never show for Stripe)
            function ensureConfirmButtonVisible() {
                const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway ? AmadexConfig.defaultCardGateway : 'nmi';
                if (gateway === 'stripe') return;
                const $confirmBtn = $('#amadex-confirm-book');
                if ($confirmBtn.length && !$confirmBtn.hasClass('amadex-confirm-book-stripe-hidden') && window.innerWidth >= 768) {
                    const $reviewSection = $('#amadex-agreement-section');
                    if ($reviewSection.hasClass('step-active')) {
                        $confirmBtn.css({
                            'display': 'block',
                            'position': 'relative',
                            'left': 'auto',
                            'top': 'auto',
                            'opacity': '1',
                            'visibility': 'visible',
                            'pointer-events': 'auto'
                        }).addClass('amadex-confirm-book-visible');
                    }
                }
            }

            // Run on load and when step changes
            ensureConfirmButtonVisible();
            $(document).on('amadexStepChanged', ensureConfirmButtonVisible);

            // Initialize Stripe "Pay securely" button based on checkbox state
            function initStripePaymentButton() {
                const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                    ? AmadexConfig.defaultCardGateway
                    : 'nmi';

                if (gateway !== 'stripe') return;

                const $paymentSubmit = $('#amadex-payment-submit');
                const $checkbox = $('#amadex-updates-consent');
                const $stepNextBtn = $('#amadex-step-next');

                if (!$paymentSubmit.length) return;

                // Function to update button state based on checkbox
                function updateStripePaymentButtonState() {
                    const isChecked = $checkbox.is(':checked');
                    const isMobile = window.innerWidth <= 767;

                    // Update Pay securely button
                    $paymentSubmit.prop('disabled', !isChecked);
                    if (isChecked) {
                        $paymentSubmit.text('Pay securely').css({
                            'opacity': '1',
                            'cursor': 'pointer'
                        });
                    } else {
                        $paymentSubmit.text('Pay securely').css({
                            'opacity': '0.6',
                            'cursor': 'not-allowed'
                        });
                    }

                    // Also update step-next button for mobile (only on review step show "Pay securely")
                    if (isMobile && $stepNextBtn.length) {
                        $stepNextBtn.prop('disabled', !isChecked);
                        if (currentStep === 'review') {
                            $stepNextBtn.text('Pay securely');
                        } else {
                            $stepNextBtn.text('Continue');
                        }
                    }
                }

                // Attach change listener to checkbox
                $checkbox.off('change.stripePayment').on('change.stripePayment', updateStripePaymentButtonState);

                // Initialize button state immediately
                updateStripePaymentButtonState();

                // Also update on window resize (mobile/desktop switch)
                $(window).off('resize.stripePayment').on('resize.stripePayment', updateStripePaymentButtonState);

            }

            // Run Stripe button init on load and step changes
            initStripePaymentButton();
            $(document).on('amadexStepChanged stepChanged', initStripePaymentButton);

            // Also run when agreement section becomes active
            const stripeButtonObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.attributeName === 'class') {
                        const $target = $(mutation.target);
                        if ($target.attr('id') === 'amadex-agreement-section' && $target.hasClass('step-active')) {
                            setTimeout(initStripePaymentButton, 100);
                        }
                    }
                });
            });

            const agreementSection = document.getElementById('amadex-agreement-section');
            if (agreementSection) {
                stripeButtonObserver.observe(agreementSection, { attributes: true });
            }

            // Initialize progress navigation (handles both mobile step nav and desktop scroll)
            initProgressNavigation();

            // Initialize step navigation system
            initStepNavigation();

            // Initialize desktop pagination button handlers
            initDesktopPaginationButtons();

            // Desktop pagination - sections will be positioned by CSS
            // No need to force hide/show - CSS handles it with transitions

            // Re-attach handlers periodically in case buttons are recreated (defensive)
            setInterval(function () {
                const $stepNext = $('#amadex-step-next');
                const $confirmBook = $('#amadex-confirm-book');

                // Check if handlers are still attached (jQuery data check)
                if ($stepNext.length && !$stepNext.data('events') && !$._data($stepNext[0], 'events')) {
                    setupNavigationButtons();
                }
                if ($confirmBook.length && !$confirmBook.data('events') && !$._data($confirmBook[0], 'events')) {
                    setupNavigationButtons();
                }
            }, 2000);

        } else {
        }
    });

    /**
     * ========================================
     * STEP NAVIGATION SYSTEM
     * ========================================
     * Manages one-step-per-page flow on mobile
     * with URL parameter state persistence
     */

    // Step definitions - Desktop pagination includes "flights" as step 0
    const BOOKING_STEPS = {
        'flights': { order: 0, label: 'Check your flights', section: 'flights' },
        'passengers': { order: 1, label: 'Fill passenger details', section: 'passengers' },
        'seats': { order: 2, label: 'Select seats', section: 'seats' },
        'addons': { order: 3, label: 'Add-ons', section: 'addons' },
        'review': { order: 4, label: 'Review & Pay', section: 'review' }
    };

    // Current step state
    let currentStep = null;
    let stepHistory = [];

    /**
     * Initialize step navigation system
     */
    function initStepNavigation() {
        // Parse current step from URL or default to first step
        const urlParams = new URLSearchParams(window.location.search);
        const stepFromUrl = urlParams.get('step');

        // Determine initial step - Always start with 'flights' when coming from results page
        let initialStep = 'flights'; // Start with flights (Step 0) for desktop pagination

        // Check if we're coming fresh from results page (no step in URL)
        const stepFromStorage = sessionStorage.getItem('amadex_booking_step');
        const isComingFromResults = !stepFromUrl;

        if (isComingFromResults) {
            // Fresh visit from results page - always start with flights
            initialStep = 'flights';
            sessionStorage.setItem('amadex_booking_step', 'flights');
            // Update URL to reflect flights step
            urlParams.set('step', 'flights');
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.replaceState({ step: 'flights' }, '', newUrl);
        } else if (stepFromUrl && BOOKING_STEPS[stepFromUrl]) {
            // URL has step parameter - use it (for navigation within booking page)
            initialStep = stepFromUrl;
        } else if (stepFromStorage && BOOKING_STEPS[stepFromStorage]) {
            // Use stored step as fallback
            initialStep = stepFromStorage;
        }

        // Handle mobile vs desktop - both use same step-by-step pagination
        const isMobile = window.innerWidth <= 767;

        // Both mobile and desktop use same step-by-step pagination
        // Ensure we start with 'flights' step when coming from results page
        if (!stepFromUrl && !sessionStorage.getItem('amadex_booking_step')) {
            initialStep = 'flights';
        }
        currentStep = initialStep;
        sessionStorage.setItem('amadex_booking_step', initialStep);
        updateProgressStepper(initialStep);
        // Remove all step-active classes first
        $('.amadex-booking-section[data-section]').removeClass('step-active active');
        updateSectionVisibility(initialStep); // Both mobile and desktop use step-by-step visibility

        // Fire step change for step-elements (hero, badge, teaser, etc.) on initial load
        $(document).trigger('amadexBookingStepChanged', [initialStep]);

        // Make progress stepper clickable (handles both mobile step nav and desktop scroll)
        // Note: Actual click handling is in initProgressNavigation() which is called separately
        makeStepperClickable();

        // Add back/next button handlers (mobile only)
        setupNavigationButtons();

        // Handle window resize to update visibility
        // EXPERT/GOD MODE FIX: Prevent scroll-to-top on mobile resize events
        // Mobile browsers fire resize events frequently (address bar show/hide, keyboard appear/disappear)
        // which would trigger scroll-to-top and create an annoying loop. On mobile, we only update
        // visibility classes without scrolling. Desktop resize events are rare (only on window resize)
        // so scrolling is acceptable there.
        let resizeTimeout;
        $(window).on('resize', function () {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function () {
                const isMobileNow = window.innerWidth <= 767;
                // Re-apply visibility based on current step and screen size
                if (currentStep) {
                    if (isMobileNow) {
                        // Switched to mobile: Apply step visibility WITHOUT scrolling
                        // This prevents the annoying scroll-to-top loop on mobile
                        updateSectionVisibility(currentStep, false);
                    } else {
                        // Switched to desktop: Apply step-by-step pagination WITH scrolling
                        updateSectionVisibility(currentStep, true);
                    }
                    updateNavigationButtons();
                }
            }, 250);
        });

        // Handle browser back/forward
        window.addEventListener('popstate', function (event) {
            const urlParams = new URLSearchParams(window.location.search);
            let step = urlParams.get('step') || 'flights';
            const isMobileNow = window.innerWidth <= 767;

            // On mobile, skip flights step
            if (isMobileNow && step === 'flights') {
                step = 'passengers';
            }

            if (isMobileNow) {
                // Mobile: Navigate to step (hides sections)
                navigateToStep(step, false);
            } else {
                // Desktop: Navigate to step (step-by-step pagination)
                navigateToStep(step, false);
            }
        });

    }

    /**
     * Navigate to a specific step
     * @param {string} stepName - Step name (passengers, seats, addons, review)
     * @param {boolean} addToHistory - Whether to add to browser history
     */
    function navigateToStep(stepName, addToHistory = true) {
        if (!BOOKING_STEPS[stepName]) {
            return false;
        }

        // Validate before leaving passengers step (catches all navigation paths)
        if (currentStep === 'passengers' && BOOKING_STEPS[stepName].order > BOOKING_STEPS['passengers'].order) {
            if (!validateCurrentStep()) {
                return false;
            }
        }

        // Validate step access (can't skip ahead without completing previous steps)
        if (!canAccessStep(stepName)) {
            // Navigate to first incomplete step instead
            const firstIncomplete = getFirstIncompleteStep();
            if (firstIncomplete) {
                stepName = firstIncomplete;
            }
        }

        const step = BOOKING_STEPS[stepName];
        currentStep = stepName;

        // Update sessionStorage
        sessionStorage.setItem('amadex_booking_step', stepName);

        // Update URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('step', stepName);
        const newUrl = window.location.pathname + '?' + urlParams.toString();

        if (addToHistory) {
            window.history.pushState({ step: stepName }, '', newUrl);
            stepHistory.push(stepName);
        } else {
            window.history.replaceState({ step: stepName }, '', newUrl);
        }

        // Show/hide sections based on step
        // EXPERT/GOD MODE: Pass false to prevent double scroll (we handle scroll separately below)
        updateSectionVisibility(stepName, false);

        // Ensure confirm button is visible and enabled when on review step (desktop)
        if (stepName === 'review') {
            const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                ? AmadexConfig.defaultCardGateway
                : 'nmi';
            const isStripe = gateway === 'stripe';
            const isMobile = window.innerWidth <= 767;

            const $confirmBtn = $('#amadex-confirm-book');
            const $stepNextBtn = $('#amadex-step-next');

            // For Stripe: never show Confirm & Book – only Pay securely / step-next
            if (isStripe) {
                if ($confirmBtn.length && !$confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                    $confirmBtn.css({ 'display': 'none', 'visibility': 'hidden' }).removeClass('amadex-confirm-book-visible');
                }
                if (isMobile && $stepNextBtn.length) {
                    $stepNextBtn.prop('disabled', false).text('Confirm & Book');
                }
            } else if (!isMobile && $confirmBtn.length && !$confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                // For NMI, just make visible (will be enabled when card is complete)
                $confirmBtn.css({
                    'display': 'block',
                    'position': 'relative',
                    'left': 'auto',
                    'top': 'auto',
                    'opacity': '1',
                    'visibility': 'visible',
                    'pointer-events': 'auto'
                }).addClass('amadex-confirm-book-visible');
            }
        }

        // Update progress stepper
        updateProgressStepper(stepName);

        // Fire step change for step-elements (hero, badge, teaser, etc.)
        $(document).trigger('amadexBookingStepChanged', [stepName]);

        // Scroll to the top of the booking container on ALL screen sizes.
        // On desktop we scroll window to 0; on mobile we scroll to the booking
        // container so the new step header is visible without over-scrolling.
        const $bookingContainer = $('.amadex-booking-page, .amadex-booking-wrap, #amadex-booking-page').first();
        if ($bookingContainer.length) {
            const targetTop = $bookingContainer.offset().top - 20;
            $('html, body').animate({ scrollTop: Math.max(0, targetTop) }, 350);
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        const isMobile = window.innerWidth <= 767;

        // Update navigation buttons
        updateNavigationButtons();

        // Initialize step-specific functionality
        if (stepName === 'review') {
            // Initialize review page content and flight card toggles
            setTimeout(function () {
                updateReviewContent();
                initFlightCardToggles(); // Ensure flight card dropdowns work

                // Ensure confirm button is visible and ready
                const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                    ? AmadexConfig.defaultCardGateway
                    : 'nmi';

                const isMobile = window.innerWidth <= 767;
                const $confirmBtn = $('#amadex-confirm-book');
                const $stepNextBtn = $('#amadex-step-next');

                if (gateway === 'stripe') {
                    // Stripe: only show Pay securely – never show Confirm & Book
                    if ($confirmBtn.length) {
                        $confirmBtn.css({ 'display': 'none', 'visibility': 'hidden' }).addClass('amadex-confirm-book-stripe-hidden').removeClass('amadex-confirm-book-visible');
                    }
                    const $paymentSubmit = $('#amadex-payment-submit');
                    if (!isMobile && $paymentSubmit.length) {
                        $paymentSubmit.prop('disabled', false).css({
                            'display': 'block',
                            'visibility': 'visible',
                            'opacity': '1',
                            'pointer-events': 'auto'
                        });
                    }

                    if (isMobile && $stepNextBtn.length) {
                        // Mobile: Ensure step-next button says "Pay securely"
                        $stepNextBtn.text('Pay securely').prop('disabled', false);
                    }

                    // Also enable the primary submit button
                    const $submitBtn = getSubmitButton();
                    if ($submitBtn.length) {
                        $submitBtn.prop('disabled', false);
                    }

                    // Note: For Stripe, we don't need to initialize Stripe Elements on booking page
                    // Payment will be handled on the separate payment page
                } else {
                    // For NMI and other gateways, also ensure mobile button is ready
                    if (isMobile && $stepNextBtn.length) {
                        $stepNextBtn.text('Confirm & Book').prop('disabled', false);
                    }
                }
            }, 100);
        }
        // ── GA4 begin_checkout: fires once when user moves to seat selection ──
        // reflect any additional passengers added before seat selection.
        if (stepName === 'seats') {
            try {
                var flightRaw = sessionStorage.getItem('amadex_booking_flight');
                if (flightRaw) {
                    var fl = JSON.parse(flightRaw);
                    var sd = window.amadexSearchData || {};

                    var offerId = (fl.rawOffer && fl.rawOffer.id) || fl.id || fl.offerId || '';
                    var airlineCode = (fl.validatingAirlineCodes && fl.validatingAirlineCodes[0]) ||
                        (fl.validating_airline_codes && fl.validating_airline_codes[0]) || '';
                    var airlineName = (typeof getAirlineName === 'function') ? getAirlineName(airlineCode) : airlineCode;

                    var origin = '', destination = '', depDate = '', retDate = '', stopsCount = 0;
                    if (fl.itineraries && fl.itineraries[0] && fl.itineraries[0].segments) {
                        var segs = fl.itineraries[0].segments;
                        var firstSeg = segs[0];
                        var lastSeg = segs[segs.length - 1];
                        origin = (firstSeg.departure && (firstSeg.departure.iataCode || firstSeg.departure.iata_code || firstSeg.departure.code)) || '';
                        destination = (lastSeg.arrival && (lastSeg.arrival.iataCode || lastSeg.arrival.iata_code || lastSeg.arrival.code)) || '';
                        stopsCount = segs.length - 1;
                        if (firstSeg.departure && firstSeg.departure.at) depDate = firstSeg.departure.at.split('T')[0];
                    }
                    if (!origin) origin = sd.origin || sd.from || '';
                    if (!destination) destination = sd.destination || sd.to || '';
                    if (!depDate) depDate = sd.departure_date || sd.departureDate || '';

                    if (fl.itineraries && fl.itineraries[1] && fl.itineraries[1].segments) {
                        var retFirstSeg = fl.itineraries[1].segments[0];
                        if (retFirstSeg && retFirstSeg.departure && retFirstSeg.departure.at) {
                            retDate = retFirstSeg.departure.at.split('T')[0];
                        }
                    }
                    if (!retDate) retDate = sd.return_date || sd.returnDate || '';

                    var cabinRaw = sd.cabin || sd.travel_class || 'ECONOMY';
                    var cabinName = (typeof getCabinClassName === 'function') ? getCabinClassName(cabinRaw) : cabinRaw;
                    var tripType = (sd.trip_type || sd.tripType || 'round_trip').toLowerCase().replace(/\s+/g, '_').replace(/^round$/, 'round_trip').replace(/^oneway$/, 'one_way');

                    var adults = parseInt(sd.adults || fl.originalAdults || 1);
                    var children = parseInt(sd.children || fl.originalChildren || 0);
                    var infants = parseInt(sd.infants || fl.originalInfants || 0);
                    var travelerCount = adults + children + infants;

                    var rawPrice = parseFloat(
                        (fl.price && fl.price.pricing_charge_total) ||
                        (fl.price && fl.price.total) ||
                        (fl.price && fl.price.grandTotal) ||
                        fl.totalPrice || 0
                    ) || 0;
                    var totalValue = parseFloat(rawPrice.toFixed(2));
                    var perPaxPrice = travelerCount > 0 ? parseFloat((rawPrice / travelerCount).toFixed(2)) : totalValue;

                    var currency = ((fl.price && (fl.price.selected_currency || fl.price.currency)) || sd.currency || 'USD').toUpperCase();
                    var itemId = airlineCode + '_' + origin + '_' + destination;
                    var itemName = origin + ' \u2192 ' + destination + (airlineName ? ' (' + airlineName + ')' : '');

                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({ ecommerce: null });
                    window.dataLayer.push({
                        event: 'begin_checkout',
                        ecommerce: {
                            currency: currency,
                            value: totalValue,
                            items: [{
                                item_id: itemId,
                                item_name: itemName,
                                item_category: 'Flights',
                                item_brand: airlineCode,
                                item_variant: cabinName,
                                price: perPaxPrice,
                                quantity: travelerCount,
                                origin: origin,
                                destination: destination,
                                start_date: depDate,
                                end_date: retDate,
                                trip_type: tripType,
                                cabin_class: cabinRaw.toLowerCase(),
                                stops_count: stopsCount,
                                traveler_count: travelerCount,
                                adults: adults,
                                children: children,
                                infants: infants,
                                itinerary_id: offerId
                            }]
                        }
                    });

                    if (typeof console !== 'undefined' && console.log) {
                        console.log('[Amadex GA4] begin_checkout pushed', window.dataLayer[window.dataLayer.length - 1]);
                    }
                }
            } catch (err) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[Amadex GA4] begin_checkout push failed:', err);
                }
            }
        }

        // ── GA4 add_payment_info: fires once when user reaches review step ──
        if (stepName === 'review') {
            try {
                var flightRaw2 = sessionStorage.getItem('amadex_booking_flight');
                if (flightRaw2) {
                    var fl2 = JSON.parse(flightRaw2);
                    var sd2 = window.amadexSearchData || {};

                    var offerId2 = (fl2.rawOffer && fl2.rawOffer.id) || fl2.id || fl2.offerId || '';
                    var airlineCode2 = (fl2.validatingAirlineCodes && fl2.validatingAirlineCodes[0]) ||
                        (fl2.validating_airline_codes && fl2.validating_airline_codes[0]) || '';
                    var airlineName2 = (typeof getAirlineName === 'function') ? getAirlineName(airlineCode2) : airlineCode2;

                    var origin2 = '', destination2 = '', depDate2 = '', retDate2 = '', stopsCount2 = 0;
                    if (fl2.itineraries && fl2.itineraries[0] && fl2.itineraries[0].segments) {
                        var segs2 = fl2.itineraries[0].segments;
                        var firstSeg2 = segs2[0];
                        var lastSeg2 = segs2[segs2.length - 1];
                        origin2 = (firstSeg2.departure && (firstSeg2.departure.iataCode || firstSeg2.departure.iata_code || firstSeg2.departure.code)) || '';
                        destination2 = (lastSeg2.arrival && (lastSeg2.arrival.iataCode || lastSeg2.arrival.iata_code || lastSeg2.arrival.code)) || '';
                        stopsCount2 = segs2.length - 1;
                        if (firstSeg2.departure && firstSeg2.departure.at) depDate2 = firstSeg2.departure.at.split('T')[0];
                    }
                    if (!origin2) origin2 = sd2.origin || sd2.from || '';
                    if (!destination2) destination2 = sd2.destination || sd2.to || '';
                    if (!depDate2) depDate2 = sd2.departure_date || sd2.departureDate || '';

                    if (fl2.itineraries && fl2.itineraries[1] && fl2.itineraries[1].segments) {
                        var retFirstSeg2 = fl2.itineraries[1].segments[0];
                        if (retFirstSeg2 && retFirstSeg2.departure && retFirstSeg2.departure.at) {
                            retDate2 = retFirstSeg2.departure.at.split('T')[0];
                        }
                    }
                    if (!retDate2) retDate2 = sd2.return_date || sd2.returnDate || '';

                    var cabinRaw2 = sd2.cabin || sd2.travel_class || 'ECONOMY';
                    var cabinName2 = (typeof getCabinClassName === 'function') ? getCabinClassName(cabinRaw2) : cabinRaw2;
                    var tripType2 = (sd2.trip_type || sd2.tripType || 'round_trip').toLowerCase().replace(/\s+/g, '_').replace(/^round$/, 'round_trip').replace(/^oneway$/, 'one_way');

                    var adults2 = parseInt(sd2.adults || fl2.originalAdults || 1);
                    var children2 = parseInt(sd2.children || fl2.originalChildren || 0);
                    var infants2 = parseInt(sd2.infants || fl2.originalInfants || 0);
                    var travelerCount2 = adults2 + children2 + infants2;

                    var rawPrice2 = parseFloat(
                        (fl2.price && fl2.price.pricing_charge_total) ||
                        (fl2.price && fl2.price.total) ||
                        (fl2.price && fl2.price.grandTotal) ||
                        fl2.totalPrice || 0
                    ) || 0;
                    var totalValue2 = parseFloat(rawPrice2.toFixed(2));
                    var perPaxPrice2 = travelerCount2 > 0 ? parseFloat((rawPrice2 / travelerCount2).toFixed(2)) : totalValue2;

                    var currency2 = ((fl2.price && (fl2.price.selected_currency || fl2.price.currency)) || sd2.currency || 'USD').toUpperCase();
                    var itemId2 = airlineCode2 + '_' + origin2 + '_' + destination2;
                    var itemName2 = origin2 + ' \u2192 ' + destination2 + (airlineName2 ? ' (' + airlineName2 + ')' : '');

                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({ ecommerce: null });
                    window.dataLayer.push({
                        event: 'add_payment_info',
                        ecommerce: {
                            currency: currency2,
                            value: totalValue2,
                            items: [{
                                item_id: itemId2,
                                item_name: itemName2,
                                item_category: 'Flights',
                                item_brand: airlineCode2,
                                item_variant: cabinName2,
                                price: perPaxPrice2,
                                quantity: travelerCount2,
                                origin: origin2,
                                destination: destination2,
                                start_date: depDate2,
                                end_date: retDate2,
                                trip_type: tripType2,
                                cabin_class: cabinRaw2.toLowerCase(),
                                stops_count: stopsCount2,
                                traveler_count: travelerCount2,
                                adults: adults2,
                                children: children2,
                                infants: infants2,
                                itinerary_id: offerId2
                            }]
                        }
                    });

                    if (typeof console !== 'undefined' && console.log) {
                        console.log('[Amadex GA4] add_payment_info pushed', window.dataLayer[window.dataLayer.length - 1]);
                    }
                }
            } catch (err) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[Amadex GA4] add_payment_info push failed:', err);
                }
            }
        }
        // Trigger custom event for other components
        $(document).trigger('stepChanged', [stepName]);

        return true;
    }

    /**
     * Check if user can access a specific step
     * @param {string} stepName - Step name to check
     * @returns {boolean} - True if accessible
     */
    function canAccessStep(stepName) {
        const stepOrder = BOOKING_STEPS[stepName].order;

        // Always allow access to flights (step 0) or passengers (step 1)
        if (stepOrder <= 1) return true;

        // To go beyond passengers, all required passenger fields must be filled
        var passengersComplete = true;
        $('.amadex-passenger-form-card').each(function () {
            var $card = $(this);
            var idx = $card.data('passenger-index');

            var fn = ($card.find('#pax' + idx + '-firstname').val() || '').trim();
            var ln = ($card.find('#pax' + idx + '-lastname').val() || '').trim();
            var gen = $card.find('input[name="pax' + idx + '-gender"]').filter(':checked').length;
            var dd = $card.find('#pax' + idx + '-dob-day').val() || '';
            var dm = $card.find('#pax' + idx + '-dob-month').val() || '';
            var dy = $card.find('#pax' + idx + '-dob-year').val() || '';
            var nat = $card.find('#pax' + idx + '-nationality').val() || '';

            if (!fn || !ln || !gen || !dd || !dm || !dy || !nat) {
                passengersComplete = false;
                return false; // break .each()
            }
        });

        if (!passengersComplete) return false;

        return true;
    }

    /**
     * Check passenger completeness and toggle locked class on steps
     * Call this whenever passenger fields change
     */
    function updateStepLockState() {
        var lockedSteps = ['seats', 'addons', 'review'];
        var complete = canAccessStep('seats'); // seats requires passengers filled

        lockedSteps.forEach(function (step) {
            var $el = $('.booking-step[data-step="' + step + '"]');
            if (complete) {
                $el.removeClass('amadex-step-locked');
            } else {
                $el.addClass('amadex-step-locked');
            }
        });
    }

    /**
     * Get first incomplete step
     * @returns {string|null} - First incomplete step name or null
     */
    function getFirstIncompleteStep() {
        // For now, return first step (can add validation later)
        return 'passengers';
    }

    /**
     * Get section element for a given step (for desktop scrolling)
     * @param {string} stepName - Step name
     * @returns {jQuery} - Section jQuery object
     */
    function getSectionForStep(stepName) {
        if (stepName === 'passengers') {
            return $('#amadex-section-passengers');
        } else if (stepName === 'seats') {
            return $('#amadex-seat-selection-section');
        } else if (stepName === 'addons') {
            return $('#amadex-addons-section');
        } else if (stepName === 'review') {
            return $('#amadex-review-section');
        }
        return null;
    }

    /**
     * Update section visibility based on current step
     * @param {string} stepName - Current step name
     */
    /**
     * Update section visibility based on current step
     * EXPERT/GOD MODE: Added shouldScroll parameter to prevent mobile scroll-to-top bug
     * 
     * @param {string} stepName - The step name to show
     * @param {boolean} shouldScroll - Whether to scroll to top (default: true, but disabled on mobile)
     */
    function updateSectionVisibility(stepName, shouldScroll = true) {
        const step = BOOKING_STEPS[stepName];
        const isMobile = window.innerWidth <= 767;

        // Remove step-active class from all sections
        $('.amadex-booking-section').removeClass('step-active');

        // Both mobile and desktop use same step-by-step pagination - show only one step at a time
        // Remove step-active from ALL sections including flights
        $('.amadex-booking-section[data-section]').removeClass('step-active active');

        // Show current step's section only (same logic for both mobile and desktop)
        if (stepName === 'flights') {
            // Step 1: Only flights section visible
            $('#amadex-section-flights').addClass('step-active').removeClass('active');
        } else if (stepName === 'passengers') {
            // Step 2: Only Passengers section visible (flights hidden)
            $('#amadex-section-flights').removeClass('step-active');
            $('#amadex-section-passengers').addClass('step-active').removeClass('active');
        } else if (stepName === 'seats') {
            // Step 3: Only Seats section visible (flights hidden)
            $('#amadex-section-flights').removeClass('step-active');
            $('#amadex-seat-selection-section').addClass('step-active').removeClass('active');
        } else if (stepName === 'addons') {
            // Step 4: Only Add-ons section visible (flights hidden)
            $('#amadex-section-flights').removeClass('step-active');
            $('#amadex-addons-section').addClass('step-active').removeClass('active');
        } else if (stepName === 'review') {
            // Step 5: Only Review sections visible (flights hidden)
            $('#amadex-section-flights').removeClass('step-active');
            $('#amadex-review-section, #amadex-contact-section, #amadex-billing-section, #amadex-payment-section, #amadex-agreement-section').addClass('step-active').removeClass('active');
        }

        // EXPERT/GOD MODE FIX: Smooth scroll to top after transition (desktop only)
        // Mobile browsers trigger frequent resize events (address bar show/hide, keyboard appear/disappear)
        // which cause continuous scroll-to-top loops. Since mobile uses step-by-step navigation
        // (only one section visible at a time), scrolling to top is unnecessary and disruptive.
        // Desktop benefits from scroll-to-top to ensure users see the section header when navigating.
        if (shouldScroll && !isMobile) {
            setTimeout(function () {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }, 200);
        } else if (shouldScroll && isMobile) {
            // Mobile: Log for debugging (can be removed in production)
        }
    }

    /**
     * Update progress stepper visual state
     * @param {string} stepName - Current step name
     */
    function updateProgressStepper(stepName) {
        if (!BOOKING_STEPS[stepName]) {
            return;
        }

        const currentOrder = BOOKING_STEPS[stepName].order;

        $('.booking-step').each(function () {
            const $step = $(this);
            const stepDataStep = $step.data('step');
            const stepOrder = BOOKING_STEPS[stepDataStep] ? BOOKING_STEPS[stepDataStep].order : -1;

            // Remove all state classes including locked
            $step.removeClass('is-active is-complete is-locked');
            $step.css('cursor', '');
            $step.removeAttr('title');

            // Add appropriate class
            if (stepOrder === currentOrder) {
                $step.addClass('is-active');
            } else if (stepOrder < currentOrder && stepOrder >= 0) {
                $step.addClass('is-complete');
            } else if (stepOrder > currentOrder && !canAccessStep(stepDataStep)) {
                // Future step that is locked — passenger details not filled
                $step.addClass('is-locked');
                $step.css('cursor', 'not-allowed');
                $step.attr('title', 'Please fill in passenger details first');
            }
        });
    }

    /**
     * Make progress stepper clickable
     * Note: Actual click handling is done in initProgressNavigation()
     * This function just ensures cursor style is applied
     */
    function makeStepperClickable() {
        // Cursor pointer style is already applied in initProgressNavigation
        // This function kept for compatibility but logic moved to initProgressNavigation
        // to handle mobile vs desktop differently
    }

    /**
     * Setup back/next navigation buttons
     */
    function setupNavigationButtons() {
        // Create navigation buttons container if it doesn't exist
        if ($('#amadex-step-navigation').length === 0) {
            $('.amadex-booking-main').append(`
                <div id="amadex-step-navigation" class="amadex-step-navigation">
                    <button type="button" id="amadex-step-back" class="amadex-step-btn amadex-step-back">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17.573" height="13.75" viewBox="0 0 17.573 13.75">
                            <path d="M.325,138.09h0l5.794-5.766a1.109,1.109,0,0,1,1.564,1.572l-3.89,3.871H16.464a1.109,1.109,0,0,1,0,2.218H3.795l3.89,3.871a1.109,1.109,0,0,1-1.564,1.572L.326,139.661h0A1.11,1.11,0,0,1,.325,138.09Z" transform="translate(0 -132)" fill="#707070"/>
                        </svg>
                        Back
                    </button>
                    <button type="button" id="amadex-step-next" class="amadex-step-btn amadex-step-next">
                       Next
                        <svg xmlns="http://www.w3.org/2000/svg" width="17.573" height="13.75" viewBox="0 0 17.573 13.75">
                            <path d="M17.248,138.09h0l-5.794-5.766a1.109,1.109,0,0,0-1.564,1.572l3.89,3.871H1.109a1.109,1.109,0,0,0,0,2.218h13.669l-3.89,3.871a1.109,1.109,0,0,0,1.564,1.572l5.794-5.766h0A1.11,1.11,0,0,0,17.248,138.09Z" transform="translate(0 -132)" fill="#fff"/>
                        </svg>
                    </button>
                </div>
            `);
        }

        // Check what buttons exist on the page
        const $stepNextBtn = $('#amadex-step-next');
        const $confirmBtn = $('#amadex-confirm-book');
        if ($stepNextBtn.length) {
        }
        if ($confirmBtn.length) {
        }

        // ✅ FIX #6: Always use .off() before .on() with namespaced events
        // Back button handler
        $('#amadex-step-back').off('click.amadex-step-back').on('click.amadex-step-back', function () {
            goToPreviousStep();
        });

        // AGGRESSIVE: Multiple event handlers for step-next button
        // 1. Delegated handler (catches dynamically added buttons) - supports both click and touchstart
        function handleStepNextClick(e) {
            // Prevent double-firing on mobile (touchstart + click)
            if (e.type === 'click' && e.originalEvent && e.originalEvent.pointerType === 'touch') {
                return; // Ignore click events that came from touch
            }

            const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                ? AmadexConfig.defaultCardGateway
                : 'nmi';

            // CRITICAL FIX: Force enable button on review step to ensure click works
            if (currentStep === 'review') {
                $(this).prop('disabled', false);
                $(this).css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1',
                    'pointer-events': 'auto'
                });

                // On review step, work exactly like desktop confirm button
                const currentOrder = BOOKING_STEPS[currentStep] ? BOOKING_STEPS[currentStep].order : 0;
                const maxOrder = Math.max(...Object.values(BOOKING_STEPS).map(s => s.order));

                if (currentOrder >= maxOrder) {
                    // This is the confirm action - handle exactly like desktop confirm button
                    e.preventDefault();
                    e.stopPropagation();

                    // Check agreement checkbox first
                    const isChecked = $('#amadex-updates-consent').is(':checked');
                    if (!isChecked) {
                        alert('Please agree to Travelay\'s Terms of Use and Privacy Policy to continue.');
                        const $agreementSection = $('#amadex-agreement-section');
                        if ($agreementSection.length) {
                            $('html, body').animate({
                                scrollTop: $agreementSection.offset().top - 100
                            }, 300);
                        }
                        return;
                    }

                    // Get flight data
                    const flightData = sessionStorage.getItem('amadex_booking_flight');
                    if (!flightData) {
                        alert('Flight data not found. Please start over.');
                        return;
                    }

                    let flight;
                    try {
                        flight = JSON.parse(flightData);
                    } catch (err) {
                        alert('Error loading flight data. Please try again.');
                        return;
                    }

                    var paymentMethodStep = ($('.amadex-payment-tab.is-active').attr('data-method') || $('#payment-method').val() || $('#payment-method-moonpay').val() || $('#payment-method-moonpay-onramp').val() || $('#payment-method-crypto-com').val() || '').trim();
                    if (paymentMethodStep === 'crypto_com') {
                        if (typeof startCryptoComPayFlow === 'function') {
                            startCryptoComPayFlow(flight);
                        } else {
                            showPaymentError('Crypto.com Pay is not available. Please refresh and try again.');
                        }
                        return;
                    }
                    if (paymentMethodStep === 'moonpay') {
                        if (typeof startMoonPayFlow === 'function') {
                            startMoonPayFlow(flight);
                        } else {
                            showPaymentError('MoonPay is not available. Please refresh and try again.');
                        }
                        return;
                    }
                    if (paymentMethodStep === 'moonpay_onramp') {
                        if (typeof startMoonPayOnrampFlow === 'function') {
                            startMoonPayOnrampFlow(flight);
                        } else {
                            showPaymentError('MoonPay Onramp is not available. Please refresh and try again.');
                        }
                        return;
                    }

                    if (gateway === 'stripe') {
                        // For Stripe, call submitBooking directly
                        // ✅ FIX #3: Apply debounce to prevent rapid clicks
                        const debouncedSubmit = debounce(function () {
                            submitBooking(flight);
                        }, 300);
                        debouncedSubmit();
                    } else {
                        // For NMI with Gateway.js 3DS: call submitBooking directly
                        const debouncedNmiSubmit = debounce(function () {
                            submitBooking(flight);
                        }, 300);
                        debouncedNmiSubmit();
                    }
                    return;
                }
            }

            // For non-review steps, just go to next step
            e.preventDefault();
            e.stopPropagation();
            goToNextStep(e);
        }

        // ✅ FIX #6: Always use .off() before .on() to prevent multiple handlers
        // Attach both click and touchstart handlers for mobile support
        $(document).off('click.amadex-step-next touchstart.amadex-step-next', '#amadex-step-next')
            .on('click.amadex-step-next touchstart.amadex-step-next', '#amadex-step-next', handleStepNextClick);

        // 2. Direct handler (if button exists now) - supports both click and touchstart
        if ($stepNextBtn.length) {
            function handleStepNextDirect(e) {
                // Prevent double-firing on mobile (touchstart + click)
                if (e.type === 'click' && e.originalEvent && e.originalEvent.pointerType === 'touch') {
                    return; // Ignore click events that came from touch
                }

                const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                    ? AmadexConfig.defaultCardGateway
                    : 'nmi';

                // Check if on review step and button says "Confirm & Book"
                if (currentStep === 'review') {
                    const currentOrder = BOOKING_STEPS[currentStep] ? BOOKING_STEPS[currentStep].order : 0;
                    const maxOrder = Math.max(...Object.values(BOOKING_STEPS).map(s => s.order));
                    const btnText = $(this).text().trim();

                    if (currentOrder >= maxOrder && (btnText.includes('Confirm') || btnText.includes('Book') || btnText.includes('Continue') || btnText.includes('Payment'))) {
                        // This is confirm action - handle exactly like desktop
                        e.preventDefault();
                        e.stopPropagation();

                        // Check agreement checkbox first
                        const isChecked = $('#amadex-updates-consent').is(':checked');
                        if (!isChecked) {
                            alert('Please agree to Travelay\'s Terms of Use and Privacy Policy to continue.');
                            const $agreementSection = $('#amadex-agreement-section');
                            if ($agreementSection.length) {
                                $('html, body').animate({
                                    scrollTop: $agreementSection.offset().top - 100
                                }, 300);
                            }
                            return;
                        }

                        // Get flight data
                        const flightData = sessionStorage.getItem('amadex_booking_flight');
                        if (!flightData) {
                            alert('Flight data not found. Please start over.');
                            return;
                        }

                        let flight;
                        try {
                            flight = JSON.parse(flightData);
                        } catch (err) {
                            alert('Error loading flight data. Please try again.');
                            return;
                        }

                        // Crypto.com Pay: must run before NMI so we don't trigger CollectJS
                        var paymentMethodDirect = ($('.amadex-payment-tab.is-active').attr('data-method') || $('#payment-method').val() || $('#payment-method-moonpay').val() || $('#payment-method-moonpay-onramp').val() || $('#payment-method-crypto-com').val() || '').trim();
                        if (paymentMethodDirect === 'crypto_com') {
                            if (typeof startCryptoComPayFlow === 'function') {
                                startCryptoComPayFlow(flight);
                            } else {
                                showPaymentError('Crypto.com Pay is not available. Please refresh and try again.');
                            }
                            return;
                        }
                        if (paymentMethodDirect === 'moonpay') {
                            if (typeof startMoonPayFlow === 'function') {
                                startMoonPayFlow(flight);
                            } else {
                                showPaymentError('MoonPay is not available. Please refresh and try again.');
                            }
                            return;
                        }
                        if (paymentMethodDirect === 'moonpay_onramp') {
                            if (typeof startMoonPayOnrampFlow === 'function') {
                                startMoonPayOnrampFlow(flight);
                            } else {
                                showPaymentError('MoonPay Onramp is not available. Please refresh and try again.');
                            }
                            return;
                        }

                        // Handle based on gateway
                        if (gateway === 'stripe') {
                            // ✅ FIX #3: Apply debounce to prevent rapid clicks
                            const debouncedSubmit = debounce(function () {
                                submitBooking(flight);
                            }, 300);
                            debouncedSubmit();
                        } else {
                            // ✅ Gateway.js 3DS path: call submitBooking() directly (not CollectJS btn.click())
                            if (!window.amadexBookingSubmissionInProgress) {
                                submitBooking(flight);
                            }
                        }

                        return;
                    }
                }

                // For non-review steps, just go to next step
                e.preventDefault();
                e.stopPropagation();
                goToNextStep(e);
            }

            // Attach both click and touchstart handlers
            $stepNextBtn.off('click.stripe touchstart.stripe').on('click.stripe touchstart.stripe', handleStepNextDirect);
        }

        // 3. Also attach to button by class as fallback
        $(document).off('click', '.amadex-step-next').on('click', '.amadex-step-next', function (e) {
            const btnId = $(this).attr('id');
            if (btnId === 'amadex-step-next') {
                const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                    ? AmadexConfig.defaultCardGateway
                    : 'nmi';

                // Check if on review step and button says "Confirm & Book"
                if (currentStep === 'review') {
                    const currentOrder = BOOKING_STEPS[currentStep] ? BOOKING_STEPS[currentStep].order : 0;
                    const maxOrder = Math.max(...Object.values(BOOKING_STEPS).map(s => s.order));
                    const btnText = $(this).text().trim();

                    if (currentOrder >= maxOrder && (btnText.includes('Confirm') || btnText.includes('Book') || btnText.includes('Continue') || btnText.includes('Payment'))) {
                        // This is confirm action - handle exactly like desktop
                        e.preventDefault();
                        e.stopPropagation();

                        // Check agreement checkbox first
                        const isChecked = $('#amadex-updates-consent').is(':checked');
                        if (!isChecked) {
                            alert('Please agree to Travelay\'s Terms of Use and Privacy Policy to continue.');
                            const $agreementSection = $('#amadex-agreement-section');
                            if ($agreementSection.length) {
                                $('html, body').animate({
                                    scrollTop: $agreementSection.offset().top - 100
                                }, 300);
                            }
                            return;
                        }

                        // Get flight data
                        const flightData = sessionStorage.getItem('amadex_booking_flight');
                        if (!flightData) {
                            alert('Flight data not found. Please start over.');
                            return;
                        }

                        let flight;
                        try {
                            flight = JSON.parse(flightData);
                        } catch (err) {
                            alert('Error loading flight data. Please try again.');
                            return;
                        }

                        // Handle based on gateway
                        if (gateway === 'stripe') {
                            submitBooking(flight);
                        } else {
                            // ✅ Gateway.js 3DS path: call submitBooking() directly
                            if (!window.amadexBookingSubmissionInProgress) {
                                submitBooking(flight);
                            }
                        }
                        return;
                    }
                }

                // For non-review steps, just go to next step
                e.preventDefault();
                e.stopPropagation();
                goToNextStep(e);
            }
        });

        // AGGRESSIVE: Multiple event handlers for confirm-book button
        // 1. Delegated handler
        // Handler for confirm-book button - supports both click and touchstart
        function handleConfirmBookClick(e) {
            // Prevent double-firing on mobile (touchstart + click)
            if (e.type === 'click' && e.originalEvent && e.originalEvent.pointerType === 'touch') {
                return; // Ignore click events that came from touch
            }

            const $btn = $(this);
            const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                ? AmadexConfig.defaultCardGateway
                : 'nmi';

            const paymentMethod = ($('.amadex-payment-tab.is-active').attr('data-method') || $('#payment-method').val() || $('#payment-method-moonpay').val() || $('#payment-method-moonpay-onramp').val() || $('#payment-method-crypto-com').val() || '').trim();
            if (paymentMethod === 'crypto_com') {
                e.preventDefault();
                e.stopPropagation();
                var flightData = sessionStorage.getItem('amadex_booking_flight');
                if (flightData && typeof startCryptoComPayFlow === 'function') {
                    try {
                        var flight = JSON.parse(flightData);
                        startCryptoComPayFlow(flight);
                    } catch (err) {
                        showPaymentError('Could not start Crypto.com Pay. Please try again.');
                    }
                } else {
                    showPaymentError('Flight data not found or Crypto.com Pay not available. Please try again.');
                }
                return;
            }
            if (paymentMethod === 'moonpay') {
                e.preventDefault();
                e.stopPropagation();
                var flightDataMoon = sessionStorage.getItem('amadex_booking_flight');
                if (flightDataMoon && typeof startMoonPayFlow === 'function') {
                    try {
                        var flightMoon = JSON.parse(flightDataMoon);
                        startMoonPayFlow(flightMoon);
                    } catch (err) {
                        showPaymentError('Could not start MoonPay. Please try again.');
                    }
                } else {
                    showPaymentError('Flight data not found or MoonPay not available. Please try again.');
                }
                return;
            }
            if (paymentMethod === 'moonpay_onramp') {
                e.preventDefault();
                e.stopPropagation();
                var flightDataOnramp = sessionStorage.getItem('amadex_booking_flight');
                if (flightDataOnramp && typeof startMoonPayOnrampFlow === 'function') {
                    try {
                        var flightOnramp = JSON.parse(flightDataOnramp);
                        startMoonPayOnrampFlow(flightOnramp);
                    } catch (err) {
                        showPaymentError('Could not start MoonPay. Please try again.');
                    }
                } else {
                    showPaymentError('Flight data not found or MoonPay not available. Please try again.');
                }
                return;
            }

            // Stripe: Confirm & Book is hidden – do not show; only NMI uses this button
            if ($btn.hasClass('amadex-confirm-book-stripe-hidden') || gateway === 'stripe') {
                e.preventDefault();
                e.stopPropagation();
                return;
            }

            // Ensure button remains visible after click (NMI only)
            if (window.innerWidth >= 768) {
                $btn.css({
                    'display': 'block',
                    'position': 'relative',
                    'left': 'auto',
                    'opacity': '1',
                    'visibility': 'visible',
                    'pointer-events': 'auto'
                });
            }

            if (gateway === 'stripe') {
                e.preventDefault();
                e.stopPropagation();
                // Trigger the same flow as step-next button
                goToNextStep(e);
                return;
            }

            // NMI with Gateway.js 3DS: call submitBooking directly (not CollectJS)
            e.preventDefault();
            e.stopPropagation();

            // Check agreement checkbox
            const isChecked = $('#amadex-updates-consent').is(':checked');
            if (!isChecked) {
                alert('Please agree to Travelay\'s Terms of Use and Privacy Policy to continue.');
                return;
            }

            const flightDataRaw = sessionStorage.getItem('amadex_booking_flight');
            if (!flightDataRaw) {
                showPaymentError('Flight data not found. Please start over.');
                return;
            }
            let flightForGateway;
            try { flightForGateway = JSON.parse(flightDataRaw); } catch (err) {
                showPaymentError('Error loading flight data. Please try again.');
                return;
            }
            submitBooking(flightForGateway);
        }

        $(document).off('click touchstart', '#amadex-confirm-book').on('click touchstart', '#amadex-confirm-book', handleConfirmBookClick);

        // Capture-phase intercept: when Confirm & Book is clicked with MoonPay/Crypto/MoonPay Onramp,
        // handle it before CollectJS so card validation does not run (user pays on external page).
        (function () {
            function captureConfirmClick(e) {
                var target = e.target;
                var btn = target && (target.id === 'amadex-confirm-book' ? target : (target.closest && target.closest('#amadex-confirm-book')));
                if (!btn) return;
                // Scope to payment section so we don't pick up another is-active (e.g. passenger tab)
                var pm = ($('#amadex-payment-section .amadex-payment-tab.is-active').attr('data-method') || $('.amadex-payment-tab.is-active').attr('data-method') || $(document).find('#payment-method-moonpay').val() || $(document).find('#payment-method-moonpay-onramp').val() || $(document).find('#payment-method-crypto-com').val() || '').trim();
                if (pm !== 'moonpay' && pm !== 'moonpay_onramp' && pm !== 'crypto_com') return;
                e.preventDefault();
                e.stopPropagation();
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                var flightData = sessionStorage.getItem('amadex_booking_flight');
                if (!flightData) {
                    showPaymentError('Flight data not found. Please start over.');
                    return;
                }
                var flight;
                try { flight = JSON.parse(flightData); } catch (err) {
                    showPaymentError('Could not load flight data. Please try again.');
                    return;
                }
                if (pm === 'crypto_com' && typeof startCryptoComPayFlow === 'function') {
                    startCryptoComPayFlow(flight);
                } else if (pm === 'moonpay' && typeof startMoonPayFlow === 'function') {
                    startMoonPayFlow(flight);
                } else if (pm === 'moonpay_onramp' && typeof startMoonPayOnrampFlow === 'function') {
                    startMoonPayOnrampFlow(flight);
                } else {
                    showPaymentError('Payment option not available. Please refresh and try again.');
                }
            }
            var captureOpt = { capture: true, passive: false };
            try {
                document.removeEventListener('click', captureConfirmClick, captureOpt);
                document.removeEventListener('touchstart', captureConfirmClick, captureOpt);
            } catch (e) { }
            document.addEventListener('click', captureConfirmClick, captureOpt);
            document.addEventListener('touchstart', captureConfirmClick, captureOpt);
        })();

        // Stripe: Handle "Pay securely" button (#amadex-payment-submit) - redirects to payment page then Stripe Checkout
        $(document).off('click.amadexPaymentSubmit touchstart.amadexPaymentSubmit', '#amadex-payment-submit').on('click.amadexPaymentSubmit touchstart.amadexPaymentSubmit', '#amadex-payment-submit', function (e) {
            // Prevent double-firing on mobile
            if (e.type === 'click' && e.originalEvent && e.originalEvent.pointerType === 'touch') return;

            const $btn = $(this);
            const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway ? AmadexConfig.defaultCardGateway : 'nmi';

            if (gateway !== 'stripe') return;

            // Check if button is disabled (checkbox not checked)
            if ($btn.prop('disabled')) {
                const $checkbox = $('#amadex-updates-consent');
                if (!$checkbox.is(':checked')) {
                    alert('Please agree to Travelay\'s Terms of Use and Privacy Policy to continue.');
                    const $agreementSection = $('#amadex-agreement-section');
                    if ($agreementSection.length) {
                        $('html, body').animate({ scrollTop: $agreementSection.offset().top - 100 }, 300);
                        $checkbox.focus();
                    }
                }
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            // Validate checkbox is checked
            const isChecked = $('#amadex-updates-consent').is(':checked');
            if (!isChecked) {
                alert('Please agree to Travelay\'s Terms of Use and Privacy Policy to continue.');
                const $agreementSection = $('#amadex-agreement-section');
                if ($agreementSection.length) {
                    $('html, body').animate({ scrollTop: $agreementSection.offset().top - 100 }, 300);
                }
                return;
            }

            // Get flight data
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            if (!flightData) {
                alert('Flight data not found. Please start over.');
                return;
            }

            let flight;
            try {
                flight = JSON.parse(flightData);
            } catch (err) {
                alert('Error loading flight data. Please try again.');
                return;
            }

            // Show loading state on button (in-page payment — no redirect to checkout.stripe.com)
            $btn.prop('disabled', true).text('Processing Payment...');
            // In-page Stripe flow: create PaymentIntent, confirm card, complete booking (no redirect)
            handleStripeSubmit(flight);
        });

        // 2. Direct handler (if button exists now) - supports both click and touchstart
        if ($confirmBtn.length) {
            function handleConfirmBookDirect(e) {
                // Prevent double-firing on mobile (touchstart + click)
                if (e.type === 'click' && e.originalEvent && e.originalEvent.pointerType === 'touch') {
                    return; // Ignore click events that came from touch
                }

                const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                    ? AmadexConfig.defaultCardGateway
                    : 'nmi';

                // Stripe: Confirm & Book is hidden – do not show it
                const $btn = $(this);
                if ($btn.hasClass('amadex-confirm-book-stripe-hidden')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                if (gateway === 'stripe' && currentStep === 'review') {
                    e.preventDefault();
                    e.stopPropagation();
                    return; // Stripe uses Pay securely only
                }

                if (gateway === 'stripe') {
                    e.preventDefault();
                    e.stopPropagation();
                    goToNextStep(e);
                }
            }

            // Attach both click and touchstart handlers
            $confirmBtn.off('click.stripe touchstart.stripe').on('click.stripe touchstart.stripe', function (e) {
                // Ensure button remains visible after click
                if (window.innerWidth >= 768) {
                    $(this).css({
                        'display': 'block',
                        'position': 'relative',
                        'left': 'auto',
                        'opacity': '1',
                        'visibility': 'visible'
                    });
                }
                handleConfirmBookDirect.call(this, e);
            });
        }

        // ULTRA-AGGRESSIVE: Catch ANY button with "Confirm" or "Book" in text or ID
        // This should catch buttons even if they're dynamically created
        // Supports both click and touchstart for mobile
        // ✅ FIX #3 & #5: Add loop prevention and submission guard
        let lastUltraAggressiveClickTime = 0;
        let ultraAggressiveClickCount = 0;
        const ULTRA_AGGRESSIVE_COOLDOWN = 2000; // 2 second cooldown
        const MAX_ULTRA_AGGRESSIVE_CLICKS = 5; // Max 5 clicks per cooldown period

        function handleUltraAggressiveButton(e) {
            // Prevent double-firing on mobile (touchstart + click)
            if (e.type === 'click' && e.originalEvent && e.originalEvent.pointerType === 'touch') {
                return; // Ignore click events that came from touch
            }

            // ✅ FIX #5: Check if submission is already in progress
            if (window.amadexBookingSubmissionInProgress) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }

            // ✅ FIX #3: Loop prevention - cooldown check
            const now = Date.now();
            if (now - lastUltraAggressiveClickTime < ULTRA_AGGRESSIVE_COOLDOWN) {
                ultraAggressiveClickCount++;
                if (ultraAggressiveClickCount > MAX_ULTRA_AGGRESSIVE_CLICKS) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
            } else {
                // Reset counter if cooldown period has passed
                ultraAggressiveClickCount = 1;
            }
            lastUltraAggressiveClickTime = now;

            const $btn = $(this);
            const btnId = $btn.attr('id') || '';
            const btnText = $btn.text().trim() || '';
            const btnClass = $btn.attr('class') || '';

            // Check if this is a booking-related button
            const isBookingButton =
                btnId.includes('confirm') ||
                btnId.includes('step-next') ||
                btnText.includes('Confirm') ||
                btnText.includes('Book') ||
                btnClass.includes('step-next') ||
                btnClass.includes('confirm');

            if (isBookingButton) {
                // ✅ FIX #5: Double-check submission flag before proceeding
                if (window.amadexBookingSubmissionInProgress) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }

                // If we're on review step and this is a confirm/book button, handle it
                if (currentStep === 'review' && (btnId === 'amadex-step-next' || btnId === 'amadex-confirm-book' || btnText.includes('Confirm') || btnText.includes('Book'))) {
                    e.preventDefault();
                    e.stopPropagation();

                    const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                        ? AmadexConfig.defaultCardGateway
                        : 'nmi';

                    const currentOrder = BOOKING_STEPS[currentStep] ? BOOKING_STEPS[currentStep].order : 0;
                    const maxOrder = Math.max(...Object.values(BOOKING_STEPS).map(s => s.order));

                    if (currentOrder >= maxOrder) {
                        // This is confirm action - handle exactly like desktop
                        // Check agreement checkbox first
                        const isChecked = $('#amadex-updates-consent').is(':checked');
                        if (!isChecked) {
                            alert('Please agree to Travelay\'s Terms of Use and Privacy Policy to continue.');
                            const $agreementSection = $('#amadex-agreement-section');
                            if ($agreementSection.length) {
                                $('html, body').animate({
                                    scrollTop: $agreementSection.offset().top - 100
                                }, 300);
                            }
                            return;
                        }

                        // Get flight data
                        const flightData = sessionStorage.getItem('amadex_booking_flight');
                        if (!flightData) {
                            alert('Flight data not found. Please start over.');
                            return;
                        }

                        let flight;
                        try {
                            flight = JSON.parse(flightData);
                        } catch (err) {
                            alert('Error loading flight data. Please try again.');
                            return;
                        }

                        // ✅ FIX #5: Final check before triggering - ensure submission not in progress
                        if (window.amadexBookingSubmissionInProgress) {
                            return;
                        }

                        // Crypto.com Pay: must run before gateway (NMI) so we don't trigger CollectJS
                        var paymentMethodUltra = ($('.amadex-payment-tab.is-active').attr('data-method') || $('#payment-method').val() || $('#payment-method-moonpay').val() || $('#payment-method-moonpay-onramp').val() || $('#payment-method-crypto-com').val() || '').trim();
                        if (paymentMethodUltra === 'crypto_com') {
                            if (typeof startCryptoComPayFlow === 'function') {
                                startCryptoComPayFlow(flight);
                            } else {
                                showPaymentError('Crypto.com Pay is not available. Please refresh and try again.');
                            }
                            return;
                        }
                        if (paymentMethodUltra === 'moonpay') {
                            if (typeof startMoonPayFlow === 'function') {
                                startMoonPayFlow(flight);
                            } else {
                                showPaymentError('MoonPay is not available. Please refresh and try again.');
                            }
                            return;
                        }
                        if (paymentMethodUltra === 'moonpay_onramp') {
                            if (typeof startMoonPayOnrampFlow === 'function') {
                                startMoonPayOnrampFlow(flight);
                            } else {
                                showPaymentError('MoonPay Onramp is not available. Please refresh and try again.');
                            }
                            return;
                        }

                        // Handle based on gateway
                        if (gateway === 'stripe') {
                            submitBooking(flight);
                        } else {
                            // ✅ Gateway.js 3DS path: call submitBooking() directly (not CollectJS btn.click())
                            if (window.amadexBookingSubmissionInProgress) {
                                return;
                            }
                            submitBooking(flight);
                        }
                    }
                }
            }
        }

        // ✅ FIX #6: Already using .off() before .on() with namespaced events (good!)
        // Attach both click and touchstart handlers for ultra-aggressive handler
        $(document).off('click.amadex-booking touchstart.amadex-booking', 'button')
            .on('click.amadex-booking touchstart.amadex-booking', 'button', handleUltraAggressiveButton);

        // Update button visibility based on screen size
        updateNavigationButtons();

        // Ensure confirm book button is visible on desktop when on review step
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';

        // For Stripe on desktop, ensure confirm button is visible and clickable
        if (gateway === 'stripe') {
            const checkAndSetupConfirmButton = function () {
                const isMobile = window.innerWidth <= 767;
                const currentOrder = currentStep ? BOOKING_STEPS[currentStep]?.order : 0;
                const maxOrder = Math.max(...Object.values(BOOKING_STEPS).map(s => s.order));

                if (!isMobile && currentOrder >= maxOrder) {
                    // Desktop and on review step - show confirm button only for NMI (Stripe uses Pay securely)
                    const $confirmBtn = $('#amadex-confirm-book');
                    const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway ? AmadexConfig.defaultCardGateway : 'nmi';
                    if ($confirmBtn.length && gateway !== 'stripe' && !$confirmBtn.hasClass('amadex-confirm-book-stripe-hidden')) {
                        $confirmBtn.css({
                            'display': 'block',
                            'visibility': 'visible',
                            'opacity': '1'
                        }).removeAttr('aria-hidden').removeAttr('tabindex');
                    }
                }
            };

            // Check immediately and on step changes
            checkAndSetupConfirmButton();
            $(document).on('stepChanged', checkAndSetupConfirmButton);
        }

        // Hide/show buttons on window resize
        $(window).on('resize', function () {
            updateNavigationButtons();
        });
    }

    /**
     * Go to previous step
     */
    function goToPreviousStep() {
        if (!currentStep) return;

        const currentOrder = BOOKING_STEPS[currentStep].order;
        if (currentOrder <= 1) {
            // On first step, go back to results
            if (confirm('Are you sure you want to go back to search results? Your progress will be saved.')) {
                window.location.href = '/flight-results/';
            }
            return;
        }

        // Find previous step
        for (const [name, step] of Object.entries(BOOKING_STEPS)) {
            if (step.order === currentOrder - 1) {
                navigateToStep(name);
                break;
            }
        }
    }

    /**
     * Go to next step
     */
    function goToNextStep(event) {
        // Allow event to be undefined for backwards compatibility
        event = event || { preventDefault: function () { }, stopPropagation: function () { } };

        if (!currentStep) {
            return;
        }

        // Validate current step before proceeding
        if (!validateCurrentStep()) {
            return;
        }
        const currentOrder = BOOKING_STEPS[currentStep].order;
        const maxOrder = Math.max(...Object.values(BOOKING_STEPS).map(s => s.order));

        if (currentOrder >= maxOrder) {
            // CRITICAL FIX: Ensure button is enabled before submitting
            const $stepNextBtn = $('#amadex-step-next');
            if ($stepNextBtn.length) {
                $stepNextBtn.prop('disabled', false);
                $stepNextBtn.css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1',
                    'pointer-events': 'auto'
                });
            }

            // On last step (review), submit booking
            // Check if agreement checkbox is checked
            const isChecked = $('#amadex-updates-consent').is(':checked');
            if (!isChecked) {
                alert('Please agree to Travelay\'s Terms of Use and Privacy Policy to continue.');
                // Scroll to agreement section
                const $agreementSection = $('#amadex-agreement-section');
                if ($agreementSection.length) {
                    $('html, body').animate({
                        scrollTop: $agreementSection.offset().top - 100
                    }, 300);
                }
                return;
            }

            // Get flight data and trigger booking submission
            const paymentMethod = ($('.amadex-payment-tab.is-active').attr('data-method') || $('#payment-method').val() || $('#payment-method-moonpay').val() || $('#payment-method-moonpay-onramp').val() || $('#payment-method-crypto-com').val() || '').trim();
            const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
                ? AmadexConfig.defaultCardGateway
                : 'nmi';
            // Get flight data from sessionStorage
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            if (!flightData) {
                alert('Flight data not found. Please start over.');
                return;
            }

            let flight;
            try {
                flight = JSON.parse(flightData);
            } catch (e) {
                alert('Error loading flight data. Please try again.');
                return;
            }

            // Crypto.com Pay: create booking + payment, then show Crypto.com Pay button
            if (paymentMethod === 'crypto_com') {
                if (event && typeof event.preventDefault === 'function') event.preventDefault();
                if (event && typeof event.stopPropagation === 'function') event.stopPropagation();
                if (typeof startCryptoComPayFlow === 'function') {
                    startCryptoComPayFlow(flight);
                } else {
                    showPaymentError('Crypto.com Pay is not available. Please refresh and try again.');
                }
                return;
            }
            // MoonPay Commerce: create booking + pay link, then redirect to MoonPay
            if (paymentMethod === 'moonpay') {
                if (event && typeof event.preventDefault === 'function') event.preventDefault();
                if (event && typeof event.stopPropagation === 'function') event.stopPropagation();
                if (typeof startMoonPayFlow === 'function') {
                    startMoonPayFlow(flight);
                } else {
                    showPaymentError('MoonPay is not available. Please refresh and try again.');
                }
                return;
            }
            // MoonPay Onramp: create booking via AJAX, show buy widget (overlay), redirect on completion
            if (paymentMethod === 'moonpay_onramp') {
                if (event && typeof event.preventDefault === 'function') event.preventDefault();
                if (event && typeof event.stopPropagation === 'function') event.stopPropagation();
                if (typeof startMoonPayOnrampFlow === 'function') {
                    startMoonPayOnrampFlow(flight);
                } else {
                    showPaymentError('MoonPay Onramp is not available. Please refresh and try again.');
                }
                return;
            }

            // For Stripe, call submitBooking directly (no CollectJS)
            if (gateway === 'stripe') {
                // Prevent default button behavior
                if (event && typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }
                if (event && typeof event.stopPropagation === 'function') {
                    event.stopPropagation();
                }

                // Ensure we have flight data
                if (!flight) {
                    alert('Flight data not found. Please start over.');
                    return;
                }

                // Call submitBooking
                try {
                    submitBooking(flight);
                } catch (error) {
                    showPaymentError('An error occurred. Please try again.');
                    const submitBtn = getSubmitButton();
                    if (submitBtn.length) {
                        updateSubmitButtons({ disabled: false, text: 'Confirm & Book' });
                    }
                }
                return;
            }

            // ✅ Gateway.js 3DS path: call submitBooking() directly (not CollectJS btn.click())
            if (!window.amadexBookingSubmissionInProgress) {
                submitBooking(flight);
            }
            return;
        }

        // Find next step
        for (const [name, step] of Object.entries(BOOKING_STEPS)) {
            if (step.order === currentOrder + 1) {
                navigateToStep(name);
                break;
            }
        }
    }

    /**
     * Validate current step before proceeding
     * @returns {boolean} - True if valid, false otherwise
     */
    function validateCurrentStep() {
        if (currentStep === 'passengers') {
            let isValid = true;
            let $firstError = null;

            // Helper: mark field invalid
            function markInvalid($field, msg) {
                const $wrap = $field.closest('.amadex-form-field, .amadex-radio-group').closest('.amadex-form-field');
                $field.addClass('apv-error').removeClass('apv-valid');
                $wrap.addClass('apv-field-error');
                $wrap.find('.apv-msg').remove();
                $wrap.append(`<span class="apv-msg apv-msg--error"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="6" fill="#ef4444"/><path d="M4 4l4 4M8 4l-4 4" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/></svg>${msg}</span>`);
                if (!$firstError) $firstError = $field;
                isValid = false;
            }

            // Helper: mark field valid
            function markValid($field) {
                const $wrap = $field.closest('.amadex-form-field');
                $field.addClass('apv-valid').removeClass('apv-error');
                $wrap.removeClass('apv-field-error');
                $wrap.find('.apv-msg').remove();
            }

            // Helper: clear a field's state
            function clearState($field) {
                $field.removeClass('apv-error apv-valid');
                $field.closest('.amadex-form-field').removeClass('apv-field-error').find('.apv-msg').remove();
            }

            // Validate all passenger form cards (adults)
            $('.amadex-passenger-form-card').each(function () {
                const $card = $(this);
                const idx = $card.data('passenger-index');

                const $fn = $card.find(`#pax${idx}-firstname`);
                const $ln = $card.find(`#pax${idx}-lastname`);
                const $genderRadios = $card.find(`input[name="pax${idx}-gender"]`);
                const $dobDay = $card.find(`#pax${idx}-dob-day`);
                const $dobMonth = $card.find(`#pax${idx}-dob-month`);
                const $dobYear = $card.find(`#pax${idx}-dob-year`);
                const $nat = $card.find(`#pax${idx}-nationality`);

                // First name
                if (!$fn.val() || !$fn.val().trim()) markInvalid($fn, 'First name is required');
                else markValid($fn);

                // Last name
                if (!$ln.val() || !$ln.val().trim()) markInvalid($ln, 'Last name is required');
                else markValid($ln);

                // Gender
                if (!$genderRadios.filter(':checked').length) {
                    markInvalid($genderRadios.first(), 'Please select gender');
                } else {
                    clearState($genderRadios.first());
                }

                // DOB
                if (!$dobDay.val()) markInvalid($dobDay, 'Required');
                else markValid($dobDay);
                if (!$dobMonth.val()) markInvalid($dobMonth, 'Required');
                else markValid($dobMonth);
                if (!$dobYear.val()) markInvalid($dobYear, 'Required');
                else markValid($dobYear);

                // Nationality
                if (!$nat.val()) markInvalid($nat, 'Please select nationality');
                else markValid($nat);

                // Passport fields (if visible)
                const $ppNo = $card.find(`#pax${idx}-passport-no`);
                const $ppCtr = $card.find(`#pax${idx}-passport-country`);
                const $ppEd = $card.find(`#pax${idx}-passport-exp-day`);
                const $ppEm = $card.find(`#pax${idx}-passport-exp-month`);
                const $ppEy = $card.find(`#pax${idx}-passport-exp-year`);
                if ($ppNo.length && $ppNo.is('[required]')) {
                    if (!$ppNo.val() || !$ppNo.val().trim()) markInvalid($ppNo, 'Passport number is required');
                    else markValid($ppNo);
                }
                if ($ppCtr.length && $ppCtr.is('[required]')) {
                    if (!$ppCtr.val()) markInvalid($ppCtr, 'Please select issuing country');
                    else markValid($ppCtr);
                }
                if ($ppEd.length && $ppEd.is('[required]')) {
                    if (!$ppEd.val()) markInvalid($ppEd, 'Required');
                    else markValid($ppEd);
                }
                if ($ppEm.length && $ppEm.is('[required]')) {
                    if (!$ppEm.val()) markInvalid($ppEm, 'Required');
                    else markValid($ppEm);
                }
                if ($ppEy.length && $ppEy.is('[required]')) {
                    if (!$ppEy.val()) markInvalid($ppEy, 'Required');
                    else markValid($ppEy);
                }
            });

            // Validate child/infant forms
            $('.amadex-passenger-form[data-passenger-type]').each(function () {
                const $form = $(this);
                $form.find('input[required], select[required]').each(function () {
                    const $f = $(this);
                    if (!$f.val() || $f.val().trim() === '') markInvalid($f, 'This field is required');
                    else markValid($f);
                });
            });

            if (!isValid && $firstError) {
                // Shake the first error card
                const $card = $firstError.closest('.amadex-passenger-form-card, .amadex-passenger-form');
                $card.addClass('apv-shake');
                setTimeout(() => $card.removeClass('apv-shake'), 600);

                // Scroll to first error field
                $('html, body').animate({
                    scrollTop: $firstError.closest('.amadex-form-field, .amadex-form-row').offset().top - 120
                }, 350);
            }

            return isValid;
        }

        return true;
    }

    /**
     * Update navigation buttons visibility and state
     */
    function updateNavigationButtons() {
        if (!currentStep) return;

        const isMobile = window.innerWidth <= 767;
        const currentOrder = BOOKING_STEPS[currentStep].order;
        const maxOrder = Math.max(...Object.values(BOOKING_STEPS).map(s => s.order));

        // On desktop: Hide step navigation buttons (user scrolls through all sections)
        // On mobile: Show step navigation buttons (step-by-step flow)
        if (!isMobile) {
            $('#amadex-step-navigation').hide();
            return;
        }

        // Mobile: Show navigation buttons
        $('#amadex-step-navigation').show();

        // Show/hide back button
        if (currentOrder <= 1) {
            $('#amadex-step-back').hide();
        } else {
            $('#amadex-step-back').show();
        }

        // Update next button text: Steps 1–4 = "Continue"; Step 5 (review) = "Confirm & Book" or "Pay securely"
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';
        if (currentOrder >= maxOrder) {
            $('#amadex-step-next').text(gateway === 'stripe' ? 'Pay securely' : 'Confirm & Book');
        } else {
            $('#amadex-step-next').text('Continue');
        }
    }

    /**
     * Update the sticky price bar "Continue" button to match current step (pagination).
     * Steps 1–4: "Continue"; Step 5 (review): "Confirm & Book" or "Pay securely".
     */
    function updatePriceBarContinueButton() {
        const $btn = $('#amadex-mobile-price-bar .amadex-flight-continue-btn, #amadex-price-bar-continue-btn');
        if (!$btn.length || !currentStep || !BOOKING_STEPS[currentStep]) return;
        const gateway = typeof AmadexConfig !== 'undefined' && AmadexConfig.defaultCardGateway
            ? AmadexConfig.defaultCardGateway
            : 'nmi';
        const currentOrder = BOOKING_STEPS[currentStep].order;
        const maxOrder = Math.max(...Object.values(BOOKING_STEPS).map(function (s) { return s.order; }));
        const isLastStep = currentOrder >= maxOrder;
        const newText = isLastStep
            ? (gateway === 'stripe' ? 'Pay securely' : 'Confirm & Book')
            : 'Continue';
        if ($btn.text().trim() === newText) return;
        // Smooth transition: brief fade then update text
        $btn.css('opacity', '0.6');
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                $btn.text(newText).attr('aria-label', isLastStep ? 'Confirm and book' : 'Continue to next step');
                $btn.css('opacity', '1');
            });
        });
    }

    // Update navigation buttons when step changes
    $(document).on('stepChanged', function () {
        updateNavigationButtons();
    });

    /**
     * ========================================
     * STICKY COLLAPSING PRICE BAR (BOTTOM)
     * ========================================
     */

    // Price bar state variable - make it globally accessible to prevent scope issues
    let priceBarExpanded = false;
    // Also set on window for global access
    if (typeof window !== 'undefined') {
        window.priceBarExpanded = false;
    }

    /**
     * Initialize sticky collapsing price bar
     */
    function initStickyPriceBar() {
        // Create price bar HTML if it doesn't exist
        if ($('#amadex-mobile-price-bar').length === 0) {
            $('body').append(`
                <div id="amadex-mobile-price-bar" class="amadex-mobile-price-bar">
                    <div class="amadex-price-bar-compact" id="amadex-price-bar-compact">
                        <div class="amadex-price-bar-total">
                            <span class="amadex-price-bar-label">Total
                            <button type="button" class="amadex-price-bar-toggle" id="amadex-price-bar-toggle" aria-label="Expand price breakdown">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button></span>
                            <span class="amadex-price-bar-value" id="amadex-price-bar-total-value">$0.00</span>
                        </div>
                        <div id="amadex-price-bar-step-next-wrap" class="amadex-price-bar-step-next-wrap"></div>
                    </div>
                    <div class="amadex-price-bar-expanded" id="amadex-price-bar-expanded" style="display: none;">
                        <div class="amadex-price-bar-header">
                            <h4>Price Summary</h4>
                            <button type="button" class="amadex-price-bar-toggle" id="amadex-price-bar-toggle-expanded" aria-label="Collapse price breakdown">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="18 15 12 9 6 15"></polyline>
                                </svg>
                            </button>
                        </div>
                        <div class="amadex-price-bar-breakdown" id="amadex-price-bar-breakdown">
                            <!-- Price breakdown will be populated here -->
                        </div>
                    </div>
                </div>
            `);
        }

        // Single toggle function for expand/collapse
        function togglePriceBar() {
            // Safety check: ensure priceBarExpanded is defined
            if (typeof priceBarExpanded === 'undefined') {
                priceBarExpanded = false;
            }
            if (priceBarExpanded) {
                collapsePriceBar();
            } else {
                expandPriceBar();
            }
        }

        // Click handler for toggle button and compact bar (exclude step-next button so it advances steps)
        $('#amadex-price-bar-toggle, #amadex-price-bar-toggle-expanded, #amadex-price-bar-compact').on('click', function (e) {
            if ($(e.target).closest('#amadex-step-next, .amadex-price-bar-step-next-wrap').length) {
                return; // Step-next lives here on mobile – do not toggle
            }
            e.preventDefault();
            e.stopPropagation();
            togglePriceBar();
        });

        /**
         * On mobile: move #amadex-step-next into the price bar so one button controls the flow.
         * On desktop: keep it in #amadex-step-navigation.
         */
        function placeStepNextInPriceBarOrNav() {
            var $stepNext = $('#amadex-step-next');
            var $wrap = $('#amadex-price-bar-step-next-wrap');
            var $nav = $('#amadex-step-navigation');
            if (!$stepNext.length) return;
            var isMobile = window.innerWidth <= 767;
            if (isMobile && $wrap.length) {
                if ($stepNext.closest('#amadex-price-bar-step-next-wrap').length === 0) {
                    $stepNext.addClass('amadex-step-next-in-price-bar');
                    $wrap.append($stepNext);
                }
            } else if ($nav.length && $stepNext.closest('#amadex-step-navigation').length === 0) {
                $stepNext.removeClass('amadex-step-next-in-price-bar');
                $nav.append($stepNext);
            }
        }
        placeStepNextInPriceBarOrNav();
        $(window).off('resize.amadexPriceBarStepNext').on('resize.amadexPriceBarStepNext', function () {
            var t = setTimeout(function () { placeStepNextInPriceBarOrNav(); clearTimeout(t); }, 100);
        });

        // Close on outside click (optional)
        $(document).off('click.priceBarClose').on('click.priceBarClose', function (e) {
            if ($(e.target).closest('#amadex-mobile-price-bar').length === 0) {
                // Check if priceBarExpanded is defined and true (check both local and window)
                const isExpanded = (typeof priceBarExpanded !== 'undefined' && priceBarExpanded) ||
                    (typeof window.priceBarExpanded !== 'undefined' && window.priceBarExpanded);
                if (isExpanded) {
                    collapsePriceBar();
                }
            }
        });

        // Update price bar when price changes - INSTANT update
        $(document).off('priceUpdated.priceBar').on('priceUpdated.priceBar', function (e, finalTotal, currency) {
            // Update immediately
            updatePriceBar();

            // Also update after a small delay to catch any DOM updates
            setTimeout(function () {
                updatePriceBar();
            }, 100);
        });

        // Initial update
        updatePriceBar();

        // Watch for changes in price breakdown to update price bar INSTANTLY
        // Use MutationObserver to detect when breakdown total changes
        const breakdownObserver = new MutationObserver(function (mutations) {
            // Check if total row was added or modified
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList' || mutation.type === 'characterData') {
                    const $totalRow = $('#amadex-price-breakdown .amadex-price-row.total .amadex-price-value');
                    if ($totalRow.length > 0) {
                        // Update price bar instantly when breakdown changes
                        updatePriceBar();
                    }
                }
            });
        });

        // Observe the price breakdown container for changes
        const $breakdownContainer = $('#amadex-price-breakdown');
        if ($breakdownContainer.length > 0) {
            breakdownObserver.observe($breakdownContainer[0], {
                childList: true,
                subtree: true,
                characterData: true
            });
        }

        // Also watch for changes in the total value element directly
        // This ensures we catch text changes instantly
        function observeTotalValue() {
            const $totalValue = $('#amadex-price-breakdown .amadex-price-row.total .amadex-price-value');
            if ($totalValue.length > 0) {
                const totalValueObserver = new MutationObserver(function () {
                    // Update price bar instantly when total value changes
                    updatePriceBar();
                });

                totalValueObserver.observe($totalValue[0], {
                    childList: true,
                    characterData: true,
                    subtree: true
                });
            }
        }

        // Observe total value immediately and also set up periodic check
        observeTotalValue();

        // Also check periodically in case element is added later
        setInterval(function () {
            const $totalValue = $('#amadex-price-breakdown .amadex-price-row.total .amadex-price-value');
            if ($totalValue.length > 0 && !$totalValue.data('observed')) {
                $totalValue.data('observed', true);
                observeTotalValue();
            }
        }, 500);
    }

    /**
     * Expand price bar
     */
    function expandPriceBar() {
        priceBarExpanded = true;
        if (typeof window !== 'undefined') {
            window.priceBarExpanded = true;
        }
        $('#amadex-price-bar-expanded').slideDown(300);
        // Rotate both toggle arrows to point up (collapse indicator)
        $('#amadex-price-bar-toggle svg, #amadex-price-bar-toggle-expanded svg').css('transform', 'rotate(180deg)');

        // Update breakdown when expanding - ensure it's current and shows nicely
        if (typeof updatePriceBar === 'function') {
            updatePriceBar();
        } else {
            // Fallback: copy breakdown from sidebar
            const sidebarBreakdown = $('#amadex-price-breakdown').html();
            $('#amadex-price-bar-breakdown').html(sidebarBreakdown || '<p>Loading price breakdown...</p>');
        }
    }

    /**
     * Collapse price bar
     */
    function collapsePriceBar() {
        // Ensure priceBarExpanded is defined
        if (typeof priceBarExpanded === 'undefined') {
            priceBarExpanded = false;
        }
        priceBarExpanded = false;
        if (typeof window !== 'undefined') {
            window.priceBarExpanded = false;
        }
        $('#amadex-price-bar-expanded').slideUp(300);
        // Rotate both toggle arrows to point down (expand indicator)
        $('#amadex-price-bar-toggle svg, #amadex-price-bar-toggle-expanded svg').css('transform', 'rotate(0deg)');
    }

    /**
     * Update price bar with current total
     * FIXED: Updates instantly without page refresh
     */
    function updatePriceBar() {
        const $priceBarTotal = $('#amadex-price-bar-total-value');
        const $priceBarBreakdown = $('#amadex-price-bar-breakdown');
        const $breakdownContainer = $('#amadex-price-breakdown');

        if (!$priceBarTotal.length) {
            // Price bar not in DOM yet (e.g. before initStickyPriceBar or on layouts that don't use it)
            return;
        }

        // Update breakdown FIRST (always update breakdown to show all items)
        if ($breakdownContainer.length > 0 && $priceBarBreakdown.length > 0) {
            const sidebarBreakdown = $breakdownContainer.html();
            if (sidebarBreakdown) {
                $priceBarBreakdown.html(sidebarBreakdown);
                // After setting breakdown HTML, extract total from the breakdown we just set
                // This ensures we get the exact total that's displayed in the breakdown
                const $breakdownTotalRow = $priceBarBreakdown.find('.amadex-price-row.total .amadex-price-value');
                if ($breakdownTotalRow.length > 0) {
                    const totalText = $breakdownTotalRow.text().trim();
                    if (totalText) {
                        $priceBarTotal.text(totalText);
                        return; // Success, exit early
                    }
                }
            }
        }

        // Fallback: Get total from the sidebar breakdown (original source)
        const $totalRow = $('#amadex-price-breakdown .amadex-price-row.total .amadex-price-value');

        if ($totalRow.length > 0) {
            // Get the exact text from the total row - this is already formatted correctly
            // Use the exact same text that's displayed in the breakdown
            const totalText = $totalRow.text().trim();

            if (totalText) {
                // Update INSTANTLY without any delay - synchronous update
                $priceBarTotal.text(totalText);
            } else {
            }
        } else {
            // Fallback: try to get from sidebar total if total row not found
            const $sidebarTotal = $('.amadex-price-total .amadex-price-value, .amadex-price-summary-card .amadex-price-row.total .amadex-price-value');
            if ($sidebarTotal.length > 0) {
                const totalText = $sidebarTotal.first().text().trim();
                if (totalText) {
                    $priceBarTotal.text(totalText);
                }
            } else {
                // Last fallback: show $0.00
                $priceBarTotal.text(formatCurrencyValue(0, 'USD'));
            }
        }
    }

    // Make updatePriceBar globally accessible for instant updates
    if (typeof window !== 'undefined') {
        window.updatePriceBar = updatePriceBar;
        // Bridge functions for code outside the main closure (e.g. updateSeatSelectionDisplay)
        window._amadexGetSelectedCurrency = getSelectedCurrency;
        window._amadexFormatCurrencyValue = formatCurrencyValue;
        window._amadexPopulatePriceBreakdown = populatePriceBreakdown;
    }

    // Initialize price bar on document ready
    $(document).ready(function () {
        if ($('#amadex-booking-page').length > 0) {
            initStickyPriceBar();
            initAddonsSystem();
            initReviewPage();
        }
    });

    /**
     * ========================================
     * ADD-ONS SYSTEM
     * ========================================
     */

    // Selected add-ons state
    let selectedAddons = {};

    /**
     * Initialize add-ons system
     */
    function initAddonsSystem() {
        // Load add-ons from sessionStorage or default list
        const savedAddons = sessionStorage.getItem('amadex_booking_addons');
        if (savedAddons) {
            try {
                selectedAddons = JSON.parse(savedAddons);
            } catch (e) {
                selectedAddons = {};
            }
        }

        // Load add-ons from backend (AmadexConfig) - replaces hardcoded array
        // This ensures add-ons added in WordPress admin appear on booking page
        let availableAddons = [];

        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.addons && Array.isArray(AmadexConfig.addons)) {
            availableAddons = AmadexConfig.addons;
        } else {
            // Fallback: empty array if backend add-ons not available
            // This ensures no hardcoded add-ons appear (prevents duplicates)
        }

        renderAddons(availableAddons);
    }

    /**
     * Render add-ons list
     * @param {Array} addons - Array of add-on objects
     */
    function renderAddons(addons) {
        const $container = $('#amadex-addons-list');
        const $empty = $('#amadex-addons-empty');

        if (!addons || addons.length === 0 || !addons.some(a => a.enabled)) {
            $empty.show();
            return;
        }

        $empty.hide();

        // Filter enabled add-ons and sort by displayOrder
        const enabledAddons = addons
            .filter(a => a.enabled)
            .sort((a, b) => (a.displayOrder || 0) - (b.displayOrder || 0));

        // Get selected currency from regional settings
        const selectedCurrency = getSelectedCurrency();
        const originalCurrency = 'USD';
        // const originalCurrency = seat.travelerPricing?.[0]?.price?.currency || 'USD';
        let html = '';
        enabledAddons.forEach(addon => {
            const isSelected = selectedAddons[addon.id] || false;
            // Get price in USD (add-ons are stored in USD)
            const usdPrice = parseFloat(addon.price || 0);

            // Convert price to selected currency (if different from USD)
            let displayPrice = usdPrice;
            if (selectedCurrency !== 'USD' && selectedCurrency !== originalCurrency) {
                // Check if exchange rate is cached
                const cacheKey = `USD_${selectedCurrency}`;
                if (exchangeRates[cacheKey]) {
                    // Use cached rate for immediate conversion
                    displayPrice = usdPrice * exchangeRates[cacheKey];
                } else {
                    // Rate not cached yet - fetch asynchronously and update display
                    getExchangeRate('USD', selectedCurrency).then(function (rate) {
                        if (rate !== 1.0) {
                            const convertedPrice = usdPrice * rate;
                            const $priceElement = $(`.amadex-addon-card[data-addon-id="${addon.id}"] .amadex-addon-price`);
                            if ($priceElement.length) {
                                $priceElement.text(formatCurrencyValue(convertedPrice, selectedCurrency));
                            }
                        }
                    }).catch(function (error) {
                    });
                }
            }

            // Format price with selected currency symbol
            const priceDisplay = formatCurrencyValue(displayPrice, selectedCurrency);

            html += `
                <div class="amadex-addon-card-new ${isSelected ? 'is-selected' : ''}" data-addon-id="${addon.id}" data-price-usd="${usdPrice}">
                    <div class="amadex-addon-card-new__header">
                        <div class="amadex-addon-card-new__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c8790a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>
                        <div class="amadex-addon-card-new__header-text">
                            <h4 class="amadex-addon-card-new__title">${addon.title}</h4>
                            <p class="amadex-addon-card-new__subtitle">Get our ${addon.title} with Only One Click.</p>
                        </div>
                        <button type="button" class="amadex-addon-card-new__btn ${isSelected ? 'is-added' : ''}" data-addon-id="${addon.id}">
                            <input type="checkbox" id="addon-${addon.id}" ${isSelected ? 'checked' : ''} style="display:none">
                            ${isSelected ? 'ADDED ✓' : 'ADD <span class="amadex-addon-card-new__btn-price">' + priceDisplay + '</span>'}
                        </button>
                    </div>
                    <div class="amadex-addon-card-new__body">
                        <div class="amadex-addon-card-new__desc">
                            <p>${addon.description}</p>
                        </div>
                        <div class="amadex-addon-card-new__price-tag amadex-addon-price" data-currency="${selectedCurrency}">
                            ${priceDisplay}
                        </div>
                    </div>
                </div>
            `;
        });

        $container.html(html);

        // Bind click handlers for new card design
        $container.find('.amadex-addon-card-new').on('click', function (e) {
            // Don't trigger card click if clicking the button directly
            if ($(e.target).closest('.amadex-addon-card-new__btn').length) return;
            const addonId = $(this).data('addon-id');
            toggleAddon(addonId, enabledAddons.find(a => a.id === addonId));
        });

        // ADD button click handler
        $container.find('.amadex-addon-card-new__btn').on('click', function (e) {
            e.stopPropagation();
            const addonId = $(this).data('addon-id');
            const addon = enabledAddons.find(a => a.id === addonId);
            toggleAddon(addonId, addon);
        });

        // Also handle hidden checkbox clicks
        $container.find('input[type="checkbox"]').on('change', function (e) {
            e.stopPropagation();
            const addonId = $(this).attr('id').replace('addon-', '');
            const addon = enabledAddons.find(a => a.id === addonId);
            toggleAddon(addonId, addon, !$(this).is(':checked'));
        });
    }

    /**
     * Toggle add-on selection
     * @param {string} addonId - Add-on ID
     * @param {Object} addon - Add-on object
     * @param {boolean} forceState - Force specific state (optional)
     */
    function toggleAddon(addonId, addon, forceState = null) {
        if (!addon) return;

        const currentState = selectedAddons[addonId] || false;
        const newState = forceState !== null ? forceState : !currentState;

        if (newState) {
            selectedAddons[addonId] = {
                id: addon.id,
                title: addon.title,
                price: addon.price,
                currency: addon.currency
            };
        } else {
            delete selectedAddons[addonId];
        }

        // Update UI
        const $card = $(`.amadex-addon-card-new[data-addon-id="${addonId}"]`);
        const $checkbox = $(`#addon-${addonId}`);
        const $btn = $card.find('.amadex-addon-card-new__btn');

        // Get price display for button
        const usdPrice = parseFloat($card.data('price-usd') || 0);
        const selectedCurrency = getSelectedCurrency();
        const priceDisplay = formatCurrencyValue(usdPrice, selectedCurrency);

        if (newState) {
            $card.addClass('is-selected');
            $checkbox.prop('checked', true);
            $btn.addClass('is-added').html('ADDED ✓');
        } else {
            $card.removeClass('is-selected');
            $checkbox.prop('checked', false);
            $btn.removeClass('is-added').html('ADD <span class="amadex-addon-card-new__btn-price">' + priceDisplay + '</span>');
        }

        // Save to sessionStorage
        sessionStorage.setItem('amadex_booking_addons', JSON.stringify(selectedAddons));

        // If this is premium-services, update price breakdown instantly
        if (addonId === 'premium-services') {
            // For premium services, update price breakdown like seat selection does
            const $premiumCheckbox = $('#amadex-addons-section input#addon-premium-services');
            if ($premiumCheckbox.length) {
                // Only update if checkbox state is different to prevent duplicate updates
                const currentCheckboxState = $premiumCheckbox.is(':checked');
                if (currentCheckboxState !== newState) {
                    // Update checkbox state - the change handler will update price breakdown
                    $premiumCheckbox.prop('checked', newState);
                    // Trigger change event which will call populatePriceBreakdown (same as seat selection)
                    $premiumCheckbox.trigger('change.premium');
                } else {
                    // State already matches, update price breakdown directly (like seat selection)
                    const flightData = sessionStorage.getItem('amadex_booking_flight');
                    const searchDataStr = sessionStorage.getItem('amadex_search_data');
                    if (flightData && searchDataStr) {
                        try {
                            const flight = JSON.parse(flightData);
                            const searchData = JSON.parse(searchDataStr);
                            populatePriceBreakdown(flight, searchData, true);
                        } catch (e) {
                        }
                    }
                }
            } else {
                updatePriceWithAddons();
            }
        } else {
            // Update price for other addons - call populatePriceBreakdown directly (same as premium-services and seat selection)
            updatePriceWithAddons();
        }

        // Trigger event
        $(document).trigger('addonsUpdated', [selectedAddons]);
    }

    /**
     * Update price with add-ons
     * Now calls populatePriceBreakdown directly for instant updates (same as premium-services and seat selection)
     */
    function updatePriceWithAddons() {
        // Get flight and search data from sessionStorage
        const flightData = sessionStorage.getItem('amadex_booking_flight');
        const searchDataStr = sessionStorage.getItem('amadex_search_data');

        if (flightData && searchDataStr) {
            try {
                const flight = JSON.parse(flightData);
                const searchData = JSON.parse(searchDataStr);
                // Update price breakdown immediately (same as premium-services and seat selection)
                if (typeof populatePriceBreakdown === 'function') {
                    populatePriceBreakdown(flight, searchData, true);
                }
            } catch (e) {
                // Fallback: trigger price update event
                $(document).trigger('priceUpdated');
            }
        } else {
            // Fallback: trigger price update event if data not available
            $(document).trigger('priceUpdated');
        }
    }

    /**
     * Get selected add-ons total
     * @returns {number} - Total add-ons price
     */
    function getAddonsTotal() {
        let total = 0;
        Object.values(selectedAddons).forEach(addon => {
            total += parseFloat(addon.price || 0);
        });
        return total;
    }

    /**
     * Initialize premium services checkbox handler in addons section
     */
    function initPremiumServicesCheckbox() {
        // Restore checkbox state from sessionStorage on page load
        const savedAddons = sessionStorage.getItem('amadex_booking_addons');
        if (savedAddons) {
            try {
                const selectedAddons = JSON.parse(savedAddons);
                if (selectedAddons['premium-services']) {
                    // Restore checkbox state
                    const $checkbox = $('#amadex-addons-section input#addon-premium-services');
                    if ($checkbox.length) {
                        $checkbox.prop('checked', true);
                        // Also update the card if it exists
                        const $card = $('.amadex-addon-card[data-addon-id="premium-services"]');
                        if ($card.length) {
                            $card.addClass('is-selected');
                        }
                    }
                }
            } catch (e) {
            }
        }

        // Prevent multiple simultaneous updates
        let isUpdatingPremiumService = false;

        // Handle container clicks for premium service card - make entire card clickable
        $(document).off('click.premium-card', '.amadex-addon-card[data-addon-id="premium-services"]').on('click.premium-card', '.amadex-addon-card[data-addon-id="premium-services"]', function (e) {
            // Don't trigger if clicking directly on the checkbox (let checkbox handle it)
            if ($(e.target).is('input[type="checkbox"]') || $(e.target).closest('input[type="checkbox"]').length) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const $checkbox = $('#amadex-addons-section input#addon-premium-services');
            if (!$checkbox.length) {
                return;
            }

            // Toggle checkbox programmatically
            const isChecked = !$checkbox.is(':checked');
            $checkbox.prop('checked', isChecked).trigger('change');
        });

        // Handle checkbox clicks in addons section, specifically for premium-services
        // Use event delegation with a more specific selector to ensure it works
        // Remove any existing handlers first to prevent duplicates
        $(document).off('change.premium', '#amadex-addons-section input#addon-premium-services').on('change.premium', '#amadex-addons-section input#addon-premium-services', function (e) {
            // Don't stop propagation or prevent default - allow normal checkbox behavior
            // Only prevent duplicate updates
            if (isUpdatingPremiumService) {
                e.preventDefault();
                return;
            }

            isUpdatingPremiumService = true;

            const $checkbox = $(this);
            const isChecked = $checkbox.is(':checked');
            const premiumServiceId = 'premium-services';
            const premiumServicePrice = 25.00;
            const currency = 'USD';

            if (isChecked) {
                // Add premium service to selected addons
                selectedAddons[premiumServiceId] = {
                    id: premiumServiceId,
                    title: 'Premium Services',
                    price: premiumServicePrice,
                    currency: currency
                };

                // Also update the premium service button state if it exists
                if ($('#amadex-premium-services-btn').length) {
                    updatePremiumServiceButton(true);
                    sessionStorage.setItem('amadex_premium_service_added', 'true');
                }

                // Update the addon card visual state if it exists - INSTANT UPDATE
                const $card = $('.amadex-addon-card[data-addon-id="premium-services"]');
                if ($card.length) {
                    $card.addClass('is-selected');
                }

            } else {
                // Remove premium service from selected addons
                delete selectedAddons[premiumServiceId];

                // Also update the premium service button state if it exists
                if ($('#amadex-premium-services-btn').length) {
                    updatePremiumServiceButton(false);
                    sessionStorage.removeItem('amadex_premium_service_added');
                }

                // Update the addon card visual state if it exists - INSTANT UPDATE
                const $card = $('.amadex-addon-card[data-addon-id="premium-services"]');
                if ($card.length) {
                    $card.removeClass('is-selected');
                }

            }

            // Save to sessionStorage immediately
            sessionStorage.setItem('amadex_booking_addons', JSON.stringify(selectedAddons));

            // Update price breakdown the same way seat selection does
            // This ensures consistent behavior - call populatePriceBreakdown like seat selection
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            const searchDataStr = sessionStorage.getItem('amadex_search_data');

            if (flightData && searchDataStr) {
                try {
                    const flight = JSON.parse(flightData);
                    const searchData = JSON.parse(searchDataStr);
                    // Update price breakdown immediately (same as seat selection)
                    populatePriceBreakdown(flight, searchData, true);
                } catch (e) {
                    // Fallback: trigger price update event
                    $(document).trigger('priceUpdated');
                }
            } else {
                // Fallback: trigger price update event
                $(document).trigger('priceUpdated');
            }

            // Reset update flag after a short delay
            setTimeout(function () {
                isUpdatingPremiumService = false;
            }, 300);
        });

        // Prevent multiple simultaneous price breakdown updates
        let isUpdatingPriceBreakdown = false;

        /**
         * Update price breakdown INSTANTLY for premium service
         * This ensures smooth, immediate updates without any delays
         * Made accessible globally for card clicks
         * FIXED: Prevents blinking by ensuring only one update at a time
         */
        window.updatePremiumServicePriceBreakdownInstantly = function (isChecked, premiumServicePrice) {
            // Prevent duplicate updates
            if (isUpdatingPriceBreakdown) {
                return;
            }

            isUpdatingPriceBreakdown = true;

            const $breakdown = $('#amadex-price-breakdown');
            if (!$breakdown.length) {
                isUpdatingPriceBreakdown = false;
                // Try full recalculation as fallback
                tryFullPriceRecalculation();
                return;
            }

            // Get current values from existing breakdown
            let basePrice = 0, taxesAndFees = 0, seatCharges = 0, discount = 0;

            $breakdown.find('.amadex-price-row').each(function () {
                const $row = $(this);
                // Skip addon, premium-service, and total rows when reading base values
                if ($row.hasClass('addon') || $row.hasClass('premium-service') || $row.hasClass('total')) {
                    return;
                }

                const label = $row.find('.amadex-price-label').text().toLowerCase();
                const valueText = $row.find('.amadex-price-value').text().replace(/[^0-9.-]/g, '');
                const value = parseFloat(valueText) || 0;

                if (label.includes('base') || label.includes('fare')) {
                    basePrice = value;
                } else if (label.includes('tax') || label.includes('fee')) {
                    taxesAndFees = value;
                } else if (label.includes('seat')) {
                    seatCharges = value;
                } else if (label.includes('discount')) {
                    discount = value;
                }
            });

            // Calculate addons total from sessionStorage (most up-to-date)
            let addonsTotal = 0;
            const savedAddons = sessionStorage.getItem('amadex_booking_addons');
            if (savedAddons) {
                try {
                    const currentSelectedAddons = JSON.parse(savedAddons);
                    Object.values(currentSelectedAddons).forEach(addon => {
                        addonsTotal += parseFloat(addon.price || 0);
                    });
                } catch (e) {
                }
            }

            // Remove ALL existing premium service rows FIRST (no animation to prevent blinking)
            $breakdown.find('.amadex-price-row.premium-service, .amadex-price-row.addon.premium-service').each(function () {
                const $row = $(this);
                const label = $row.find('.amadex-price-label').text().toLowerCase();
                if (label.includes('premium service')) {
                    $row.stop(true, true).remove(); // Stop any animations and remove instantly
                }
            });

            // Add premium service row if checked
            if (isChecked && addonsTotal > 0) {
                const addonRow = $(`
                    <div class="amadex-price-row addon premium-service">
                        <div class="amadex-price-info">
                            <span class="amadex-price-label">Premium Service Addon</span>
                            <span class="amadex-price-subtext">TravelayGent™</span>
                        </div>
                        <span class="amadex-price-value">${formatCurrencyValue(addonsTotal, 'USD')}</span>
                    </div>
                `);

                // Insert before total row
                const $totalRow = $breakdown.find('.amadex-price-row.total');
                if ($totalRow.length) {
                    $totalRow.before(addonRow);
                    // Show instantly without fade to prevent blinking
                    addonRow.show();
                } else {
                    $breakdown.append(addonRow);
                    addonRow.show();
                }
            } else {
            }

            // Calculate and update total INSTANTLY
            const finalTotal = basePrice + taxesAndFees;
            const finalTotalWithExtras = finalTotal + seatCharges + addonsTotal - discount;

            const $totalRow = $breakdown.find('.amadex-price-row.total');
            if ($totalRow.length) {
                const $totalValue = $totalRow.find('.amadex-price-value');
                $totalValue.text(formatCurrencyValue(finalTotalWithExtras, 'USD'));
                // Highlight animation
                $totalValue.addClass('updating');
                setTimeout(function () {
                    $totalValue.removeClass('updating');
                }, 500);
            }

            // Update mobile price bar if exists
            const $mobilePriceBar = $('#amadex-price-bar-breakdown');
            if ($mobilePriceBar.length) {
                $mobilePriceBar.html($breakdown.html());
            }

            const $mobileTotal = $('#amadex-price-bar-total-value');
            if ($mobileTotal.length) {
                $mobileTotal.text(formatCurrencyValue(finalTotalWithExtras, 'USD'));
            }

            // Reset update flag
            isUpdatingPriceBreakdown = false;

            // Trigger priceUpdated event
            $(document).trigger('priceUpdated', [finalTotalWithExtras, 'USD']);

            // Do full recalculation in background (non-blocking, with delay to prevent conflicts)
            setTimeout(function () {
                tryFullPriceRecalculation();
            }, 200);
        }

        /**
         * Try full price recalculation if flight data is available
         */
        function tryFullPriceRecalculation() {
            const flightData = sessionStorage.getItem('amadex_booking_flight');
            const searchDataStr = sessionStorage.getItem('amadex_search_data');

            if (flightData && searchDataStr) {
                try {
                    const flight = JSON.parse(flightData);
                    const searchData = JSON.parse(searchDataStr);
                    // Small delay to let instant update show first
                    setTimeout(function () {
                        populatePriceBreakdown(flight, searchData, true);
                    }, 100);
                } catch (e) {
                }
            }
        }

        /**
         * Legacy function for backward compatibility
         */
        function updatePriceBreakdownManually() {
            const $breakdown = $('#amadex-price-breakdown');
            if (!$breakdown.length) {
                return;
            }

            // Get current base price and taxes from existing breakdown
            let basePrice = 0;
            let taxesAndFees = 0;
            let seatCharges = 0;
            let discount = 0;

            $breakdown.find('.amadex-price-row').each(function () {
                const $row = $(this);
                const label = $row.find('.amadex-price-label').text().toLowerCase();
                const valueText = $row.find('.amadex-price-value').text().replace(/[^0-9.-]/g, '');
                const value = parseFloat(valueText) || 0;

                // Skip addon rows and total row when calculating base values
                if ($row.hasClass('addon') || $row.hasClass('premium-service') || $row.hasClass('total')) {
                    return;
                }

                if (label.includes('base') || label.includes('fare')) {
                    basePrice = value;
                } else if (label.includes('tax') || label.includes('fee')) {
                    taxesAndFees = value;
                } else if (label.includes('seat')) {
                    seatCharges = value;
                } else if (label.includes('discount')) {
                    discount = value;
                }
            });

            // Calculate addons total from selectedAddons (read from sessionStorage to ensure latest)
            let addonsTotal = 0;
            const savedAddons = sessionStorage.getItem('amadex_booking_addons');
            if (savedAddons) {
                try {
                    const currentSelectedAddons = JSON.parse(savedAddons);
                    Object.values(currentSelectedAddons).forEach(addon => {
                        addonsTotal += parseFloat(addon.price || 0);
                    });
                } catch (e) {
                }
            }

            // Calculate final total
            const finalTotal = basePrice + taxesAndFees;
            const finalTotalWithExtras = finalTotal + seatCharges + addonsTotal - discount;

            // Find and update/remove premium service row - INSTANT UPDATE
            let addonRowExists = false;
            // Find ALL premium service rows (multiple selectors to catch all variations)
            const $premiumRows = $breakdown.find('.amadex-price-row.addon.premium-service, .amadex-price-row.premium-service, .amadex-price-row.addon');

            $premiumRows.each(function () {
                const $row = $(this);
                const label = $row.find('.amadex-price-label').text().toLowerCase();
                // Check if this is a premium service row
                if (label.includes('premium service') || $row.hasClass('premium-service')) {
                    if (addonsTotal > 0) {
                        // Update existing row instantly
                        $row.find('.amadex-price-value').text(formatCurrencyValue(addonsTotal, 'USD'));
                        addonRowExists = true;
                        // Show row if hidden - INSTANT
                        $row.show();
                    } else {
                        // Remove row if unchecked - INSTANT removal (no fade, immediate)
                        $row.remove();
                    }
                }
            });

            // Add addon row if it doesn't exist and is checked - INSTANT addition
            if (addonsTotal > 0 && !addonRowExists) {
                const addonRow = $(`
                    <div class="amadex-price-row addon premium-service">
                        <div class="amadex-price-info">
                            <span class="amadex-price-label">Premium Service Addon</span>
                            <span class="amadex-price-subtext">TravelayGent™</span>
                        </div>
                        <span class="amadex-price-value">${formatCurrencyValue(addonsTotal, 'USD')}</span>
                    </div>
                `);
                // Insert before total row - INSTANT insertion
                const $totalRow = $breakdown.find('.amadex-price-row.total');
                if ($totalRow.length) {
                    $totalRow.before(addonRow);
                    // Show instantly with a subtle animation
                    addonRow.hide().fadeIn(150);
                } else {
                    $breakdown.append(addonRow);
                    addonRow.hide().fadeIn(150);
                }
            }

            // Update total row INSTANTLY
            const $totalRow = $breakdown.find('.amadex-price-row.total');
            if ($totalRow.length) {
                const $totalValue = $totalRow.find('.amadex-price-value');
                $totalValue.text(formatCurrencyValue(finalTotalWithExtras, 'USD'));
                // Add highlight animation
                $totalValue.addClass('updating');
                setTimeout(function () {
                    $totalValue.removeClass('updating');
                }, 500);
            }

            // Also update mobile price bar if it exists
            const $mobilePriceBar = $('#amadex-price-bar-breakdown');
            if ($mobilePriceBar.length) {
                // Update mobile price bar breakdown
                const mobileBreakdown = $breakdown.html();
                $mobilePriceBar.html(mobileBreakdown || '<p>Loading price breakdown...</p>');
            }

            // Update mobile price bar total value
            const $mobileTotal = $('#amadex-price-bar-total-value');
            if ($mobileTotal.length) {
                $mobileTotal.text(formatCurrencyValue(finalTotalWithExtras, 'USD'));
            }

            // Trigger priceUpdated event for other components
            $(document).trigger('priceUpdated', [finalTotalWithExtras, 'USD']);

        }

        // Also handle any checkbox in the addons section (for general addon handling)
        // Use off() first to prevent duplicate handlers
        $(document).off('change', '#amadex-addons-section input[type="checkbox"]').on('change', '#amadex-addons-section input[type="checkbox"]', function (e) {
            const checkboxId = $(this).attr('id');

            // Skip if it's the premium services checkbox (already handled above)
            if (checkboxId === 'addon-premium-services') {
                return;
            }

            e.stopPropagation();

            // Handle other addon checkboxes
            const addonId = checkboxId.replace('addon-', '');
            const isChecked = $(this).is(':checked');

            // Try to find the addon in the enabled addons list
            const savedAddons = sessionStorage.getItem('amadex_booking_addons');
            let enabledAddons = [];

            if (savedAddons) {
                try {
                    const parsed = JSON.parse(savedAddons);
                    // This is a fallback - the main addon system should handle this
                } catch (e) {
                }
            }

            // If we can't find the addon, create a basic entry
            if (isChecked && !selectedAddons[addonId]) {
                selectedAddons[addonId] = {
                    id: addonId,
                    title: 'Add-on',
                    price: 0,
                    currency: 'USD'
                };
            } else if (!isChecked) {
                delete selectedAddons[addonId];
            }

            // Save to sessionStorage
            sessionStorage.setItem('amadex_booking_addons', JSON.stringify(selectedAddons));

            // Update price
            updatePriceWithAddons();
        });
    }

    /**
     * ========================================
     * REVIEW PAGE
     * ========================================
     */

    /**
     * Initialize review page
     */
    function initReviewPage() {
        // Bind step link handlers
        $(document).on('click', '.amadex-step-link', function (e) {
            e.preventDefault();
            const stepName = $(this).data('step');
            if (stepName && BOOKING_STEPS[stepName]) {
                navigateToStep(stepName);
            }
        });

        // Update review content when step changes to review.
        // Must be delayed > 100ms so it fires AFTER navigateToStep's own
        // updateReviewContent() call, not before — otherwise the immediate
        // call here reads stale DOM values and overwrites the correct ones.
        $(document).on('stepChanged', function (e, stepName) {
            if (stepName === 'review') {
                clearTimeout(window._amadexReviewStepTimeout);
                window._amadexReviewStepTimeout = setTimeout(function () {
                    updateReviewContent();
                }, 350);
            }
        });

        // ── GA4 view_item: fires once per review-step entry ──────────────────
        // Pushes a GA4 ecommerce view_item event when the user reaches the
        // "Check your flights" review step. A per-page-load flag prevents
        // duplicate pushes if the user navigates back and forward.
        function _amadexFireGA4ViewItem() {
            if (window._amadexGA4ViewItemFired) return;
            window._amadexGA4ViewItemFired = true;

            try {
                // ── 1. Load raw data ─────────────────────────────────────
                var flightRaw = sessionStorage.getItem('amadex_booking_flight');
                if (!flightRaw) return;
                var fl = JSON.parse(flightRaw);

                var sd = window.amadexSearchData || {};

                // ── 2. Offer ID ──────────────────────────────────────────
                var offerId = (fl.rawOffer && fl.rawOffer.id) || fl.id || fl.offerId || '';

                // ── 3. Airline code + name ───────────────────────────────
                var airlineCode = (fl.validatingAirlineCodes && fl.validatingAirlineCodes[0]) ||
                    (fl.validating_airline_codes && fl.validating_airline_codes[0]) || '';
                var airlineName = (typeof getAirlineName === 'function')
                    ? getAirlineName(airlineCode)
                    : airlineCode;

                // ── 4. Route: origin, destination, dates, stops ──────────
                var origin = '';
                var destination = '';
                var depDate = '';
                var retDate = '';
                var stopsCount = 0;

                if (fl.itineraries && fl.itineraries[0] && fl.itineraries[0].segments) {
                    var segs = fl.itineraries[0].segments;
                    var firstSeg = segs[0];
                    var lastSeg = segs[segs.length - 1];

                    origin = (firstSeg.departure && (firstSeg.departure.iataCode || firstSeg.departure.iata_code || firstSeg.departure.code)) || '';
                    destination = (lastSeg.arrival && (lastSeg.arrival.iataCode || lastSeg.arrival.iata_code || lastSeg.arrival.code)) || '';
                    stopsCount = segs.length - 1;

                    if (firstSeg.departure && firstSeg.departure.at) {
                        depDate = firstSeg.departure.at.split('T')[0];
                    }
                }

                // Fallbacks from searchData
                if (!origin) origin = sd.origin || sd.from || '';
                if (!destination) destination = sd.destination || sd.to || '';
                if (!depDate) depDate = sd.departure_date || sd.departureDate || '';

                // Return date — itinerary leg 2 or searchData
                if (fl.itineraries && fl.itineraries[1] && fl.itineraries[1].segments) {
                    var retSegs = fl.itineraries[1].segments;
                    var retFirstSeg = retSegs[0];
                    if (retFirstSeg && retFirstSeg.departure && retFirstSeg.departure.at) {
                        retDate = retFirstSeg.departure.at.split('T')[0];
                    }
                }
                if (!retDate) retDate = sd.return_date || sd.returnDate || '';

                // ── 5. Cabin class ───────────────────────────────────────
                var cabinRaw = sd.cabin || sd.travel_class || 'ECONOMY';
                var cabinName = (typeof getCabinClassName === 'function')
                    ? getCabinClassName(cabinRaw)
                    : (cabinRaw.charAt(0).toUpperCase() + cabinRaw.slice(1).toLowerCase());

                // ── 6. Trip type ─────────────────────────────────────────
                var tripType = (sd.trip_type || sd.tripType || 'round_trip')
                    .toLowerCase()
                    .replace(/\s+/g, '_')
                    .replace(/^round$/, 'round_trip')
                    .replace(/^oneway$/, 'one_way');

                // ── 7. Passenger counts ──────────────────────────────────
                var adults = parseInt(sd.adults || fl.originalAdults || 1);
                var children = parseInt(sd.children || fl.originalChildren || 0);
                var infants = parseInt(sd.infants || fl.originalInfants || 0);
                var travelerCount = adults + children + infants;

                // ── 8. Price (marked-up total → per-pax) ─────────────────
                // Priority: pricing_charge_total (rules engine) → total → grandTotal
                var rawPrice = parseFloat(
                    (fl.price && fl.price.pricing_charge_total) ||
                    (fl.price && fl.price.total) ||
                    (fl.price && fl.price.grandTotal) ||
                    fl.totalPrice || 0
                ) || 0;

                var totalValue = parseFloat(rawPrice.toFixed(2));
                var perPaxPrice = travelerCount > 0
                    ? parseFloat((rawPrice / travelerCount).toFixed(2))
                    : totalValue;

                // ── 9. Currency ──────────────────────────────────────────
                var currency = (fl.price && (fl.price.selected_currency || fl.price.currency)) ||
                    sd.currency || 'USD';
                currency = currency.toUpperCase();

                // ── 10. item_id: aggregated carrier_origin_destination ────
                var itemId = airlineCode + '_' + origin + '_' + destination;

                // ── 11. item_name: human-readable route ──────────────────
                var itemName = origin + ' \u2192 ' + destination +
                    (airlineName ? ' (' + airlineName + ')' : '');

                // ── 12. Push to dataLayer ────────────────────────────────
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null }); // clear previous ecommerce object (GA4 best practice)
                window.dataLayer.push({
                    event: 'view_item',
                    ecommerce: {
                        currency: currency,
                        value: totalValue,
                        items: [{
                            item_id: itemId,
                            item_name: itemName,
                            item_category: 'Flights',
                            item_brand: airlineCode,
                            item_variant: cabinName,
                            price: perPaxPrice,
                            quantity: travelerCount,
                            origin: origin,
                            destination: destination,
                            start_date: depDate,
                            end_date: retDate,
                            trip_type: tripType,
                            cabin_class: cabinRaw.toLowerCase(),
                            stops_count: stopsCount,
                            traveler_count: travelerCount,
                            adults: adults,
                            children: children,
                            infants: infants,
                            itinerary_id: offerId
                        }]
                    }
                });

                // Debug log — visible in DevTools Console for GTM DebugView validation
                if (typeof console !== 'undefined' && console.log) {
                    console.log('[Amadex GA4] view_item pushed', window.dataLayer[window.dataLayer.length - 1]);
                }
            } catch (err) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[Amadex GA4] view_item push failed:', err);
                }
            }
        }

        // Fires on any step — once per page load, as soon as flight data is available
        $(document).on('amadexBookingStepChanged', function (e, stepName) {
            _amadexFireGA4ViewItem();
        });

        $(document).on('stepChanged', function (e, stepName) {
            _amadexFireGA4ViewItem();
        });

        // Fire immediately on page load after flight data is ready
        setTimeout(function () {
            _amadexFireGA4ViewItem();
        }, 1000);
        // ── end GA4 view_item ─────────────────────────────────────────────

        // Update review passengers section when passenger data changes (if review section is visible)
        $(document).on('input blur change',
            '#amadex-section-passengers input[id*="firstname"], ' +
            '#amadex-section-passengers input[id*="lastname"], ' +
            '#amadex-section-passengers input[id*="middlename"], ' +
            '#amadex-section-passengers select[id*="gender"], ' +
            '#amadex-section-passengers input[name*="gender"], ' +
            '#amadex-section-passengers select[id*="dob-day"], ' +
            '#amadex-section-passengers select[id*="dob-month"], ' +
            '#amadex-section-passengers select[id*="dob-year"], ' +
            '#amadex-section-passengers select[id*="nationality"], ' +
            '#amadex-section-passengers input[id*="passport-no"], ' +
            '#amadex-section-passengers select[id*="passport-country"], ' +
            '#amadex-section-passengers select[id*="passport-exp-day"], ' +
            '#amadex-section-passengers select[id*="passport-exp-month"], ' +
            '#amadex-section-passengers select[id*="passport-exp-year"]',
            function () {
                // Update step lock state whenever a passenger field changes
                updateStepLockState();

                // Only update if review section is currently visible
                if ($('#amadex-review-section').is(':visible') || $('#amadex-review-passengers').length) {
                    // Debounce the update to avoid too many calls
                    clearTimeout(window.amadexReviewUpdateTimeout);
                    window.amadexReviewUpdateTimeout = setTimeout(function () {
                        updateReviewPassengers();
                    }, 300);
                }
            }
        );

        // Set initial lock state on page load
        updateStepLockState();
    }

    /**
     * Update review page content
     */
    function updateReviewContent() {
        // Flight itinerary removed - already shown in "Review your flight details" section
        // No need to update itinerary (duplicate removed)

        // Update passengers
        updateReviewPassengers();

        // Update seats
        updateReviewSeats();

        // Update add-ons
        updateReviewAddons();

        // Update price (already shown in price bar, but ensure it's updated)
        const $priceContent = $('#amadex-review-price-content');
        const sidebarBreakdown = $('#amadex-price-breakdown').html();
        if (sidebarBreakdown) {
            $priceContent.html(sidebarBreakdown);
        }
    }

    /**
     * Update review itinerary section
     * NOTE: Flight itinerary is no longer shown in review section (duplicate removed)
     * It's already visible in #amadex-section-flights section
     */
    function updateReviewItinerary() {
        // No-op: Flight itinerary removed from review section to avoid duplication
        return;
    }

    /**
     * Update review passengers section
     * Fetches passenger data from #amadex-section-passengers and displays in review section
     */
    function updateReviewPassengers() {
        const $container = $('#amadex-review-passengers-content');
        const $passengerSection = $('#amadex-section-passengers');

        // Return early if passenger section doesn't exist
        if (!$passengerSection.length || !$container.length) {
            return;
        }

        let html = '<ul class="amadex-review-list">';
        let hasPassengers = false;

        // Helper function to get month name
        function getMonthName(monthNum) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[parseInt(monthNum) - 1] || monthNum;
        }

        // Helper function to get gender text
        function getGenderText(gender) {
            if (!gender) return '';
            return gender === 'M' ? 'Male' : gender === 'F' ? 'Female' : gender;
        }

        // Helper function to get nationality name
        function getNationalityName(code) {
            if (!code) return '';
            // Use AMADEX_COUNTRY_LIST directly to avoid matching passport-country selects
            if (typeof AMADEX_COUNTRY_LIST !== 'undefined') {
                const country = AMADEX_COUNTRY_LIST.find(c => c.code === code);
                if (country) return country.name;
            }
            // Fallback: find only the nationality select (not passport-country)
            const $select = $passengerSection.find('select[id$="-nationality"]').first();
            const $option = $select.find(`option[value="${code}"]`);
            return $option.length ? $option.text() : code;
        }

        // Process Adult passengers (amadex-passenger-form-card)
        $passengerSection.find('.amadex-passenger-form-card').each(function () {
            const $form = $(this);
            const firstName = $form.find('input[id*="firstname"]').val() || '';
            const lastName = $form.find('input[id*="lastname"]').val() || '';
            const middleName = $form.find('input[id*="middlename"]').val() || '';

            // Skip if no name entered
            if (!firstName && !lastName) {
                return;
            }

            // Get gender (radio buttons for adults)
            let gender = '';
            const $genderRadio = $form.find('input[name*="gender"]:checked');
            if ($genderRadio.length) {
                gender = $genderRadio.val() || '';
            }

            // Get date of birth
            const dobDay = $form.find('select[id*="dob-day"]').val() || '';
            const dobMonth = $form.find('select[id*="dob-month"]').val() || '';
            const dobYear = $form.find('select[id*="dob-year"]').val() || '';
            let dobDisplay = '';
            if (dobDay && dobMonth && dobYear) {
                dobDisplay = `${dobDay} ${getMonthName(dobMonth)} ${dobYear}`;
            }

            // Get nationality
            const nationality = $form.find('select[id*="nationality"]').val() || '';
            const nationalityName = getNationalityName(nationality);

            // Get passport details
            const passportNo = $form.find('input[id*="passport-no"]').val() || '';
            const passportCountry = $form.find('select[id*="passport-country"]').val() || '';
            const passportExpDay = $form.find('select[id*="passport-exp-day"]').val() || '';
            const passportExpMonth = $form.find('select[id*="passport-exp-month"]').val() || '';
            const passportExpYear = $form.find('select[id*="passport-exp-year"]').val() || '';

            let passportDisplay = '';
            if (passportNo) {
                passportDisplay = `Passport: ${passportNo}`;
                if (passportExpDay && passportExpMonth && passportExpYear) {
                    passportDisplay += ` (Exp: ${passportExpDay} ${getMonthName(passportExpMonth)} ${passportExpYear})`;
                }
            }

            // Build full name
            const fullName = [firstName, middleName, lastName].filter(Boolean).join(' ');

            // Build table rows
            let details = [];
            details.push('Adult');
            if (dobDisplay) details.push(`DOB: ${dobDisplay}`);
            if (gender) details.push(getGenderText(gender));
            if (nationalityName) details.push(`Nationality: ${nationalityName}`);
            if (passportDisplay) details.push(passportDisplay);

            const adultRows = [
                { label: 'Type', value: 'Adult' },
                gender ? { label: 'Gender', value: getGenderText(gender) } : null,
                dobDisplay ? { label: 'Date of Birth', value: dobDisplay } : null,
                nationalityName ? { label: 'Nationality', value: nationalityName } : null,
                middleName ? { label: 'Middle Name', value: middleName } : null,
                passportNo ? { label: 'Passport No.', value: passportNo } : null,
                passportCountry ? { label: 'Issuing Country', value: getNationalityName(passportCountry) || passportCountry } : null,
                (passportExpDay && passportExpMonth && passportExpYear)
                    ? { label: 'Passport Expiry', value: `${passportExpDay} ${getMonthName(passportExpMonth)} ${passportExpYear}` } : null,
            ].filter(Boolean);

            html += `<li class="amadex-review-passenger-item">
                <div class="amadex-review-passenger-name"><strong>${fullName}</strong></div>
                <table class="amadex-review-passenger-table">
                    ${adultRows.map(r => `<tr><td class="amadex-rpt-label">${r.label}</td><td class="amadex-rpt-value">${r.value}</td></tr>`).join('')}
                </table>
            </li>`;
            hasPassengers = true;
        });

        // Process Child and Infant passengers (amadex-passenger-form)
        $passengerSection.find('.amadex-passenger-form').each(function () {
            const $form = $(this);
            const passengerType = $form.data('passenger-type') || 'child';
            const firstName = $form.find('input[id*="firstname"]').val() || '';
            const lastName = $form.find('input[id*="lastname"]').val() || '';
            const middleName = $form.find('input[id*="middlename"]').val() || '';

            // Skip if no name entered
            if (!firstName && !lastName) {
                return;
            }

            // Get gender (select dropdown for children/infants)
            const gender = $form.find('select[id*="gender"]').val() || '';

            // Get date of birth
            const dobDay = $form.find('select[id*="dob-day"]').val() || '';
            const dobMonth = $form.find('select[id*="dob-month"]').val() || '';
            const dobYear = $form.find('select[id*="dob-year"]').val() || '';
            let dobDisplay = '';
            if (dobDay && dobMonth && dobYear) {
                dobDisplay = `${dobDay} ${getMonthName(dobMonth)} ${dobYear}`;
            }

            // Get nationality (if exists)
            const nationality = $form.find('select[id*="nationality"]').val() || '';
            const nationalityName = getNationalityName(nationality);

            // Get passport details (if exists)
            const passportNo = $form.find('input[id*="passport-no"]').val() || '';
            const passportCountry = $form.find('select[id*="passport-country"]').val() || '';
            const passportExpDay = $form.find('select[id*="passport-exp-day"]').val() || '';
            const passportExpMonth = $form.find('select[id*="passport-exp-month"]').val() || '';
            const passportExpYear = $form.find('select[id*="passport-exp-year"]').val() || '';

            let passportDisplay = '';
            if (passportNo) {
                passportDisplay = `Passport: ${passportNo}`;
                if (passportExpDay && passportExpMonth && passportExpYear) {
                    passportDisplay += ` (Exp: ${passportExpDay} ${getMonthName(passportExpMonth)} ${passportExpYear})`;
                }
            }

            // Build full name
            const fullName = [firstName, middleName, lastName].filter(Boolean).join(' ');

            // Build table rows
            let details = [];
            const typeLabel = passengerType.charAt(0).toUpperCase() + passengerType.slice(1);
            details.push(typeLabel);
            if (dobDisplay) details.push(`DOB: ${dobDisplay}`);
            if (gender) details.push(getGenderText(gender));
            if (nationalityName) details.push(`Nationality: ${nationalityName}`);
            if (passportDisplay) details.push(passportDisplay);

            const childRows = [
                { label: 'Type', value: typeLabel },
                gender ? { label: 'Gender', value: getGenderText(gender) } : null,
                dobDisplay ? { label: 'Date of Birth', value: dobDisplay } : null,
                nationalityName ? { label: 'Nationality', value: nationalityName } : null,
                passportNo ? { label: 'Passport No.', value: passportNo } : null,
                passportCountry ? { label: 'Issuing Country', value: getNationalityName(passportCountry) || passportCountry } : null,
                (passportExpDay && passportExpMonth && passportExpYear)
                    ? { label: 'Passport Expiry', value: `${passportExpDay} ${getMonthName(passportExpMonth)} ${passportExpYear}` } : null,
            ].filter(Boolean);

            html += `<li class="amadex-review-passenger-item">
                <div class="amadex-review-passenger-name"><strong>${fullName}</strong></div>
                <table class="amadex-review-passenger-table">
                    ${childRows.map(r => `<tr><td class="amadex-rpt-label">${r.label}</td><td class="amadex-rpt-value">${r.value}</td></tr>`).join('')}
                </table>
            </li>`;
            hasPassengers = true;
        });

        html += '</ul>';

        // Display message if no passengers found
        if (!hasPassengers) {
            $container.html('<p class="amadex-review-empty">No passenger details available. Please fill in passenger information.</p>');
        } else {
            $container.html(html);
        }
    }

    /**
     * Update review seats section
     */
    function updateReviewSeats() {
        const $container = $('#amadex-review-seats-content');

        if (window.AmadexSeatSelection && window.AmadexSeatSelection.selectedSeats) {
            const selectedSeats = window.AmadexSeatSelection.selectedSeats;
            let html = '<ul class="amadex-review-list">';
            let hasSeats = false;

            Object.keys(selectedSeats).forEach(segmentId => {
                const segmentSeats = selectedSeats[segmentId];
                Object.keys(segmentSeats).forEach(travelerId => {
                    const seat = segmentSeats[travelerId];
                    const routeInfo = window.AmadexSeatSelection.getSegmentRoute ? window.AmadexSeatSelection.getSegmentRoute(segmentId) : null;
                    const segmentLabel = (routeInfo && routeInfo.route) ? routeInfo.route : `Segment ${segmentId}`;
                    // Get passenger name — fall back to type label then generic
                    let passengerName = '';
                    if (window.AmadexSeatSelection.getPassengerName) {
                        passengerName = window.AmadexSeatSelection.getPassengerName(travelerId);
                    }
                    if (!passengerName && window.AmadexSeatSelection.getPassengerLabel) {
                        passengerName = window.AmadexSeatSelection.getPassengerLabel(travelerId);
                    }
                    if (!passengerName) passengerName = `Traveler ${travelerId}`;
                    html += `<li>
                        <strong>${segmentLabel}</strong>
                        <span>${passengerName}: ${seat.seat_number}</span>
                    </li>`;
                    hasSeats = true;
                });
            });

            html += '</ul>';
            $container.html(hasSeats ? html : '<p>No seats selected.</p>');
        } else {
            $container.html('<p>No seats selected.</p>');
        }
    }

    /**
     * Update review add-ons section
     */
    function updateReviewAddons() {
        const $container = $('#amadex-review-addons-content');

        if (Object.keys(selectedAddons).length > 0) {
            // Get selected currency from regional settings
            const selectedCurrency = getSelectedCurrency();
            const originalCurrency = 'USD'; // Add-ons are stored in USD

            let html = '<ul class="amadex-review-list">';
            Object.values(selectedAddons).forEach(addon => {
                // Get price in USD (add-ons are stored in USD)
                const usdPrice = parseFloat(addon.price || 0);

                // Convert price to selected currency (if different from USD)
                let displayPrice = usdPrice;
                if (selectedCurrency !== 'USD' && selectedCurrency !== originalCurrency) {
                    // Check if exchange rate is cached
                    const cacheKey = `USD_${selectedCurrency}`;
                    if (exchangeRates[cacheKey]) {
                        // Use cached rate for immediate conversion
                        displayPrice = usdPrice * exchangeRates[cacheKey];
                    } else {
                        // Rate not cached yet - fetch asynchronously and update display
                        getExchangeRate('USD', selectedCurrency).then(function (rate) {
                            if (rate !== 1.0) {
                                const convertedPrice = usdPrice * rate;
                                const $addonItem = $container.find(`li:has(strong:contains("${addon.title}"))`);
                                if ($addonItem.length) {
                                    $addonItem.find('span').text(formatCurrencyValue(convertedPrice, selectedCurrency));
                                }
                            }
                        }).catch(function (error) {
                        });
                    }
                }

                // Format price with selected currency symbol
                const priceDisplay = formatCurrencyValue(displayPrice, selectedCurrency);

                html += `<li>
                    <strong>${addon.title}</strong>
                    <span>${priceDisplay}</span>
                </li>`;
            });
            html += '</ul>';
            $container.html(html);
        } else {
            $container.html('<p>No add-ons selected.</p>');
        }
    }

    /* ========================================
       BOOKING PROCESSING MODAL FUNCTIONS
       ======================================== */

    /**
     * Generate unique request hash for backend deduplication
     * @param {Object} flight - Flight data object
     * @param {Object} bookingData - Booking data object
     * @returns {string} Request hash string
     */
    /**
     * Simple MD5 implementation for hash generation (matches backend PHP md5())
     * Lightweight, self-contained, browser-compatible
     */
    function md5(string) {
        function md5_RotateLeft(lValue, iShiftBits) {
            return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
        }
        function md5_AddUnsigned(lX, lY) {
            var lX4, lY4, lX8, lY8, lResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
            if (lX4 & lY4) {
                return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
            }
            if (lX4 | lY4) {
                if (lResult & 0x40000000) {
                    return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                } else {
                    return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                }
            } else {
                return (lResult ^ lX8 ^ lY8);
            }
        }
        function md5_F(x, y, z) {
            return (x & y) | ((~x) & z);
        }
        function md5_G(x, y, z) {
            return (x & z) | (y & (~z));
        }
        function md5_H(x, y, z) {
            return (x ^ y ^ z);
        }
        function md5_I(x, y, z) {
            return (y ^ (x | (~z)));
        }
        function md5_FF(a, b, c, d, x, s, ac) {
            a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_F(b, c, d), x), ac));
            return md5_AddUnsigned(md5_RotateLeft(a, s), b);
        }
        function md5_GG(a, b, c, d, x, s, ac) {
            a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_G(b, c, d), x), ac));
            return md5_AddUnsigned(md5_RotateLeft(a, s), b);
        }
        function md5_HH(a, b, c, d, x, s, ac) {
            a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_H(b, c, d), x), ac));
            return md5_AddUnsigned(md5_RotateLeft(a, s), b);
        }
        function md5_II(a, b, c, d, x, s, ac) {
            a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_I(b, c, d), x), ac));
            return md5_AddUnsigned(md5_RotateLeft(a, s), b);
        }
        function md5_ConvertToWordArray(string) {
            var lWordCount;
            var lMessageLength = string.length;
            var lNumberOfWords_temp1 = lMessageLength + 8;
            var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
            var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
            var lWordArray = Array(lNumberOfWords - 1);
            var lBytePosition = 0;
            var lByteCount = 0;
            while (lByteCount < lMessageLength) {
                lWordCount = (lByteCount - (lByteCount % 4)) / 4;
                lBytePosition = (lByteCount % 4) * 8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
            lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
            lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
            return lWordArray;
        }
        function md5_WordToHex(lValue) {
            var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount;
            for (lCount = 0; lCount <= 3; lCount++) {
                lByte = (lValue >>> (lCount * 8)) & 255;
                WordToHexValue_temp = "0" + lByte.toString(16);
                WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
            }
            return WordToHexValue;
        }
        function md5_Utf8Encode(string) {
            string = string.replace(/\r\n/g, "\n");
            var utftext = "";
            for (var n = 0; n < string.length; n++) {
                var c = string.charCodeAt(n);
                if (c < 128) {
                    utftext += String.fromCharCode(c);
                } else if ((c > 127) && (c < 2048)) {
                    utftext += String.fromCharCode((c >> 6) | 192);
                    utftext += String.fromCharCode((c & 63) | 128);
                } else {
                    utftext += String.fromCharCode((c >> 12) | 224);
                    utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                    utftext += String.fromCharCode((c & 63) | 128);
                }
            }
            return utftext;
        }
        var x = Array();
        var k, AA, BB, CC, DD, a, b, c, d;
        var S11 = 7, S12 = 12, S13 = 17, S14 = 22;
        var S21 = 5, S22 = 9, S23 = 14, S24 = 20;
        var S31 = 4, S32 = 11, S33 = 16, S34 = 23;
        var S41 = 6, S42 = 10, S43 = 15, S44 = 21;
        string = md5_Utf8Encode(string);
        x = md5_ConvertToWordArray(string);
        a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
        for (k = 0; k < x.length; k += 16) {
            AA = a; BB = b; CC = c; DD = d;
            a = md5_FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
            d = md5_FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
            c = md5_FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
            b = md5_FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
            a = md5_FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
            d = md5_FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
            c = md5_FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
            b = md5_FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
            a = md5_FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
            d = md5_FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
            c = md5_FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
            b = md5_FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
            a = md5_FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
            d = md5_FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
            c = md5_FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
            b = md5_FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
            a = md5_GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
            d = md5_GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
            c = md5_GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
            b = md5_GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
            a = md5_GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
            d = md5_GG(d, a, b, c, x[k + 10], S22, 0x2441453);
            c = md5_GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
            b = md5_GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
            a = md5_GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
            d = md5_GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
            c = md5_GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
            b = md5_GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
            a = md5_GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
            d = md5_GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
            c = md5_GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
            b = md5_GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
            a = md5_HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
            d = md5_HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
            c = md5_HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
            b = md5_HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
            a = md5_HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
            d = md5_HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
            c = md5_HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
            b = md5_HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
            a = md5_HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
            d = md5_HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
            c = md5_HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
            b = md5_HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
            a = md5_HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
            d = md5_HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
            c = md5_HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
            b = md5_HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
            a = md5_II(a, b, c, d, x[k + 0], S41, 0xF4292244);
            d = md5_II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
            c = md5_II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
            b = md5_II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
            a = md5_II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
            d = md5_II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
            c = md5_II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
            b = md5_II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
            a = md5_II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
            d = md5_II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
            c = md5_II(c, d, a, b, x[k + 6], S43, 0xA3014314);
            b = md5_II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
            a = md5_II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
            d = md5_II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
            c = md5_II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
            b = md5_II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
            a = md5_AddUnsigned(a, AA);
            b = md5_AddUnsigned(b, BB);
            c = md5_AddUnsigned(c, CC);
            d = md5_AddUnsigned(d, DD);
        }
        return (md5_WordToHex(a) + md5_WordToHex(b) + md5_WordToHex(c) + md5_WordToHex(d)).toLowerCase();
    }

    /**
     * ✅ FIX #3: Enterprise Debounce Function
     * Prevents rapid button clicks and ensures only one execution per time window
     * @param {Function} func - Function to debounce
     * @param {number} wait - Delay in milliseconds (default: 300ms)
     * @param {boolean} immediate - Execute immediately on first call
     * @returns {Function} Debounced function
     */
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const context = this;
            const later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    /**
     * Generate request hash for duplicate prevention
     * ✅ FIXED: Now matches backend exactly (MD5, 10-second precision)
     * Hash is generated once and cached for idempotency
     */
    function generateRequestHash(flight, bookingData) {
        try {
            // ✅ Check if hash already generated for this request (idempotency)
            if (window.amadexCurrentRequestHash) {
                return window.amadexCurrentRequestHash;
            }

            // Create unique hash from:
            // - Flight ID/offer ID
            // - Passenger count
            // - Contact email
            // - Timestamp (rounded to nearest 10 seconds - MATCHES BACKEND)

            const hashData = {
                flight_id: flight?.id || flight?.offerId || flight?.itineraryId || '',
                passengers: bookingData?.passengers?.length || 0,
                email: bookingData?.contact?.email || '',
                timestamp: Math.floor(Date.now() / 10000) * 10 // ✅ FIXED: 10-second precision (matches backend)
            };

            // ✅ FIXED: Use MD5 (matches backend PHP md5())
            const hashString = JSON.stringify(hashData);
            const hash = md5(hashString).substring(0, 32); // ✅ MD5 produces 32-char hex, take first 32

            // ✅ Cache hash for idempotency (reuse on retries)
            window.amadexCurrentRequestHash = hash;

            return hash;
        } catch (error) {
            // Fallback: timestamp-based hash (10-second precision)
            const fallbackHash = 'hash_' + Math.floor(Date.now() / 10000) * 10;
            window.amadexCurrentRequestHash = fallbackHash;
            return fallbackHash;
        }
    }

    /**
     * Global state for booking processing modal
     */
    window.amadexBookingProcessingActive = false;
    window.amadexBookingSubmissionInProgress = false;
    window.amadexPaymentTokenization = {
        isTokenizing: false,
        tokenizationInProgress: false,
        generatedToken: null,
        tokenGeneratedAt: null
    };

    /**
         * Show NMI 3DS bank authentication modal
         * Collect.js renders the bank challenge inside a div — we wrap it in our modal
         */
    function show3DSAuthModal() {
        $('#amadex-3ds-auth-overlay').remove();

        $('body').append(`
            <div id="amadex-3ds-auth-overlay" style="
                position: fixed;
                inset: 0;
                z-index: 999999;
                background: rgba(0,0,0,0.6);
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <div id="amadex-3ds-challenge-area" style="
                    background: #fff;
                    border-radius: 12px;
                    box-shadow: 0 8px 40px rgba(0,0,0,0.3);
                    overflow: hidden;
                    width: 480px;
                    max-width: 95vw;
                    min-height: 100px;
                    max-height: 500px;
                    position: relative;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <div id="amadex-3ds-spinner" style="
                        width: 40px;
                        height: 40px;
                        border: 3px solid #e5e7eb;
                        border-top-color: #0e7d3f;
                        border-radius: 50%;
                        animation: amadex3dsSpin 0.8s linear infinite;
                    "></div>
                </div>
            </div>
            <style>
                @keyframes amadex3dsSpin { to { transform: rotate(360deg); } }
            </style>
        `);

        $('body').css('overflow', 'hidden');

        // Collect.js renders the 3DS challenge into whatever container NMI targets.
        // Watch for NMI to inject content into the page and move it into our modal.
        var checkCount = 0;
        var checkInterval = setInterval(function () {
            checkCount++;
            var $nmiFrame = $('iframe[id*="3ds"], iframe[src*="nmi"], iframe.CollectJS-frame').not('#amadex-3ds-auth-overlay iframe');
            if ($nmiFrame.length) {
                clearInterval(checkInterval);
                $('#amadex-3ds-spinner').hide();
                var $area = $('#amadex-3ds-challenge-area');
                $area.css({
                    'display': 'block',
                    'width': '480px',
                    'max-width': '95vw',
                    'align-items': 'unset',
                    'justify-content': 'unset'
                });
                $nmiFrame.css({
                    width: '100%',
                    height: '600px',
                    border: 'none',
                    display: 'block',
                    'min-height': '600px'
                });
                $area.append($nmiFrame);
            }
            if (checkCount > 60) clearInterval(checkInterval); // Stop after 30s
        }, 500);
    }

    /**
     * Close the 3DS auth modal
     */
    function close3DSAuthModal() {
        $('#amadex-3ds-auth-overlay').fadeOut(300, function () {
            $(this).remove();
        });
        $('body').css('overflow', '');
    }

    /**
     * Show NMI 3DS inline authentication modal
     * Loads the NMI Three Step form-url inside an iframe overlay
     * @param {string} formUrl - The NMI form-url from Three Step Step 1
     */
    function show3DSModal(formUrl) {
        // Remove any existing 3DS modal
        $('#amadex-3ds-modal-overlay').remove();

        var modalHtml = `
            <div id="amadex-3ds-modal-overlay" style="
                position: fixed;
                inset: 0;
                z-index: 999999;
                background: rgba(0,0,0,0.55);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            ">
                <div id="amadex-3ds-modal" style="
                ">
                    <div style="
                        display: none;
                    ">
                    </div>
                    <div style="flex: 1; min-height: 420px; position: relative;">
                        <div id="amadex-3ds-spinner" style="
                            position: absolute;
                            inset: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background: #fff;
                            z-index: 2;
                        ">
                            <div style="
                                width: 36px;
                                height: 36px;
                                border: 3px solid #e5e7eb;
                                border-top-color: #0e7d3f;
                                border-radius: 50%;
                                animation: amadex3dsSpin 0.8s linear infinite;
                            "></div>
                        </div>
                        <iframe
                            id="amadex-3ds-iframe"
                            src="${formUrl}"
                            style="
                                width: 100%;
                                height: 100%;
                                min-height: 420px;
                                border: none;
                                display: block;
                                position: relative;
                                z-index: 1;
                            "
                            allow="payment"
                            sandbox="allow-forms allow-scripts allow-same-origin allow-top-navigation allow-popups"
                        ></iframe>
                    </div>
                </div>
            </div>
            <style>
                @keyframes amadex3dsSpin {
                    to { transform: rotate(360deg); }
                }
            </style>
        `;

        $('body').append(modalHtml);

        // Hide spinner once iframe loads
        $('#amadex-3ds-iframe').on('load', function () {
            $('#amadex-3ds-spinner').fadeOut(200);
        });

        // Prevent body scroll while modal is open
        $('body').css('overflow', 'hidden');

        // Listen for postMessage from NMI iframe (in case NMI sends completion signal)
        window.addEventListener('message', function amadex3dsMessageHandler(event) {
            // NMI may post a message when 3DS completes
            if (event.data && (event.data.type === '3ds-complete' || event.data.amadex3ds === 'complete')) {
                window.removeEventListener('message', amadex3dsMessageHandler);
                close3DSModal();
            }
        });
    }

    /**
     * Close the 3DS modal and restore scroll
     */
    function close3DSModal() {
        $('#amadex-3ds-modal-overlay').fadeOut(300, function () {
            $(this).remove();
        });
        $('body').css('overflow', '');
    }

    /**
     * Show booking processing modal
     * Creates and displays a full-screen modal overlay that prevents user interaction
     * during booking submission. The modal is non-dismissible during processing.
     * 
     * @returns {boolean} True if modal was shown successfully, false otherwise
     */
    function showBookingProcessingModal() {
        // Check if jQuery is available
        if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
            return false;
        }

        // Prevent duplicate calls
        if (window.amadexBookingProcessingActive) {
            return true;
        }

        // Set flag
        window.amadexBookingProcessingActive = true;

        try {
            // Check if modal already exists
            let $overlay = $('#amadex-booking-processing-overlay');

            // If exists, reset to initial state
            if ($overlay.length > 0) {
                resetModalToInitialState($overlay);
            } else {
                // Create modal HTML
                const modalHTML = buildBookingProcessingModalHTML();
                $('body').append(modalHTML);
                $overlay = $('#amadex-booking-processing-overlay');

                if ($overlay.length === 0) {
                    throw new Error('Failed to create modal overlay');
                }
            }

            // Show modal with animation
            setTimeout(function () {
                if ($overlay.length) {
                    $overlay.addClass('show');
                }
            }, 10);

            // Prevent body scroll
            $('body').css('overflow', 'hidden');

            // Announce to screen readers
            announceToScreenReader('Booking is being processed. Please wait.');

            return true;
        } catch (error) {
            window.amadexBookingProcessingActive = false;
            // Fallback: Show alert if modal creation fails
            alert('Processing your booking... Please wait.');
            return false;
        }
    }

    /**
     * Build booking processing modal HTML
     * @returns {string} HTML string for the modal
     */
    function buildBookingProcessingModalHTML() {
        return `
            <div id="amadex-booking-processing-overlay" 
                 class="amadex-booking-processing-overlay" 
                 role="dialog" 
                 aria-modal="true"
                 aria-live="polite"
                 aria-labelledby="processing-title"
                 aria-describedby="processing-message">
                <div class="amadex-booking-processing-modal">
                    <div class="amadex-processing-spinner-container">
                        <div class="amadex-processing-spinner"></div>
                    </div>
                    <div class="amadex-processing-content">
                        <h2 id="processing-title" class="amadex-processing-title">
                            Processing Your Booking
                        </h2>
                        <p id="processing-message" class="amadex-processing-message">
                            Your booking is being confirmed...
                        </p>
                        <p class="amadex-processing-submessage" id="processing-submessage">
                            Generating confirmation number...
                        </p>
                        <div class="amadex-processing-warning">
                            <svg class="amadex-warning-icon" width="20" height="20" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                            </svg>
                            <span>Please do not refresh or close this page</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Reset modal to initial processing state
     * @param {jQuery} $overlay - The modal overlay element
     */
    function resetModalToInitialState($overlay) {
        const $modal = $overlay.find('.amadex-booking-processing-modal');

        // Reset spinner
        $modal.find('.amadex-processing-spinner-container').html('<div class="amadex-processing-spinner"></div>');

        // Reset content
        $('#processing-title').text('Processing Your Booking');
        $('#processing-message').text('Your booking is being confirmed...');
        $('#processing-submessage').text('Generating confirmation number...');

        // Show warning
        $('.amadex-processing-warning').show();

        // Remove close button if exists
        $modal.find('.amadex-modal-close').remove();
    }

    /**
     * Update processing message
     * @param {string} message - Primary message
     * @param {string} submessage - Secondary message
     * @returns {boolean} True if update was successful
     */
    function updateProcessingMessage(message, submessage) {
        try {
            // Validate inputs
            if (typeof message !== 'string' && message !== null) {
                message = 'Processing...';
            }

            const $overlay = $('#amadex-booking-processing-overlay');
            if ($overlay.length === 0) {
                return false;
            }

            const $messageEl = $('#processing-message');
            const $submessageEl = $('#processing-submessage');

            if ($messageEl.length === 0 || $submessageEl.length === 0) {
                return false;
            }

            // Update messages safely
            if (message) {
                $messageEl.text(String(message));
            }
            if (submessage) {
                $submessageEl.text(String(submessage));
            }

            // Update ARIA
            announceToScreenReader(message || 'Processing booking');

            return true;
        } catch (error) {
            return false;
        }
    }

    /**
     * Show success state in modal
     * @param {string} bookingReference - Booking reference number
     * @param {string} message - Success message
     * @returns {boolean} True if success state was shown
     */
    function showBookingSuccessModal(bookingReference, message) {
        const $overlay = $('#amadex-booking-processing-overlay');

        if ($overlay.length === 0) {
            // Fallback: Show alert
            alert('Booking confirmed! Reference: ' + (bookingReference || 'N/A'));
            return false;
        }

        const $modal = $overlay.find('.amadex-booking-processing-modal');
        if ($modal.length === 0) {
            return false;
        }

        // Validate booking reference
        if (!bookingReference || typeof bookingReference !== 'string') {
            bookingReference = 'N/A';
        }

        // Replace spinner with checkmark
        $modal.find('.amadex-processing-spinner-container').html(`
            <div class="amadex-success-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" fill="#0E7D3F"/>
                    <path d="M8 12l2 2 4-4" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        `);

        // Update content
        $('#processing-title').text('Booking Confirmed!');
        $('#processing-message').html(
            (message || 'Your booking has been confirmed.') + '<br>' +
            '<strong style="color: #0E7D3F; font-size: 18px; margin-top: 8px; display: block;">Booking Reference: ' + bookingReference + '</strong>'
        );
        $('#processing-submessage').text('Redirecting to confirmation page...');
        $('.amadex-processing-warning').hide();

        // Announce success
        announceToScreenReader('Booking confirmed. Reference: ' + bookingReference);

        return true;
    }

    /**
     * Show error state in modal
     * @param {string} errorMessage - Error message to display
     * @returns {boolean} True if error state was shown
     */
    function showBookingErrorModal(errorMessage) {
        const $overlay = $('#amadex-booking-processing-overlay');
        const $modal = $overlay.find('.amadex-booking-processing-modal');

        if ($overlay.length === 0 || $modal.length === 0) {
            // Fallback: Show alert
            alert('Booking Error: ' + (errorMessage || 'An error occurred. Please try again.'));
            return false;
        }

        // Replace spinner with error icon
        $modal.find('.amadex-processing-spinner-container').html(`
            <div class="amadex-error-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" fill="#d32f2f"/>
                    <path d="M12 8v4M12 16h.01" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        `);

        // Update content
        $('#processing-title').text('Booking Failed');
        $('#processing-message').text(errorMessage || 'An error occurred. Please try again.');
        $('#processing-submessage').text('Please try again or contact support.');
        $('.amadex-processing-warning').hide();

        // ✅ ALWAYS add close button on error (user must be able to close)
        $modal.find('.amadex-modal-close').remove(); // Remove any existing close button
        $modal.append('<button class="amadex-modal-close" aria-label="Close">&times;</button>');
        $modal.find('.amadex-modal-close').on('click', function () {
            hideBookingProcessingModal();
            // ✅ Reset submission flags on close
            window.amadexBookingSubmissionInProgress = false;
            window.amadexPaymentTokenization.isTokenizing = false;
        });

        // ✅ Allow ESC key to close on error
        $(document).off('keydown.amadex-booking-modal-error');
        $(document).on('keydown.amadex-booking-modal-error', function (e) {
            if (e.key === 'Escape' && $overlay.hasClass('show')) {
                hideBookingProcessingModal();
                // ✅ Reset submission flags on close
                window.amadexBookingSubmissionInProgress = false;
                window.amadexPaymentTokenization.isTokenizing = false;
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // ✅ Allow clicking overlay to close on error (only on error state)
        $overlay.off('click.amadex-error-close');
        $overlay.on('click.amadex-error-close', function (e) {
            if ($(e.target).is($overlay)) {
                hideBookingProcessingModal();
                // ✅ Reset submission flags on close
                window.amadexBookingSubmissionInProgress = false;
                window.amadexPaymentTokenization.isTokenizing = false;
            }
        });

        // Announce error
        announceToScreenReader('Booking failed. ' + (errorMessage || 'An error occurred'));

        return true;
    }

    /**
     * Hide booking processing modal
     */
    function hideBookingProcessingModal() {
        const $overlay = $('#amadex-booking-processing-overlay');

        if ($overlay.length === 0) {
            return;
        }

        // Remove ALL event listeners
        $overlay.off('keydown click.amadex-error-close');
        $(document).off('keydown.amadex-booking-modal keydown.amadex-booking-modal-error');

        // Hide modal
        $overlay.removeClass('show');

        // Restore body scroll
        $('body').css('overflow', '');

        // ✅ CRITICAL: Reset submission flags when modal is closed
        window.amadexBookingSubmissionInProgress = false;
        window.amadexBookingProcessingActive = false;
        window.amadexPaymentTokenization.isTokenizing = false;

        // Remove modal after animation
        setTimeout(function () {
            $overlay.remove();
        }, 300);
    }

    /**
     * Announce to screen readers
     * @param {string} message - Message to announce
     */
    function announceToScreenReader(message) {
        // Create or update ARIA live region
        let $liveRegion = $('#amadex-aria-live-region');

        if ($liveRegion.length === 0) {
            $liveRegion = $('<div>', {
                id: 'amadex-aria-live-region',
                'aria-live': 'polite',
                'aria-atomic': 'true',
                class: 'sr-only',
                css: {
                    position: 'absolute',
                    left: '-10000px',
                    width: '1px',
                    height: '1px',
                    overflow: 'hidden'
                }
            });
            $('body').append($liveRegion);
        }

        // Update message
        $liveRegion.text(message);

        // Clear after announcement (for screen readers that cache)
        setTimeout(function () {
            $liveRegion.text('');
        }, 1000);
    }

    // Prevent ESC key from closing during processing
    $(document).on('keydown.amadex-booking-modal', function (e) {
        const $overlay = $('#amadex-booking-processing-overlay');
        if ($overlay.hasClass('show') && e.key === 'Escape') {
            // Only allow ESC if in error state (has close button)
            const $closeBtn = $overlay.find('.amadex-modal-close');
            if ($closeBtn.length === 0) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });

    // Clean up on page unload
    $(window).on('beforeunload', function () {
        hideBookingProcessingModal();
    });
    // Price breakdown toggle
    $(document).on('click', '.amadex-fare-breakdown-toggle', function () {
        const $toggle = $(this);
        const $breakdown = $toggle.closest('.amadex-price-row--expandable').next('.amadex-fare-breakdown');
        if ($breakdown.is(':visible')) {
            $breakdown.slideUp(200);
            $toggle.removeClass('is-open');
        } else {
            $breakdown.slideDown(200);
            $toggle.addClass('is-open');
        }
    });
})(jQuery);

jQuery(document).on("change", ".amadex-seat-badge-passenger-radio", function () {

    let $tabs = jQuery(".amadex-seat-map-tab");
    let $currentTab = $tabs.filter(".active");
    let currentIndex = $currentTab.index();

    setTimeout(function () {

        let $nextTab = $tabs.eq(currentIndex + 1);

        if ($nextTab.length) {

            $tabs.removeClass("active");
            $nextTab.addClass("active");

            let nextIndex = $nextTab.data("tab-index");

            jQuery(".amadex-seat-map-tab-panel").removeClass("active");
            jQuery('.amadex-seat-map-tab-panel[data-tab-index="' + nextIndex + '"]').addClass("active");

            jQuery("html, body").animate({
                scrollTop: jQuery(".amadex-seat-map-tabs-container").offset().top - 80
            }, 400);

        }

    }, 400);

});

// Auto-switch to next passenger after seat selection (Fixed - clears seat display on flight change)
// jQuery(document).ready(function($) {
//     var isProcessing = false;
//     var isMovingToNextFlight = false;

//     // Store which passengers have seats for the CURRENT flight only
//     var seatedPassengers = {};
//     var currentFlightSegmentId = null;
//     var resetTimeout = null;

//     function getCurrentFlightSegmentId() {
//         var $activeTab = $(".amadex-seat-map-tab.active");
//         if ($activeTab.length) {
//             return $activeTab.data("segment-id");
//         }
//         return null;
//     }

//     function clearPassengerSeatDisplay() {
//         // Clear all passenger seat displays for the new flight
//         $(".amadex-pax-panel-item").each(function() {
//             var passengerId = $(this).data("passenger-id");
//             var $seatDisplay = $("#pax-seat-display-" + passengerId);
//             // Reset to "Select Seat" text
//             $seatDisplay.html('<span class="amadex-pax-seat-empty">Select Seat</span>');
//         });
//     }

//     function resetForNewFlight() {
//         console.log("Resetting for new flight...");

//         // Clear all seat displays for new flight
//         clearPassengerSeatDisplay();

//         // Clear all tracking
//         seatedPassengers = {};
//         isMovingToNextFlight = false;
//         isProcessing = false;

//         // Get current flight segment
//         currentFlightSegmentId = getCurrentFlightSegmentId();
//         console.log("New flight segment ID:", currentFlightSegmentId);

//         // Count already selected seats for this flight (should be none since we cleared)
//         $(".amadex-pax-panel-item").each(function() {
//             var passengerId = $(this).data("passenger-id");
//             var $seatDisplay = $("#pax-seat-display-" + passengerId);
//             var seatText = $seatDisplay.text().trim();
//             if (seatText && seatText !== "Select Seat") {
//                 seatedPassengers[passengerId] = true;
//                 console.log("Passenger", passengerId, "already has seat:", seatText);
//             }
//         });

//         // Ensure first passenger is active
//         var $firstPassenger = $(".amadex-pax-panel-item").first();
//         if ($firstPassenger.length) {
//             var $allPassengers = $(".amadex-pax-panel-item");
//             $allPassengers.removeClass("active");
//             $firstPassenger.addClass("active");

//             if (window.AmadexSeatSelection) {
//                 var firstPassengerId = $firstPassenger.data("passenger-id");
//                 window.AmadexSeatSelection.currentPassengerId = firstPassengerId;

//                 // Clear seat selection for the new segment in AmadexSeatSelection
//                 if (currentFlightSegmentId && window.AmadexSeatSelection.selectedSeats) {
//                     // Initialize empty seat selection for new segment if not exists
//                     if (!window.AmadexSeatSelection.selectedSeats[currentFlightSegmentId]) {
//                         window.AmadexSeatSelection.selectedSeats[currentFlightSegmentId] = {};
//                     }
//                 }

//                 // Small delay to ensure DOM is ready
//                 setTimeout(function() {
//                     window.AmadexSeatSelection.highlightSeatsForCurrentPassenger();
//                 }, 100);
//             }
//         }
//     }

//     function switchToNextPassenger() {
//         if (isProcessing || isMovingToNextFlight) {
//             console.log("Skipping - processing or moving to next flight");
//             return;
//         }

//         var $passengers = $(".amadex-pax-panel-item");
//         var $activePassenger = $passengers.filter(".active");
//         var currentIndex = $passengers.index($activePassenger);
//         var $nextPassenger = $passengers.eq(currentIndex + 1);

//         if ($nextPassenger.length) {
//             console.log("Switching to next passenger, index:", currentIndex + 1);
//             isProcessing = true;

//             // Switch to next passenger
//             $passengers.removeClass("active");
//             $nextPassenger.addClass("active");

//             var nextPassengerId = $nextPassenger.data("passenger-id");
//             if (window.AmadexSeatSelection) {
//                 window.AmadexSeatSelection.currentPassengerId = nextPassengerId;
//                 window.AmadexSeatSelection.highlightSeatsForCurrentPassenger();
//             }

//             // Scroll to keep active passenger in view
//             var $container = $(".amadex-seat-passenger-panel");
//             if ($container.length) {
//                 var scrollTop = $nextPassenger.offset().top - $container.offset().top + $container.scrollTop() - 50;
//                 $container.animate({ scrollTop: scrollTop }, 300);
//             }

//             var passengerName = $nextPassenger.find(".amadex-pax-panel-label").text();
//             showNotification("Now selecting seat for " + passengerName);

//             setTimeout(function() {
//                 isProcessing = false;
//             }, 500);

//         } else {
//             // All passengers on current flight have seats - move to next flight
//             var totalPassengers = $passengers.length;
//             var seatedCount = Object.keys(seatedPassengers).length;

//             console.log("All passengers check - Seated:", seatedCount, "Total:", totalPassengers);

//             if (seatedCount >= totalPassengers && !isMovingToNextFlight) {
//                 isMovingToNextFlight = true;
//                 showNotification("All passengers seated! Moving to next flight...", "#0e7d3f");

//                 setTimeout(function() {
//                     var $tabs = $(".amadex-seat-map-tab");
//                     var $currentTab = $tabs.filter(".active");
//                     var currentIndex = $tabs.index($currentTab);
//                     var $nextTab = $tabs.eq(currentIndex + 1);

//                     if ($nextTab.length) {
//                         console.log("Moving to next flight tab");

//                         // Switch tab
//                         $tabs.removeClass("active");
//                         $nextTab.addClass("active");

//                         var nextIndex = $nextTab.data("tab-index");
//                         $(".amadex-seat-map-tab-panel").removeClass("active");
//                         $('.amadex-seat-map-tab-panel[data-tab-index="' + nextIndex + '"]').addClass("active");

//                         // Initialize seat selection for new segment
//                         var newSegmentId = $nextTab.data("segment-id");
//                         if (window.AmadexSeatSelection && newSegmentId) {
//                             if (!window.AmadexSeatSelection.selectedSeats[newSegmentId]) {
//                                 window.AmadexSeatSelection.selectedSeats[newSegmentId] = {};
//                             }
//                         }

//                         // Wait for tab transition to complete, then reset
//                         setTimeout(function() {
//                             resetForNewFlight();
//                         }, 500);

//                         $("html, body").animate({
//                             scrollTop: $(".amadex-seat-map-tabs-container").offset().top - 80
//                         }, 400);

//                     } else {
//                         showNotification("All flights and passengers seated! ✓", "#0e7d3f");
//                         isMovingToNextFlight = false;
//                     }
//                 }, 800);
//             }
//         }
//     }

//     function showNotification(message, color) {
//         color = color || "#f97316";
//         $(".amadex-passenger-switch-notif").remove();
//         var $notification = $('<div class="amadex-passenger-switch-notif" style="position:fixed;top:80px;right:20px;background:' + color + ';color:#fff;padding:10px 16px;border-radius:8px;z-index:9999;font-size:13px;box-shadow:0 2px 10px rgba(0,0,0,0.1);">' + message + '</div>');
//         $("body").append($notification);
//         setTimeout(function() {
//             $notification.fadeOut(500, function() { $(this).remove(); });
//         }, 1500);
//     }

//     // Monitor seat selection for current flight only
//     function checkSeatSelection() {
//         if (isProcessing || isMovingToNextFlight) {
//             return;
//         }

//         var currentFlightId = getCurrentFlightSegmentId();

//         // Reset if flight changed (detect manual tab change)
//         if (currentFlightId && currentFlightId !== currentFlightSegmentId) {
//             console.log("Flight changed detected, resetting...");
//             resetForNewFlight();
//             return;
//         }

//         var $activePassenger = $(".amadex-pax-panel-item.active");
//         if (!$activePassenger.length) return;

//         var activeId = $activePassenger.data("passenger-id");
//         var $paxSeat = $("#pax-seat-display-" + activeId);
//         var hasSeat = $paxSeat.find(".amadex-pax-seat-selected").length > 0;

//         // If current passenger has a seat and we haven't recorded it
//         if (hasSeat && !seatedPassengers[activeId]) {
//             console.log("Seat detected for passenger:", activeId);
//             seatedPassengers[activeId] = true;

//             // Auto-switch to next passenger after seat is selected
//             setTimeout(function() {
//                 if (!isProcessing && !isMovingToNextFlight) {
//                     switchToNextPassenger();
//                 }
//             }, 500);
//         }
//     }

//     // Check every 500ms
//     var checkInterval = setInterval(checkSeatSelection, 500);

//     // Also check on seat click for immediate response
//     $(document).on("click", ".amadex-seat.available, .amadex-seat.selected", function() {
//         setTimeout(function() {
//             checkSeatSelection();
//         }, 300);
//     });

//     // Manual tab click - reset everything with delay
//     $(document).on("click", ".amadex-seat-map-tab", function() {
//         console.log("Manual tab click detected");

//         // Clear any pending reset
//         if (resetTimeout) clearTimeout(resetTimeout);

//         // Wait for tab to fully activate
//         resetTimeout = setTimeout(function() {
//             resetForNewFlight();
//             resetTimeout = null;
//         }, 300);
//     });

//     // Also watch for tab panel changes (when auto-switching flights)
//     var observer = new MutationObserver(function(mutations) {
//         mutations.forEach(function(mutation) {
//             if (mutation.attributeName === 'class') {
//                 var $target = $(mutation.target);
//                 if ($target.hasClass('active') && $target.hasClass('amadex-seat-map-tab')) {
//                     console.log("Tab became active via observer");
//                     if (resetTimeout) clearTimeout(resetTimeout);
//                     resetTimeout = setTimeout(function() {
//                         resetForNewFlight();
//                         resetTimeout = null;
//                     }, 300);
//                 }
//             }
//         });
//     });

//     // Observe tab buttons for class changes
//     $(".amadex-seat-map-tab").each(function() {
//         observer.observe(this, { attributes: true });
//     });

//     // Initialize on page load
//     setTimeout(function() {
//         console.log("Initializing on page load");
//         resetForNewFlight();
//     }, 1000);
// });
// Auto-switch to next passenger after seat selection (Fixed - restores seat displays when switching back)f
jQuery(document).ready(function ($) {
    var isProcessing = false;
    var isMovingToNextFlight = false;

    // Store which passengers have seats for EACH flight separately
    var flightSeatData = {}; // { segmentId: { passengerId: seatNumber } }
    var currentFlightSegmentId = null;
    var resetTimeout = null;

    function getCurrentFlightSegmentId() {
        var $activeTab = $(".amadex-seat-map-tab.active");
        if ($activeTab.length) {
            return $activeTab.data("segment-id");
        }
        return null;
    }

    function saveCurrentFlightSeats() {
        // Save current flight's seat selections before leaving
        if (currentFlightSegmentId) {
            if (!flightSeatData[currentFlightSegmentId]) {
                flightSeatData[currentFlightSegmentId] = {};
            }

            $(".amadex-pax-panel-item").each(function () {
                var passengerId = $(this).data("passenger-id");
                var $seatDisplay = $("#pax-seat-display-" + passengerId);
                var seatText = $seatDisplay.text().trim();
                if (seatText && seatText !== "Select Seat") {
                    flightSeatData[currentFlightSegmentId][passengerId] = seatText;
                }
            });
            console.log("Saved seats for flight", currentFlightSegmentId, flightSeatData[currentFlightSegmentId]);
        }
    }

    function restoreFlightSeats(segmentId) {
        // Restore seat displays for the flight being switched to
        if (segmentId && flightSeatData[segmentId]) {
            console.log("Restoring seats for flight", segmentId, flightSeatData[segmentId]);

            $(".amadex-pax-panel-item").each(function () {
                var passengerId = $(this).data("passenger-id");
                var savedSeat = flightSeatData[segmentId][passengerId];
                var $seatDisplay = $("#pax-seat-display-" + passengerId);

                if (savedSeat) {
                    $seatDisplay.html('<span class="amadex-pax-seat-selected">' + savedSeat + '</span>');
                } else {
                    $seatDisplay.html('<span class="amadex-pax-seat-empty">Select Seat</span>');
                }
            });
        } else {
            // No saved seats, clear all displays
            clearPassengerSeatDisplay();
        }
    }

    function clearPassengerSeatDisplay() {
        // Clear all passenger seat displays
        $(".amadex-pax-panel-item").each(function () {
            var passengerId = $(this).data("passenger-id");
            var $seatDisplay = $("#pax-seat-display-" + passengerId);
            $seatDisplay.html('<span class="amadex-pax-seat-empty">Select Seat</span>');
        });
    }

    function resetForNewFlight() {
        console.log("Resetting/loading for flight...");

        // Save current flight seats before switching
        saveCurrentFlightSeats();

        // Get current flight segment
        currentFlightSegmentId = getCurrentFlightSegmentId();
        console.log("Current flight segment ID:", currentFlightSegmentId);

        // Restore seats for this flight
        restoreFlightSeats(currentFlightSegmentId);

        // Clear tracking for current flight
        var seatedPassengers = {};

        // Count already selected seats for this flight
        $(".amadex-pax-panel-item").each(function () {
            var passengerId = $(this).data("passenger-id");
            var $seatDisplay = $("#pax-seat-display-" + passengerId);
            var seatText = $seatDisplay.text().trim();
            if (seatText && seatText !== "Select Seat") {
                seatedPassengers[passengerId] = true;
            }
        });

        // Store seated count in flight data
        if (currentFlightSegmentId) {
            if (!flightSeatData[currentFlightSegmentId]) {
                flightSeatData[currentFlightSegmentId] = {};
            }
            flightSeatData[currentFlightSegmentId]._seatedCount = Object.keys(seatedPassengers).length;
        }

        // Reset flags
        isMovingToNextFlight = false;
        isProcessing = false;

        // Ensure first passenger is active
        var $firstPassenger = $(".amadex-pax-panel-item").first();
        if ($firstPassenger.length) {
            var $allPassengers = $(".amadex-pax-panel-item");
            $allPassengers.removeClass("active");
            $firstPassenger.addClass("active");

            if (window.AmadexSeatSelection) {
                var firstPassengerId = $firstPassenger.data("passenger-id");
                window.AmadexSeatSelection.currentPassengerId = firstPassengerId;

                // Clear seat selection for the new segment in AmadexSeatSelection
                if (currentFlightSegmentId && window.AmadexSeatSelection.selectedSeats) {
                    if (!window.AmadexSeatSelection.selectedSeats[currentFlightSegmentId]) {
                        window.AmadexSeatSelection.selectedSeats[currentFlightSegmentId] = {};
                    }
                }

                setTimeout(function () {
                    window.AmadexSeatSelection.highlightSeatsForCurrentPassenger();
                }, 100);
            }
        }
    }

    function switchToNextPassenger() {
        if (isProcessing || isMovingToNextFlight) {
            return;
        }

        var $passengers = $(".amadex-pax-panel-item");
        var $activePassenger = $passengers.filter(".active");
        var currentIndex = $passengers.index($activePassenger);
        var $nextPassenger = $passengers.eq(currentIndex + 1);

        if ($nextPassenger.length) {
            console.log("Switching to next passenger, index:", currentIndex + 1);
            isProcessing = true;

            // Switch to next passenger
            $passengers.removeClass("active");
            $nextPassenger.addClass("active");

            var nextPassengerId = $nextPassenger.data("passenger-id");
            if (window.AmadexSeatSelection) {
                window.AmadexSeatSelection.currentPassengerId = nextPassengerId;
                window.AmadexSeatSelection.highlightSeatsForCurrentPassenger();
            }

            // Scroll to keep active passenger in view
            var $container = $(".amadex-seat-passenger-panel");
            if ($container.length) {
                var scrollTop = $nextPassenger.offset().top - $container.offset().top + $container.scrollTop() - 50;
                $container.animate({ scrollTop: scrollTop }, 300);
            }

            var passengerName = $nextPassenger.find(".amadex-pax-panel-label").text();
            showNotification("Now selecting seat for " + passengerName);

            setTimeout(function () {
                isProcessing = false;
            }, 500);

        } else {
            // Check if all passengers on current flight have seats
            var totalPassengers = $passengers.length;
            var seatedCount = 0;

            $(".amadex-pax-panel-item").each(function () {
                var passengerId = $(this).data("passenger-id");
                var $seatDisplay = $("#pax-seat-display-" + passengerId);
                var seatText = $seatDisplay.text().trim();
                if (seatText && seatText !== "Select Seat") {
                    seatedCount++;
                }
            });

            console.log("All passengers check - Seated:", seatedCount, "Total:", totalPassengers);

            if (seatedCount >= totalPassengers && !isMovingToNextFlight) {
                isMovingToNextFlight = true;
                showNotification("All passengers seated! Moving to next flight...", "#0e7d3f");

                // Save current flight seats before moving
                saveCurrentFlightSeats();

                setTimeout(function () {
                    var $tabs = $(".amadex-seat-map-tab");
                    var $currentTab = $tabs.filter(".active");
                    var currentIndex = $tabs.index($currentTab);
                    var $nextTab = $tabs.eq(currentIndex + 1);

                    if ($nextTab.length) {
                        console.log("Moving to next flight tab");

                        // Switch tab
                        $tabs.removeClass("active");
                        $nextTab.addClass("active");

                        var nextIndex = $nextTab.data("tab-index");
                        $(".amadex-seat-map-tab-panel").removeClass("active");
                        $('.amadex-seat-map-tab-panel[data-tab-index="' + nextIndex + '"]').addClass("active");

                        // Initialize seat selection for new segment
                        var newSegmentId = $nextTab.data("segment-id");
                        if (window.AmadexSeatSelection && newSegmentId) {
                            if (!window.AmadexSeatSelection.selectedSeats[newSegmentId]) {
                                window.AmadexSeatSelection.selectedSeats[newSegmentId] = {};
                            }
                        }

                        // Wait for tab transition to complete, then reset
                        setTimeout(function () {
                            resetForNewFlight();
                        }, 500);

                        $("html, body").animate({
                            scrollTop: $(".amadex-seat-map-tabs-container").offset().top - 80
                        }, 400);

                    } else {
                        showNotification("All flights and passengers seated! ✓", "#0e7d3f");
                        isMovingToNextFlight = false;
                    }
                }, 800);
            }
        }
    }

    function showNotification(message, color) {
        color = color || "#f97316";
        $(".amadex-passenger-switch-notif").remove();
        var $notification = $('<div class="amadex-passenger-switch-notif" style="position:fixed;top:80px;right:20px;background:' + color + ';color:#fff;padding:10px 16px;border-radius:8px;z-index:9999;font-size:13px;box-shadow:0 2px 10px rgba(0,0,0,0.1);">' + message + '</div>');
        $("body").append($notification);
        setTimeout(function () {
            $notification.fadeOut(500, function () { $(this).remove(); });
        }, 1500);
    }

    // Monitor seat selection for current flight only
    function checkSeatSelection() {
        if (isProcessing || isMovingToNextFlight) {
            return;
        }

        var currentFlightId = getCurrentFlightSegmentId();

        // Reset if flight changed (detect manual tab change)
        if (currentFlightId && currentFlightId !== currentFlightSegmentId) {
            console.log("Flight changed detected, resetting...");
            resetForNewFlight();
            return;
        }

        var $activePassenger = $(".amadex-pax-panel-item.active");
        if (!$activePassenger.length) return;

        var activeId = $activePassenger.data("passenger-id");
        var $paxSeat = $("#pax-seat-display-" + activeId);
        var hasSeat = $paxSeat.find(".amadex-pax-seat-selected").length > 0;

        // If current passenger has a seat
        if (hasSeat) {
            // Save to flight data
            if (currentFlightSegmentId) {
                if (!flightSeatData[currentFlightSegmentId]) {
                    flightSeatData[currentFlightSegmentId] = {};
                }
                var seatText = $paxSeat.text().trim();
                flightSeatData[currentFlightSegmentId][activeId] = seatText;
            }

            // Check if we haven't processed this seat selection yet
            var seatProcessedKey = currentFlightSegmentId + '_' + activeId;
            if (!window._processedSeats) window._processedSeats = {};

            if (!window._processedSeats[seatProcessedKey]) {
                window._processedSeats[seatProcessedKey] = true;
                console.log("Seat detected for passenger:", activeId);

                // Auto-switch to next passenger after seat is selected
                setTimeout(function () {
                    if (!isProcessing && !isMovingToNextFlight) {
                        switchToNextPassenger();
                    }
                }, 500);
            }
        }
    }

    // Check every 500ms
    setInterval(checkSeatSelection, 500);

    // Also check on seat click for immediate response
    $(document).on("click", ".amadex-seat.available, .amadex-seat.selected", function () {
        setTimeout(function () {
            checkSeatSelection();
        }, 300);
    });

    // Manual tab click - reset everything with delay
    $(document).on("click", ".amadex-seat-map-tab", function () {
        console.log("Manual tab click detected");

        // Clear any pending reset
        if (resetTimeout) clearTimeout(resetTimeout);

        // Wait for tab to fully activate
        resetTimeout = setTimeout(function () {
            resetForNewFlight();
            resetTimeout = null;
        }, 300);
    });

    // Also watch for tab panel changes (when auto-switching flights)
    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.attributeName === 'class') {
                var $target = $(mutation.target);
                if ($target.hasClass('active') && $target.hasClass('amadex-seat-map-tab')) {
                    console.log("Tab became active via observer");
                    if (resetTimeout) clearTimeout(resetTimeout);
                    resetTimeout = setTimeout(function () {
                        resetForNewFlight();
                        resetTimeout = null;
                    }, 300);
                }
            }
        });
    });

    // Observe tab buttons for class changes
    $(".amadex-seat-map-tab").each(function () {
        observer.observe(this, { attributes: true });
    });

    // Initialize on page load
    setTimeout(function () {
        console.log("Initializing on page load");
        resetForNewFlight();
    }, 1000);
});
/**
 * Force update seat selection display and price
 * NOTE: This function is outside the main jQuery closure, so inner functions
 * like getSelectedCurrency/formatCurrencyValue are not directly accessible.
 * We use window-scoped bridges set up below.
 */
function updateSeatSelectionDisplay() {
    var $ = jQuery;
    if (!window.AmadexSeatSelection) {
        return;
    }

    // Safe wrappers for closure-scoped functions
    function _getSelectedCurrency() {
        if (typeof window._amadexGetSelectedCurrency === 'function') {
            return window._amadexGetSelectedCurrency();
        }
        return sessionStorage.getItem('amadex_selected_currency') || 'USD';
    }
    function _formatCurrencyValue(amount, currency) {
        if (typeof window._amadexFormatCurrencyValue === 'function') {
            return window._amadexFormatCurrencyValue(amount, currency);
        }
        try {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD', minimumFractionDigits: 2 }).format(amount);
        } catch (e) {
            return '$' + parseFloat(amount || 0).toFixed(2);
        }
    }
    function _populatePriceBreakdown(flight, searchData, animate) {
        if (typeof window._amadexPopulatePriceBreakdown === 'function') {
            window._amadexPopulatePriceBreakdown(flight, searchData, animate);
        }
    }

    // Calculate total seat charges from selectedSeats
    let totalSeatCharges = 0;
    let hasSeats = false;
    const selectedSeats = window.AmadexSeatSelection.selectedSeats || {};

    // Calculate total from all selected seats
    Object.keys(selectedSeats).forEach((segmentId) => {
        const segmentSeats = selectedSeats[segmentId] || {};
        Object.keys(segmentSeats).forEach((travelerId) => {
            const seat = segmentSeats[travelerId];
            if (seat && seat.price !== undefined) {
                const seatPrice = parseFloat(seat.price) || 0;
                totalSeatCharges += seatPrice;
                hasSeats = true;

            }
        });
    });

    // Update global variable
    window.AmadexSeatSelection.totalSeatCharges = totalSeatCharges;

    // Update the seat total price display
    const $seatTotalPrice = $('#amadex-seat-total-price');
    const $seatSummary = $('#amadex-selected-seats-summary');
    const $seatList = $('#amadex-selected-seats-list');

    // Get selected currency
    const currency = _getSelectedCurrency();

    if (hasSeats && totalSeatCharges > 0) {
        // Update total price display
        if ($seatTotalPrice.length) {
            $seatTotalPrice.html(`<strong>Total Seat Charges: ${_formatCurrencyValue(totalSeatCharges, currency)}</strong>`);
            $seatTotalPrice.attr('data-seat-charges', totalSeatCharges.toFixed(2));
            $seatTotalPrice.show();
        }

        // Build seat summary list
        if ($seatList.length) {
            let summaryHtml = '';
            Object.keys(selectedSeats).forEach((segmentId) => {
                const segmentSeats = selectedSeats[segmentId] || {};
                Object.keys(segmentSeats).forEach((travelerId) => {
                    const seat = segmentSeats[travelerId];
                    if (seat) {
                        // Get passenger name
                        let passengerName = '';
                        if (window.AmadexSeatSelection.getPassengerName) {
                            passengerName = window.AmadexSeatSelection.getPassengerName(travelerId);
                        }
                        if (!passengerName) {
                            passengerName = window.AmadexSeatSelection.getPassengerLabel ?
                                window.AmadexSeatSelection.getPassengerLabel(travelerId) :
                                `Passenger ${travelerId}`;
                        }

                        summaryHtml += `
                            <li class="amadex-seat-summary-item">
                                <strong>${passengerName}</strong>
                                <span>Seat ${seat.seat_number}</span>
                                <span>${_formatCurrencyValue(seat.price, currency)}</span>
                            </li>
                        `;
                    }
                });
            });
            $seatList.html(summaryHtml);
            $seatSummary.show();
        }

        // Force price breakdown update
        const flightData = sessionStorage.getItem('amadex_booking_flight');
        const searchDataStr = sessionStorage.getItem('amadex_search_data');
        if (flightData && searchDataStr) {
            try {
                const flight = JSON.parse(flightData);
                const searchData = JSON.parse(searchDataStr);
                if (!flight.price) flight.price = {};
                flight.price.seat_charges = totalSeatCharges;
                sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flight));
                _populatePriceBreakdown(flight, searchData, true);
            } catch (e) { }
        }
    } else {
        // No seats selected - hide or clear
        if ($seatTotalPrice.length) {
            $seatTotalPrice.html('');
            $seatTotalPrice.removeAttr('data-seat-charges');
            $seatTotalPrice.hide();
        }
        if ($seatSummary.length) {
            $seatSummary.hide();
        }

        // Clear seat charges from flight data
        const flightData = sessionStorage.getItem('amadex_booking_flight');
        const searchDataStr = sessionStorage.getItem('amadex_search_data');
        if (flightData && searchDataStr) {
            try {
                const flight = JSON.parse(flightData);
                if (flight.price) {
                    flight.price.seat_charges = 0;
                    sessionStorage.setItem('amadex_booking_flight', JSON.stringify(flight));
                }
                const searchData = JSON.parse(searchDataStr);
                _populatePriceBreakdown(flight, searchData, true);
            } catch (e) { }
        }
    }

    return totalSeatCharges;
}


(function ($) {
    $(document).ready(function () {
        // Skip seat selection — handled by single delegated handler above
        console.log('Skip button goes to Add-ons');
    });
})(jQuery);

// Review & Confirm Dropdown Functionality
jQuery(document).ready(function ($) {

    // Function to create the dropdown structure
    function createReviewDropdown() {
        // Check if dropdown already exists
        if ($('#amadex-review-dropdown').length) return;

        // Find the review section
        var $reviewSection = $('#amadex-review-section');
        if (!$reviewSection.length) return;

        // Get existing content that should go inside dropdown
        var $passengerSection = $('#amadex-review-passengers');
        var $seatsSection = $('#amadex-review-seats');
        var $addonsSection = $('#amadex-review-addons');

        // Create dropdown HTML
        var dropdownHTML = `
            <div id="amadex-review-dropdown" class="amadex-review-dropdown">
                <div class="amadex-review-dropdown-header">
                    <h3>Review Your Booking Details</h3>
                    <span class="amadex-review-dropdown-chevron">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                </div>
                <div class="amadex-review-dropdown-content">
                    <!-- Passenger Details will be moved here -->
                    <div id="amadex-review-dropdown-passengers"></div>
                    <!-- Seat Selection will be moved here -->
                    <div id="amadex-review-dropdown-seats"></div>
                    <!-- Add-ons will be moved here -->
                    <div id="amadex-review-dropdown-addons"></div>
                </div>
            </div>
        `;

        // Insert dropdown after the h3 in review section
        $reviewSection.find('h3').after(dropdownHTML);

        // Populate dropdown from already-updated content divs
        // (updateReviewPassengers writes to #amadex-review-passengers-content, not the outer wrapper)
        var $passengerContent = $('#amadex-review-passengers-content');
        if ($passengerContent.length) {
            $('#amadex-review-dropdown-passengers').html(
                '<div class="amadex-review-section amadex-review-dropdown-section">' +
                '<h4>Passenger Details</h4>' +
                $passengerContent.html() +
                '</div>'
            );
            if ($passengerSection.length) $passengerSection.hide();
        } else if ($passengerSection.length) {
            var $passengerClone = $passengerSection.clone();
            $passengerClone.find('h4').text('Passenger Details');
            $passengerClone.find('.amadex-review-edit-link').remove();
            $('#amadex-review-dropdown-passengers').html($passengerClone.html());
            $passengerSection.hide();
        }

        var $seatsContent = $('#amadex-review-seats-content');
        if ($seatsContent.length) {
            $('#amadex-review-dropdown-seats').html(
                '<div class="amadex-review-section amadex-review-dropdown-section">' +
                '<h4>Selected Seats</h4>' +
                $seatsContent.html() +
                '</div>'
            );
            if ($seatsSection.length) $seatsSection.hide();
        } else if ($seatsSection.length) {
            var $seatsClone = $seatsSection.clone();
            $seatsClone.find('h4').text('Selected Seats');
            $seatsClone.find('.amadex-review-edit-link').remove();
            $('#amadex-review-dropdown-seats').html($seatsClone.html());
            $seatsSection.hide();
        }

        if ($addonsSection.length) {
            var $addonsClone = $addonsSection.clone();
            $addonsClone.find('h4').text('Add-ons');
            $addonsClone.find('.amadex-review-edit-link').remove();
            $('#amadex-review-dropdown-addons').html($addonsClone.html());
            // Hide original
            $addonsSection.hide();
        }

        // Add CSS classes for dropdown styling
        $('#amadex-review-dropdown-passengers .amadex-review-section').removeClass().addClass('amadex-review-dropdown-section');
        $('#amadex-review-dropdown-seats .amadex-review-section').removeClass().addClass('amadex-review-dropdown-section');
        $('#amadex-review-dropdown-addons .amadex-review-section').removeClass().addClass('amadex-review-dropdown-section');

        // Convert passenger list to dropdown friendly format
        formatPassengerListForDropdown();

        // Toggle dropdown on header click
        $('.amadex-review-dropdown-header').on('click', function () {
            var $content = $('.amadex-review-dropdown-content');
            var $chevron = $('.amadex-review-dropdown-chevron');

            $content.toggleClass('open');
            $chevron.toggleClass('open');
        });
    }

    function formatPassengerListForDropdown() {
        var $passengerContent = $('#amadex-review-dropdown-passengers .amadex-review-passengers-content');
        if (!$passengerContent.length) return;

        var $items = $passengerContent.find('.amadex-review-passenger-item');
        if (!$items.length) return;

        var html = '<ul class="amadex-review-dropdown-passenger-list">';
        $items.each(function () {
            var $item = $(this);
            var name = $item.find('.amadex-review-passenger-name strong').text();
            var details = $item.find('.amadex-review-passenger-details').text();

            html += `
                <li class="amadex-review-dropdown-passenger-item">
                    <div class="amadex-review-dropdown-passenger-name">${name}</div>
                    <div class="amadex-review-dropdown-passenger-details">${details}</div>
                </li>
            `;
        });
        html += '</ul>';
        $passengerContent.html(html);
    }

    // Update dropdown content when review section updates
    function updateReviewDropdownContent() {
        // Read from #amadex-review-passengers-content (written by updateReviewPassengers)
        // NOT from #amadex-review-passengers (the hidden original wrapper - never updated)
        var $passengerContent = $('#amadex-review-passengers-content');
        var $seatsSection = $('#amadex-review-seats');
        var $addonsSection = $('#amadex-review-addons');

        if ($passengerContent.length && $('#amadex-review-dropdown-passengers').length) {
            $('#amadex-review-dropdown-passengers').html(
                '<div class="amadex-review-section amadex-review-dropdown-section">' +
                '<h4>Passenger Details</h4>' +
                $passengerContent.html() +
                '</div>'
            );
            formatPassengerListForDropdown();
        }

        // Seats - read from the updated content div, same pattern as passengers
        var $seatsContent = $('#amadex-review-seats-content');
        if ($seatsContent.length && $('#amadex-review-dropdown-seats').length) {
            $('#amadex-review-dropdown-seats').html(
                '<div class="amadex-review-section amadex-review-dropdown-section">' +
                '<h4>Selected Seats</h4>' +
                $seatsContent.html() +
                '</div>'
            );
        } else if ($seatsSection.length && $('#amadex-review-dropdown-seats').length) {
            var $seatsClone = $seatsSection.clone();
            $seatsClone.find('h4').text('Selected Seats');
            $seatsClone.find('.amadex-review-edit-link').remove();
            $('#amadex-review-dropdown-seats').html($seatsClone.html());
        }

        if ($addonsSection.length && $('#amadex-review-dropdown-addons').length) {
            var $addonsClone = $addonsSection.clone();
            $addonsClone.find('h4').text('Add-ons');
            $addonsClone.find('.amadex-review-edit-link').remove();

            // Format add-ons for dropdown
            var addonsHtml = '<ul class="amadex-review-dropdown-addons-list">';
            $addonsClone.find('.amadex-review-list li').each(function () {
                var $li = $(this);
                var name = $li.find('strong').text();
                var price = $li.find('span').text();
                addonsHtml += `
                    <li class="amadex-review-dropdown-addons-item">
                        <span class="amadex-review-dropdown-addons-name">${name}</span>
                        <span class="amadex-review-dropdown-addons-price">${price}</span>
                    </li>
                `;
            });
            addonsHtml += '</ul>';
            $('#amadex-review-dropdown-addons').html(addonsHtml);
        }
    }

    // Create dropdown when review section becomes active
    function initReviewDropdown() {
        if ($('#amadex-review-section').hasClass('step-active') || $('#amadex-review-section').is(':visible')) {
            createReviewDropdown();
        }
    }

    // Watch for step changes
    $(document).on('amadexBookingStepChanged stepChanged', function (e, stepName) {
        if (stepName === 'review') {
            // Delay must be > 350ms so updateReviewContent() has already written
            // fresh passenger data into #amadex-review-passengers-content before
            // we copy it into the dropdown.
            setTimeout(function () {
                createReviewDropdown();
                updateReviewDropdownContent();
            }, 500);
        }
    });

    // Also check on page load
    setTimeout(function () {
        initReviewDropdown();
    }, 1000);

    // Update dropdown when passenger/seat/addon data changes
    $(document).on('input blur change', '.amadex-passenger-form-card input, .amadex-passenger-form-card select, .amadex-passenger-form input, .amadex-passenger-form select', function () {
        if ($('#amadex-review-section').hasClass('step-active')) {
            setTimeout(function () {
                updateReviewDropdownContent();
            }, 500);
        }
    });

    $(document).on('seatSelected', function () {
        if ($('#amadex-review-section').hasClass('step-active')) {
            setTimeout(function () {
                updateReviewDropdownContent();
            }, 300);
        }
    });

    $(document).on('addonsUpdated', function () {
        if ($('#amadex-review-section').hasClass('step-active')) {
            setTimeout(function () {
                updateReviewDropdownContent();
            }, 300);
        }
    });
});