jQuery(document).ready(function($) {
    
    // Initialize all ENTSOE charts on page load
    initializeCharts();
    
    // Also initialize charts after Elementor preview updates
    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', function($scope) {
            initializeChartsInScope($scope);
        });
    } else {
        // Fallback: wait for Elementor to load
        $(window).on('elementor/frontend/init', function() {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', function($scope) {
                    initializeChartsInScope($scope);
                });
            }
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
        
        console.log('Loading comparison data with settings:', settings);
        
        if (!settings.datasets || settings.datasets.length === 0) {
            $('#' + loadingId).hide();
            showError($canvas, 'No datasets configured');
            console.error('No datasets configured for chart');
            return;
        }
        
        console.log('Number of datasets:', settings.datasets.length);
        
        // Create AJAX requests for each dataset
        var requests = [];
        for (var i = 0; i < settings.datasets.length; i++) {
            var dataset = settings.datasets[i];
            
            console.log('Creating AJAX request', i, 'for area:', dataset.area_code);
            
            requests.push(
                $.ajax({
                    url: entsoeAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'entsoe_fetch_data',
                        nonce: entsoeAjax.nonce,
                        data_type: settings.data_type,
                        start_date: settings.start_date,
                        end_date: settings.end_date,
                        area_code: dataset.area_code
                    }
                })
            );
        }
        
        $.when.apply($, requests).done(function() {
            $('#' + loadingId).hide();
            
            var responses = arguments;
            var dataResponses = [];
            var hasError = false;
            var errorMessage = '';
            
            // Handle single vs multiple responses
            if (requests.length === 1) {
                responses = [responses];
            }
            
            // Process all responses
            for (var i = 0; i < responses.length; i++) {
                var response = responses[i][0]; // jQuery AJAX returns [data, status, xhr]
                console.log('Response', i, ':', response);
                
                if (response.data) {
                    console.log('Response data details:', {
                        labels_count: response.data.labels ? response.data.labels.length : 0,
                        values_count: response.data.values ? response.data.values.length : 0,
                        unit: response.data.unit,
                        sample_labels: response.data.labels ? response.data.labels.slice(0, 5) : [],
                        sample_values: response.data.values ? response.data.values.slice(0, 5) : []
                    });
                }
                
                if (response.success) {
                    dataResponses.push(response.data);
                } else {
                    hasError = true;
                    errorMessage = response.error || 'Unknown error occurred';
                    console.error('API error for dataset', i, ':', errorMessage);
                    break;
                }
            }
            
            if (hasError) {
                showError($canvas, errorMessage);
            } else {
                console.log('All data loaded successfully, rendering chart...');
                console.log('Data responses:', dataResponses);
                console.log('Canvas element:', $canvas[0]);
                console.log('Chart.js available:', typeof Chart !== 'undefined');
                renderMultiComparisonChart($canvas[0], dataResponses, settings);
            }
        }).fail(function(xhr, status, error) {
            $('#' + loadingId).hide();
            console.error('AJAX error:', error);
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
                backgroundColor: settings.chart_background_color || '#ffffff',
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
        
        if (settings.chart_type === 'pie') {
            renderPieChart(canvas, data, settings, colors);
            return;
        }
        
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
                backgroundColor: settings.chart_background_color || '#ffffff',
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
    
    function renderPieChart(canvas, data, settings, colors) {
        var labels = [];
        var values = [];
        var backgroundColors = [];
        var colorIndex = 0;
        
        // Calculate total generation for each source (sum all time periods)
        for (var genType in data) {
            if (genType === 'unit') continue;
            
            var total = 0;
            if (data[genType].values) {
                for (var i = 0; i < data[genType].values.length; i++) {
                    total += data[genType].values[i] || 0;
                }
            }
            
            if (total > 0) {
                labels.push(genType);
                values.push(total);
                backgroundColors.push(colors[colorIndex % colors.length]);
                colorIndex++;
            }
        }
        
        var config = {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: backgroundColors,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                backgroundColor: settings.chart_background_color || '#ffffff',
                plugins: {
                    legend: {
                        display: settings.show_legend,
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.parsed;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value.toFixed(0) + ' MW (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        };
        
        new Chart(canvas, config);
    }
    
    function renderComparisonChart(canvas, data1, data2, settings) {
        // Merge and synchronize labels from both datasets
        var allLabels = [];
        var mergedData1 = [];
        var mergedData2 = [];
        
        // Create a map of all unique timestamps
        var timeMap = {};
        
        // Add all timestamps from dataset1
        if (data1.labels && data1.values) {
            for (var i = 0; i < data1.labels.length; i++) {
                timeMap[data1.labels[i]] = true;
            }
        }
        
        // Add all timestamps from dataset2
        if (data2.labels && data2.values) {
            for (var i = 0; i < data2.labels.length; i++) {
                timeMap[data2.labels[i]] = true;
            }
        }
        
        // Sort all unique timestamps
        allLabels = Object.keys(timeMap).sort();
        
        // Create data arrays with null values for missing timestamps
        for (var i = 0; i < allLabels.length; i++) {
            var timestamp = allLabels[i];
            
            // Find value in dataset1
            var value1 = null;
            if (data1.labels && data1.values) {
                var index1 = data1.labels.indexOf(timestamp);
                if (index1 !== -1) {
                    value1 = data1.values[index1];
                }
            }
            mergedData1.push(value1);
            
            // Find value in dataset2
            var value2 = null;
            if (data2.labels && data2.values) {
                var index2 = data2.labels.indexOf(timestamp);
                if (index2 !== -1) {
                    value2 = data2.values[index2];
                }
            }
            mergedData2.push(value2);
        }
        
        var config = {
            type: settings.chart_type,
            data: {
                labels: allLabels,
                datasets: [
                    {
                        label: settings.dataset1.label + ' (' + (data1.unit || '') + ')',
                        data: mergedData1,
                        borderColor: settings.dataset1.color,
                        backgroundColor: settings.dataset1.color + '33',
                        tension: 0.4,
                        fill: false,
                        spanGaps: true
                    },
                    {
                        label: settings.dataset2.label + ' (' + (data2.unit || '') + ')',
                        data: mergedData2,
                        borderColor: settings.dataset2.color,
                        backgroundColor: settings.dataset2.color + '33',
                        tension: 0.4,
                        fill: false,
                        spanGaps: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                backgroundColor: settings.chart_background_color || '#ffffff',
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
    
    function renderMultiComparisonChart(canvas, dataArray, settings) {
        console.log('renderMultiComparisonChart called');
        console.log('Canvas:', canvas);
        console.log('Data array:', dataArray);
        console.log('Settings:', settings);
        
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library not found!');
            showError($(canvas), 'Chart.js library not loaded');
            return;
        }
        
        // Generate default colors for datasets
        var defaultColors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16',
            '#6366f1', '#f43f5e', '#a3e635', '#fb923c', '#c084fc'
        ];
        
        // Merge and synchronize labels from all datasets
        var allLabels = [];
        var timeMap = {};
        
        // Collect all unique timestamps
        for (var i = 0; i < dataArray.length; i++) {
            var data = dataArray[i];
            if (data.labels) {
                for (var j = 0; j < data.labels.length; j++) {
                    timeMap[data.labels[j]] = true;
                }
            }
        }
        
        // Sort all unique timestamps
        allLabels = Object.keys(timeMap).sort();
        
        // Create datasets for Chart.js
        var datasets = [];
        
        for (var i = 0; i < dataArray.length; i++) {
            var data = dataArray[i];
            var datasetConfig = settings.datasets[i];
            var mergedData = [];
            
            // Create data array with null values for missing timestamps
            for (var j = 0; j < allLabels.length; j++) {
                var timestamp = allLabels[j];
                var value = null;
                
                if (data.labels && data.values) {
                    var index = data.labels.indexOf(timestamp);
                    if (index !== -1) {
                        value = data.values[index];
                    }
                }
                mergedData.push(value);
            }
            
            var color = datasetConfig.color || defaultColors[i % defaultColors.length];
            
            datasets.push({
                label: datasetConfig.label + ' (' + (data.unit || '') + ')',
                data: mergedData,
                borderColor: color,
                backgroundColor: color + '33',
                tension: 0.4,
                fill: false,
                spanGaps: true
            });
        }
        
        var config = {
            type: settings.chart_type,
            data: {
                labels: allLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                backgroundColor: settings.chart_background_color || '#ffffff',
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
        
        console.log('Creating Chart.js with config:', config);
        
        try {
            var chart = new Chart(canvas, config);
            console.log('Chart created successfully:', chart);
        } catch (error) {
            console.error('Error creating chart:', error);
            showError($(canvas), 'Error creating chart: ' + error.message);
        }
    }
    
    function showError($canvas, message) {
        $canvas.parent().html('<div class="entsoe-chart-error">Error: ' + message + '</div>');
    }
});
