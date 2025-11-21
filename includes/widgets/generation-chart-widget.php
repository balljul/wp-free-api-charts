<?php

if (!defined('ABSPATH')) {
    exit;
}

class ENTSOE_Generation_Chart_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'entsoe_generation_chart';
    }
    
    public function get_title() {
        return 'ENTSOE Generation Chart';
    }
    
    public function get_icon() {
        return 'eicon-products';
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
                    'energy_mix_today' => '🔋 Today\'s Energy Mix (Bar Chart)',
                    'energy_mix_weekly' => '📊 Weekly Energy Sources (Stacked Bar)',
                    'renewable_vs_fossil' => '🌱 Renewable vs Fossil (Pie Chart)',
                ],
                'description' => 'Select a preset for energy source analysis',
            ]
        );
        
        $this->add_control(
            'preset_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<div style="background: #f0f9ff; padding: 10px; border-radius: 5px; margin: 10px 0;">
                    <strong>Preset 1:</strong> Today\'s energy generation by source (Bar chart)<br>
                    <strong>Preset 2:</strong> Weekly energy sources comparison (Stacked bars)<br>
                    <strong>Preset 3:</strong> Renewable vs fossil fuel breakdown (Pie chart)
                </div>',
                'condition' => [
                    'chart_preset!' => 'custom',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Chart Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'chart_preset' => 'custom',
                ],
            ]
        );
        
        $this->add_control(
            'area_code',
            [
                'label' => 'Area/Country',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => get_option('entsoe_default_area', '10YAT-APG------L'),
                'options' => $this->get_area_options(),
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
                    'yesterday' => 'Yesterday',
                    'last_7_days' => 'Last 7 Days',
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
                    'size' => 500,
                ],
            ]
        );
        
        $this->end_controls_section();
        
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
                'default' => 'bar',
                'options' => [
                    'bar' => 'Stacked Bar Chart',
                    'line' => 'Multi-Line Chart',
                    'pie' => 'Pie Chart',
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
            case 'energy_mix_today':
                return array_merge($settings, [
                    'area_code' => '10YDE-VE-------2', // Germany as example
                    'date_range' => 'today',
                    'chart_type' => 'bar',
                    'show_grid' => 'yes',
                    'show_legend' => 'yes',
                ]);
                
            case 'energy_mix_weekly':
                return array_merge($settings, [
                    'area_code' => '10YDE-VE-------2', // Germany as example
                    'date_range' => 'last_7_days',
                    'chart_type' => 'bar',
                    'show_grid' => 'yes',
                    'show_legend' => 'yes',
                ]);
                
            case 'renewable_vs_fossil':
                return array_merge($settings, [
                    'area_code' => '10YDE-VE-------2', // Germany as example
                    'date_range' => 'today',
                    'chart_type' => 'pie',
                    'show_grid' => 'no',
                    'show_legend' => 'yes',
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
        $widget_id = 'entsoe-chart-' . $this->get_id();
        
        ?>
        <div class="entsoe-chart-wrapper">
            <div class="entsoe-chart-loading" id="loading-<?php echo $widget_id; ?>">
                <span>Loading generation data...</span>
            </div>
            <canvas 
                id="<?php echo $widget_id; ?>" 
                style="height: <?php echo $settings['chart_height']['size']; ?>px;"
                data-widget-settings="<?php echo esc_attr(json_encode([
                    'data_type' => 'generation_per_type',
                    'area_code' => $settings['area_code'],
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
            case 'yesterday':
                $start = new DateTime('yesterday', $timezone);
                $end = new DateTime('today', $timezone);
                break;
            case 'last_7_days':
                $start = new DateTime('-7 days', $timezone);
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
