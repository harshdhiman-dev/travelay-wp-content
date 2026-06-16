/**
 * Digital Silk Gravity Forms URL Parameters
 *
 * This script handles:
 * 1. Reading UTM parameters from URL
 * 2. Storing parameters in a cookie
 * 3. Populating hidden Gravity Forms fields with stored values
 */
class ds_gf_url_params {
    constructor() {
        this.cookie_name = "ds_gf_data";
        this.cookie_expiry = 30; // Days
        this.init();
    }

    init() {
        // First, try to collect UTM parameters from URL if present
        const utm_params = this.get_utm_params_from_url();

        // Get referrer information
        const page_referer = this.get_page_referer();

        // Check if we have existing cookie data
        let cookie_data = this.get_cookie_data();

        // Update cookie data with new values if present in URL
        if (Object.keys(utm_params).length > 0) {
            // If we have new UTM parameters, update them
            cookie_data.utm_data = utm_params;
        }

        // Always update the referrer info when page loads (similar to PHP behavior)
        if (page_referer) {
            cookie_data.referer_data = page_referer;
        }

        // Store updated data in cookie
        this.set_cookie_data(cookie_data);

        // Wait for Gravity Forms to load and populate the fields
        this.wait_for_gravity_forms(() => {
            const utm_data = cookie_data.utm_data || {};
            const referer_url = cookie_data.referer_data || '';
            this.set_lead_fields(utm_data, referer_url);
        });
    }

    /**
     * Extract UTM parameters from current URL
     */
    get_utm_params_from_url() {
        const url_params = new URLSearchParams(window.location.search);
        const utm_params = {};

        // Define UTM parameters to extract
        const utm_keys = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'utm_id'
        ];

        // Extract values if present
        utm_keys.forEach(key => {
            if (url_params.has(key)) {
                utm_params[key] = url_params.get(key);
            }
        });

        return utm_params;
    }

    /**
     * Get the current referrer information
     */
    get_page_referer() {
        const current_url = window.location.protocol + '//' + window.location.host + window.location.pathname;
        const referrer = document.referrer;

        // If no referrer or referrer is from same domain, mark as direct
        if (!referrer || referrer.indexOf(window.location.hostname) > -1) {
            return 'Direct';
        }

        return referrer;
    }

    /**
     * Get cookie data if it exists, or create empty structure
     */
    get_cookie_data() {
        const cookie_value = this.get_cookie(this.cookie_name);

        if (!cookie_value) {
            return {
                utm_data: {
                    utm_source: 'Organic',
                    utm_medium: 'N/A',
                    utm_campaign: 'N/A',
                    utm_id: 'N/A',
                    utm_term: 'N/A',
                    utm_content: 'N/A'
                },
                referer_data: 'Direct'
            };
        }

        try {
            // Decode Base64 JSON
            const decoded = atob(cookie_value);
            const parsed_data = JSON.parse(decoded);

            return parsed_data;
        } catch (error) {
            return {
                utm_data: {
                    utm_source: 'Organic',
                    utm_medium: 'N/A',
                    utm_campaign: 'N/A',
                    utm_id: 'N/A',
                    utm_term: 'N/A',
                    utm_content: 'N/A'
                },
                referer_data: 'Direct'
            };
        }
    }

    /**
     * Store data in cookie
     */
    set_cookie_data(data) {
        const json_data = JSON.stringify(data);
        const encoded_data = btoa(json_data);

        // Calculate expiry date
        const date = new Date();
        date.setTime(date.getTime() + (this.cookie_expiry * 24 * 60 * 60 * 1000));

        // Set cookie
        document.cookie = `${this.cookie_name}=${encoded_data}; expires=${date.toUTCString()}; path=/; SameSite=Lax`;
    }

    /**
     * Wait for Gravity Forms to be loaded
     */
    wait_for_gravity_forms(callback) {
        document.addEventListener("gform_post_render", () => {
            callback();
        });

        let observer = new MutationObserver(() => {
            if (document.querySelector('.gform_wrapper')) {
                callback();
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });

        // Also try immediately in case form is already loaded
        if (document.querySelector('.gform_wrapper')) {
            callback();
        }
    }

    /**
     * Set values for the hidden fields in Gravity Forms
     */
    set_lead_fields(utm_data, referer_url) {

        const fields = {
            "input_9990": referer_url,
            "input_9991": window.location.href,
            "input_9992": utm_data.utm_campaign || 'N/A',
            "input_9993": utm_data.utm_medium || 'N/A',
            "input_9994": utm_data.utm_source || 'Organic',
            "input_9995": utm_data.utm_term || 'N/A',
            "input_9996": utm_data.utm_content || 'N/A',
            "input_9997": utm_data.utm_id || 'N/A',
        };

        for (const [fieldName, value] of Object.entries(fields)) {
            let inputs = document.querySelectorAll(`input[name="${fieldName}"]`);

            if (inputs.length === 0) {
                continue;
            }

            inputs.forEach((input, index) => {
                input.value = value;
            });
        }
    }

    /**
     * Get cookie by name
     */
    get_cookie(name) {
        const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
        return match ? decodeURIComponent(match[1]) : null;
    }
}

/**
 * Initialize script when document is ready
 */
document.addEventListener("DOMContentLoaded", function () {
    // Always initialize to capture UTM parameters on any page
    new ds_gf_url_params();
});