/**
 * Amadex Fraud Detection - Device Fingerprinting
 * Collects comprehensive device, browser, and behavior data
 *
 * @package Amadex
 * @since 1.0.0
 */

(function(window) {
    'use strict';
    
    /**
     * Amadex Fraud Detection Object
     */
    window.AmadexFraudDetection = {
        
        // Behavior tracking
        behaviorData: {
            mouseMovements: 0,
            clicks: 0,
            keyStrokes: 0,
            scrollDepth: 0,
            formInteractions: 0,
            timeToFirstClick: null,
            timeToFormStart: null,
            timeToFormSubmit: null,
            formAbandons: 0,
            pageLoadTime: null,
            interactionPattern: 'NORMAL',
            sessionStart: Date.now()
        },
        
        // Device fingerprint cache
        deviceFingerprint: null,
        
        /**
         * Initialize fraud detection
         */
        init: function() {
            // Track page load time
            if (window.performance && window.performance.timing) {
                var timing = window.performance.timing;
                this.behaviorData.pageLoadTime = (timing.loadEventEnd - timing.navigationStart) / 1000;
            }
            
            // Start behavior tracking
            this.startBehaviorTracking();
            
            // Track form interactions
            this.trackFormInteractions();
        },
        
        /**
         * Start behavior tracking
         */
        startBehaviorTracking: function() {
            var self = this;
            var firstClick = true;
            
            // Track mouse movements
            document.addEventListener('mousemove', function() {
                self.behaviorData.mouseMovements++;
            }, { passive: true });
            
            // Track clicks
            document.addEventListener('click', function(e) {
                self.behaviorData.clicks++;
                if (firstClick) {
                    self.behaviorData.timeToFirstClick = (Date.now() - self.behaviorData.sessionStart) / 1000;
                    firstClick = false;
                }
            }, { passive: true });
            
            // Track keystrokes
            document.addEventListener('keydown', function() {
                self.behaviorData.keyStrokes++;
            }, { passive: true });
            
            // Track scroll depth
            var maxScroll = 0;
            window.addEventListener('scroll', function() {
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                var docHeight = document.documentElement.scrollHeight - window.innerHeight;
                var scrollPercent = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
                maxScroll = Math.max(maxScroll, scrollPercent);
                self.behaviorData.scrollDepth = Math.round(maxScroll);
            }, { passive: true });
        },
        
        /**
         * Track form interactions
         */
        trackFormInteractions: function() {
            var self = this;
            var formStarted = false;
            
            // Track form field interactions
            document.addEventListener('focus', function(e) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                    self.behaviorData.formInteractions++;
                    if (!formStarted) {
                        self.behaviorData.timeToFormStart = (Date.now() - self.behaviorData.sessionStart) / 1000;
                        formStarted = true;
                    }
                }
            }, true);
            
            // Track form submission
            var forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function() {
                    self.behaviorData.timeToFormSubmit = (Date.now() - self.behaviorData.sessionStart) / 1000;
                });
            });
        },
        
        /**
         * Collect complete device fingerprint
         * 
         * @return {Object} Device fingerprint data
         */
        collectDeviceFingerprint: function() {
            if (this.deviceFingerprint !== null) {
                return this.deviceFingerprint;
            }
            
            var fingerprint = {
                browser: this.getBrowserInfo(),
                screen: this.getScreenInfo(),
                timezone: this.getTimezoneInfo(),
                hardware: this.getHardwareInfo(),
                plugins: this.getPluginInfo(),
                fingerprint: this.getCanvasFingerprint(),
                fonts: this.getFontFingerprint(),
                privacy: this.getPrivacyInfo(),
                botDetection: this.detectBots(),
                behavior: this.behaviorData,
                session: this.getSessionInfo()
            };
            
            this.deviceFingerprint = fingerprint;
            return fingerprint;
        },
        
        /**
         * Get browser information
         * 
         * @return {Object} Browser info
         */
        getBrowserInfo: function() {
            var nav = window.navigator;
            var ua = nav.userAgent || '';
            
            // Detect browser
            var browser = 'Unknown';
            var version = '';
            var engine = 'Unknown';
            var vendor = nav.vendor || 'Unknown';
            
            if (ua.indexOf('Chrome') > -1 && ua.indexOf('Edg') === -1) {
                browser = 'Chrome';
                var match = ua.match(/Chrome\/([0-9.]+)/);
                version = match ? match[1] : '';
                engine = 'Blink';
            } else if (ua.indexOf('Firefox') > -1) {
                browser = 'Firefox';
                var match = ua.match(/Firefox\/([0-9.]+)/);
                version = match ? match[1] : '';
                engine = 'Gecko';
            } else if (ua.indexOf('Safari') > -1 && ua.indexOf('Chrome') === -1) {
                browser = 'Safari';
                var match = ua.match(/Version\/([0-9.]+)/);
                version = match ? match[1] : '';
                engine = 'WebKit';
            } else if (ua.indexOf('Edg') > -1) {
                browser = 'Edge';
                var match = ua.match(/Edg\/([0-9.]+)/);
                version = match ? match[1] : '';
                engine = 'Blink';
            } else if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) {
                browser = 'Opera';
                var match = ua.match(/(?:Opera|OPR)\/([0-9.]+)/);
                version = match ? match[1] : '';
                engine = 'Blink';
            }
            
            return {
                name: browser,
                version: version,
                engine: engine,
                vendor: vendor,
                language: nav.language || 'en-US',
                languages: nav.languages || [nav.language || 'en-US'],
                platform: nav.platform || 'Unknown',
                cookieEnabled: nav.cookieEnabled !== false,
                doNotTrack: nav.doNotTrack || '0',
                javaEnabled: nav.javaEnabled ? nav.javaEnabled() : false,
                onlineStatus: nav.onLine !== false
            };
        },
        
        /**
         * Get screen information
         * 
         * @return {Object} Screen info
         */
        getScreenInfo: function() {
            var screen = window.screen;
            return {
                width: screen.width || 0,
                height: screen.height || 0,
                availWidth: screen.availWidth || 0,
                availHeight: screen.availHeight || 0,
                colorDepth: screen.colorDepth || 24,
                pixelDepth: screen.pixelDepth || 24,
                orientation: screen.orientation ? screen.orientation.type : 'unknown',
                devicePixelRatio: window.devicePixelRatio || 1
            };
        },
        
        /**
         * Get timezone information
         * 
         * @return {Object} Timezone info
         */
        getTimezoneInfo: function() {
            try {
                var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
                var offset = new Date().getTimezoneOffset();
                return {
                    timezone: timezone,
                    timezoneOffset: -offset, // Negative because getTimezoneOffset returns opposite
                    dateTime: new Date().toISOString(),
                    locale: Intl.DateTimeFormat().resolvedOptions().locale || 'en-US'
                };
            } catch (e) {
                return {
                    timezone: 'Unknown',
                    timezoneOffset: new Date().getTimezoneOffset(),
                    dateTime: new Date().toISOString(),
                    locale: 'en-US'
                };
            }
        },
        
        /**
         * Get hardware information
         * 
         * @return {Object} Hardware info
         */
        getHardwareInfo: function() {
            var nav = window.navigator;
            return {
                cpuClass: nav.cpuClass || 'unknown',
                deviceMemory: nav.deviceMemory || null,
                hardwareConcurrency: nav.hardwareConcurrency || 0,
                maxTouchPoints: nav.maxTouchPoints || 0
            };
        },
        
        /**
         * Get plugin information
         * 
         * @return {Object} Plugin info
         */
        getPluginInfo: function() {
            var nav = window.navigator;
            var plugins = [];
            var mimeTypes = [];
            
            if (nav.plugins && nav.plugins.length > 0) {
                for (var i = 0; i < nav.plugins.length; i++) {
                    var plugin = nav.plugins[i];
                    plugins.push({
                        name: plugin.name || '',
                        filename: plugin.filename || '',
                        description: plugin.description || ''
                    });
                }
            }
            
            if (nav.mimeTypes && nav.mimeTypes.length > 0) {
                for (var i = 0; i < nav.mimeTypes.length; i++) {
                    var mime = nav.mimeTypes[i];
                    mimeTypes.push({
                        type: mime.type || '',
                        description: mime.description || ''
                    });
                }
            }
            
            return {
                plugins: plugins,
                mimeTypes: mimeTypes
            };
        },
        
        /**
         * Get canvas fingerprint
         * 
         * @return {Object} Canvas fingerprint
         */
        getCanvasFingerprint: function() {
            try {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                canvas.width = 200;
                canvas.height = 50;
                
                ctx.textBaseline = 'top';
                ctx.font = '14px Arial';
                ctx.textBaseline = 'alphabetic';
                ctx.fillStyle = '#f60';
                ctx.fillRect(125, 1, 62, 20);
                ctx.fillStyle = '#069';
                ctx.fillText('Amadex Fingerprint 🔒', 2, 15);
                ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                ctx.fillText('Amadex Fingerprint 🔒', 4, 17);
                
                var canvasHash = this.simpleHash(canvas.toDataURL());
                
                // WebGL fingerprint
                var webglVendor = 'Unknown';
                var webglRenderer = 'Unknown';
                try {
                    var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
                    if (gl) {
                        var debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                        if (debugInfo) {
                            webglVendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) || 'Unknown';
                            webglRenderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) || 'Unknown';
                        }
                    }
                } catch (e) {
                    // WebGL not available
                }
                
                var webglHash = this.simpleHash(webglVendor + webglRenderer);
                
                return {
                    canvasHash: canvasHash,
                    webglVendor: webglVendor,
                    webglRenderer: webglRenderer,
                    webglHash: webglHash
                };
            } catch (e) {
                return {
                    canvasHash: 'error',
                    webglVendor: 'error',
                    webglRenderer: 'error',
                    webglHash: 'error'
                };
            }
        },
        
        /**
         * Get font fingerprint (simplified)
         * 
         * @return {Object} Font info
         */
        getFontFingerprint: function() {
            // Common fonts to check
            var commonFonts = ['Arial', 'Times New Roman', 'Courier New', 'Verdana', 'Georgia', 'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS', 'Trebuchet MS', 'Impact'];
            var availableFonts = [];
            
            // Simplified font detection (check if font exists)
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var baseFont = 'monospace';
            var baseSize = '72px';
            ctx.font = baseSize + ' ' + baseFont;
            var baseWidth = ctx.measureText('mmmmmmmmmmlli').width;
            
            for (var i = 0; i < commonFonts.length; i++) {
                ctx.font = baseSize + ' ' + commonFonts[i] + ', ' + baseFont;
                var width = ctx.measureText('mmmmmmmmmmlli').width;
                if (width !== baseWidth) {
                    availableFonts.push(commonFonts[i]);
                }
            }
            
            return {
                fonts: availableFonts,
                fontHash: this.simpleHash(availableFonts.join(','))
            };
        },
        
        /**
         * Get privacy information
         * 
         * @return {Object} Privacy info
         */
        getPrivacyInfo: function() {
            // Detect incognito mode (simplified check)
            var incognito = false;
            try {
                // Check for storage quota (incognito has very limited storage)
                if ('storage' in navigator && 'estimate' in navigator.storage) {
                    navigator.storage.estimate().then(function(estimate) {
                        // If quota is very small, likely incognito
                        if (estimate.quota < 120000000) { // Less than 120MB
                            incognito = true;
                        }
                    }).catch(function() {});
                }
            } catch (e) {
                // Can't detect
            }
            
            return {
                incognitoMode: incognito,
                doNotTrack: navigator.doNotTrack || '0',
                cookieBlocked: !navigator.cookieEnabled,
                javascriptDisabled: false, // If this runs, JS is enabled
                adBlocker: this.detectAdBlocker(),
                trackingProtection: false // Hard to detect
            };
        },
        
        /**
         * Detect ad blocker (simplified)
         * 
         * @return {boolean}
         */
        detectAdBlocker: function() {
            try {
                var testDiv = document.createElement('div');
                testDiv.innerHTML = '&nbsp;';
                testDiv.className = 'adsbox';
                testDiv.style.position = 'absolute';
                testDiv.style.left = '-9999px';
                document.body.appendChild(testDiv);
                var detected = testDiv.offsetHeight === 0;
                document.body.removeChild(testDiv);
                return detected;
            } catch (e) {
                return false;
            }
        },
        
        /**
         * Detect bots and automation tools
         * 
         * @return {Object} Bot detection info
         */
        detectBots: function() {
            var ua = navigator.userAgent.toLowerCase();
            var isBot = false;
            var botConfidence = 0;
            var automationTools = [];
            
            // Check for common bot indicators
            var botPatterns = ['bot', 'crawler', 'spider', 'scraper'];
            for (var i = 0; i < botPatterns.length; i++) {
                if (ua.indexOf(botPatterns[i]) > -1) {
                    isBot = true;
                    botConfidence = 80;
                    break;
                }
            }
            
            // Check for automation tools
            if (window.webdriver) {
                automationTools.push('WebDriver');
                isBot = true;
                botConfidence = 90;
            }
            
            if (navigator.webdriver) {
                automationTools.push('Selenium');
                isBot = true;
                botConfidence = 90;
            }
            
            // Check for headless browser indicators
            var headlessBrowser = false;
            if (navigator.plugins.length === 0 && navigator.languages.length === 0) {
                headlessBrowser = true;
                botConfidence = Math.max(botConfidence, 70);
            }
            
            // Check for PhantomJS
            if (window.callPhantom || window._phantom) {
                automationTools.push('PhantomJS');
                isBot = true;
                botConfidence = 95;
            }
            
            // Check for Puppeteer
            if (window.chrome && window.chrome.runtime && window.chrome.runtime.onConnect) {
                // Additional checks for Puppeteer
                if (navigator.userAgent.indexOf('HeadlessChrome') > -1) {
                    automationTools.push('Puppeteer');
                    isBot = true;
                    botConfidence = 95;
                }
            }
            
            return {
                isBot: isBot,
                botConfidence: botConfidence,
                automationTools: automationTools,
                headlessBrowser: headlessBrowser,
                webdriver: window.webdriver || navigator.webdriver || false,
                selenium: window.webdriver || navigator.webdriver || false,
                puppeteer: navigator.userAgent.indexOf('HeadlessChrome') > -1,
                phantomJS: window.callPhantom || window._phantom || false,
                playwright: false // Hard to detect
            };
        },
        
        /**
         * Get session information
         * 
         * @return {Object} Session info
         */
        getSessionInfo: function() {
            return {
                sessionId: this.getSessionId(),
                sessionStart: new Date(this.behaviorData.sessionStart).toISOString(),
                sessionDuration: (Date.now() - this.behaviorData.sessionStart) / 1000,
                pageViews: 1, // Could be enhanced with history API
                timeOnSite: (Date.now() - this.behaviorData.sessionStart) / 1000,
                referrer: document.referrer || '',
                landingPage: window.location.pathname,
                exitPage: window.location.pathname
            };
        },
        
        /**
         * Get or create session ID
         * 
         * @return {string} Session ID
         */
        getSessionId: function() {
            var sessionId = sessionStorage.getItem('amadex_session_id');
            if (!sessionId) {
                sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                sessionStorage.setItem('amadex_session_id', sessionId);
            }
            return sessionId;
        },
        
        /**
         * Simple hash function
         * 
         * @param {string} str String to hash
         * @return {string} Hash
         */
        simpleHash: function(str) {
            var hash = 0;
            if (str.length === 0) return hash.toString();
            for (var i = 0; i < str.length; i++) {
                var char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return Math.abs(hash).toString(16);
        },
        
        /**
         * Analyze behavior pattern
         */
        analyzeBehaviorPattern: function() {
            var behavior = this.behaviorData;
            
            // Very fast completion
            if (behavior.timeToFormSubmit && behavior.timeToFormSubmit < 30) {
                behavior.interactionPattern = 'SUSPICIOUS';
            }
            
            // No mouse movements but form submitted
            if (behavior.mouseMovements === 0 && behavior.timeToFormSubmit && behavior.timeToFormSubmit > 10) {
                behavior.interactionPattern = 'BOT';
            }
            
            // Very few interactions
            if (behavior.formInteractions < 3 && behavior.timeToFormSubmit) {
                behavior.interactionPattern = 'SUSPICIOUS';
            }
        },
        
        /**
         * Get complete fraud data package
         * 
         * @return {Object} Complete fraud data
         */
        getCompleteFraudData: function() {
            // Analyze behavior before collecting
            this.analyzeBehaviorPattern();
            
            // Update time to form submit if not set
            if (!this.behaviorData.timeToFormSubmit) {
                this.behaviorData.timeToFormSubmit = (Date.now() - this.behaviorData.sessionStart) / 1000;
            }
            
            return this.collectDeviceFingerprint();
        }
    };
    
    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.AmadexFraudDetection.init();
        });
    } else {
        window.AmadexFraudDetection.init();
    }
    
})(window);
