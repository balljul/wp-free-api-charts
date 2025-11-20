jQuery(document).ready(function($) {
    
    // Tab switching
    $('.entsoe-tab-button').on('click', function() {
        var tabName = $(this).data('tab');
        
        $('.entsoe-tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.entsoe-tab-content').removeClass('active');
        $('#' + tabName + '-tab').addClass('active');
    });
    
    // Preview functionality
    var previewChart = null;
    
    $('#preview-fetch-btn').on('click', function() {
        var dataType = $('#preview-data-type').val();
        var startDate = $('#preview-start-date').val();
        var endDate = $('#preview-end-date').val();
        var areaCode = $('#preview-area').val();
        
        showStatus('loading', 'Fetching data from ENTSOE API...');
        
        $.ajax({
            url: entsoeAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'entsoe_fetch_data',
                nonce: entsoeAjax.nonce,
                data_type: dataType,
                start_date: startDate,
                end_date: endDate,
                area_code: areaCode
            },
            success: function(response) {
                if (response.success) {
                    showStatus('success', 'Data loaded successfully!' + (response.cached ? ' (from cache)' : ''));
                    renderPreviewChart(response.data, dataType);
                } else {
                    showStatus('error', 'Error: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                showStatus('error', 'AJAX Error: ' + error);
            }
        });
    });
    
    function showStatus(type, message) {
        var $status = $('#preview-status');
        $status.removeClass('success error loading').addClass(type);
        $status.text(message);
    }
    
    function renderPreviewChart(data, dataType) {
        var ctx = document.getElementById('preview-chart');
        
        if (previewChart) {
            previewChart.destroy();
        }
        
        var chartConfig;
        
        if (dataType === 'generation_per_type') {
            chartConfig = createGenerationChart(data);
        } else {
            chartConfig = createStandardChart(data, dataType);
        }
        
        previewChart = new Chart(ctx, chartConfig);
    }
    
    function createStandardChart(data, dataType) {
        var label = dataType === 'day_ahead_prices' ? 'Day-Ahead Prices' :
                   dataType === 'actual_load' ? 'Actual Load' :
                   dataType === 'forecasted_load' ? 'Forecasted Load' : 'Data';
        
        return {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: label + ' (' + data.unit + ')',
                    data: data.values,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
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
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: data.unit
                        }
                    }
                }
            }
        };
    }
    
    function createGenerationChart(data) {
        var colors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16'
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
                borderWidth: 1
            });
            
            colorIndex++;
        }
        
        var firstType = Object.keys(data).find(key => key !== 'unit');
        var labels = data[firstType] ? data[firstType].labels : [];
        
        return {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        display: true,
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        stacked: true,
                        display: true,
                        title: {
                            display: true,
                            text: data.unit || 'MW'
                        }
                    }
                }
            }
        };
    }
});
