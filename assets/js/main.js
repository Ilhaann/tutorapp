// Document ready function
$(document).ready(function() {
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

    // Form validation
    $('form').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // File upload preview
    $('input[type="file"]').on('change', function() {
        var file = this.files[0];
        var reader = new FileReader();
        var preview = $(this).siblings('.preview');
        
        if (preview.length) {
            reader.onload = function(e) {
                preview.attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // Search functionality
    $('.search-input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.searchable-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Availability slot selection
    $('.availability-slot').on('click', function() {
        if (!$(this).hasClass('booked')) {
            $('.availability-slot').removeClass('selected');
            $(this).addClass('selected');
        }
    });

    // Rating system
    $('.rating-star').on('click', function() {
        var rating = $(this).data('rating');
        $('.rating-star').removeClass('active');
        $('.rating-star').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).addClass('active');
            }
        });
        $('#rating-value').val(rating);
    });

    // Message system
    $('.message-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            var message = $(this).val().trim();
            if (message) {
                // Add message to chat
                var messageHtml = `
                    <div class="message message-sent">
                        <p>${message}</p>
                        <small class="text-muted">Just now</small>
                    </div>
                `;
                $('.message-container').append(messageHtml);
                $(this).val('');
                
                // Scroll to bottom
                $('.message-container').scrollTop($('.message-container')[0].scrollHeight);
                
                // TODO: Send message to server
            }
        }
    });

    // Real-time messaging functionality
    function initMessaging() {
        // Check for unread messages every 30 seconds
        setInterval(function() {
            checkUnreadMessages();
        }, 30000);
        
        // Initial check
        checkUnreadMessages();
    }
    
    function checkUnreadMessages() {
        $.ajax({
            url: '../api/messages.php',
            method: 'POST',
            data: JSON.stringify({
                action: 'get_unread_count'
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success && response.unread_count > 0) {
                    updateMessageBadge(response.unread_count);
                } else {
                    hideMessageBadge();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking unread messages:', error);
            }
        });
    }
    
    function updateMessageBadge(count) {
        var badge = $('.nav-link[href*="messages.php"] .badge');
        if (badge.length === 0) {
            $('.nav-link[href*="messages.php"]').append('<span class="badge bg-danger ms-1">' + count + '</span>');
        } else {
            badge.text(count);
        }
    }
    
    function hideMessageBadge() {
        $('.nav-link[href*="messages.php"] .badge').remove();
    }
    
    // Initialize messaging if on a page with messaging
    if ($('.messages-container').length > 0) {
        initMessaging();
    }

    // Session timer
    function startSessionTimer(duration) {
        var timer = duration, minutes, seconds;
        var display = $('.session-timer');
        
        var interval = setInterval(function() {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.text(minutes + ":" + seconds);

            if (--timer < 0) {
                clearInterval(interval);
                display.text("Session Ended");
                // TODO: Handle session end
            }
        }, 1000);
    }

    // Initialize session timer if present
    if ($('.session-timer').length) {
        var duration = parseInt($('.session-timer').data('duration'));
        if (duration) {
            startSessionTimer(duration);
        }
    }

    // Mobile menu toggle
    $('.navbar-toggler').on('click', function() {
        $('.navbar-collapse').toggleClass('show');
    });

    // Close mobile menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.navbar').length) {
            $('.navbar-collapse').removeClass('show');
        }
    });

    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 70
            }, 1000);
        }
    });

    // Handle payment form submission
    $('.payment-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        
        submitButton.prop('disabled', true);
        submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
        
        // TODO: Handle payment processing
        // Simulate payment processing
        setTimeout(function() {
            submitButton.prop('disabled', false);
            submitButton.text('Pay Now');
            // Show success message
            form.find('.alert').remove();
            form.prepend('<div class="alert alert-success">Payment successful!</div>');
        }, 2000);
    });
}); 