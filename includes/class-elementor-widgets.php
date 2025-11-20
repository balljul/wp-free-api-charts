<?php

if (!defined('ABSPATH')) {
    exit;
}

class ENTSOE_Elementor_Widgets {
    
    public static function register_widgets($widgets_manager) {
        require_once ENTSOE_CHARTS_PLUGIN_DIR . 'includes/widgets/price-chart-widget.php';
        require_once ENTSOE_CHARTS_PLUGIN_DIR . 'includes/widgets/load-chart-widget.php';
        require_once ENTSOE_CHARTS_PLUGIN_DIR . 'includes/widgets/generation-chart-widget.php';
        require_once ENTSOE_CHARTS_PLUGIN_DIR . 'includes/widgets/comparison-chart-widget.php';
        
        $widgets_manager->register(new \ENTSOE_Price_Chart_Widget());
        $widgets_manager->register(new \ENTSOE_Load_Chart_Widget());
        $widgets_manager->register(new \ENTSOE_Generation_Chart_Widget());
        $widgets_manager->register(new \ENTSOE_Comparison_Chart_Widget());
    }
}
