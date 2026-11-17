<?php
/**
 * Plugin Name: IP2Location (Matomo Plugin)
 * Description: IP2Location geolocation provider for Matomo for WordPress.
 * Version: 5.6.0
 * Requires at least: 4.8
 * Requires PHP: 7.2.5
 * Author: IP2Location
 * License: GPL-3.0+
 * Text Domain: ip2location-matomo
 */

if (defined('ABSPATH') && function_exists('add_action')) {
    add_action('plugins_loaded', function () {
        if (function_exists('matomo_add_plugin')) {
            matomo_add_plugin(__DIR__, __FILE__, true);
        }
    });
}
