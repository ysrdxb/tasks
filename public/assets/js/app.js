/**
 * AI Project Manager - Main JavaScript
 */

// Global App Object
window.App = {
    // Configuration
    config: {
        baseUrl: window.location.origin,
        apiUrl: window.location.origin + '/api',
        csrfToken: null
    },
    
    // Initialize the application
    init: function() {
        this.setupCSRF();
        this.setupAjax();
        this.setupEventListeners();
        this.setupTooltips();
        this.setupFileUpload();
        this.setupFormValidation();
        console.log('AI Project Manager initialized');
    },
    
    // Setup CSRF token
    setupCSRF: function() {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            this.config.csrfToken = csrfMeta.getAttribute('content');
        }
    },
    
    // Setup AJAX defaults
    setupAjax: function() {
        const self = this;
        
        // jQuery AJAX setup
        if (typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': self.config.csrfToken
                },
                beforeSend: function() {
                    self.showLoading();
                },
                complete: function() {
                    self.hideLoading();
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, status, error);
                }
            });
        }
    },
    
    // Setup global event listeners
    setupEventListeners: function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
        
        // Confirm delete actions
        $(document).on('click', '[data-confirm-delete]', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Task completion toggle
        $(document).on('change', '.task-checkbox', function() {
            const taskId = $(this).data('task-id');
            const isCompleted = $(this).is(':checked');
            App.toggleTaskCompletion(taskId, isCompleted);
        });
        
        // Project status update
        $(document).on('change', '.project-status-select', function() {
            const projectId = $(this).data('project-id');
            const newStatus = $(this).val();
            App.updateProjectStatus(projectId, newStatus);
        });
        
        // Search functionality
        $(document).on('input', '.search-input', function() {
            const query = $(this).val();
            const searchType = $(this).data('search-type') || 'all';
            App.performSearch(query, searchType);
        });
        
        // Filter functionality
        $(document).on('click', '.filter-chip', function() {
            $(this).toggleClass('active');
            App.applyFilters();
        });
    },
    
    // Setup Bootstrap tooltips
    setupTooltips: function() {
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    },
    
    // Setup file upload functionality
    setupFileUpload: function() {
        const uploadAreas = document.querySelectorAll('.file-upload-area');
        
        uploadAreas.forEach(area => {
            const fileInput = area.querySelector('input[type="file"]');
            
            // Drag and drop events
            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                area.classList.add('drag-over');
            });
            
            area.addEventListener('dragleave', function(e) {
                e.preventDefault();
                area.classList.remove('drag-over');
            });
            
            area.addEventListener('drop', function(e) {
                e.preventDefault();
                area.classList.remove('drag-over');
                
                const files = e.dataTransfer.files;
                if (files.length > 0 && fileInput) {
                    fileInput.files = files;
                    App.handleFileUpload(fileInput);
                }
            });
            
            // Click to upload
            area.addEventListener('click', function() {
                if (fileInput) {
                    fileInput.click();
                }
            });
            
            // File input change
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    App.handleFileUpload(this);
                });
            }
        });
    },
    
    // Setup form validation
    setupFormValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    },
    
    // Show loading overlay
    showLoading: function() {
        $('#loadingOverlay').removeClass('d-none');
    },
    
    // Hide loading overlay
    hideLoading: function() {
        $('#loadingOverlay').addClass('d-none');
    },
    
    // Handle AJAX errors
    handleAjaxError: function(xhr, status, error) {
        let message = 'An error occurred. Please try again.';
        
        if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        } else if (xhr.responseText) {
            try {
                const response = JSON.parse(xhr.responseText);
                message = response.message || message;
            } catch (e) {
                // Use default message
            }
        }
        
        this.showAlert(message, 'danger');
    },
    
    // Show alert message
    showAlert: function(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const container = $('.container-fluid').first();
        container.prepend(alertHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.alert').first().fadeOut();
        }, 5000);
    },
    
    // Handle file upload
    handleFileUpload: function(fileInput) {
        const files = fileInput.files;
        if (files.length === 0) return;
        
        const file = files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        // Validate file size
        if (file.size > maxSize) {
            this.showAlert('File size must be less than 10MB', 'danger');
            return;
        }
        
        // Validate file type
        const allowedTypes = [
            'text/plain',
            'image/jpeg',
            'image/png',
            'image/gif',
            'audio/mpeg',
            'audio/wav'
        ];
        
        if (!allowedTypes.includes(file.type)) {
            this.showAlert('Unsupported file type. Please use text, image, or audio files.', 'danger');
            return;
        }
        
        // Update UI to show selected file
        const uploadArea = fileInput.closest('.file-upload-area');
        if (uploadArea) {
            const fileInfo = uploadArea.querySelector('.file-info') || document.createElement('div');
            fileInfo.className = 'file-info mt-3';
            fileInfo.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    <span>${file.name}</span>
                    <span class="badge bg-secondary ms-2">${this.formatFileSize(file.size)}</span>
                </div>
            `;
            
            if (!uploadArea.querySelector('.file-info')) {
                uploadArea.appendChild(fileInfo);
            }
        }
    },
    
    // Format file size
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // Toggle task completion
    toggleTaskCompletion: function(taskId, isCompleted) {
        $.ajax({
            url: `${this.config.apiUrl}/tasks/${taskId}/toggle`,
            method: 'POST',
            data: {
                is_completed: isCompleted
            },
            success: function(response) {
                App.showAlert('Task updated successfully', 'success');
                
                // Update UI
                const taskItem = $(`.task-item[data-task-id="${taskId}"]`);
                if (isCompleted) {
                    taskItem.addClass('completed');
                } else {
                    taskItem.removeClass('completed');
                }
            }
        });
    },
    
    // Update project status
    updateProjectStatus: function(projectId, newStatus) {
        $.ajax({
            url: `${this.config.apiUrl}/projects/${projectId}/status`,
            method: 'POST',
            data: {
                status: newStatus
            },
            success: function(response) {
                App.showAlert('Project status updated successfully', 'success');
                location.reload(); // Refresh to show updated status
            }
        });
    },
    
    // Perform search
    performSearch: function(query, type = 'all') {
        if (query.length < 2) {
            $('.search-results').empty();
            return;
        }
        
        $.ajax({
            url: `${this.config.apiUrl}/search`,
            method: 'GET',
            data: {
                q: query,
                type: type
            },
            success: function(response) {
                App.displaySearchResults(response.results);
            }
        });
    },
    
    // Display search results
    displaySearchResults: function(results) {
        const container = $('.search-results');
        container.empty();
        
        if (results.length === 0) {
            container.html('<p class="text-muted">No results found</p>');
            return;
        }
        
        results.forEach(result => {
            const resultHtml = `
                <div class="search-result-item mb-2">
                    <a href="${result.url}" class="text-decoration-none">
                        <div class="card card-body">
                            <h6 class="mb-1">${result.title}</h6>
                            <p class="mb-1 text-muted">${result.description}</p>
                            <small class="text-muted">${result.type}</small>
                        </div>
                    </a>
                </div>
            `;
            container.append(resultHtml);
        });
    },
    
    // Apply filters
    applyFilters: function() {
        const activeFilters = [];
        $('.filter-chip.active').each(function() {
            activeFilters.push($(this).data('filter'));
        });
        
        // Apply filters to visible items
        $('.filterable-item').each(function() {
            const item = $(this);
            const itemData = item.data();
            let shouldShow = true;
            
            if (activeFilters.length > 0) {
                shouldShow = activeFilters.some(filter => {
                    return item.hasClass(`filter-${filter}`) || 
                           itemData[filter] === true;
                });
            }
            
            if (shouldShow) {
                item.show();
            } else {
                item.hide();
            }
        });
    },
    
    // Utility function to format dates
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    // Utility function to format time
    formatTime: function(minutes) {
        if (minutes < 60) {
            return `${minutes}m`;
        }
        
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;
        
        if (remainingMinutes === 0) {
            return `${hours}h`;
        }
        
        return `${hours}h ${remainingMinutes}m`;
    }
};

// Initialize when DOM is ready
$(document).ready(function() {
    App.init();
});

// Export for use in other scripts
window.App = App;