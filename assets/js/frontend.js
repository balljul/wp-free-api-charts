jQuery(document).ready(function($) {
    
    // Initialize all ENTSOE charts on page load
    initializeCharts();
    
    // Also initialize charts after Elementor preview updates
    if (window.elementorFrontend) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', function($scope) {
            initializeChartsInScope($scope);
        });
    }
    
    function initializeCharts() {
        $('canvas[id^="entsoe-"]').each(function() {
            var $canvas = $(this);
            if (!$canvas.data('chart-initialized')) {
                initializeChart($canvas);
            }
        });
    }
    
    function initializeChartsInScope($scope) {
        $scope.find('canvas[id^="entsoe-"]').each(function() {
            var $canvas = $(this);
            if (!$canvas.data('chart-initialized')) {
                initializeChart($canvas);
            }
        });
    }
    
    function initializeChart($canvas) {
        var settings = $canvas.data('widget-settings');
        var isComparison = $canvas.data('comparison') === true;
        
        if (!settings) return;
        
        $canvas.data('chart-initialized', true);
        
        if (isComparison) {
            loadComparisonData($canvas, settings);
        } else {
            loadSingleData($canvas, settings);
        }
    }
    
    function loadSingleData($canvas, settings) {
        var loadingId = 'loading-' + $canvas.attr('id');
        
        $.ajax({
            url: entsoeAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'entsoe_fetch_data',
                nonce: entsoeAjax.nonce,
                data_type: settings.data_type,
                start_date: settings.start_date,
                end_date: settings.end_date,
                area_code: settings.area_code
            },
            success: function(response) {
                $('#' + loadingId).hide();
                
                if (response.success) {
                    if (settings.data_type === 'generation_per_type') {
                        renderGenerationChart($canvas[0], response.data, settings);
                    } else {
                        renderStandardChart($canvas[0], response.data, settings);
                    }
                } else {
                    showError($canvas, response.error);
                }
            },
            error: function(xhr, status, error) {
                $('#' + loadingId).hide();
                showError($canvas, 'Failed to load data: ' + error);
            }
        });
    }
    
    function loadComparisonData($canvas, settings) {
        var loadingId = 'loading-' + $canvas.attr('id');
        
        var requests = [
            $.ajax({
                url: entsoeAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'entsoe_fetch_data',
                    nonce: entsoeAjax.nonce,
                    data_type: settings.dataset1.data_type,
                    start_date: settings.start_date,
                    end_date: settings.end_date,
                    area_code: settings.dataset1.area_code
                }
            }),
            $.ajax({
                url: entsoeAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'entsoe_fetch_data',
                    nonce: entsoeAjax.nonce,
                    data_type: settings.dataset2.data_type,
                    start_date: settings.start_date,
                    end_date: settings.end_date,
                    area_code: settings.dataset2.area_code
                }
            })
        ];
        
        $.when.apply($, requests).done(function(response1, response2) {
            $('#' + loadingId).hide();
            
            if (response1[0].success && response2[0].success) {
                renderComparisonChart($canvas[0], response1[0].data, response2[0].data, settings);
            } else {
                var error = !response1[0].success ? response1[0].error : response2[0].error;
                showError($canvas, error);
            }
        }).fail(function(xhr, status, error) {
            $('#' + loadingId).hide();
            showError($canvas, 'Failed to load comparison data: ' + error);
        });
    }
    
    function renderStandardChart(canvas, data, settings) {
        var chartType = settings.chart_type;
        if (chartType === 'area') {
            chartType = 'line';
        }
        
        var config = {
            type: chartType,
            data: {
                labels: data.labels,
                datasets: [{
                    label: settings.data_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + ' (' + data.unit + ')',
                    data: data.values,
                    borderColor: settings.line_color,
                    backgroundColor: settings.chart_type === 'area' ? settings.background_color : settings.line_color,
                    tension: 0.4,
                    fill: settings.chart_type === 'area'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: settings.show_legend
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: settings.show_grid
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            display: settings.show_grid
                        },
                        title: {
                            display: true,
                            text: data.unit
                        }
                    }
                }
            }
        };
        
        new Chart(canvas, config);
    }
    
    function renderGenerationChart(canvas, data, settings) {
        var colors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16',
            '#6366f1', '#f43f5e', '#a3e635', '#fb923c', '#c084fc'
        ];
        
        var datasets = [];
        var colorIndex = 0;
        
        for (var genType in data) {
            if (genType === 'unit') continue;
            
            datasets.push({
                label: genType,
                data: data[genType].values,
                backgroundColor: colors[colorIndex % colors.length],
                borderColor: colors[colorIndex % colors.length],
                borderWidth: settings.chart_type === 'line' ? 2 : 0,
                tension: 0.4,
                fill: settings.chart_type === 'line'
            });
            
            colorIndex++;
        }
        
        var firstType = Object.keys(data).find(key => key !== 'unit');
        var labels = data[firstType] ? data[firstType].labels : [];
        
        var isStacked = settings.chart_type === 'bar';
        
        var config = {
            type: settings.chart_type === 'bar' ? 'bar' : 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: settings.show_legend,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        stacked: isStacked,
                        display: true,
                        grid: {
                            display: settings.show_grid
                        }
                    },
                    y: {
                        stacked: isStacked,
                        display: true,
                        grid: {
                            display: settings.show_grid
                        },
                        title: {
                            display: true,
                            text: data.unit || 'MW'
                        }
                    }
                }
            }
        };
        
        new Chart(canvas, config);
    }
    
    function renderComparisonChart(canvas, data1, data2, settings) {
        var config = {
            type: settings.chart_type,
            data: {
                labels: data1.labels,
                datasets: [
                    {
                        label: settings.dataset1.label + ' (' + data1.unit + ')',
                        data: data1.values,
                        borderColor: settings.dataset1.color,
                        backgroundColor: settings.dataset1.color + '33',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: settings.dataset2.label + ' (' + data2.unit + ')',
                        data: data2.values,
                        borderColor: settings.dataset2.color,
                        backgroundColor: settings.dataset2.color + '33',
                        tension: 0.4,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: settings.show_legend,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: settings.show_grid
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            display: settings.show_grid
                        }
                    }
                }
            }
        };
        
        new Chart(canvas, config);
    }
    
    function showError($canvas, message) {
        $canvas.parent().html('<div class="entsoe-chart-error">Error: ' + message + '</div>');
    }
});
