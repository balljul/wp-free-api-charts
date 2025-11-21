<?php

if (!defined('ABSPATH')) {
    exit;
}

class ENTSOE_Comparison_Chart_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'entsoe_comparison_chart';
    }
    
    public function get_title() {
        return 'ENTSOE Comparison Chart';
    }
    
    public function get_icon() {
        return 'eicon-barcode';
    }
    
    public function get_categories() {
        return ['general'];
    }
    
    protected function register_controls() {
        
        // Preset Templates Section
        $this->start_controls_section(
            'preset_section',
            [
                'label' => 'Chart Presets',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'chart_preset',
            [
                'label' => 'Preset Configuration',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'custom',
                'options' => [
                    'custom' => 'Custom Configuration',
                    'weekly_consumption' => '📊 Weekly Power Consumption (DE + AT)',
                    'daily_prices' => '💰 Daily Price Comparison (DE + AT)',
                    'load_forecast' => '🔮 Germany Actual Load (Today)',
                ],
                'description' => 'Select a preset or use custom configuration',
            ]
        );
        
        $this->add_control(
            'preset_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<div style="background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0;">
                    <strong>Preset 1:</strong> Weekly power consumption (Germany vs Austria)<br>
                    <strong>Preset 2:</strong> Daily electricity prices (Germany vs Austria)<br>
                    <strong>Preset 3:</strong> Germany actual power load for today
                </div>',
                'condition' => [
                    'chart_preset!' => 'custom',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Data Settings Section
        $this->start_controls_section(
            'data_section',
            [
                'label' => 'Data Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'chart_preset' => 'custom',
                ],
            ]
        );
        
        $this->add_control(
            'data_type',
            [
                'label' => 'Metric Type',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'day_ahead_prices',
                'options' => [
                    'day_ahead_prices' => 'Day-Ahead Prices',
                    'intraday_prices' => 'Intraday Prices',
                    'actual_load' => 'Actual Load',
                    'forecasted_load' => 'Forecasted Load',
                ],
            ]
        );
        
        $this->add_control(
            'date_range',
            [
                'label' => 'Date Range',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'today',
                'options' => [
                    'today' => 'Today',
                    'tomorrow' => 'Tomorrow',
                    'yesterday' => 'Yesterday',
                    'last_7_days' => 'Last 7 Days',
                    'last_30_days' => 'Last 30 Days',
                    'custom' => 'Custom Range',
                ],
            ]
        );
        
        $this->add_control(
            'start_date',
            [
                'label' => 'Start Date',
                'type' => \Elementor\Controls_Manager::DATE_TIME,
                'condition' => [
                    'date_range' => 'custom',
                ],
            ]
        );
        
        $this->add_control(
            'end_date',
            [
                'label' => 'End Date',
                'type' => \Elementor\Controls_Manager::DATE_TIME,
                'condition' => [
                    'date_range' => 'custom',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Datasets Section
        $this->start_controls_section(
            'datasets_section',
            [
                'label' => 'Areas/Countries to Compare',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'chart_preset' => 'custom',
                ],
            ]
        );
        
        $repeater = new \Elementor\Repeater();
        
        $repeater->add_control(
            'area_code',
            [
                'label' => 'Area/Country',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => get_option('entsoe_default_area', '10YAT-APG------L'),
                'options' => $this->get_area_options(),
            ]
        );
        
        $repeater->add_control(
            'label',
            [
                'label' => 'Display Label',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Area',
                'placeholder' => 'e.g., Austria, Germany, etc.',
            ]
        );
        
        $repeater->add_control(
            'color',
            [
                'label' => 'Line Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#3b82f6',
            ]
        );
        
        $this->add_control(
            'datasets',
            [
                'label' => 'Areas to Compare',
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'area_code' => get_option('entsoe_default_area', '10YAT-APG------L'),
                        'label' => 'Austria',
                        'color' => '#3b82f6',
                    ],
                    [
                        'area_code' => '10YDE-VE-------2',
                        'label' => 'Germany',
                        'color' => '#10b981',
                    ],
                ],
                'title_field' => '{{{ label }}} ({{{ area_code }}})',
            ]
        );
        
        $this->end_controls_section();
        
        // Chart Settings
        $this->start_controls_section(
            'chart_section',
            [
                'label' => 'Chart Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'chart_height',
            [
                'label' => 'Chart Height',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 800,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Chart Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'chart_type',
            [
                'label' => 'Chart Type',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'line',
                'options' => [
                    'line' => 'Line Chart',
                    'bar' => 'Bar Chart',
                ],
            ]
        );
        
        $this->add_control(
            'show_grid',
            [
                'label' => 'Show Grid',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_legend',
            [
                'label' => 'Show Legend',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
    }
    
    private function get_area_options() {
        $api = new ENTSOE_API();
        return $api->get_area_codes();
    }
    
    private function apply_preset($settings) {
        switch ($settings['chart_preset']) {
            case 'weekly_consumption':
                return array_merge($settings, [
                    'data_type' => 'actual_load',
                    'date_range' => 'yesterday',  // Changed to yesterday for more reliable data
                    'chart_type' => 'line',
                    'show_grid' => 'yes',
                    'show_legend' => 'yes',
                    'datasets' => [
                        [
                            'area_code' => '10YDE-VE-------2', // Germany
                            'label' => 'Germany',
                            'color' => '#ef4444',
                        ],
                        [
                            'area_code' => '10YAT-APG------L', // Austria  
                            'label' => 'Austria',
                            'color' => '#10b981',
                        ],
                    ]
                ]);
                
            case 'daily_prices':
                return array_merge($settings, [
                    'data_type' => 'day_ahead_prices',
                    'date_range' => 'today',
                    'chart_type' => 'line',
                    'show_grid' => 'yes',
                    'show_legend' => 'yes',
                    'datasets' => [
                        [
                            'area_code' => '10YDE-VE-------2', // Germany
                            'label' => 'Germany',
                            'color' => '#ef4444',
                        ],
                        [
                            'area_code' => '10YFR-RTE------C', // France
                            'label' => 'France',
                            'color' => '#3b82f6',
                        ],
                        [
                            'area_code' => '10YAT-APG------L', // Austria
                            'label' => 'Austria',
                            'color' => '#10b981',
                        ],
                    ]
                ]);
                
            case 'load_forecast':
                return array_merge($settings, [
                    'data_type' => 'actual_load',
                    'date_range' => 'today',
                    'chart_type' => 'line',
                    'show_grid' => 'yes',
                    'show_legend' => 'yes',
                    'datasets' => [
                        [
                            'area_code' => '10YDE-VE-------2', // Germany
                            'label' => 'Germany - Actual Load',
                            'color' => '#ef4444',
                        ],
                    ]
                ]);
                
            default:
                return $settings;
        }
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Apply preset configurations
        if ($settings['chart_preset'] !== 'custom') {
            $settings = $this->apply_preset($settings);
        }
        
        list($start_date, $end_date) = $this->calculate_dates($settings);
        $widget_id = 'entsoe-comparison-' . $this->get_id();
        
        ?>
        <div class="entsoe-chart-wrapper">
            <div class="entsoe-chart-loading" id="loading-<?php echo $widget_id; ?>">
                <span>Loading comparison data...</span>
            </div>
            <canvas 
                id="<?php echo $widget_id; ?>" 
                style="height: <?php echo $settings['chart_height']['size']; ?>px;"
                data-comparison="true"
                data-widget-settings="<?php echo esc_attr(json_encode([
                    'data_type' => $settings['data_type'],
                    'datasets' => $settings['datasets'] ?: [],
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'chart_type' => $settings['chart_type'],
                    'show_grid' => $settings['show_grid'] === 'yes',
                    'show_legend' => $settings['show_legend'] === 'yes',
                ])); ?>">
            </canvas>
        </div>
        <?php
    }
    
    private function calculate_dates($settings) {
        $timezone = new DateTimeZone('UTC');
        
        switch ($settings['date_range']) {
            case 'today':
                $start = new DateTime('today', $timezone);
                $end = new DateTime('tomorrow', $timezone);
                break;
            case 'tomorrow':
                $start = new DateTime('tomorrow', $timezone);
                $end = new DateTime('+2 days', $timezone);
                break;
            case 'yesterday':
                $start = new DateTime('yesterday', $timezone);
                $end = new DateTime('today', $timezone);
                break;
            case 'last_7_days':
                $start = new DateTime('-7 days', $timezone);
                $end = new DateTime('now', $timezone);
                break;
            case 'last_30_days':
                $start = new DateTime('-30 days', $timezone);
                $end = new DateTime('now', $timezone);
                break;
            case 'custom':
                $start = new DateTime($settings['start_date'], $timezone);
                $end = new DateTime($settings['end_date'], $timezone);
                break;
            default:
                $start = new DateTime('today', $timezone);
                $end = new DateTime('tomorrow', $timezone);
        }
        
        return [
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        ];
    }
}
