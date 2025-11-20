<?php

if (!defined('ABSPATH')) {
    exit;
}

class ENTSOE_Admin_Page {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'ENTSOE Charts',
            'ENTSOE Charts',
            'manage_options',
            'entsoe-charts',
            array($this, 'render_admin_page'),
            'dashicons-chart-line',
            30
        );
    }
    
    public function register_settings() {
        register_setting('entsoe_charts_settings', 'entsoe_api_key');
        register_setting('entsoe_charts_settings', 'entsoe_default_area');
        register_setting('entsoe_charts_settings', 'entsoe_cache_duration');
        
        add_settings_section(
            'entsoe_settings_section',
            'API Settings',
            array($this, 'settings_section_callback'),
            'entsoe-charts'
        );
        
        add_settings_field(
            'entsoe_api_key',
            'ENTSOE API Key',
            array($this, 'api_key_field_callback'),
            'entsoe-charts',
            'entsoe_settings_section'
        );
        
        add_settings_field(
            'entsoe_default_area',
            'Default Area',
            array($this, 'default_area_field_callback'),
            'entsoe-charts',
            'entsoe_settings_section'
        );
        
        add_settings_field(
            'entsoe_cache_duration',
            'Cache Duration (seconds)',
            array($this, 'cache_duration_field_callback'),
            'entsoe-charts',
            'entsoe_settings_section'
        );
    }
    
    public function settings_section_callback() {
        echo '<p>Configure your ENTSOE API settings. Get your API key from <a href="https://transparency.entsoe.eu/content/static_content/Static%20content/web%20api/Guide.html" target="_blank">ENTSOE Transparency Platform</a>.</p>';
    }
    
    public function api_key_field_callback() {
        $value = get_option('entsoe_api_key', '');
        echo '<input type="text" name="entsoe_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your ENTSOE API security token</p>';
    }
    
    public function default_area_field_callback() {
        $value = get_option('entsoe_default_area', '10YAT-APG------L');
        $api = new ENTSOE_API();
        $areas = $api->get_area_codes();
        
        echo '<select name="entsoe_default_area">';
        foreach ($areas as $code => $name) {
            $selected = ($value === $code) ? 'selected' : '';
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . ' (' . esc_html($code) . ')</option>';
        }
        echo '</select>';
        echo '<p class="description">Default bidding zone for data requests</p>';
    }
    
    public function cache_duration_field_callback() {
        $value = get_option('entsoe_cache_duration', 3600);
        echo '<input type="number" name="entsoe_cache_duration" value="' . esc_attr($value) . '" class="small-text" min="0" />';
        echo '<p class="description">How long to cache API responses (0 = no caching)</p>';
    }
    
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['settings-updated'])) {
            add_settings_error('entsoe_messages', 'entsoe_message', 'Settings Saved', 'updated');
        }
        
        settings_errors('entsoe_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="entsoe-admin-container">
                <div class="entsoe-admin-tabs">
                    <button class="entsoe-tab-button active" data-tab="settings">Settings</button>
                    <button class="entsoe-tab-button" data-tab="preview">Data Preview</button>
                    <button class="entsoe-tab-button" data-tab="help">Help</button>
                </div>
                
                <div class="entsoe-tab-content active" id="settings-tab">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('entsoe_charts_settings');
                        do_settings_sections('entsoe-charts');
                        submit_button('Save Settings');
                        ?>
                    </form>
                </div>
                
                <div class="entsoe-tab-content" id="preview-tab">
                    <h2>Data Preview</h2>
                    <p>Test your API connection and preview data:</p>
                    
                    <div class="entsoe-preview-controls">
                        <div class="control-group">
                            <label>Data Type:</label>
                            <select id="preview-data-type">
                                <option value="day_ahead_prices">Day-Ahead Prices</option>
                                <option value="actual_load">Actual Load</option>
                                <option value="generation_per_type">Generation by Type</option>
                            </select>
                        </div>
                        
                        <div class="control-group">
                            <label>Start Date:</label>
                            <input type="date" id="preview-start-date" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" />
                        </div>
                        
                        <div class="control-group">
                            <label>End Date:</label>
                            <input type="date" id="preview-end-date" value="<?php echo date('Y-m-d'); ?>" />
                        </div>
                        
                        <div class="control-group">
                            <label>Area:</label>
                            <select id="preview-area">
                                <?php
                                $api = new ENTSOE_API();
                                $areas = $api->get_area_codes();
                                $default_area = get_option('entsoe_default_area', '10YAT-APG------L');
                                foreach ($areas as $code => $name) {
                                    $selected = ($default_area === $code) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <button type="button" class="button button-primary" id="preview-fetch-btn">Fetch Data</button>
                    </div>
                    
                    <div id="preview-status"></div>
                    <div class="entsoe-chart-container">
                        <canvas id="preview-chart"></canvas>
                    </div>
                </div>
                
                <div class="entsoe-tab-content" id="help-tab">
                    <h2>How to Use</h2>
                    
                    <div class="entsoe-help-section">
                        <h3>1. Get Your API Key</h3>
                        <p>Visit <a href="https://transparency.entsoe.eu/content/static_content/Static%20content/web%20api/Guide.html" target="_blank">ENTSOE Transparency Platform</a> and register for a free API key.</p>
                    </div>
                    
                    <div class="entsoe-help-section">
                        <h3>2. Configure Settings</h3>
                        <p>Enter your API key in the Settings tab and choose your default area/bidding zone.</p>
                    </div>
                    
                    <div class="entsoe-help-section">
                        <h3>3. Use with Elementor</h3>
                        <p>In the Elementor editor, search for "ENTSOE" widgets:</p>
                        <ul>
                            <li><strong>ENTSOE Price Chart</strong> - Display day-ahead or intraday prices</li>
                            <li><strong>ENTSOE Load Chart</strong> - Display actual or forecasted load</li>
                            <li><strong>ENTSOE Generation Chart</strong> - Display generation by fuel type</li>
                            <li><strong>ENTSOE Comparison Chart</strong> - Compare data side-by-side</li>
                        </ul>
                    </div>
                    
                    <div class="entsoe-help-section">
                        <h3>4. Available Data Types</h3>
                        <ul>
                            <li><strong>Day-Ahead Prices</strong> - Hourly electricity prices for the next day</li>
                            <li><strong>Actual Load</strong> - Real-time electricity consumption</li>
                            <li><strong>Forecasted Load</strong> - Predicted electricity consumption</li>
                            <li><strong>Generation per Type</strong> - Power generation breakdown by source</li>
                        </ul>
                    </div>
                    
                    <div class="entsoe-help-section">
                        <h3>5. Area Codes</h3>
                        <p>Each country/bidding zone has a unique EIC code. The default is set to Austria (10YAT-APG------L).</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
