/**
 * Amadex Payment Page JavaScript
 * Handles Stripe Checkout Sessions with standard redirect flow (Stripe-hosted Checkout)
 * User is redirected to Stripe Checkout, then returns to success/cancel URL
 *
 * @package Amadex
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    let stripe = null;
    let sessionId = null;
    
    /**
     * Initialize payment page when DOM is ready
     */
    $(document).ready(function() {
        console.log('Amadex Payment Page: Initializing...');
        
        // Check if we're returning from payment (session_id in URL)
        const urlParams = new URLSearchParams(window.location.search);
        const sessionId = urlParams.get('session_id');
        const stripeErrorFromUrl = urlParams.get('stripe_error');
        if (stripeErrorFromUrl) {
            console.warn('Amadex Payment Page: Server reported Stripe error (from redirect):', stripeErrorFromUrl);
            try {
                var decoded = decodeURIComponent(stripeErrorFromUrl);
                if (decoded && $('.amadex-payment-error').length) {
                    $('.amadex-payment-error').text(decoded).show();
                }
            } catch (e) {}
        }
        
        if (sessionId) {
            // User returned from Stripe - skip complete_payment form; show confirmation state and go to confirmation page
            console.log('Amadex Payment Page: User returned from Stripe Checkout with session_id:', sessionId);
            showConfirmingState('Confirming your booking...');
            checkSessionStatus(sessionId);
            return;
        }
        
        // No session_id: user landed on complete-payment with ?st=token — skip page and redirect straight to Stripe Checkout
        const submitBtn = $('#amadex-payment-submit');
        submitBtn.prop('disabled', true);
        
        // Check Stripe.js and config first (needed for redirect)
        if (typeof Stripe === 'undefined') {
            console.error('Amadex Payment Page: ❌ Stripe.js not loaded');
            showPaymentError('Stripe payment system not configured. Stripe.js library failed to load. Please check your Stripe Publishable Key in WordPress Admin → Amadex Settings → Payment Settings.');
            submitBtn.prop('disabled', false).text('Continue to Payment');
            return;
        }
        if (typeof AmadexStripe === 'undefined' || !AmadexStripe.publishableKey) {
            console.error('Amadex Payment Page: ❌ Stripe config not available');
            showPaymentError('Stripe payment system not configured. Stripe Publishable Key is missing. Please go to WordPress Admin → Amadex Settings → Payment Settings and enter your Stripe Publishable Key.');
            submitBtn.prop('disabled', false).text('Continue to Payment');
            return;
        }
        try {
            stripe = Stripe(AmadexStripe.publishableKey);
        } catch (error) {
            console.error('Amadex Payment Page: ❌ Error creating Stripe instance:', error);
            showPaymentError('Failed to initialize Stripe. Please check your Stripe Publishable Key in WordPress Admin → Amadex Settings → Payment Settings.');
            submitBtn.prop('disabled', false).text('Continue to Payment');
            return;
        }
        
        // Hide payment form and show "Redirecting to Stripe Checkout..." then redirect immediately (skip "Continue to Payment" click)
        showConfirmingState('Redirecting to secure payment...');
        console.log('Amadex Payment Page: Skipping complete-payment page — redirecting to Stripe Checkout.');
        
        initializePayment()
            .then(function() {
                // Redirect happens inside initializePayment; we only reach here if no redirect occurred
            })
            .catch(function(error) {
                console.warn('Amadex Payment Page: Auto-redirect failed, showing payment form:', error && error.message ? error.message : error);
                hideConfirmingState();
                submitBtn.prop('disabled', false).text('Continue to Payment');
                // Clear any previous error so user can retry
                $('.amadex-payment-error').text('').hide();
            });
        
        // Fallback: if user stays on page (e.g. redirect failed), "Continue to Payment" still works
        submitBtn.on('click', handlePaymentSubmit);
        submitBtn.on('mousedown', function(e) {
            if ($(this).prop('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
        submitBtn.on('touchend', function(e) {
            if (!$(this).prop('disabled')) {
                e.preventDefault();
                handlePaymentSubmit(e);
            }
        });
        
        // Handle email input for validation
        const emailInput = $('#amadex-payment-email');
        if (emailInput.length > 0) {
            emailInput.on('input', function() {
                // Clear any validation errors
                $('#email-errors').text('').hide();
                emailInput.removeClass('error');
            });
            
            emailInput.on('blur', function() {
                const email = emailInput.val().trim();
                if (email) {
                    validateEmail(email).then(function(result) {
                        if (!result.isValid) {
                            $('#email-errors').text(result.message).show();
                            emailInput.addClass('error');
                        }
                    });
                }
            });
        }
        
        // Handle mobile summary toggle
        $('#mobile-view-summary').on('click', function() {
            $('.amadex-payment-left-column').addClass('mobile-expanded');
        });
        
        // Handle mobile summary close button
        $('#mobile-summary-close').on('click', function() {
            $('.amadex-payment-left-column').removeClass('mobile-expanded');
        });
        
        // Close summary when clicking outside (mobile only)
        $(document).on('click', function(e) {
            const $summaryPanel = $('.amadex-payment-left-column');
            const $viewBtn = $('#mobile-view-summary');
            
            // Only handle on mobile (when summary panel is expanded)
            if ($(window).width() <= 768 && $summaryPanel.hasClass('mobile-expanded')) {
                // Don't close if clicking inside the summary panel or the view button
                if (!$(e.target).closest($summaryPanel).length && 
                    !$(e.target).closest($viewBtn).length &&
                    !$(e.target).is($summaryPanel) &&
                    !$(e.target).is($viewBtn)) {
                    $summaryPanel.removeClass('mobile-expanded');
                }
            }
        });
    });
    
    /**
     * Initialize Stripe Checkout Session
     */
    async function initializePayment() {
        console.log('Amadex Payment Page: Creating Checkout Session...');
        
        // Get booking data
        const bookingData = typeof AmadexPaymentData !== 'undefined' ? AmadexPaymentData.bookingData : null;
        
        if (!bookingData) {
            const errorMsg = 'Booking data not found. Please start your booking again.';
            console.error('Amadex Payment Page:', errorMsg);
            showPaymentError(errorMsg);
            $('#amadex-payment-submit').prop('disabled', true).text('Payment unavailable');
            return;
        }
        
        // Calculate total amount - CRITICAL: Use exact total from stored booking data
        const pricing = bookingData.pricing || {};
        
        // Use exact total if provided (includes all components: fare, tax, addons, seats, premium)
        let totalAmount = parseFloat(pricing.total || 0);
        
        // If total not provided, calculate from components
        if (totalAmount <= 0) {
            const fare = parseFloat(pricing.fare || 0);
            const tax = parseFloat(pricing.tax || 0);
            const surcharge = parseFloat(pricing.surcharge || 0);
            const addons = parseFloat(pricing.addons || 0);
            const seatCharges = parseFloat(pricing.seat_charges || 0);
            const premiumService = parseFloat(pricing.premium_service || 0);
            
            totalAmount = fare + tax + surcharge + addons + seatCharges + premiumService;
            
            console.log('Amadex Payment Page: Calculated total from components:', {
                fare: fare,
                tax: tax,
                surcharge: surcharge,
                addons: addons,
                seatCharges: seatCharges,
                premiumService: premiumService,
                total: totalAmount
            });
        }
        
        const currency = 'usd'; // Default to USD
        
        if (totalAmount <= 0) {
            const errorMsg = 'Invalid payment amount. Please start your booking again.';
            console.error('Amadex Payment Page:', errorMsg, 'Amount:', totalAmount, 'Pricing:', pricing);
            showPaymentError(errorMsg);
            $('#amadex-payment-submit').prop('disabled', true).text('Payment unavailable');
            return;
        }
        
        console.log('Amadex Payment Page: Using exact total amount for Stripe:', totalAmount, 'Currency:', currency);
        
        // Extract flight data for metadata
        const flightData = bookingData.flight || {};
        const firstSegment = flightData.itineraries && flightData.itineraries[0] && flightData.itineraries[0].segments && flightData.itineraries[0].segments[0] 
            ? flightData.itineraries[0].segments[0] 
            : null;
        
        let flightMetadata = null;
        if (firstSegment && firstSegment.departure && firstSegment.arrival) {
            const depIata = firstSegment.departure.iataCode || firstSegment.departure.iata_code || '';
            const arrIata = firstSegment.arrival.iataCode || firstSegment.arrival.iata_code || '';
            const carrierCode = firstSegment.carrierCode || firstSegment.carrier_code || '';
            
            if (depIata && arrIata && carrierCode) {
                const passengerName = bookingData.passengers && bookingData.passengers[0] 
                    ? ((bookingData.passengers[0].first_name || bookingData.passengers[0].firstname || '') + ' ' + (bookingData.passengers[0].last_name || bookingData.passengers[0].lastname || ''))
                    : (bookingData.contact ? (bookingData.contact.first_name + ' ' + bookingData.contact.last_name) : '');
                
                flightMetadata = {
                    booking_reference: bookingData.booking_reference || '',
                    carrier_name: carrierCode,
                    carrier_iata: carrierCode,
                    passenger_name: passengerName,
                    departure_airport: depIata,
                    arrival_airport: arrIata,
                    departure_date: firstSegment.departure.at || '',
                    arrival_date: firstSegment.arrival.at || '',
                    ticket_class: firstSegment.cabin || 'ECONOMY',
                    ticketing_agent: 'Travelay'
                };
            }
        }
        
        // Create Checkout Session via AJAX
        try {
            console.log('Amadex Payment Page: Sending AJAX request to create Checkout Session...');
            console.log('Amadex Payment Page: AJAX URL:', AmadexPaymentData.ajaxUrl);
            console.log('Amadex Payment Page: Amount:', totalAmount, 'Currency:', currency);
            console.log('Amadex Payment Page: Booking reference:', bookingData.booking_reference || 'temp-' + Date.now());
            
            const ajaxData = {
                action: 'amadex_create_elements_session',
                nonce: AmadexPaymentData.nonce,
                amount: totalAmount,
                currency: currency,
                booking_reference: bookingData.booking_reference || 'temp-' + Date.now(),
                flight_data: flightMetadata ? JSON.stringify(flightMetadata) : null,
                token: (AmadexPaymentData && AmadexPaymentData.bookingToken) ? AmadexPaymentData.bookingToken : ''
            };
            
            console.log('Amadex Payment Page: AJAX data:', ajaxData);
            
            const response = await $.ajax({
                url: AmadexPaymentData.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                dataType: 'json', // Explicitly expect JSON response
                timeout: 30000, // 30 second timeout
                beforeSend: function() {
                    console.log('Amadex Payment Page: AJAX request started...');
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                // Handle AJAX failure explicitly with detailed logging
                console.error('=== Amadex Payment Page: AJAX Request Failed ===');
                console.error('Status:', jqXHR.status);
                console.error('Status Text:', textStatus);
                console.error('Error Thrown:', errorThrown);
                console.error('Response Text Length:', jqXHR.responseText ? jqXHR.responseText.length : 0);
                
                // Try to parse response as JSON
                let errorData = null;
                let errorMessage = 'Failed to create payment session. Please try again.';
                
                if (jqXHR.responseText) {
                    // Always log response text for debugging
                    console.error('Response Text (first 2000 chars):', jqXHR.responseText.substring(0, 2000));
                    
                    try {
                        errorData = JSON.parse(jqXHR.responseText);
                        console.error('✅ Parsed JSON Response:', JSON.stringify(errorData, null, 2));
                        
                        if (errorData.data && errorData.data.message) {
                            errorMessage = errorData.data.message;
                            console.error('Error Message:', errorMessage);
                        }
                        
                        if (errorData.data) {
                            console.error('Error Data:', errorData.data);
                            if (errorData.data.error_type) {
                                console.error('Error Type:', errorData.data.error_type);
                            }
                            if (errorData.data.error_details) {
                                console.error('Error Details:', errorData.data.error_details);
                            }
                        }
                    } catch (e) {
                        // Response is not JSON (likely HTML error page or fatal PHP error)
                        console.error('❌ Response is NOT valid JSON:', e.message);
                        console.error('Response appears to be HTML or plain text');
                        
                        // Try to extract JSON from HTML (WordPress error pages sometimes include JSON)
                        const jsonMatch = jqXHR.responseText.match(/\{[\s\S]*"success"[\s\S]*\}/);
                        if (jsonMatch) {
                            try {
                                errorData = JSON.parse(jsonMatch[0]);
                                console.error('✅ Extracted JSON from HTML:', errorData);
                                if (errorData.data && errorData.data.message) {
                                    errorMessage = errorData.data.message;
                                }
                            } catch (e2) {
                                console.error('Failed to extract JSON from HTML:', e2.message);
                            }
                        }
                        
                        // Check for fatal PHP errors in HTML
                        if (jqXHR.responseText.includes('Fatal error') || jqXHR.responseText.includes('Cannot declare class')) {
                            if (jqXHR.responseText.includes('Cannot declare class') && jqXHR.responseText.includes('Stripe')) {
                                errorMessage = 'Stripe library conflict detected. Please check WordPress debug log for details.';
                            } else {
                                errorMessage = 'Server error occurred. Please check WordPress debug log (wp-content/debug.log) for details.';
                            }
                        }
                    }
                }
                
                // Show error to user immediately
                showPaymentError(errorMessage);
                
                // Create error object with all details
                const ajaxError = new Error('AJAX request failed: ' + errorMessage);
                ajaxError.status = jqXHR.status;
                ajaxError.statusText = textStatus;
                ajaxError.responseText = jqXHR.responseText;
                ajaxError.responseJSON = errorData;
                ajaxError.jqXHR = jqXHR;
                
                console.error('=== End AJAX Error Details ===');
                
                throw ajaxError;
            });
            
            console.log('Amadex Payment Page: AJAX response received:', response);
            
            if (!response) {
                const errorMsg = 'Empty response from server. Please try again.';
                console.error('Amadex Payment Page:', errorMsg);
                showPaymentError(errorMsg);
                throw new Error(errorMsg);
            }
            
            if (!response.success) {
                const errorMsg = response && response.data && response.data.message 
                    ? response.data.message 
                    : 'Failed to create payment session. Please try again.';
                const errorType = response && response.data && response.data.error_type ? response.data.error_type : 'unknown';
                console.error('Amadex Payment Page: Checkout Session creation failed:', errorMsg);
                console.error('Amadex Payment Page: Error type:', errorType);
                console.error('Amadex Payment Page: Full response:', response);
                showPaymentError(errorMsg);
                throw new Error(errorMsg);
            }
            
            if (!response.data) {
                const errorMsg = 'Response data is missing. Please try again.';
                console.error('Amadex Payment Page:', errorMsg);
                console.error('Amadex Payment Page: Full response:', response);
                showPaymentError(errorMsg);
                throw new Error(errorMsg);
            }
            
            // Standard Stripe Checkout redirect flow - get session_id and url
            sessionId = response.data.session_id || null;
            const checkoutUrl = response.data.url || null;
            
            // Validate: need either url or session_id for redirect
            if (!checkoutUrl && !sessionId) {
                const errorMsg = 'No Checkout Session URL or session_id returned from server. Please try again.';
                console.error('Amadex Payment Page:', errorMsg);
                console.error('Amadex Payment Page: Response data:', response.data);
                showPaymentError(errorMsg);
                throw new Error(errorMsg);
            }
            
            console.log('Amadex Payment Page: Checkout Session created successfully');
            console.log('Amadex Payment Page: Session ID:', sessionId);
            console.log('Amadex Payment Page: Checkout URL:', checkoutUrl);
            
            // Preferred: redirect directly to Stripe-hosted Checkout page using URL
            if (checkoutUrl) {
                console.log('Amadex Payment Page: Redirecting to Stripe Checkout using URL...');
                window.location.href = checkoutUrl;
                return;
            }
            
            // Fallback: use Stripe.js redirectToCheckout if url not provided but session_id is
            if (sessionId && stripe && typeof stripe.redirectToCheckout === 'function') {
                console.log('Amadex Payment Page: Using stripe.redirectToCheckout() with sessionId...');
                const result = await stripe.redirectToCheckout({ sessionId: sessionId });
                if (result.error) {
                    throw new Error(result.error.message || 'Failed to redirect to Checkout');
                }
                return;
            }
            
            throw new Error('No Checkout Session URL or session_id returned from server');
            
        } catch (error) {
            // Always log errors for debugging
            console.error('=== Amadex Payment Page: Checkout Session AJAX Error ===');
            console.error('Error object:', error);
            console.error('Error type:', error.constructor.name);
            console.error('Error status:', error.status);
            console.error('Error statusText:', error.statusText);
            console.error('Error message:', error.message);
            
            // ALWAYS log response text for debugging
            if (error.responseText) {
                console.error('=== Server Response (Status: ' + (error.status || 'N/A') + ') ===');
                console.error('Response text length:', error.responseText.length);
                console.error('Response text (first 2000 chars):', error.responseText.substring(0, 2000));
                
                // Check if it's HTML (fatal PHP error)
                if (error.responseText.trim().startsWith('<!') || error.responseText.includes('<html') || error.responseText.includes('Fatal error')) {
                    console.error('⚠️ CRITICAL: Server returned HTML instead of JSON - this is a PHP fatal error!');
                    console.error('This means the shutdown function did not catch the error properly.');
                    console.error('Full HTML response:', error.responseText);
                    
                    // Try to extract error message from HTML
                    const errorMatch = error.responseText.match(/Fatal error[^<]+/i) || 
                                      error.responseText.match(/Parse error[^<]+/i) ||
                                      error.responseText.match(/Cannot declare class[^<]+/i) ||
                                      error.responseText.match(/<h1[^>]*>([^<]+)<\/h1>/i) ||
                                      error.responseText.match(/<p[^>]*>([^<]+)<\/p>/i);
                    if (errorMatch) {
                        const extractedError = errorMatch[0] || errorMatch[1];
                        console.error('Extracted error from HTML:', extractedError);
                        
                        // Show user-friendly message
                        if (extractedError.includes('Cannot declare class') && extractedError.includes('Stripe')) {
                            showPaymentError('Stripe library conflict detected. Another plugin has already loaded Stripe. Please check WordPress debug log for details.');
                        } else {
                            showPaymentError('Server error occurred. Please check WordPress debug log (wp-content/debug.log) for details.');
                        }
                    } else {
                        showPaymentError('Server error occurred. Please check WordPress debug log (wp-content/debug.log) for details.');
                    }
                } else {
                    // Try to parse as JSON
                    try {
                        const parsedResponse = JSON.parse(error.responseText);
                        console.error('✅ Parsed JSON response:', parsedResponse);
                        if (parsedResponse.data) {
                            console.error('Error data:', parsedResponse.data);
                            if (parsedResponse.data.error_type) {
                                console.error('Error type:', parsedResponse.data.error_type);
                            }
                            if (parsedResponse.data.message) {
                                console.error('Error message:', parsedResponse.data.message);
                                // Show the actual error message to user
                                showPaymentError(parsedResponse.data.message);
                            } else {
                                showPaymentError('Failed to create payment session. Please try again.');
                            }
                            if (parsedResponse.data.error_details && isDebug) {
                                console.error('Error details:', parsedResponse.data.error_details);
                            }
                        }
                    } catch (e) {
                        if (isDebug) {
                            console.warn('Response is not valid JSON:', e.message);
                        }
                    }
                }
            }
            
            // Log responseJSON if available - only in debug mode
            if (error.responseJSON && isDebug) {
                console.error('Response JSON:', JSON.stringify(error.responseJSON, null, 2));
            }
            
            // Log stack trace - only in debug mode
            if (error.stack && isDebug) {
                console.error('Stack trace:', error.stack);
            }
            
            if (isDebug) {
                console.error('=== End of Error Details ===');
            }
            
            let errorMsg = 'Failed to initialize payment. ';
            let errorDetails = '';
            
            // Check if it's a network/timeout error
            if (error.status === 0 || error.statusText === 'timeout' || error.statusText === 'abort') {
                errorMsg = 'Network error or timeout. Please check your internet connection and try again.';
            } else if (error.status === 500) {
                // 500 error - server-side issue
                errorMsg = 'Server error occurred. ';
                
                // Check if response is HTML (fatal PHP error) instead of JSON
                if (error.responseText && (error.responseText.trim().startsWith('<!') || error.responseText.includes('<html'))) {
                    if (isDebug) {
                        console.error('Amadex Payment Page: Server returned HTML instead of JSON - likely a fatal PHP error');
                        console.error('Amadex Payment Page: Response preview:', error.responseText.substring(0, 500));
                    }
                    
                    // Try to extract JSON from HTML response (WordPress error page often includes JSON)
                    const jsonMatch = error.responseText.match(/\{[\s\S]*"success"[\s\S]*\}/);
                    if (jsonMatch) {
                        try {
                            const jsonData = JSON.parse(jsonMatch[0]);
                            if (isDebug) {
                                console.error('✅ Extracted JSON from HTML response:', jsonData);
                            }
                            
                            // Use the extracted JSON data
                            if (jsonData.data) {
                                const errorType = jsonData.data.error_type || '';
                                const errorMessage = jsonData.data.error_message || '';
                                
                                // Check for "class already declared" error
                                if (errorMessage.includes('already in use') || 
                                    errorMessage.includes('already declared') ||
                                    errorMessage.includes('RateLimitException') ||
                                    errorType === 'stripe_class_conflict') {
                                    errorMsg = 'Stripe library conflict detected: Another plugin has loaded Stripe library. ';
                                    errorMsg += 'This causes a "class already declared" error. ';
                                    errorMsg += 'Solution: Please deactivate other Stripe plugins (WooCommerce Stripe, EDD Stripe, etc.) temporarily. ';
                                    errorMsg += 'Error: ' + errorMessage;
                                } else if (errorType === 'fatal_error') {
                                    errorMsg = jsonData.data.message || 'Server error occurred. ';
                                    if (errorMessage) {
                                        errorMsg += ' Details: ' + errorMessage;
                                    }
                                    if (jsonData.data.error_file) {
                                        errorMsg += ' (File: ' + jsonData.data.error_file.split('/').pop() + ':' + jsonData.data.error_line + ')';
                                    }
                                } else {
                                    errorMsg = jsonData.data.message || 'Server error occurred.';
                                }
                            } else {
                                errorMsg = 'Critical server error occurred. The server returned an HTML error page. ';
                                errorMsg += 'Please check WordPress debug log (wp-content/debug.log) for details.';
                            }
                        } catch (e) {
                            // JSON extraction failed, use generic message
                            errorMsg = 'Critical server error occurred. The server returned an HTML error page instead of a JSON response. ';
                            errorMsg += 'This usually indicates a PHP fatal error. Please check:';
                            errorMsg += '\n1. WordPress debug log (wp-content/debug.log)';
                            errorMsg += '\n2. Server error logs';
                            errorMsg += '\n3. Contact your administrator with the error details';
                            
                            // Try to extract error message from HTML if possible
                            const htmlMatch = error.responseText.match(/Fatal error[^<]+/i) ||
                                             error.responseText.match(/<h1[^>]*>([^<]+)<\/h1>/i) || 
                                             error.responseText.match(/<title[^>]*>([^<]+)<\/title>/i);
                            if (htmlMatch) {
                                console.error('Amadex Payment Page: Extracted error from HTML:', htmlMatch[0] || htmlMatch[1]);
                                errorDetails = htmlMatch[0] || htmlMatch[1];
                            }
                        }
                    } else {
                        errorMsg = 'Critical server error occurred. The server returned an HTML error page. ';
                        errorMsg += 'Please check WordPress debug log (wp-content/debug.log) for details.';
                    }
                } else if (error.responseJSON && error.responseJSON.data) {
                    // Valid JSON response with error data
                    if (error.responseJSON.data.message) {
                        errorMsg = error.responseJSON.data.message;
                    }
                    if (error.responseJSON.data.error_details) {
                        errorDetails = error.responseJSON.data.error_details;
                        if (isDebug) {
                            console.error('Amadex Payment Page: Server error details:', errorDetails);
                        }
                    }
                    if (error.responseJSON.data.error_type) {
                        if (isDebug) {
                            console.error('Amadex Payment Page: Error type:', error.responseJSON.data.error_type);
                        }
                        
                        // Provide specific guidance based on error type
                        if (error.responseJSON.data.error_type === 'class_not_found' || 
                            error.responseJSON.data.error_type === 'stripe_client_not_found' ||
                            error.responseJSON.data.error_type === 'stripe_library_not_found' ||
                            error.responseJSON.data.error_type === 'stripe_load_error' ||
                            error.responseJSON.data.error_type === 'stripe_load_exception' ||
                            error.responseJSON.data.error_type === 'class_not_loaded' ||
                            error.responseJSON.data.error_type === 'file_load_error' ||
                            error.responseJSON.data.error_type === 'file_load_exception') {
                            errorMsg = 'Stripe PHP library error. Please ensure Stripe library is properly installed in includes/vendor/stripe/stripe-php/. Contact your administrator.';
                        } else if (error.responseJSON.data.error_type === 'library_not_found' || 
                                   error.responseJSON.data.error_type === 'file_not_found') {
                            errorMsg = 'Stripe library files not found. Please contact your administrator to install Stripe PHP library.';
                        } else if (error.responseJSON.data.error_type === 'fatal_error') {
                            errorMsg = 'Server error occurred. Please check WordPress debug log (wp-content/debug.log) or contact support.';
                            if (error.responseJSON.data.error_details && isDebug) {
                                console.error('Amadex Payment Page: Fatal error details:', error.responseJSON.data.error_details);
                            }
                        } else if (error.responseJSON.data.error_type === 'stripe_api_error') {
                            errorMsg = 'Stripe API error: ' + (error.responseJSON.data.message || 'Unknown error');
                            if (error.responseJSON.data.error_code && isDebug) {
                                console.error('Amadex Payment Page: Stripe error code:', error.responseJSON.data.error_code);
                            }
                        } else if (error.responseJSON.data.error_type === 'stripe_class_conflict') {
                            errorMsg = 'Stripe library conflict detected. Another plugin has loaded Stripe library. ';
                            errorMsg += 'Please deactivate other Stripe plugins temporarily or contact support. ';
                            errorMsg += 'Error: ' + (error.responseJSON.data.error_details || 'Class already declared');
                        } else if (error.responseJSON.data.error_type === 'invalid_api_key' ||
                                   error.responseJSON.data.error_type === 'invalid_api_key_format') {
                            errorMsg = 'Invalid Stripe API key. Please check your Stripe API keys in WordPress Admin → Amadex Settings → Payment Settings.';
                        } else if (error.responseJSON.data.error_type === 'invalid_amount') {
                            errorMsg = 'Invalid payment amount. Please refresh the page and try again.';
                        } else if (error.responseJSON.data.error_type === 'configuration_error') {
                            errorMsg = 'Plugin configuration error. Please deactivate and reactivate the Amadex plugin.';
                        } else if (error.responseJSON.data.error_type === 'fatal_error') {
                            // Check for specific "class already declared" error
                            const errorMessage = error.responseJSON.data.error_message || '';
                            if (errorMessage.includes('already in use') || 
                                errorMessage.includes('already declared') ||
                                errorMessage.includes('RateLimitException')) {
                                errorMsg = 'Stripe library conflict detected: Another plugin has loaded Stripe library. ';
                                errorMsg += 'This causes a "class already declared" error. ';
                                errorMsg += 'Solution: Please deactivate other Stripe plugins (WooCommerce Stripe, EDD Stripe, etc.) or contact support. ';
                                errorMsg += 'Error: ' + errorMessage;
                            } else {
                                errorMsg = error.responseJSON.data.message || 'Server error occurred. Please check WordPress debug log.';
                                if (error.responseJSON.data.error_file) {
                                    errorMsg += ' (File: ' + error.responseJSON.data.error_file.split('/').pop() + ':' + error.responseJSON.data.error_line + ')';
                                }
                                if (errorMessage) {
                                    errorMsg += ' Details: ' + errorMessage;
                                }
                            }
                        }
                    }
                } else {
                    // 500 error but no JSON response structure
                    errorMsg += 'Please check if Stripe library files are properly installed or contact support.';
                    errorMsg += ' Check WordPress debug log (wp-content/debug.log) for details.';
                }
            } else if (error.status === 404) {
                errorMsg = 'Payment endpoint not found. Please contact support.';
            } else if (error.status === 403) {
                errorMsg = 'Access denied. Please refresh the page and try again.';
            } else if (error.responseJSON && error.responseJSON.data && error.responseJSON.data.message) {
                errorMsg = error.responseJSON.data.message;
                if (error.responseJSON.data.error_details) {
                    errorDetails = error.responseJSON.data.error_details;
                }
            } else if (error.message) {
                errorMsg += error.message;
            }
            
            // Show error with details if available
            if (errorDetails && errorDetails.length < 100) {
                errorMsg += ' (' + errorDetails + ')';
            }
            
            console.error('Amadex Payment Page: Final error message:', errorMsg);
            showPaymentError(errorMsg);
            $('#amadex-payment-submit').prop('disabled', true).text('Payment unavailable');
            
            // Re-throw so retry logic can catch it
            throw error;
        }
    }
    
    /**
     * DEPRECATED: initializeCheckout() function removed
     * 
     * This function used Clover API (custom UI) with client_secret and initCheckout().
     * It has been removed because the plugin now uses standard Stripe Checkout redirect flow.
     * 
     * Redirect flow:
     * 1. Create Checkout Session (backend returns session_id + url)
     * 2. Redirect browser to Stripe Checkout (window.location.href = url)
     * 3. User completes payment on Stripe's page
     * 4. Stripe redirects back to success_url with session_id
     * 5. Page checks session status and processes booking
     * 
     * No client_secret, initCheckout, or custom UI needed.
     */
    
    /**
     * Validate email using Checkout actions
     */
    async function validateEmail(email) {
        if (!actions) {
            return { isValid: true, message: null };
        }
        
        try {
            const updateResult = await actions.updateEmail(email);
            const isValid = updateResult.type !== 'error';
            
            if (!isValid) {
                const message = updateResult.error ? updateResult.error.message : 'Invalid email address';
                $('#email-errors').text(message).show();
                $('#amadex-payment-email').addClass('error');
                return { isValid: false, message: message };
            }
            
            return { isValid: true, message: null };
            
        } catch (error) {
            console.error('Email validation error:', error);
            return { isValid: true, message: null }; // Don't block on validation errors
        }
    }
    
    /**
     * Handle payment form submission (redirect flow)
     * 
     * In redirect flow, this creates a Checkout Session and redirects to Stripe Checkout.
     * The user completes payment on Stripe's hosted page, then returns to success/cancel URL.
     */
    async function handlePaymentSubmit(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Amadex Payment Page: Submit button clicked - creating Checkout Session and redirecting...');
        
        // Disable submit button
        const submitBtn = $('#amadex-payment-submit');
        submitBtn.prop('disabled', true).text('Redirecting to payment...');
        
        // Validate email if email field exists (optional - Stripe Checkout also collects email)
        const emailInput = $('#amadex-payment-email');
        if (emailInput.length > 0) {
            const email = emailInput.val().trim();
            if (email) {
                const { isValid, message } = await validateEmail(email);
                if (!isValid) {
                    showPaymentError(message);
                    submitBtn.prop('disabled', false).text('Pay now & book your flights');
                    return;
                }
            }
        }
        
        // Create Checkout Session and redirect (initializePayment handles this)
        try {
            await initializePayment();
            // initializePayment will redirect to Stripe Checkout, so we won't reach here
        } catch (error) {
            console.error('Payment initialization error:', error);
            showPaymentError('Failed to create payment session. Please try again.');
            submitBtn.prop('disabled', false).text('Pay now & book your flights');
        }
    }
    
    /**
     * Show payment error
     */
    function showPaymentError(message) {
        console.error('Amadex Payment Page: Showing error to user:', message);
        
        // Try multiple error container selectors
        let errorContainer = $('#stripe-payment-element-errors');
        if (errorContainer.length === 0) {
            errorContainer = $('#amadex-payment-error');
        }
        if (errorContainer.length === 0) {
            errorContainer = $('.amadex-payment-error');
        }
        if (errorContainer.length === 0) {
            // Create error container if it doesn't exist
            errorContainer = $('<div id="amadex-payment-error" class="amadex-payment-error" style="color: red; padding: 10px; margin: 10px 0; background: #ffe6e6; border: 1px solid #ff9999; border-radius: 4px;"></div>');
            $('#amadex-payment-submit').before(errorContainer);
        }
        
        errorContainer.text(message).addClass('visible').show();
        errorContainer.attr('role', 'alert');
        
        // Also show as alert for critical errors
        if (message.includes('Fatal error') || message.includes('Cannot declare class') || message.includes('Server error')) {
            console.error('CRITICAL ERROR - Showing alert:', message);
        }
    }
    
    /**
     * Hide payment error
     */
    function hidePaymentError() {
        const errorContainer = $('#stripe-payment-element-errors');
        errorContainer.text('').removeClass('visible');
    }
    
    /**
     * Show confirming state (hide payment form, show confirmation/redirect message)
     * Used when returning from Stripe so user does not see complete_payment form
     */
    function showConfirmingState(message) {
        var $container = $('.amadex-payment-page-container');
        var $content = $container.find('.amadex-payment-content');
        $content.hide();
        var $block = $container.find('.amadex-payment-confirming-state');
        if (!$block.length) {
            $block = $('<div class="amadex-payment-confirming-state" role="status" aria-live="polite"></div>');
            $container.find('.amadex-payment-header').after($block);
        }
        $block.html('<div class="amadex-confirming-message">' + (message || 'Confirming your booking...') + '</div><div class="amadex-confirming-spinner"></div>').show();
    }
    
    /**
     * Update confirming state message (e.g. "Redirecting to confirmation...")
     */
    function updateConfirmingMessage(message) {
        var $block = $('.amadex-payment-confirming-state');
        if ($block.length) {
            $block.find('.amadex-confirming-message').text(message || 'Redirecting to your confirmation page...');
            $block.find('.amadex-confirming-spinner').hide();
        }
    }
    
    /**
     * Hide confirming state and show payment form again (e.g. on error)
     */
    function hideConfirmingState() {
        $('.amadex-payment-confirming-state').hide();
        $('.amadex-payment-page-container').find('.amadex-payment-content').show();
    }
    
    /**
     * Check Checkout Session status after redirect (complete page flow)
     */
    async function checkSessionStatus(sessionId) {
        console.log('Amadex Payment Page: Checking session status for:', sessionId);
        
        try {
            const response = await $.ajax({
                url: AmadexPaymentData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_checkout_session_status',
                    session_id: sessionId
                },
                dataType: 'json'
            });
            
            if (!response || !response.success) {
                const errorMsg = response && response.data && response.data.message 
                    ? response.data.message 
                    : 'Failed to verify payment status. Please contact support.';
                console.error('Session status check failed:', errorMsg);
                hideConfirmingState();
                showPaymentError(errorMsg);
                return;
            }
            
            const session = response.data;
            console.log('Session status:', session);
            
            // Process booking when Checkout Session is complete (paid or authorized for manual capture)
            var paymentOk = session.status === 'complete' && (session.payment_status === 'paid' || session.payment_status === 'unpaid');
            if (paymentOk) {
                if (session.payment_intent_id) {
                    await processBookingWithSession(session.payment_intent_id, session);
                } else {
                    hideConfirmingState();
                    showPaymentError('Payment succeeded but no PaymentIntent ID found. Please contact support.');
                }
            } else if (session.status === 'open') {
                hideConfirmingState();
                showPaymentError('Payment was not completed. Please try again.');
            } else {
                hideConfirmingState();
                showPaymentError('Payment status: ' + session.status + '. Please contact support if payment was processed.');
            }
            
        } catch (error) {
            console.error('Session status check error:', error);
            hideConfirmingState();
            showPaymentError('Failed to verify payment status. Please contact support.');
        }
    }
    
    /**
     * Process booking with Checkout Session PaymentIntent
     */
    async function processBookingWithSession(paymentIntentId, session) {
        console.log('Processing booking with PaymentIntent:', paymentIntentId);
        
        const bookingData = AmadexPaymentData.bookingData;
        const bookingToken = AmadexPaymentData.bookingToken;
        
        // Show success message and loading state
        var $submitBtn = $('#amadex-payment-submit');
        $submitBtn.prop('disabled', true).text('Processing...');
        var $statusEl = $('#amadex-payment-status-message, .amadex-payment-status');
        if ($statusEl.length) {
            $statusEl.text('Payment successful! Completing your booking...').addClass('success').show();
        }
        
        try {
            // Send full booking data so backend creates lead/booking and shows in All Leads (pricing, addons, seat_selection, etc.)
            var payload = $.extend(true, {}, bookingData);
            payload.payment_intent_id = paymentIntentId;
            if (!payload.flight) payload.flight = bookingData.flight || {};
            if (!payload.passengers) payload.passengers = bookingData.passengers || [];
            if (!payload.contact) payload.contact = bookingData.contact || {};
            if (!payload.billing) payload.billing = bookingData.billing || {};
            if (!payload.search_params) payload.search_params = bookingData.search_params || {};
            if (!payload.pricing) payload.pricing = bookingData.pricing || {};
            // Ensure backend has all keys needed for total/booking (confirmation page + All Leads)
            if (!payload.addons) payload.addons = bookingData.addons || [];
            if (!payload.seat_selection) payload.seat_selection = bookingData.seat_selection || null;

            // Send booking_data as JSON so backend receives full nested structure (flight, passengers, addons, seat_selection, etc.)
            const response = await $.ajax({
                url: AmadexPaymentData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'amadex_process_booking',
                    nonce: AmadexPaymentData.nonce,
                    payment_intent_id: paymentIntentId,
                    booking_data: JSON.stringify(payload)
                }
            });
            
            if (response.success) {
                // Delete temporary booking data
                try {
                    await $.ajax({
                        url: AmadexPaymentData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'amadex_delete_booking_token',
                            nonce: AmadexPaymentData.nonce,
                            token: bookingToken
                        }
                    });
                } catch (e) {
                    console.warn('Failed to delete booking token:', e);
                }
                
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
                
                bookingKeysToClear.forEach(function(key) {
                    if (sessionStorage.getItem(key) !== null) {
                        sessionStorage.removeItem(key);
                    }
                });
                
                console.log('Amadex: Cleared booking data from sessionStorage after successful booking (before redirect)');
                
                // Show redirect message (and update confirming state if we're on return-from-Stripe view)
                if ($submitBtn.length) $submitBtn.text('Redirecting to confirmation...');
                if ($statusEl.length) $statusEl.text('Booking confirmed! Redirecting to your confirmation page...');
                updateConfirmingMessage('Booking confirmed! Redirecting to your confirmation page...');
                
                // Redirect to Amadex booking confirmation page: booking-confirmation/?reference=AMD...
                // Prefer backend-provided confirmation_url (includes reference); else build from base + reference
                const bookingRef = response.data && response.data.booking_reference ? response.data.booking_reference : '';
                let redirectUrl = response.data && response.data.confirmation_url ? response.data.confirmation_url : '';
                if (!redirectUrl) {
                    const baseUrl = (AmadexPaymentData && AmadexPaymentData.confirmationUrl) ? AmadexPaymentData.confirmationUrl : '/booking-confirmation/';
                    redirectUrl = bookingRef ? baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'reference=' + encodeURIComponent(bookingRef) : baseUrl;
                }
                window.location.href = redirectUrl;
            } else {
                const errorMsg = response.data && response.data.message 
                    ? response.data.message 
                    : 'Booking failed. Please contact support.';
                hideConfirmingState();
                showPaymentError(errorMsg);
            }
            
        } catch (error) {
            console.error('Booking processing error:', error);
            hideConfirmingState();
            showPaymentError('An error occurred while processing your booking. Please contact support.');
        }
    }
    
})(jQuery);