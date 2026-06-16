/**
 * Amadex Admin JavaScript
 */
(function($) {
    'use strict';
    
    /**
     * Initialize tab navigation
     */
    function initTabs() {
        $('.amadex-tab-button').on('click', function() {
            // Get target tab
            var targetTab = $(this).data('tab');
            
            // Remove active class from all buttons and content
            $('.amadex-tab-button').removeClass('active');
            $('.amadex-tab-content').removeClass('active');
            
            // Add active class to current button and content
            $(this).addClass('active');
            $('#' + targetTab).addClass('active');
        });
    }
    
    /**
     * Initialize FAQ accordions
     */
    function initFAQAccordions() {
        $('.amadex-faq-question').on('click', function() {
            var $answer = $(this).next('.amadex-faq-answer');
            var $item = $(this).parent('.amadex-faq-item');
            
            if ($answer.is(':visible')) {
                $answer.slideUp(200);
                $item.removeClass('active');
            } else {
                $('.amadex-faq-answer').slideUp(200);
                $('.amadex-faq-item').removeClass('active');
                $answer.slideDown(200);
                $item.addClass('active');
            }
        });
    }
    
    /**
     * Initialize shortcode copying
     */
    function initShortcodeCopy() {
        $('.amadex-copy-shortcode').on('click', function() {
            var shortcode = $(this).data('shortcode');
            
            // Create temporary textarea to copy from
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Update button text temporarily
            var $button = $(this);
            var originalText = $button.html();
            $button.html('<span class="dashicons dashicons-yes"></span>');
            
            setTimeout(function() {
                $button.html(originalText);
            }, 1000);
        });
    }
    
    /**
     * Initialize API connection test
     */
    function initAPITest() {
        $('#amadex-test-api').on('click', function() {
            var $button = $(this);
            var $result = $('#amadex-test-result');
            
            // Disable button during test
            $button.prop('disabled', true).text('Testing...');
            
            // Clear previous results
            $result.removeClass('success error').empty();
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'amadex_test_connection',
                    nonce: amadex_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').html('<p><span class="dashicons dashicons-yes"></span> ' + response.data.message + '</p>');
                    } else {
                        $result.addClass('error').html('<p><span class="dashicons dashicons-no"></span> ' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    $result.addClass('error').html('<p><span class="dashicons dashicons-no"></span> Connection test failed. Please try again.</p>');
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        });
    }
    
    /**
     * Initialize contact form
     */
    function initContactForm() {
        $('#amadex-contact-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $response = $('#amadex-contact-response');
            
            // Disable submit button
            $submit.prop('disabled', true).text('Sending...');
            
            // Clear previous messages
            $response.removeClass('success error').empty();
            
            // Simulate form submission (in a real plugin, this would send an AJAX request)
            setTimeout(function() {
                $response.addClass('success').html('<p>Thank you for your message! We will get back to you shortly.</p>');
                $form[0].reset();
                $submit.prop('disabled', false).text('Send Message');
            }, 1500);
        });
    }
    

    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initTabs();
        initFAQAccordions();
        initShortcodeCopy();
        initAPITest();
        initContactForm();
    });
    
})(jQuery);