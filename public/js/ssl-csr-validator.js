/**
 * SSL CSR Validator - Real-time CSR validation for SSL checkout
 * Validates CSR format and extracts certificate information
 */
class CSRValidator {
    constructor() {
        this.csrInput = document.getElementById('csr');
        this.validationDiv = document.getElementById('csr-validation');
        this.domainInput = document.getElementById('ssl_primary_domain');
        this.organizationInputs = this.getOrganizationInputs();
        
        this.debounceTimer = null;
        this.lastValidCSR = null;
        
        this.init();
    }

    init() {
        if (!this.csrInput) {
            console.log('CSR input not found, skipping CSR validator initialization');
            return;
        }

        // Add validation container if it doesn't exist
        this.ensureValidationContainer();
        
        // Bind events
        this.csrInput.addEventListener('input', this.handleCSRInput.bind(this));
        this.csrInput.addEventListener('paste', this.handlePaste.bind(this));
        this.csrInput.addEventListener('blur', this.handleBlur.bind(this));
        
        console.log('CSR Validator initialized successfully');
    }

    ensureValidationContainer() {
        if (!this.validationDiv) {
            this.validationDiv = document.createElement('div');
            this.validationDiv.id = 'csr-validation';
            this.validationDiv.className = 'csr-validation mt-2';
            this.csrInput.parentNode.appendChild(this.validationDiv);
        }
    }

    getOrganizationInputs() {
        return {
            name: document.getElementById('organization_name'),
            unit: document.getElementById('organization_unit'),
            city: document.getElementById('organization_city'),
            state: document.getElementById('organization_state'),
            country: document.getElementById('organization_country')
        };
    }

    handleCSRInput(event) {
        this.debounceValidation();
    }

    handlePaste(event) {
        // Short delay to allow paste content to be processed
        setTimeout(() => {
            this.debounceValidation();
        }, 100);
    }

    handleBlur(event) {
        // Immediate validation on blur
        this.validateCSR();
    }

    debounceValidation() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        
        this.debounceTimer = setTimeout(() => {
            this.validateCSR();
        }, 500);
    }

    async validateCSR() {
        const csr = this.csrInput.value.trim();
        
        if (!csr) {
            this.showValidationResult('', 'neutral');
            return;
        }

        // Show loading state
        this.showValidationResult('🔄 בודק תקינות CSR...', 'loading');

        // Basic format validation
        if (!this.isValidCSRFormat(csr)) {
            this.showValidationResult(
                '❌ פורמט CSR לא תקין. CSR חייב להתחיל ב-"-----BEGIN CERTIFICATE REQUEST-----" ולהסתיים ב-"-----END CERTIFICATE REQUEST-----"', 
                'error'
            );
            return;
        }

        try {
            // Server-side validation
            const result = await this.validateCSROnServer(csr);
            
            if (result.valid) {
                this.handleValidCSR(result);
            } else {
                this.showValidationResult(`❌ ${result.error}`, 'error');
            }
            
        } catch (error) {
            console.error('CSR validation error:', error);
            this.showValidationResult('⚠️ שגיאה בבדיקת CSR. אנא נסה שוב.', 'error');
        }
    }

    isValidCSRFormat(csr) {
        const csrRegex = /-----BEGIN CERTIFICATE REQUEST-----[\s\S]*-----END CERTIFICATE REQUEST-----/;
        return csrRegex.test(csr);
    }

    async validateCSROnServer(csr) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (!csrfToken) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch('/api/validate-csr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ csr: csr })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    handleValidCSR(result) {
        this.lastValidCSR = result;
        
        // Build success message
        let message = '✅ CSR תקין';
        let details = [];
        
        if (result.key_size) {
            details.push(`רמת הצפנה: ${result.key_size} bit`);
        }
        
        if (result.common_name) {
            details.push(`דומיין: ${result.common_name}`);
        }
        
        if (result.organization) {
            details.push(`ארגון: ${result.organization}`);
        }
        
        if (details.length > 0) {
            message += ` (${details.join(', ')})`;
        }
        
        this.showValidationResult(message, 'success');
        
        // Auto-fill form fields if available
        this.autoFillFormFields(result);
    }

    autoFillFormFields(csrData) {
        // Fill domain name if available and field is empty
        if (csrData.common_name && this.domainInput && !this.domainInput.value) {
            this.domainInput.value = csrData.common_name;
            
            // Trigger change event to update any dependent fields
            this.domainInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Fill organization fields if available and certificate type is OV/EV
        if (this.isOrganizationValidationRequired()) {
            this.fillOrganizationFields(csrData);
        }
    }

    isOrganizationValidationRequired() {
        const certTypeInputs = document.querySelectorAll('input[name="certificate_type"]');
        for (let input of certTypeInputs) {
            if (input.checked && (input.value === 'organization_validated' || input.value === 'extended_validation')) {
                return true;
            }
        }
        return false;
    }

    fillOrganizationFields(csrData) {
        if (csrData.organization && this.organizationInputs.name && !this.organizationInputs.name.value) {
            this.organizationInputs.name.value = csrData.organization;
        }
        
        if (csrData.organizational_unit && this.organizationInputs.unit && !this.organizationInputs.unit.value) {
            this.organizationInputs.unit.value = csrData.organizational_unit;
        }
        
        if (csrData.locality && this.organizationInputs.city && !this.organizationInputs.city.value) {
            this.organizationInputs.city.value = csrData.locality;
        }
        
        if (csrData.state && this.organizationInputs.state && !this.organizationInputs.state.value) {
            this.organizationInputs.state.value = csrData.state;
        }
        
        if (csrData.country && this.organizationInputs.country && !this.organizationInputs.country.value) {
            this.organizationInputs.country.value = csrData.country;
        }
    }

    showValidationResult(message, type) {
        if (!this.validationDiv) return;
        
        this.validationDiv.innerHTML = message;
        this.validationDiv.className = `csr-validation mt-2 csr-validation-${type}`;
        
        // Add some basic styling if not already present
        if (!document.getElementById('csr-validation-styles')) {
            this.addValidationStyles();
        }
    }

    addValidationStyles() {
        const style = document.createElement('style');
        style.id = 'csr-validation-styles';
        style.textContent = `
            .csr-validation {
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 0.875rem;
                line-height: 1.5;
            }
            .csr-validation-success {
                background-color: #d1f2eb;
                color: #0f5132;
                border: 1px solid #badbcc;
            }
            .csr-validation-error {
                background-color: #f8d7da;
                color: #842029;
                border: 1px solid #f5c2c7;
            }
            .csr-validation-loading {
                background-color: #d1ecf1;
                color: #0c5460;
                border: 1px solid #b8daff;
            }
            .csr-validation-neutral {
                background-color: transparent;
                color: transparent;
                border: none;
                padding: 0;
            }
        `;
        document.head.appendChild(style);
    }

    // Public method to get the last valid CSR data
    getLastValidCSR() {
        return this.lastValidCSR;
    }

    // Public method to manually trigger validation
    triggerValidation() {
        this.validateCSR();
    }
}

// Enhanced CSR Generator (optional utility)
class CSRGenerator {
    constructor() {
        this.generateButton = document.getElementById('generate-csr-btn');
        this.init();
    }

    init() {
        if (this.generateButton) {
            this.generateButton.addEventListener('click', this.handleGenerateCSR.bind(this));
        }
    }

    async handleGenerateCSR() {
        const domainName = document.getElementById('ssl_primary_domain')?.value;
        const organizationName = document.getElementById('organization_name')?.value;
        
        if (!domainName) {
            alert('אנא הזן שם דומיין לפני יצירת CSR');
            return;
        }

        try {
            this.generateButton.disabled = true;
            this.generateButton.textContent = 'מייצר CSR...';
            
            // This would typically call a server endpoint to generate CSR
            const response = await fetch('/api/generate-csr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    domain: domainName,
                    organization: organizationName,
                    key_size: 2048
                })
            });

            const result = await response.json();
            
            if (result.success) {
                document.getElementById('csr').value = result.csr;
                
                // Trigger validation of the generated CSR
                if (window.csrValidator) {
                    window.csrValidator.triggerValidation();
                }
                
                alert('CSR נוצר בהצלחה!');
            } else {
                alert('שגיאה ביצירת CSR: ' + result.error);
            }
            
        } catch (error) {
            console.error('CSR generation error:', error);
            alert('שגיאה ביצירת CSR. אנא נסה שוב.');
        } finally {
            this.generateButton.disabled = false;
            this.generateButton.textContent = 'יצירת CSR אוטומטית';
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize CSR validator
    window.csrValidator = new CSRValidator();
    
    // Initialize CSR generator (optional)
    window.csrGenerator = new CSRGenerator();
    
    console.log('SSL CSR tools initialized');
});