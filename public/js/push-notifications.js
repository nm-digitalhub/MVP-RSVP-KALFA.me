/**
 * NM-DigitalHUB Web Push Notifications Manager
 * Version: 1.0.0
 */

class WebPushManager {
    constructor() {
        this.swRegistration = null;
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
        this.isSubscribed = false;
        this.subscription = null;
        this.vapidPublicKey = null;
        this.deviceId = null;
        
        // Hebrew messages
        this.messages = {
            notSupported: 'התראות דחיפה אינן נתמכות בדפדפן זה',
            permissionDenied: 'הרשאת התראות נדחתה',
            subscribeSuccess: 'הרשמה להתראות הושלמה בהצלחה',
            subscribeFailed: 'שגיאה בהרשמה להתראות',
            unsubscribeSuccess: 'ביטול ההרשמה בוצע בהצלחה',
            unsubscribeFailed: 'שגיאה בביטול ההרשמה',
            testSent: 'התראת בדיקה נשלחה',
            testFailed: 'שליחת התראת בדיקה נכשלה'
        };
        
        this.init();
    }

    /**
     * Initialize the push manager
     */
    async init() {
        if (!this.isSupported) {
            console.warn('🚫 [Push] Push notifications not supported');
            this.showMessage(this.messages.notSupported, 'warning');
            return false;
        }

        try {
            // Register service worker
            await this.registerServiceWorker();
            
            // Get VAPID public key and status
            await this.getStatus();
            
            // Update UI
            this.updateUI();
            
            return true;
        } catch (error) {
            console.error('❌ [Push] Initialization failed:', error);
            return false;
        }
    }

    /**
     * Register service worker
     */
    async registerServiceWorker() {
        try {
            this.swRegistration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            });
            
            console.log('✅ [Push] Service worker registered:', this.swRegistration);
            
            // Listen for messages from service worker
            navigator.serviceWorker.addEventListener('message', this.handleServiceWorkerMessage.bind(this));
            
            return this.swRegistration;
        } catch (error) {
            console.error('❌ [Push] Service worker registration failed:', error);
            throw error;
        }
    }

    /**
     * Handle messages from service worker
     */
    handleServiceWorkerMessage(event) {
        console.log('📨 [Push] Message from SW:', event.data);
        
        if (event.data?.type === 'NOTIFICATION_CLICKED') {
            // Handle notification click
            if (event.data.url && event.data.url !== window.location.pathname) {
                window.location.href = event.data.url;
            }
        }
    }

    /**
     * Get subscription status from server
     */
    async getStatus() {
        try {
            const response = await fetch('/api/web-push/status', {
                headers: this.getAuthHeaders()
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.vapidPublicKey = data.vapid_public_key;
                this.isSubscribed = data.devices_count > 0;
                
                if (data.devices.length > 0) {
                    this.deviceId = data.devices[0].id;
                }
                
                console.log('ℹ️ [Push] Status:', data);
            }
            
            return data;
        } catch (error) {
            console.error('❌ [Push] Failed to get status:', error);
            return null;
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribe() {
        try {
            if (!this.swRegistration) {
                throw new Error('Service worker not registered');
            }

            // Check permission
            let permission = Notification.permission;
            if (permission === 'default') {
                permission = await Notification.requestPermission();
            }

            if (permission !== 'granted') {
                throw new Error(this.messages.permissionDenied);
            }

            // Subscribe to push
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            console.log('✅ [Push] Subscription created:', subscription);

            // Send subscription to server
            const response = await fetch('/api/web-push/subscribe', {
                method: 'POST',
                headers: {
                    ...this.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: this.arrayBufferToBase64(subscription.getKey('p256dh')),
                        auth: this.arrayBufferToBase64(subscription.getKey('auth'))
                    },
                    device_type: this.getDeviceType(),
                    user_agent: navigator.userAgent
                })
            });

            const data = await response.json();

            if (data.success) {
                this.subscription = subscription;
                this.isSubscribed = true;
                this.deviceId = data.device_id;
                
                this.showMessage(this.messages.subscribeSuccess, 'success');
                this.updateUI();
                
                // Show test notification
                this.showLocalNotification(
                    'התראות הופעלו',
                    'תקבל עדכונים חשובים על פעילות המערכת'
                );
            } else {
                throw new Error(data.message || this.messages.subscribeFailed);
            }

            return subscription;
        } catch (error) {
            console.error('❌ [Push] Subscription failed:', error);
            this.showMessage(error.message || this.messages.subscribeFailed, 'error');
            throw error;
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribe() {
        try {
            const response = await fetch('/api/web-push/unsubscribe', {
                method: 'DELETE',
                headers: this.getAuthHeaders()
            });

            const data = await response.json();

            if (data.success) {
                // Unsubscribe from push manager
                if (this.subscription) {
                    await this.subscription.unsubscribe();
                }

                this.subscription = null;
                this.isSubscribed = false;
                this.deviceId = null;

                this.showMessage(this.messages.unsubscribeSuccess, 'success');
                this.updateUI();
            } else {
                throw new Error(data.message || this.messages.unsubscribeFailed);
            }

            return true;
        } catch (error) {
            console.error('❌ [Push] Unsubscribe failed:', error);
            this.showMessage(error.message || this.messages.unsubscribeFailed, 'error');
            throw error;
        }
    }

    /**
     * Send test notification
     */
    async sendTest() {
        try {
            const response = await fetch('/api/web-push/test', {
                method: 'POST',
                headers: this.getAuthHeaders()
            });

            const data = await response.json();

            if (data.success) {
                this.showMessage(this.messages.testSent, 'success');
            } else {
                throw new Error(data.message || this.messages.testFailed);
            }

            return true;
        } catch (error) {
            console.error('❌ [Push] Test failed:', error);
            this.showMessage(error.message || this.messages.testFailed, 'error');
            throw error;
        }
    }

    /**
     * Show local notification (for immediate feedback)
     */
    showLocalNotification(title, body, data = {}) {
        if (!this.isSupported || Notification.permission !== 'granted') {
            return;
        }

        const notification = new Notification(title, {
            body,
            icon: '/images/notification-icon.png',
            badge: '/images/notification-badge.png',
            tag: `local-${Date.now()}`,
            dir: 'rtl',
            lang: 'he',
            data
        });

        // Auto-close after 5 seconds
        setTimeout(() => {
            notification.close();
        }, 5000);
    }

    /**
     * Update UI elements
     */
    updateUI() {
        // Update subscription button
        const subscribeBtn = document.getElementById('push-subscribe-btn');
        const unsubscribeBtn = document.getElementById('push-unsubscribe-btn');
        const testBtn = document.getElementById('push-test-btn');
        const statusEl = document.getElementById('push-status');

        if (subscribeBtn) {
            subscribeBtn.style.display = this.isSubscribed ? 'none' : 'inline-block';
            subscribeBtn.disabled = !this.vapidPublicKey;
        }

        if (unsubscribeBtn) {
            unsubscribeBtn.style.display = this.isSubscribed ? 'inline-block' : 'none';
        }

        if (testBtn) {
            testBtn.style.display = this.isSubscribed ? 'inline-block' : 'none';
        }

        if (statusEl) {
            statusEl.textContent = this.isSubscribed ? 'מופעל' : 'כבוי';
            statusEl.className = this.isSubscribed ? 'status-active' : 'status-inactive';
        }

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('pushStatusChanged', {
            detail: {
                isSubscribed: this.isSubscribed,
                isSupported: this.isSupported,
                deviceId: this.deviceId
            }
        }));
    }

    /**
     * Get authentication headers
     */
    getAuthHeaders() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const headers = {
            'Accept': 'application/json'
        };

        if (token) {
            headers['X-CSRF-TOKEN'] = token;
        }

        // Add authorization header if available
        const authToken = localStorage.getItem('auth_token') || 
                         sessionStorage.getItem('auth_token');
        if (authToken) {
            headers['Authorization'] = `Bearer ${authToken}`;
        }

        return headers;
    }

    /**
     * Get device type
     */
    getDeviceType() {
        const userAgent = navigator.userAgent;
        
        if (/iPad|iPhone|iPod/.test(userAgent)) {
            return 'ios';
        } else if (/Android/.test(userAgent)) {
            return 'android';
        } else if (/Macintosh/.test(userAgent)) {
            return 'macos';
        } else if (/Windows/.test(userAgent)) {
            return 'windows';
        } else {
            return 'unknown';
        }
    }

    /**
     * Show message to user
     */
    showMessage(message, type = 'info') {
        // Try to use existing notification system
        if (window.Swal) {
            // SweetAlert
            window.Swal.fire({
                title: type === 'success' ? 'הצלחה' : type === 'error' ? 'שגיאה' : 'הודעה',
                text: message,
                icon: type,
                timer: 3000,
                showConfirmButton: false
            });
        } else if (window.toastr) {
            // Toastr
            window.toastr[type](message);
        } else {
            // Fallback to alert
            alert(message);
        }
    }

    /**
     * Utility: Convert VAPID key to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Utility: Convert ArrayBuffer to Base64
     */
    arrayBufferToBase64(buffer) {
        const binary = String.fromCharCode.apply(null, new Uint8Array(buffer));
        return window.btoa(binary);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize for authenticated users
    if (document.querySelector('meta[name="user-authenticated"]')) {
        window.pushManager = new WebPushManager();
    }
});

// Global functions for easy access
window.subscribeToPush = () => window.pushManager?.subscribe();
window.unsubscribeFromPush = () => window.pushManager?.unsubscribe();
window.testPushNotification = () => window.pushManager?.sendTest();