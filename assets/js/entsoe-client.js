/**
 * Direct ENTSO-E API Client
 * Bypasses WordPress PHP backend and calls API directly from browser
 */

class EntsoeClient {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.baseUrl = 'https://web-api.tp.entsoe.eu/api';
        this.documentTypes = {
            'day_ahead_prices': 'A44',
            'actual_load': 'A65',
            'forecasted_load': 'A65',
            'generation_per_type': 'A75',
            'intraday_prices': 'A61'
        };
    }

    /**
     * Format date for ENTSO-E API (YYYYMMDDHHMM)
     */
    formatDate(date) {
        const d = new Date(date);
        const year = d.getUTCFullYear();
        const month = String(d.getUTCMonth() + 1).padStart(2, '0');
        const day = String(d.getUTCDate()).padStart(2, '0');
        const hour = String(d.getUTCHours()).padStart(2, '0');
        const minute = String(d.getUTCMinutes()).padStart(2, '0');
        return `${year}${month}${day}${hour}${minute}`;
    }

    /**
     * Calculate timestamp from position
     */
    calculateTimestamp(start, resolution, position, isSingleDay = false) {
        const dt = new Date(start + 'Z'); // Ensure UTC
        
        // Parse resolution (e.g., PT15M, PT60M)
        const matches = resolution.match(/PT(\d+)M/);
        const minutes = matches ? parseInt(matches[1]) : 60;
        
        // Add minutes for position (position is 1-based)
        dt.setMinutes(dt.getMinutes() + ((position - 1) * minutes));
        
        // Format based on whether it's single day or multi-day data
        if (isSingleDay) {
            return dt.toLocaleTimeString('en-GB', { 
                hour: '2-digit', 
                minute: '2-digit',
                timeZone: 'UTC'
            });
        } else {
            return dt.toISOString().slice(0, 16).replace('T', ' ');
        }
    }

    /**
     * Parse XML response for load data
     */
    parseLoadData(xmlText, isSingleDay = false) {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
        
        const data = {
            labels: [],
            values: [],
            unit: 'MW'
        };

        const timeSeries = xmlDoc.getElementsByTagName('TimeSeries');
        
        for (let ts of timeSeries) {
            const periods = ts.getElementsByTagName('Period');
            
            for (let period of periods) {
                const start = period.getElementsByTagName('start')[0]?.textContent;
                const resolution = period.getElementsByTagName('resolution')[0]?.textContent;
                const points = period.getElementsByTagName('Point');
                
                for (let point of points) {
                    const position = parseInt(point.getElementsByTagName('position')[0]?.textContent);
                    const quantity = parseFloat(point.getElementsByTagName('quantity')[0]?.textContent);
                    
                    if (!isNaN(position) && !isNaN(quantity)) {
                        const timestamp = this.calculateTimestamp(start, resolution, position, isSingleDay);
                        data.labels.push(timestamp);
                        data.values.push(quantity);
                    }
                }
            }
        }

        return data;
    }

    /**
     * Parse XML response for price data
     */
    parsePriceData(xmlText, isSingleDay = false) {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
        
        const data = {
            labels: [],
            values: [],
            unit: 'EUR/MWh'
        };

        const timeSeries = xmlDoc.getElementsByTagName('TimeSeries');
        
        for (let ts of timeSeries) {
            const periods = ts.getElementsByTagName('Period');
            
            for (let period of periods) {
                const start = period.getElementsByTagName('start')[0]?.textContent;
                const resolution = period.getElementsByTagName('resolution')[0]?.textContent;
                const points = period.getElementsByTagName('Point');
                
                for (let point of points) {
                    const position = parseInt(point.getElementsByTagName('position')[0]?.textContent);
                    const priceAmount = parseFloat(point.getElementsByTagName('price.amount')[0]?.textContent);
                    
                    if (!isNaN(position) && !isNaN(priceAmount)) {
                        const timestamp = this.calculateTimestamp(start, resolution, position, isSingleDay);
                        data.labels.push(timestamp);
                        data.values.push(priceAmount);
                    }
                }
            }
        }

        return data;
    }

    /**
     * Check if date range spans only one day
     */
    isSingleDay(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffHours = (end - start) / (1000 * 60 * 60);
        return diffHours <= 24;
    }

    /**
     * Fetch data from ENTSO-E API
     */
    async fetchData(dataType, startDate, endDate, areaCode) {
        const isSingleDay = this.isSingleDay(startDate, endDate);
        
        const params = new URLSearchParams({
            securityToken: this.apiKey,
            documentType: this.documentTypes[dataType],
            periodStart: this.formatDate(startDate),
            periodEnd: this.formatDate(endDate)
        });

        // Add appropriate domain parameters based on data type
        if (dataType === 'actual_load' || dataType === 'forecasted_load') {
            params.append('outBiddingZone_Domain', areaCode);
            params.append('processType', 'A16');
        } else if (dataType === 'day_ahead_prices' || dataType === 'intraday_prices') {
            params.append('in_Domain', areaCode);
            params.append('out_Domain', areaCode);
        } else if (dataType === 'generation_per_type') {
            params.append('in_Domain', areaCode);
            params.append('processType', 'A16');
        }

        const url = `${this.baseUrl}?${params.toString()}`;
        
        if (window.entsoeSettings && window.entsoeSettings.debug) {
            console.log('ENTSO-E API Request:', url);
        }
        
        try {
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const xmlText = await response.text();
            
            if (window.entsoeSettings && window.entsoeSettings.debug) {
                console.log('ENTSO-E API Response length:', xmlText.length);
            }
            
            // Parse based on data type
            let data;
            if (dataType === 'actual_load' || dataType === 'forecasted_load') {
                data = this.parseLoadData(xmlText, isSingleDay);
            } else if (dataType === 'day_ahead_prices' || dataType === 'intraday_prices') {
                data = this.parsePriceData(xmlText, isSingleDay);
            } else {
                // For generation data, we'll use load parsing as fallback for now
                data = this.parseLoadData(xmlText, isSingleDay);
            }
            
            if (window.entsoeSettings && window.entsoeSettings.debug) {
                console.log('Parsed data:', data);
            }
            
            return {
                success: true,
                data: data
            };
            
        } catch (error) {
            console.error('ENTSO-E API Error:', error);
            
            return {
                success: false,
                error: error.message
            };
        }
    }
}

// Export for global use
window.EntsoeClient = EntsoeClient;