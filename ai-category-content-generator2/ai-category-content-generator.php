<?php
/**
 * Plugin Name:       AI Category Content & Image Generator
 * Plugin URI:        https://bizsitenow.com/
 * Description:       Generates Post content and featured image based on custom prompts
 * Version:           1.1
 * Author:            Joseph Triplett
 * Author URI:        https://bizsitenow.com/
 * Text Domain:       ai-cat-content-gen-google
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

// Includes
require_once plugin_dir_path(__FILE__) . 'includes/constants.php';
require_once plugin_dir_path(__FILE__) . 'includes/textdomain.php';
require_once plugin_dir_path(__FILE__) . 'includes/all-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/core-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/cron-handler.php';

// WP Standard Plugin Action Scheduler
if ( ! class_exists( 'ActionScheduler' ) && ! function_exists( 'as_schedule_single_action' ) ) {
    $as_path = plugin_dir_path( __FILE__ ) . 'includes/action-scheduler/action-scheduler.php'; // Adjust path
    if ( file_exists( $as_path ) ) {
        require_once $as_path;
        //error_log('ERROR: Action Scheduler library file found in plugin.');
    } else {
        // Log an error or add an admin notice: Action Scheduler library is missing!
        error_log('ERROR: Action Scheduler library file not found in plugin.');
    }
}
// Seprate Debug file for the plugin
function my_plugin_log($message) {
    if (WP_DEBUG === true) {
        $log_file = plugin_dir_path(__FILE__) . 'aicci-debug.log';
        error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $log_file);
    }
}