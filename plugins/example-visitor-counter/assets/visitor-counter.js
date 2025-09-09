/**
 * Visitor Counter JavaScript
 * Handles real-time updates and animations
 */
(function($) {
    'use strict';

    // Visitor Counter Object
    window.VisitorCounter = {
        
        // Configuration
        config: {
            updateInterval: 30000, // 30 seconds
            animationDuration: 500,
            endpoint: visitor_counter_ajax.ajax_url,
            nonce: visitor_counter_ajax.nonce
        },

        // Initialize the counter
        init: function() {
            this.bindEvents();
            this.startAutoUpdate();
            this.animateCounters();
        },

        // Bind event handlers
        bindEvents: function() {
            var self = this;
            
            // Refresh button click
            $(document).on('click', '.visitor-counter-refresh', function(e) {
                e.preventDefault();
                self.updateCounter($(this).closest('.visitor-counter'));
            });

            // Page visibility change - pause updates when page is hidden
            if (typeof document.hidden !== "undefined") {
                document.addEventListener("visibilitychange", function() {
                    if (document.hidden) {
                        self.stopAutoUpdate();
                    } else {
                        self.startAutoUpdate();
                    }
                });
            }
        },

        // Start automatic updates
        startAutoUpdate: function() {
            var self = this;
            
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
            }
            
            this.updateTimer = setInterval(function() {
                $('.visitor-counter').each(function() {
                    self.updateCounter($(this));
                });
            }, this.config.updateInterval);
        },

        // Stop automatic updates
        stopAutoUpdate: function() {
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
                this.updateTimer = null;
            }
        },

        // Update counter via AJAX
        updateCounter: function($counter) {
            var self = this;
            var pageId = $counter.data('page-id') || 0;
            
            $.ajax({
                url: this.config.endpoint,
                type: 'POST',
                data: {
                    action: 'visitor_counter_get_stats',
                    page_id: pageId,
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.updateCounterDisplay($counter, response.data);
                    }
                },
                error: function() {
                    console.log('Visitor Counter: Update failed');
                }
            });
        },

        // Update counter display with animation
        updateCounterDisplay: function($counter, data) {
            var self = this;
            
            // Update total visits
            var $visits = $counter.find('.stat-visits .stat-number');
            if ($visits.length && data.total_visits) {
                this.animateNumber($visits, parseInt(data.total_visits));
            }
            
            // Update unique visitors
            var $unique = $counter.find('.stat-unique .stat-number');
            if ($unique.length && data.unique_visitors) {
                this.animateNumber($unique, parseInt(data.unique_visitors));
            }
            
            // Update today's visits
            var $today = $counter.find('.stat-today .stat-number');
            if ($today.length && data.today_visits) {
                this.animateNumber($today, parseInt(data.today_visits));
            }
            
            // Update last visit time
            var $lastVisit = $counter.find('.last-visit');
            if ($lastVisit.length && data.last_visit) {
                $lastVisit.text(data.last_visit);
            }
        },

        // Animate number change
        animateNumber: function($element, newValue) {
            var currentValue = parseInt($element.text().replace(/[^\d]/g, '')) || 0;
            
            if (currentValue === newValue) {
                return; // No change
            }
            
            var difference = newValue - currentValue;
            var increment = difference > 0 ? 1 : -1;
            var steps = Math.abs(difference);
            var stepDuration = this.config.animationDuration / steps;
            
            // Highlight the change
            $element.addClass('updating');
            
            var counter = 0;
            var timer = setInterval(function() {
                currentValue += increment;
                $element.text(currentValue.toLocaleString());
                
                counter++;
                if (counter >= steps) {
                    clearInterval(timer);
                    $element.removeClass('updating');
                    
                    // Add a brief highlight
                    $element.addClass('updated');
                    setTimeout(function() {
                        $element.removeClass('updated');
                    }, 1000);
                }
            }, stepDuration);
        },

        // Animate counters on page load
        animateCounters: function() {
            $('.visitor-counter .stat-number').each(function() {
                var $this = $(this);
                var finalValue = parseInt($this.text().replace(/[^\d]/g, '')) || 0;
                
                if (finalValue > 0) {
                    $this.text('0');
                    
                    setTimeout(function() {
                        var increment = Math.ceil(finalValue / 30);
                        var current = 0;
                        
                        var timer = setInterval(function() {
                            current += increment;
                            if (current >= finalValue) {
                                current = finalValue;
                                clearInterval(timer);
                            }
                            $this.text(current.toLocaleString());
                        }, 50);
                    }, Math.random() * 500);
                }
            });
        },

        // Format numbers with locale
        formatNumber: function(num) {
            return num.toLocaleString();
        }
    };

    // Admin Dashboard Functionality
    window.VisitorCounterAdmin = {
        
        // Initialize admin features
        init: function() {
            this.initCharts();
            this.bindAdminEvents();
        },

        // Bind admin event handlers
        bindAdminEvents: function() {
            var self = this;
            
            // Date range filter
            $(document).on('change', '#stats-date-range', function() {
                self.updateStatsView($(this).val());
            });
            
            // Export data
            $(document).on('click', '.export-stats', function(e) {
                e.preventDefault();
                self.exportStats($(this).data('format'));
            });
            
            // Clear old data
            $(document).on('click', '.clear-old-data', function(e) {
                e.preventDefault();
                if (confirm('Sind Sie sicher, dass Sie alte Daten löschen möchten?')) {
                    self.clearOldData();
                }
            });
        },

        // Initialize charts (if Chart.js is available)
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }
            
            var ctx = document.getElementById('visitor-chart');
            if (!ctx) {
                return;
            }
            
            // Sample chart data - would be populated via AJAX
            var chartData = {
                labels: [],
                datasets: [{
                    label: 'Besucher',
                    data: [],
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.1
                }]
            };
            
            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },

        // Update statistics view
        updateStatsView: function(dateRange) {
            $.ajax({
                url: visitor_counter_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'visitor_counter_admin_stats',
                    date_range: dateRange,
                    nonce: visitor_counter_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the stats display
                        location.reload(); // Simple refresh - could be made more dynamic
                    }
                }
            });
        },

        // Export statistics
        exportStats: function(format) {
            var url = visitor_counter_ajax.ajax_url + 
                     '?action=visitor_counter_export&format=' + format + 
                     '&nonce=' + visitor_counter_ajax.nonce;
            window.open(url, '_blank');
        },

        // Clear old data
        clearOldData: function() {
            $.ajax({
                url: visitor_counter_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'visitor_counter_clear_old_data',
                    nonce: visitor_counter_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Alte Daten wurden erfolgreich gelöscht.');
                        location.reload();
                    } else {
                        alert('Fehler beim Löschen der Daten: ' + response.data);
                    }
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize frontend counter
        if ($('.visitor-counter').length) {
            VisitorCounter.init();
        }
        
        // Initialize admin features
        if ($('.visitor-counter-stats').length) {
            VisitorCounterAdmin.init();
        }
    });

})(jQuery);

// CSS Animations for updates
var style = document.createElement('style');
style.textContent = `
    .visitor-counter .stat-number.updating {
        color: #f56e00;
        transform: scale(1.05);
        transition: all 0.3s ease;
    }
    
    .visitor-counter .stat-number.updated {
        color: #00a32a;
        text-shadow: 0 0 10px rgba(0, 163, 42, 0.3);
        transition: all 0.5s ease;
    }
`;
document.head.appendChild(style);
