/**
 * PackageSync - מערכת סנכרון חבילות בזמן אמת
 * מספקת עדכון אוטומטי של חבילות מה-API ועדכון הממשק
 */
class PackageSync {
    constructor(config = {}) {
        this.config = {
            apiEndpoint: config.apiEndpoint || '/api/packages/live',
            enableRealTimeUpdates: config.enableRealTimeUpdates ?? true,
            refreshInterval: config.refreshInterval || 300000, // 5 minutes
            debug: config.debug || false,
            ...config
        };
        
        this.isActive = false;
        this.intervalId = null;
        this.lastUpdate = null;
        
        if (this.config.debug) {
            console.log('🚀 PackageSync initialized with config:', this.config);
        }
        
        this.init();
    }

    init() {
        if (!this.config.apiEndpoint) {
            if (this.config.debug) {
                console.warn('⚠️ PackageSync: No API endpoint configured');
            }
            return;
        }
        
        this.startSync();
    }

    async startSync() {
        if (this.isActive) return;
        
        this.isActive = true;
        
        // עדכון ראשוני
        await this.fetchPackages();
        
        // עדכון מחזורי
        if (this.config.enableRealTimeUpdates) {
            this.intervalId = setInterval(() => {
                this.fetchPackages();
            }, this.config.refreshInterval);
            
            if (this.config.debug) {
                console.log(`📦 PackageSync: Started with ${this.config.refreshInterval}ms interval`);
            }
        }
    }

    async stopSync() {
        this.isActive = false;
        
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        
        if (this.config.debug) {
            console.log('⏹️ PackageSync: Stopped');
        }
    }

    async fetchPackages(filters = {}) {
        try {
            const url = new URL(this.config.apiEndpoint, window.location.origin);
            
            // הוסף פילטרים ל-URL
            Object.keys(filters).forEach(key => {
                if (filters[key] !== null && filters[key] !== undefined) {
                    url.searchParams.append(key, filters[key]);
                }
            });
            
            const response = await fetch(url.toString());
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            this.lastUpdate = new Date();
            
            // שלח אירוע עדכון חבילות
            this.dispatchPackageUpdate(data);
            
            if (this.config.debug) {
                console.log('📦 Packages updated:', data);
            }
            
            return data;
            
        } catch (error) {
            this.handleError('Failed to fetch packages', error);
            return null;
        }
    }

    async refreshPackages(containerSelector, filters = {}) {
        const data = await this.fetchPackages(filters);
        
        if (data && data.packages) {
            this.updatePackageContainer(containerSelector, data.packages);
        }
        
        return data;
    }

    updatePackageContainer(containerSelector, packages) {
        const container = document.querySelector(containerSelector);
        
        if (!container) {
            if (this.config.debug) {
                console.warn(`⚠️ Container not found: ${containerSelector}`);
            }
            return;
        }
        
        try {
            // נקה את הקונטיינר
            container.innerHTML = '';
            
            // הוסף כל חבילה
            packages.forEach(pkg => {
                const packageElement = this.createPackageElement(pkg);
                container.appendChild(packageElement);
            });
            
            // הוסף אנימציות
            this.animatePackageUpdate(container);
            
            if (this.config.debug) {
                console.log(`📦 Updated ${packages.length} packages in ${containerSelector}`);
            }
            
        } catch (error) {
            this.handleError('Failed to update package container', error);
        }
    }

    createPackageElement(pkg) {
        const element = document.createElement('div');
        element.className = 'package-card bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-6';
        
        // בדוק אם זה חבילה מומלצת
        if (pkg.is_featured) {
            element.classList.add('border-2', 'border-blue-500', 'ring-2', 'ring-blue-500', 'ring-opacity-30');
        }
        
        // יצירת SVG icons עבור התגיות
        const starIcon = `<svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>`;

        const heartIcon = `<svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
        </svg>`;
        
        element.innerHTML = `
            ${pkg.is_featured ? `<div class="text-center mb-4"><span class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">${starIcon} מומלץ</span></div>` : ''}
            ${pkg.is_popular ? `<div class="text-center mb-4"><span class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">${heartIcon} פופולרי</span></div>` : ''}
            
            <h3 class="text-2xl font-bold text-center mb-4">${pkg.name || 'חבילה'}</h3>
            <p class="h-16 mb-6 text-center text-slate-600">${pkg.description || ''}</p>
            
            <div class="mb-6 text-center">
                ${pkg.discount_percentage ? `
                    <div class="mb-2">
                        <span class="text-lg text-slate-400 line-through">${pkg.formatted_price || pkg.price || '0'}</span>
                        <span class="mr-2 px-2 py-1 text-xs font-bold text-red-700 bg-red-100 rounded">-${pkg.discount_percentage}%</span>
                    </div>
                ` : ''}
                <span class="text-5xl font-extrabold text-slate-900">${pkg.formatted_discounted_price || pkg.price || '0'}</span>
                <span class="text-slate-500">/${pkg.billing_cycle || 'חודש'}</span>
            </div>
            
            <ul class="grow mb-8 space-y-4 text-right">
                ${(pkg.features || []).map(feature => `
                    <li class="flex items-center">
                        <svg class="shrink-0 w-5 h-5 ml-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>${typeof feature === 'string' ? feature : feature.text || feature.name || 'תכונה'}</span>
                    </li>
                `).join('')}
            </ul>
            
            <div class="space-y-2">
                <a href="${pkg.order_url || '#'}" 
                   class="block w-full py-3 font-semibold text-center text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                    הזמן עכשיו
                </a>
                ${pkg.details_url ? `
                    <a href="${pkg.details_url}" 
                       class="block w-full py-2 font-medium text-center text-blue-600 transition-colors bg-blue-50 rounded-lg hover:bg-blue-100">
                        פרטים נוספים
                    </a>
                ` : ''}
            </div>
        `;
        
        return element;
    }

    animatePackageUpdate(container) {
        // הוסף אנימציה לכל הקלפים
        const cards = container.querySelectorAll('.package-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    dispatchPackageUpdate(data) {
        const event = new CustomEvent('package-sync:packages', {
            detail: {
                packages: data.packages || data,
                data: data,
                timestamp: this.lastUpdate
            }
        });
        
        document.dispatchEvent(event);
    }

    handleError(message, error) {
        const errorEvent = new CustomEvent('package-sync:error', {
            detail: {
                message: message,
                error: error.message || error,
                timestamp: new Date()
            }
        });
        
        document.dispatchEvent(errorEvent);
        
        if (this.config.debug) {
            console.error(`❌ PackageSync Error: ${message}`, error);
        }
    }

    // Method למתן מידע על סטטוס הסינכרון
    getStatus() {
        return {
            isActive: this.isActive,
            lastUpdate: this.lastUpdate,
            config: this.config,
            nextUpdate: this.isActive && this.intervalId ? 
                new Date(Date.now() + this.config.refreshInterval) : null
        };
    }
}

// גרסה גלובלית למי שצריך
if (typeof window !== 'undefined') {
    window.PackageSync = PackageSync;
}

// Export למודולים
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PackageSync;
}