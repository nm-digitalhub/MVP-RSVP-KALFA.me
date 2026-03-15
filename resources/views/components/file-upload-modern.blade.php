{{-- 
   Enhanced File Upload Component for Mobile & Desktop
   Usage: <x-file-upload-modern name="document" accept="image/*,application/pdf" />
--}}

@props([
    'name' => 'file',
    'accept' => 'image/*,application/pdf',
    'maxSize' => 10, // MB
    'label' => 'העלאת קובץ',
    'hint' => 'JPG, PNG או PDF עד 10MB',
    'required' => false,
    'multiple' => false,
    'preview' => true,
    'id' => null
])

@php
    $inputId = $id ?? 'file-upload-' . Str::random(8);
    $maxSizeBytes = $maxSize * 1024 * 1024;
@endphp

<div class="file-upload-container" x-data="modernFileUpload({
    name: '{{ $name }}',
    accept: '{{ $accept }}',
    maxSize: {{ $maxSizeBytes }},
    multiple: {{ $multiple ? 'true' : 'false' }},
    preview: {{ $preview ? 'true' : 'false' }}
})" dir="rtl">
    
    <!-- Label -->
    <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-900 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    <!-- Upload Area -->
    <div class="upload-area relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:border-blue-400 focus-within:border-blue-500 transition-all duration-300"
         :class="{
             'border-blue-400 bg-blue-50': isDragging,
             'border-green-400 bg-green-50': files.length > 0,
             'border-red-400 bg-red-50': hasError
         }"
         @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop($event)">
        
        <!-- Hidden File Input -->
        <input type="file" 
               id="{{ $inputId }}"
               name="{{ $name }}{{ $multiple ? '[]' : '' }}"
               accept="{{ $accept }}"
               {{ $multiple ? 'multiple' : '' }}
               {{ $required ? 'required' : '' }}
               class="sr-only"
               @change="handleFileSelect($event)"
               x-ref="fileInput">
        
        <!-- Upload Prompt (shown when no files) -->
        <div x-show="files.length === 0" class="text-center cursor-pointer" @click="$refs.fileInput.click()">
            <div class="mx-auto h-16 w-16 text-gray-400 mb-4" :class="{ 'text-blue-500': isDragging }">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-full h-full">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            
            <p class="text-base font-medium text-gray-900 mb-1">
                <span class="text-blue-600 hover:text-blue-500 cursor-pointer">לחץ להעלאת קובץ</span>
                <span class="hidden sm:inline"> או גרור לכאן</span>
            </p>
            <p class="text-sm text-gray-500 mb-4">{{ $hint }}</p>
            
            <!-- Mobile-friendly Upload Button -->
            <div class="sm:hidden">
                <button type="button" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 touch-manipulation"
                        @click.stop="$refs.fileInput.click()">
                    <svg class="w-4 h-4 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    בחר קובץ
                </button>
            </div>
        </div>
        
        <!-- File List -->
        <div x-show="files.length > 0" class="space-y-3">
            <template x-for="(file, index) in files" :key="index">
                <div class="file-item flex items-center p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                    
                    <!-- File Preview/Icon -->
                    <div class="shrink-0 ms-3">
                        <!-- Image Preview -->
                        <div x-show="file.isImage && file.preview" class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100">
                            <img :src="file.preview" :alt="file.name" class="w-full h-full object-cover">
                        </div>
                        
                        <!-- File Icon -->
                        <div x-show="!file.isImage || !file.preview" class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- File Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="file.name"></p>
                        <div class="flex items-center mt-1">
                            <p class="text-xs text-gray-500" x-text="formatFileSize(file.size)"></p>
                            
                            <!-- Upload Progress -->
                            <div x-show="file.uploading" class="me-2 flex items-center">
                                <div class="w-16 h-1 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 transition-all duration-300" 
                                         :style="`width: ${file.progress}%`"></div>
                                </div>
                                <span class="text-xs text-gray-500 me-1" x-text="`${file.progress}%`"></span>
                            </div>
                            
                            <!-- Success/Error Icons -->
                            <div x-show="file.uploaded" class="me-2">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            
                            <div x-show="file.error" class="me-2">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Error Message -->
                        <p x-show="file.error" x-text="file.errorMessage" class="text-xs text-red-600 mt-1"></p>
                    </div>
                    
                    <!-- Remove Button -->
                    <button type="button" 
                            @click="removeFile(index)"
                            class="shrink-0 p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-gray-100 touch-manipulation">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
            
            <!-- Add More Button -->
            <div x-show="multiple" class="text-center pt-3 border-t border-gray-200">
                <button type="button" 
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-500"
                        @click="$refs.fileInput.click()">
                    <svg class="w-4 h-4 ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    הוסף קובץ נוסף
                </button>
            </div>
        </div>
        
        <!-- Loading Overlay -->
        <div x-show="isUploading" 
             class="absolute inset-0 bg-white/90 flex items-center justify-center rounded-xl">
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                <p class="text-sm text-gray-600">מעלה קבצים...</p>
            </div>
        </div>
    </div>
    
    <!-- Error Message -->
    <div x-show="hasError" class="mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-600" x-text="errorMessage"></p>
    </div>
    
    <!-- Success Message -->
    <div x-show="allUploaded && files.length > 0" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
        <p class="text-sm text-green-600">
            <svg class="w-4 h-4 inline ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            כל הקבצים הועלו בהצלחה
        </p>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('modernFileUpload', (config) => ({
        files: [],
        isDragging: false,
        isUploading: false,
        hasError: false,
        errorMessage: '',
        autoUpload: config.autoUpload || false,
        
        init() {
            // Touch device detection
            this.isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
            
            // PWA detection
            this.isPWA = window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;
            
            // Check if running in service worker context
            this.hasServiceWorker = 'serviceWorker' in navigator;
            
            console.log('File upload component initialized', {
                autoUpload: this.autoUpload,
                isTouchDevice: this.isTouchDevice,
                isPWA: this.isPWA,
                hasServiceWorker: this.hasServiceWorker
            });
        },
        
        handleFileSelect(event) {
            const selectedFiles = Array.from(event.target.files);
            this.processFiles(selectedFiles);
        },
        
        handleDrop(event) {
            this.isDragging = false;
            const droppedFiles = Array.from(event.dataTransfer.files);
            this.processFiles(droppedFiles);
        },
        
        processFiles(newFiles) {
            this.hasError = false;
            this.errorMessage = '';
            
            newFiles.forEach(file => {
                // Validate file
                const validation = this.validateFile(file);
                if (!validation.valid) {
                    this.showError(validation.message);
                    return;
                }
                
                const fileData = {
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    isImage: file.type.startsWith('image/'),
                    preview: null,
                    uploading: false,
                    uploaded: false,
                    error: false,
                    errorMessage: '',
                    progress: 0
                };
                
                // Generate preview for images
                if (fileData.isImage && config.preview) {
                    this.generatePreview(file, fileData);
                }
                
                // Add to files array
                if (config.multiple) {
                    this.files.push(fileData);
                } else {
                    this.files = [fileData];
                }
                
                // Auto-upload if configured
                if (this.autoUpload) {
                    this.uploadFile(fileData);
                }
            });
            
            // Clear the input so the same file can be selected again
            this.$refs.fileInput.value = '';
        },
        
        validateFile(file) {
            // Check file size
            if (file.size > config.maxSize) {
                return {
                    valid: false,
                    message: `הקובץ גדול מדי. מקסימום ${this.formatFileSize(config.maxSize)}`
                };
            }
            
            if (file.size < 1024) {
                return {
                    valid: false,
                    message: 'הקובץ קטן מדי. מינימום 1KB'
                };
            }
            
            // Check file type
            const acceptedTypes = config.accept.split(',').map(type => type.trim());
            const isValidType = acceptedTypes.some(type => {
                if (type === 'image/*') return file.type.startsWith('image/');
                if (type === 'application/pdf') return file.type === 'application/pdf';
                return file.type === type;
            });
            
            if (!isValidType) {
                return {
                    valid: false,
                    message: 'סוג קובץ לא נתמך. השתמש בקבצים המותרים בלבד'
                };
            }
            
            return { valid: true };
        },
        
        generatePreview(file, fileData) {
            const reader = new FileReader();
            reader.onload = (e) => {
                fileData.preview = e.target.result;
            };
            reader.readAsDataURL(file);
        },
        
        removeFile(index) {
            this.files.splice(index, 1);
            if (this.files.length === 0) {
                this.hasError = false;
                this.errorMessage = '';
            }
        },
        
        showError(message) {
            this.hasError = true;
            this.errorMessage = message;
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        get allUploaded() {
            return this.files.length > 0 && this.files.every(file => file.uploaded);
        },
        
        // Real upload with progress tracking
        async uploadFile(fileData) {
            try {
                fileData.uploading = true;
                fileData.progress = 0;
                fileData.error = false;
                fileData.errorMessage = '';
                
                const formData = new FormData();
                formData.append('document', fileData.file);
                formData.append('document_type', 'israeli_id');
                formData.append('enable_ocr', 'true');
                formData.append('session_id', this.generateSessionId());
                
                const response = await fetch('/api/kyc/upload-document', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`Upload failed: ${response.statusText}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    fileData.progress = 100;
                    fileData.uploading = false;
                    fileData.uploaded = true;
                    fileData.sessionId = result.upload_data.session_id;
                    
                    // Store processing results
                    if (result.processing) {
                        fileData.processingResults = result.processing;
                    }
                    
                    console.log('Upload successful:', result);
                } else {
                    throw new Error(result.message || 'Upload failed');
                }
                
            } catch (error) {
                fileData.uploading = false;
                fileData.error = true;
                fileData.errorMessage = error.message;
                fileData.progress = 0;
                
                console.error('Upload error:', error);
            }
        },
        
        // Simulate upload progress (fallback for testing)
        simulateUpload(fileData) {
            fileData.uploading = true;
            fileData.progress = 0;
            
            const interval = setInterval(() => {
                fileData.progress += Math.random() * 30;
                if (fileData.progress >= 100) {
                    fileData.progress = 100;
                    fileData.uploading = false;
                    fileData.uploaded = true;
                    clearInterval(interval);
                }
            }, 200);
        },
        
        // Helper methods
        generateSessionId() {
            return 'kyc_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },
        
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        }
    }));
});
</script>

<style>
.file-upload-container {
    --upload-border-radius: 0.75rem;
    --upload-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.upload-area {
    transition: var(--upload-transition);
    min-height: 120px;
    background-image: radial-gradient(circle at 1px 1px, rgba(156, 163, 175, 0.3) 1px, transparent 0);
    background-size: 20px 20px;
}

.upload-area:hover {
    background-color: rgba(239, 246, 255, 0.5);
}

.file-item {
    transition: var(--upload-transition);
    backdrop-filter: blur(10px);
}

.file-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Touch-friendly styles for mobile */
@media (max-width: 768px) {
    .upload-area {
        min-height: 140px;
        padding: 1.5rem 1rem;
    }
    
    .file-item {
        padding: 1rem;
    }
    
    /* Larger touch targets */
    button {
        min-height: 44px;
        min-width: 44px;
    }
    
    /* Prevent iOS zoom on input focus */
    input[type="file"] {
        font-size: 16px;
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .upload-area,
    .file-item,
    * {
        transition: none !important;
    }
    
    .animate-spin {
        animation: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .upload-area {
        border-width: 3px;
        border-style: solid;
    }
    
    .file-item {
        border-width: 2px;
    }
}

/* RTL Support */
[dir="rtl"] .file-item {
    direction: rtl;
    text-align: right;
}

[dir="rtl"] .upload-area {
    text-align: center;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .upload-area {
        background-color: rgba(31, 41, 55, 0.5);
        border-color: rgba(75, 85, 99, 0.6);
    }
    
    .file-item {
        background-color: rgba(31, 41, 55, 0.8);
        border-color: rgba(75, 85, 99, 0.6);
    }
}
</style>