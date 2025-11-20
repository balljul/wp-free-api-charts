# ENTSOE Energy Charts WordPress Plugin

A comprehensive WordPress plugin that integrates the ENTSOE (European Network of Transmission System Operators for Electricity) API to display beautiful, interactive electricity market data charts with full Elementor support.

## Features

- 🔌 **ENTSOE API Integration** - Direct connection to official ENTSOE Transparency Platform
- 📊 **Multiple Chart Types** - Display day-ahead prices, actual load, forecasted load, and generation by fuel type
- 🎨 **Beautiful Visualizations** - Powered by Chart.js with customizable colors and styles
- 🔄 **Side-by-Side Comparisons** - Compare data from different countries or data types
- 🧩 **Elementor Widgets** - 4 ready-to-use Elementor widgets
- ⚡ **Caching System** - Configurable caching to reduce API calls
- 🌍 **Multi-Country Support** - Pre-configured for 13+ European countries
- 📱 **Responsive Design** - Charts adapt perfectly to all screen sizes

## Installation

1. **Upload the Plugin**
   - Download the plugin ZIP file
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin" and select the ZIP file
   - Click "Install Now"

2. **Activate the Plugin**
   - After installation, click "Activate Plugin"

3. **Get Your API Key**
   - Visit [ENTSOE Transparency Platform](https://transparency.entsoe.eu/content/static_content/Static%20content/web%20api/Guide.html)
   - Register for a free account
   - Generate your API security token

4. **Configure Settings**
   - Go to WordPress Admin → ENTSOE Charts
   - Enter your API key in the Settings tab
   - Select your default area/country
   - Set cache duration (recommended: 3600 seconds)
   - Click "Save Settings"

## Usage

### Admin Panel

The plugin adds a new menu item "ENTSOE Charts" in your WordPress admin:

1. **Settings Tab** - Configure API key, default area, and caching
2. **Data Preview Tab** - Test your API connection and preview charts
3. **Help Tab** - Documentation and usage instructions

### Elementor Widgets

The plugin provides 4 Elementor widgets:

#### 1. ENTSOE Price Chart
Display day-ahead or intraday electricity prices.

**Settings:**
- Price Type (Day-Ahead / Intraday)
- Area/Country
- Date Range
- Chart Height
- Chart Type (Line / Bar / Area)
- Colors and styling

#### 2. ENTSOE Load Chart
Display actual or forecasted electricity load.

**Settings:**
- Load Type (Actual / Forecasted)
- Area/Country
- Date Range
- Chart Height
- Chart Type (Line / Bar / Area)
- Colors and styling

#### 3. ENTSOE Generation Chart
Display power generation breakdown by fuel type (solar, wind, hydro, etc.).

**Settings:**
- Area/Country
- Date Range
- Chart Height
- Chart Type (Stacked Bar / Multi-Line)
- Grid and legend options

#### 4. ENTSOE Comparison Chart
Compare data side-by-side from different countries or data types.

**Settings:**
- Dataset 1: Data type, area, label, color
- Dataset 2: Data type, area, label, color
- Date Range
- Chart Height
- Chart Type (Line / Bar)
- Grid and legend options

### Using Widgets in Elementor

1. Edit a page with Elementor
2. Search for "ENTSOE" in the widget panel
3. Drag the desired widget to your page
4. Configure settings in the left panel
5. Click "Update" to save

## Available Data Types

- **Day-Ahead Prices** - Hourly electricity prices for the next day
- **Intraday Prices** - Real-time intraday market prices
- **Actual Load** - Real-time electricity consumption
- **Forecasted Load** - Predicted electricity consumption
- **Generation per Type** - Power generation by fuel type (solar, wind, hydro, nuclear, etc.)

## Supported Countries

- 🇦🇹 Austria (10YAT-APG------L) - Default
- 🇩🇪 Germany (10YDE-VE-------2)
- 🇨🇿 Czech Republic (10YCZ-CEPS-----N)
- 🇸🇰 Slovakia (10YSK-SEPS-----K)
- 🇭🇺 Hungary (10YHU-MAVIR----U)
- 🇸🇮 Slovenia (10YSI-ELES-----O)
- 🇨🇭 Switzerland (10YCH-SWISSGRIDZ)
- 🇮🇹 Italy (10YIT-GRTN-----B)
- 🇫🇷 France (10YFR-RTE------C)
- 🇳🇱 Netherlands (10YNL----------L)
- 🇧🇪 Belgium (10YBE----------2)
- 🇪🇸 Spain (10YES-REE------0)
- 🇵🇱 Poland (10YPL-AREA-----S)

## Technical Details

### Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Elementor (for widget functionality)
- Active internet connection
- Valid ENTSOE API key

### Caching

The plugin includes a built-in caching system to:
- Reduce API calls
- Improve page load times
- Stay within API rate limits

Default cache duration: 1 hour (3600 seconds)

### API Rate Limits

Be aware of ENTSOE API rate limits:
- Recommended: Enable caching
- Use appropriate cache durations based on data update frequency
- Day-ahead prices update daily
- Real-time data updates every 15-60 minutes

## Customization

### Chart Colors

Each widget allows you to customize:
- Line/bar colors
- Background colors
- Grid visibility
- Legend display

### Date Ranges

Available options:
- Today
- Yesterday
- Last 7 Days
- Last 30 Days
- Custom Range (specify exact dates)

## Troubleshooting

### "API key not configured" error
- Go to ENTSOE Charts → Settings
- Ensure your API key is entered correctly
- Click "Save Settings"

### "Failed to parse API response" error
- Check if the selected date range has available data
- Some historical data may not be available for all countries
- Try a different date range

### Charts not displaying
- Clear WordPress cache
- Clear browser cache
- Check if Elementor is active
- Verify API key is valid
- Test in the Data Preview tab

### Empty data
- Some countries may not provide all data types
- Historical data availability varies by country
- Try selecting a different country or data type

## Support

For issues, questions, or feature requests:
- Check the Help tab in ENTSOE Charts admin page
- Review ENTSOE API documentation
- Contact plugin developer

## Changelog

### Version 1.0.0
- Initial release
- ENTSOE API integration
- 4 Elementor widgets
- Multi-country support
- Caching system
- Admin preview functionality

## Credits

- Built with Chart.js
- Powered by ENTSOE Transparency Platform API
- Developed for WordPress + Elementor

## License

GPL v2 or later

---

Made with ⚡ for energy data visualization
