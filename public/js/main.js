// Main JavaScript for RentFinder SL

document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Initialize dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    console.log('Found dropdown elements:', dropdownElementList.length);
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        console.log('Initializing dropdown:', dropdownToggleEl);
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Additional dropdown event listeners for debugging
    dropdownElementList.forEach(function (dropdownToggleEl) {
        dropdownToggleEl.addEventListener('click', function (e) {
            console.log('Dropdown clicked:', e.target);
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            // Only proceed if href is not just '#' (empty hash)
            if (href && href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(function (input) {
        input.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0 && !value.startsWith('0')) {
                value = '0' + value;
            }
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            e.target.value = value;
        });
    });

    // Price formatting
    const priceInputs = document.querySelectorAll('input[name="price"], input[name="max_price"], input[name="min_price"]');
    priceInputs.forEach(function (input) {
        input.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });
    });

    // Image preview for file uploads
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(function (input) {
        input.addEventListener('change', function (e) {
            const files = e.target.files;
            const previewContainer = document.getElementById('image-preview');

            if (previewContainer && files.length > 0) {
                previewContainer.innerHTML = '';

                Array.from(files).forEach(function (file) {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'img-thumbnail me-2 mb-2';
                            img.style.width = '100px';
                            img.style.height = '100px';
                            img.style.objectFit = 'cover';
                            previewContainer.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    });

    // Search form enhancement
    const searchForm = document.getElementById('property-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            const formData = new FormData(this);
            const params = new URLSearchParams();

            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    params.append(key, value);
                }
            }

            const url = new URL(window.location);
            url.search = params.toString();
            window.location.href = url.toString();
            e.preventDefault();
        });
    }

    // Property card hover effects
    const propertyCards = document.querySelectorAll('.property-card');
    propertyCards.forEach(function (card) {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-5px)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
        });
    });

    // Visit booking form
    const visitForm = document.getElementById('visit-booking-form');
    if (visitForm) {
        visitForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.innerHTML = '<span class="spinner"></span> Booking...';
            submitBtn.disabled = true;

            // Simulate API call (replace with actual AJAX call)
            setTimeout(function () {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                // Show success message
                showAlert('Visit request submitted successfully!', 'success');

                // Reset form
                visitForm.reset();
            }, 2000);
        });
    }

    // Payment form handling
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
            submitBtn.disabled = true;

            // Simulate payment processing
            setTimeout(function () {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                showAlert('Payment processed successfully!', 'success');
            }, 3000);
        });
    }

    // OTP countdown timer
    const otpTimer = document.getElementById('otp-timer');
    if (otpTimer) {
        let timeLeft = 60;
        const timer = setInterval(function () {
            timeLeft--;
            otpTimer.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(timer);
                otpTimer.textContent = 'Resend OTP';
                otpTimer.style.cursor = 'pointer';
                otpTimer.addEventListener('click', function () {
                    // Resend OTP logic here
                    showAlert('OTP sent to your phone number', 'info');
                    timeLeft = 60;
                    clearInterval(timer);
                    startOTPTimer();
                });
            }
        }, 1000);
    }

    // Filter toggle for mobile
    const filterToggle = document.getElementById('filter-toggle');
    const filterPanel = document.getElementById('filter-panel');

    if (filterToggle && filterPanel) {
        filterToggle.addEventListener('click', function () {
            filterPanel.classList.toggle('show');
        });
    }

    // Lazy loading for images
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(function (img) {
        imageObserver.observe(img);
    });
});

// Utility functions
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();

    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    alertContainer.appendChild(alert);

    // Auto-remove after 5 seconds
    setTimeout(function () {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.width = '300px';
    document.body.appendChild(container);
    return container;
}

function formatCurrency(amount) {
    return 'LKR ' + new Intl.NumberFormat('en-LK').format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-LK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function startOTPTimer() {
    const otpTimer = document.getElementById('otp-timer');
    if (otpTimer) {
        let timeLeft = 60;
        const timer = setInterval(function () {
            timeLeft--;
            otpTimer.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(timer);
                otpTimer.textContent = 'Resend OTP';
                otpTimer.style.cursor = 'pointer';
            }
        }, 1000);
    }
}

// AJAX helper function
function makeRequest(url, method = 'GET', data = null) {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: data ? JSON.stringify(data) : null
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        });
}

// Property search with AJAX
function searchProperties(filters) {
    const params = new URLSearchParams(filters);
    return makeRequest(`api/search.php?${params.toString()}`);
}

// Book property visit
function bookVisit(propertyId, visitData) {
    return makeRequest('api/book_visit.php', 'POST', {
        property_id: propertyId,
        ...visitData
    });
}

// Process payment
function processPayment(paymentData) {
    return makeRequest('api/process_payment.php', 'POST', paymentData);
}

// Send OTP
function sendOTP(phone) {
    return makeRequest('api/send_otp.php', 'POST', { phone: phone });
}

// Verify OTP
function verifyOTP(phone, otp) {
    return makeRequest('api/verify_otp.php', 'POST', { phone: phone, otp: otp });
}
