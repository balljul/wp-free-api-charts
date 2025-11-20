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
        
        // Data Settings Section
        $this->start_controls_section(
            'data_section',
            [
                'label' => 'Data Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
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
    
    protected function render() {
        $settings = $this->get_settings_for_display();
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
