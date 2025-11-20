<?php

if (!defined('ABSPATH')) {
    exit;
}

class ENTSOE_API {
    
    private $api_url = 'https://web-api.tp.entsoe.eu/api';
    private $api_key;
    private $cache_duration;
    
    public function __construct() {
        $this->api_key = get_option('entsoe_api_key', '');
        $this->cache_duration = get_option('entsoe_cache_duration', 3600);
    }
    
    /**
     * Document types for ENTSOE API
     */
    private function get_document_types() {
        return array(
            'day_ahead_prices' => 'A44',
            'actual_load' => 'A65',
            'forecasted_load' => 'A65',
            'generation_per_type' => 'A75',
            'cross_border_flows' => 'A11',
            'intraday_prices' => 'A61'
        );
    }
    
    /**
     * Common area codes
     */
    public function get_area_codes() {
        return array(
            '10YAT-APG------L' => 'Austria',
            '10YDE-VE-------2' => 'Germany',
            '10YCZ-CEPS-----N' => 'Czech Republic',
            '10YSK-SEPS-----K' => 'Slovakia',
            '10YHU-MAVIR----U' => 'Hungary',
            '10YSI-ELES-----O' => 'Slovenia',
            '10YCH-SWISSGRIDZ' => 'Switzerland',
            '10YIT-GRTN-----B' => 'Italy',
            '10YFR-RTE------C' => 'France',
            '10YNL----------L' => 'Netherlands',
            '10YBE----------2' => 'Belgium',
            '10YES-REE------0' => 'Spain',
            '10YPL-AREA-----S' => 'Poland'
        );
    }
    
    /**
     * Fetch data from ENTSOE API
     */
    public function fetch_data($data_type, $start_date, $end_date, $area_code = '') {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'error' => 'API key not configured. Please set it in the settings.'
            );
        }
        
        if (empty($area_code)) {
            $area_code = get_option('entsoe_default_area', '10YAT-APG------L');
        }
        
        // Check cache
        $cache_key = 'entsoe_' . md5($data_type . $start_date . $end_date . $area_code);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return array(
                'success' => true,
                'data' => $cached_data,
                'cached' => true
            );
        }
        
        // Build API request
        $params = $this->build_params($data_type, $start_date, $end_date, $area_code);
        
        if (!$params) {
            return array(
                'success' => false,
                'error' => 'Invalid data type'
            );
        }
        
        // Make API request
        $response = wp_remote_get($this->api_url . '?' . http_build_query($params), array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/xml'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $parsed_data = $this->parse_xml_response($body, $data_type);
        
        if ($parsed_data) {
            // Cache the result
            set_transient($cache_key, $parsed_data, $this->cache_duration);
            
            return array(
                'success' => true,
                'data' => $parsed_data,
                'cached' => false
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Failed to parse API response'
        );
    }
    
    /**
     * Build API parameters
     */
    private function build_params($data_type, $start_date, $end_date, $area_code) {
        $doc_types = $this->get_document_types();
        
        if (!isset($doc_types[$data_type])) {
            return false;
        }
        
        $params = array(
            'securityToken' => $this->api_key,
            'documentType' => $doc_types[$data_type],
            'periodStart' => $this->format_date($start_date),
            'periodEnd' => $this->format_date($end_date),
        );
        
        // Add area-specific parameters
        switch ($data_type) {
            case 'day_ahead_prices':
            case 'intraday_prices':
                $params['in_Domain'] = $area_code;
                $params['out_Domain'] = $area_code;
                break;
            case 'actual_load':
            case 'forecasted_load':
                $params['outBiddingZone_Domain'] = $area_code;
                break;
            case 'generation_per_type':
                $params['in_Domain'] = $area_code;
                $params['processType'] = 'A16'; // Realised
                break;
        }
        
        return $params;
    }
    
    /**
     * Format date for ENTSOE API
     */
    private function format_date($date) {
        $dt = new DateTime($date, new DateTimeZone('UTC'));
        return $dt->format('YmdHi');
    }
    
    /**
     * Parse XML response from ENTSOE API
     */
    private function parse_xml_response($xml_string, $data_type) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_string);
        
        if ($xml === false) {
            return false;
        }
        
        $namespaces = $xml->getNamespaces(true);
        $data = array();
        
        switch ($data_type) {
            case 'day_ahead_prices':
            case 'intraday_prices':
                $data = $this->parse_price_data($xml, $namespaces);
                break;
            case 'actual_load':
            case 'forecasted_load':
                $data = $this->parse_load_data($xml, $namespaces);
                break;
            case 'generation_per_type':
                $data = $this->parse_generation_data($xml, $namespaces);
                break;
        }
        
        return $data;
    }
    
    /**
     * Parse price data
     */
    private function parse_price_data($xml, $namespaces) {
        $data = array(
            'labels' => array(),
            'values' => array(),
            'unit' => 'EUR/MWh'
        );
        
        foreach ($xml->TimeSeries as $timeSeries) {
            foreach ($timeSeries->Period as $period) {
                $start = (string)$period->timeInterval->start;
                $resolution = (string)$period->resolution;
                
                foreach ($period->Point as $point) {
                    $position = (int)$point->position;
                    $price = (float)$point->{'price.amount'};
                    
                    // Calculate timestamp
                    $timestamp = $this->calculate_timestamp($start, $resolution, $position);
                    
                    $data['labels'][] = $timestamp;
                    $data['values'][] = $price;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Parse load data
     */
    private function parse_load_data($xml, $namespaces) {
        $data = array(
            'labels' => array(),
            'values' => array(),
            'unit' => 'MW'
        );
        
        foreach ($xml->TimeSeries as $timeSeries) {
            foreach ($timeSeries->Period as $period) {
                $start = (string)$period->timeInterval->start;
                $resolution = (string)$period->resolution;
                
                foreach ($period->Point as $point) {
                    $position = (int)$point->position;
                    $quantity = (float)$point->quantity;
                    
                    $timestamp = $this->calculate_timestamp($start, $resolution, $position);
                    
                    $data['labels'][] = $timestamp;
                    $data['values'][] = $quantity;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Parse generation data
     */
    private function parse_generation_data($xml, $namespaces) {
        $data = array();
        
        foreach ($xml->TimeSeries as $timeSeries) {
            $psrType = (string)$timeSeries->MktPSRType->psrType;
            $generation_type = $this->get_generation_type_name($psrType);
            
            if (!isset($data[$generation_type])) {
                $data[$generation_type] = array(
                    'labels' => array(),
                    'values' => array()
                );
            }
            
            foreach ($timeSeries->Period as $period) {
                $start = (string)$period->timeInterval->start;
                $resolution = (string)$period->resolution;
                
                foreach ($period->Point as $point) {
                    $position = (int)$point->position;
                    $quantity = (float)$point->quantity;
                    
                    $timestamp = $this->calculate_timestamp($start, $resolution, $position);
                    
                    $data[$generation_type]['labels'][] = $timestamp;
                    $data[$generation_type]['values'][] = $quantity;
                }
            }
        }
        
        $data['unit'] = 'MW';
        return $data;
    }
    
    /**
     * Calculate timestamp from position
     */
    private function calculate_timestamp($start, $resolution, $position) {
        $dt = new DateTime($start, new DateTimeZone('UTC'));
        
        // Parse resolution (e.g., PT15M, PT60M)
        preg_match('/PT(\d+)M/', $resolution, $matches);
        $minutes = isset($matches[1]) ? (int)$matches[1] : 60;
        
        // Add minutes for position
        $dt->modify('+' . (($position - 1) * $minutes) . ' minutes');
        
        return $dt->format('Y-m-d H:i');
    }
    
    /**
     * Get generation type name
     */
    private function get_generation_type_name($code) {
        $types = array(
            'B01' => 'Biomass',
            'B02' => 'Fossil Brown coal/Lignite',
            'B03' => 'Fossil Coal-derived gas',
            'B04' => 'Fossil Gas',
            'B05' => 'Fossil Hard coal',
            'B06' => 'Fossil Oil',
            'B09' => 'Geothermal',
            'B10' => 'Hydro Pumped Storage',
            'B11' => 'Hydro Run-of-river and poundage',
            'B12' => 'Hydro Water Reservoir',
            'B13' => 'Marine',
            'B14' => 'Nuclear',
            'B15' => 'Other renewable',
            'B16' => 'Solar',
            'B17' => 'Waste',
            'B18' => 'Wind Offshore',
            'B19' => 'Wind Onshore',
            'B20' => 'Other'
        );
        
        return isset($types[$code]) ? $types[$code] : $code;
    }
}
