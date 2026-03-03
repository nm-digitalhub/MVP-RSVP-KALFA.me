/**
 * Device Fingerprinting and Advanced Device Information Collection
 * אוסף מידע מתקדם על המכשיר לזיהוי ייחודי
 */

class DeviceFingerprinter {
    constructor() {
        this.fingerprint = {};
        this.isReady = false;
    }

    /**
     * אוסף את כל המידע על המכשיר
     */
    async collectDeviceInfo() {
        try {
            // מידע בסיסי על המכשיר
            await this.collectBasicDeviceInfo();
            
            // מידע על המסך
            this.collectScreenInfo();
            
            // מידע על הדפדפן
            this.collectBrowserInfo();
            
            // מידע טכני מתקדם
            this.collectAdvancedInfo();
            
            // Device Fingerprinting
            await this.generateFingerprints();
            
            // מידע רשת
            await this.collectNetworkInfo();
            
            // מידע גיאוגרפי (אם מאושר)
            await this.collectLocationInfo();
            
            // הרשאות ותכונות
            await this.collectPermissionsAndFeatures();
            
            // מידע PWA
            this.collectPWAInfo();
            
            this.isReady = true;
            return this.fingerprint;
        } catch (error) {
            console.error('Error collecting device info:', error);
            return this.fingerprint;
        }
    }

    /**
     * מידע בסיסי על המכשיר
     */
    async collectBasicDeviceInfo() {
        const ua = navigator.userAgent;
        
        // ניסיון לגלות מידע מתקדם על המכשיר
        this.fingerprint.device_name = this.getDeviceName();
        this.fingerprint.device_model = this.getDeviceModel();
        this.fingerprint.device_platform = navigator.platform || this.getPlatformFromUA();
        this.fingerprint.device_version = this.getOSVersion();
        
        // מידע נוסף מ-navigator
        if ('deviceMemory' in navigator) {
            this.fingerprint.device_memory = navigator.deviceMemory;
        }
        
        if ('hardwareConcurrency' in navigator) {
            this.fingerprint.cpu_cores = navigator.hardwareConcurrency;
        }
    }

    /**
     * מידע על המסך
     */
    collectScreenInfo() {
        this.fingerprint.screen_width = screen.width;
        this.fingerprint.screen_height = screen.height;
        this.fingerprint.screen_pixel_ratio = window.devicePixelRatio || 1;
        this.fingerprint.screen_color_depth = screen.colorDepth;
        
        // מידע נוסף על המסך
        this.fingerprint.screen_available_width = screen.availWidth;
        this.fingerprint.screen_available_height = screen.availHeight;
        this.fingerprint.window_width = window.innerWidth;
        this.fingerprint.window_height = window.innerHeight;
    }

    /**
     * מידע על הדפדפן
     */
    collectBrowserInfo() {
        this.fingerprint.browser_name = this.getBrowserName();
        this.fingerprint.browser_version = this.getBrowserVersion();
        this.fingerprint.browser_engine = this.getBrowserEngine();
        
        // מידע נוסף על הדפדפן
        this.fingerprint.user_agent = navigator.userAgent;
        this.fingerprint.vendor = navigator.vendor;
        this.fingerprint.app_name = navigator.appName;
        this.fingerprint.app_version = navigator.appVersion;
    }

    /**
     * מידע טכני מתקדם
     */
    collectAdvancedInfo() {
        this.fingerprint.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        this.fingerprint.language = navigator.language;
        this.fingerprint.languages = navigator.languages;
        this.fingerprint.cookies_enabled = navigator.cookieEnabled;
        this.fingerprint.do_not_track = navigator.doNotTrack === '1';
        
        // מידע נוסף
        this.fingerprint.max_touch_points = navigator.maxTouchPoints || 0;
        this.fingerprint.pdf_viewer_enabled = navigator.pdfViewerEnabled;
        this.fingerprint.web_driver = navigator.webdriver;
    }

    /**
     * יצירת fingerprints ייחודיים
     */
    async generateFingerprints() {
        try {
            // Canvas fingerprint
            this.fingerprint.canvas_fingerprint = this.generateCanvasFingerprint();
            
            // WebGL fingerprint
            this.fingerprint.webgl_fingerprint = this.generateWebGLFingerprint();
            
            // Audio fingerprint
            this.fingerprint.audio_fingerprint = await this.generateAudioFingerprint();
            
            // Combined device fingerprint
            this.fingerprint.device_fingerprint = this.generateCombinedFingerprint();
        } catch (error) {
            console.error('Error generating fingerprints:', error);
        }
    }

    /**
     * Canvas fingerprinting
     */
    generateCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = 200;
            canvas.height = 50;
            
            // ציור טקסט וצורות
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('Device Fingerprint 🔍', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Device Fingerprint 🔍', 4, 17);
            
            const dataURL = canvas.toDataURL();
            return this.hashString(dataURL);
        } catch (error) {
            return 'canvas_error';
        }
    }

    /**
     * WebGL fingerprinting
     */
    generateWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) return 'webgl_not_supported';
            
            const params = [
                'VERSION',
                'SHADING_LANGUAGE_VERSION',
                'VENDOR',
                'RENDERER',
                'MAX_TEXTURE_SIZE',
                'MAX_VERTEX_ATTRIBS',
                'MAX_VERTEX_UNIFORM_VECTORS',
                'MAX_FRAGMENT_UNIFORM_VECTORS',
                'MAX_VARYING_VECTORS'
            ];
            
            let fingerprint = '';
            params.forEach(param => {
                try {
                    const value = gl.getParameter(gl[param]);
                    fingerprint += param + ':' + value + ';';
                } catch (e) {
                    fingerprint += param + ':error;';
                }
            });
            
            return this.hashString(fingerprint);
        } catch (error) {
            return 'webgl_error';
        }
    }

    /**
     * Audio fingerprinting
     */
    async generateAudioFingerprint() {
        try {
            if (!window.AudioContext && !window.webkitAudioContext) {
                return 'audio_not_supported';
            }
            
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const audioContext = new AudioContext();
            
            const oscillator = audioContext.createOscillator();
            const analyser = audioContext.createAnalyser();
            const gainNode = audioContext.createGain();
            const scriptProcessor = audioContext.createScriptProcessor(4096, 1, 1);
            
            oscillator.type = 'triangle';
            oscillator.frequency.setValueAtTime(10000, audioContext.currentTime);
            
            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            
            oscillator.connect(analyser);
            analyser.connect(scriptProcessor);
            scriptProcessor.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.start(0);
            
            const frequencies = new Float32Array(analyser.frequencyBinCount);
            analyser.getFloatFrequencyData(frequencies);
            
            oscillator.stop();
            audioContext.close();
            
            return this.hashString(frequencies.toString());
        } catch (error) {
            return 'audio_error';
        }
    }

    /**
     * מידע רשת
     */
    async collectNetworkInfo() {
        // Connection API
        if ('connection' in navigator) {
            const conn = navigator.connection;
            this.fingerprint.connection_type = conn.effectiveType;
            this.fingerprint.downlink = conn.downlink;
            this.fingerprint.rtt = conn.rtt;
        }
        
        this.fingerprint.online = navigator.onLine;
        
        // מידע נוסף על הרשת
        this.fingerprint.network_info = {
            effective_type: navigator.connection?.effectiveType,
            downlink_max: navigator.connection?.downlinkMax,
            save_data: navigator.connection?.saveData
        };
    }

    /**
     * מידע גיאוגרפי
     */
    async collectLocationInfo() {
        if (!navigator.geolocation) return;
        
        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    resolve, 
                    reject, 
                    { timeout: 5000, enableHighAccuracy: false }
                );
            });
            
            this.fingerprint.latitude = position.coords.latitude;
            this.fingerprint.longitude = position.coords.longitude;
        } catch (error) {
            // המשתמש סירב או שגיאה אחרת
            this.fingerprint.location_denied = true;
        }
    }

    /**
     * הרשאות ותכונות
     */
    async collectPermissionsAndFeatures() {
        const features = [];
        const permissions = {};
        
        // בדיקת תכונות זמינות
        const featuresToCheck = [
            'serviceWorker',
            'pushManager',
            'notifications',
            'geolocation',
            'camera',
            'microphone',
            'bluetooth',
            'usb',
            'mediaDevices',
            'webgl',
            'webgl2',
            'webrtc',
            'indexeddb',
            'localstorage',
            'sessionstorage'
        ];
        
        featuresToCheck.forEach(feature => {
            if (this.checkFeatureSupport(feature)) {
                features.push(feature);
            }
        });
        
        // בדיקת הרשאות
        if ('permissions' in navigator) {
            const permissionsToCheck = ['notifications', 'geolocation', 'camera', 'microphone'];
            
            for (const permission of permissionsToCheck) {
                try {
                    const result = await navigator.permissions.query({ name: permission });
                    permissions[permission] = result.state;
                } catch (error) {
                    permissions[permission] = 'unknown';
                }
            }
        }
        
        this.fingerprint.features = features;
        this.fingerprint.permissions = permissions;
    }

    /**
     * מידע PWA
     */
    collectPWAInfo() {
        this.fingerprint.is_pwa = window.matchMedia('(display-mode: standalone)').matches;
        this.fingerprint.is_installed = 'standalone' in window.navigator;
        
        // בדיקת Service Worker
        if ('serviceWorker' in navigator) {
            this.fingerprint.has_service_worker = true;
            navigator.serviceWorker.getRegistrations().then(registrations => {
                this.fingerprint.service_worker_count = registrations.length;
            });
        }
    }

    /**
     * יצירת fingerprint משולב
     */
    generateCombinedFingerprint() {
        const components = [
            this.fingerprint.canvas_fingerprint,
            this.fingerprint.webgl_fingerprint,
            this.fingerprint.audio_fingerprint,
            this.fingerprint.screen_width + 'x' + this.fingerprint.screen_height,
            this.fingerprint.timezone,
            this.fingerprint.language,
            this.fingerprint.browser_name + this.fingerprint.browser_version,
            this.fingerprint.device_platform
        ];
        
        return this.hashString(components.filter(c => c).join('|'));
    }

    /**
     * Helper methods
     */
    getDeviceName() {
        const ua = navigator.userAgent;
        
        // iPhone/iPad
        if (/iPhone/.test(ua)) return 'iPhone';
        if (/iPad/.test(ua)) return 'iPad';
        
        // Android devices
        const androidMatch = ua.match(/Android[^;]*; ([^)]+)/);
        if (androidMatch) return androidMatch[1];
        
        // Windows devices
        if (/Windows/.test(ua)) return 'Windows PC';
        
        // Mac
        if (/Macintosh/.test(ua)) return 'Mac';
        
        return 'Unknown Device';
    }

    getDeviceModel() {
        const ua = navigator.userAgent;
        
        // iPhone models
        const iphoneMatch = ua.match(/iPhone OS (\d+_\d+)/);
        if (iphoneMatch) return `iPhone (iOS ${iphoneMatch[1].replace('_', '.')})`;
        
        // Android models
        const androidMatch = ua.match(/Android (\d+\.?\d*)/);
        if (androidMatch) return `Android ${androidMatch[1]}`;
        
        return null;
    }

    getPlatformFromUA() {
        const ua = navigator.userAgent;
        if (/iPhone|iPad/.test(ua)) return 'iOS';
        if (/Android/.test(ua)) return 'Android';
        if (/Windows/.test(ua)) return 'Windows';
        if (/Macintosh/.test(ua)) return 'macOS';
        if (/Linux/.test(ua)) return 'Linux';
        return 'Unknown';
    }

    getOSVersion() {
        const ua = navigator.userAgent;
        
        // iOS
        const iosMatch = ua.match(/OS (\d+_\d+_?\d*)/);
        if (iosMatch) return iosMatch[1].replace(/_/g, '.');
        
        // Android
        const androidMatch = ua.match(/Android (\d+\.?\d*\.?\d*)/);
        if (androidMatch) return androidMatch[1];
        
        // Windows
        const windowsMatch = ua.match(/Windows NT (\d+\.\d+)/);
        if (windowsMatch) return windowsMatch[1];
        
        return null;
    }

    getBrowserName() {
        const ua = navigator.userAgent;
        
        if (/Chrome/.test(ua) && !/Edge/.test(ua)) return 'Chrome';
        if (/Firefox/.test(ua)) return 'Firefox';
        if (/Safari/.test(ua) && !/Chrome/.test(ua)) return 'Safari';
        if (/Edge/.test(ua)) return 'Edge';
        if (/Opera/.test(ua)) return 'Opera';
        
        return 'Unknown';
    }

    getBrowserVersion() {
        const ua = navigator.userAgent;
        
        const chromeMatch = ua.match(/Chrome\/(\d+\.\d+)/);
        if (chromeMatch) return chromeMatch[1];
        
        const firefoxMatch = ua.match(/Firefox\/(\d+\.\d+)/);
        if (firefoxMatch) return firefoxMatch[1];
        
        const safariMatch = ua.match(/Version\/(\d+\.\d+)/);
        if (safariMatch) return safariMatch[1];
        
        return null;
    }

    getBrowserEngine() {
        const ua = navigator.userAgent;
        
        if (/WebKit/.test(ua)) return 'WebKit';
        if (/Gecko/.test(ua) && !/WebKit/.test(ua)) return 'Gecko';
        if (/Trident/.test(ua)) return 'Trident';
        
        return 'Unknown';
    }

    checkFeatureSupport(feature) {
        switch (feature) {
            case 'serviceWorker':
                return 'serviceWorker' in navigator;
            case 'pushManager':
                return 'PushManager' in window;
            case 'notifications':
                return 'Notification' in window;
            case 'geolocation':
                return 'geolocation' in navigator;
            case 'mediaDevices':
                return 'mediaDevices' in navigator;
            case 'webgl':
                try {
                    const canvas = document.createElement('canvas');
                    return !!(canvas.getContext('webgl') || canvas.getContext('experimental-webgl'));
                } catch (e) {
                    return false;
                }
            case 'indexeddb':
                return 'indexedDB' in window;
            case 'localstorage':
                return 'localStorage' in window;
            case 'sessionstorage':
                return 'sessionStorage' in window;
            default:
                return feature in window || feature in navigator;
        }
    }

    hashString(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        return Math.abs(hash).toString(16);
    }

    /**
     * שליחת המידע לשרת
     */
    async sendToServer(endpoint = '/api/device-info') {
        if (!this.isReady) {
            await this.collectDeviceInfo();
        }
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(this.fingerprint)
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error sending device info to server:', error);
            return null;
        }
    }
}

// יצירת instance גלובלי
window.DeviceFingerprinter = DeviceFingerprinter;

// אתחול אוטומטי
document.addEventListener('DOMContentLoaded', () => {
    window.deviceFingerprinter = new DeviceFingerprinter();
});