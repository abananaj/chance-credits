<?php

/**
 * Plugin Name: Chance Theater Credits
 * Plugin URI: https://chancetheater.org
 * Description: Production credits management with custom blocks for cast and crew display
 * Version: 1.0.0
 * Author: Chance Theater
 * Text Domain: chance-credits
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
  exit;
}

define('CHANCE_CREDITS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHANCE_CREDITS_PLUGIN_URL', plugin_dir_url(__FILE__));

global $wpdb;
define('CHANCE_CREDITS_TABLE', $wpdb->prefix . 'credits');

// Create/verify custom table
require_once CHANCE_CREDITS_PLUGIN_DIR . 'inc/table.php';

// Load models
require_once CHANCE_CREDITS_PLUGIN_DIR . 'models/credits.php';

// Register credit post type
require_once CHANCE_CREDITS_PLUGIN_DIR . 'inc/credit.php';
add_action('init', 'ct_register_credit');

// Load ACF fields
require_once CHANCE_CREDITS_PLUGIN_DIR . 'inc/acf-fields.php';

// Load REST endpoints for block editor previews
require_once CHANCE_CREDITS_PLUGIN_DIR . 'inc/rest-endpoints.php';

// Admin credits list (queries ct_credits)
require_once CHANCE_CREDITS_PLUGIN_DIR . 'inc/admin-list.php';

/**
 * Register the artist-credits and production-credits blocks.
 */
function chance_credits_register_blocks()
{
  $blocks = array('artist-credits', 'production-credits');
  foreach ($blocks as $block) {
    register_block_type(CHANCE_CREDITS_PLUGIN_DIR . 'build/blocks/' . $block);
  }
}
add_action('init', 'chance_credits_register_blocks');

add_action('add_meta_boxes', function () {
  add_meta_box(
    'chance-credits-manager',
    'Production Credits',
    function () {
      echo '<div id="chance-credits-manager-root"></div>';
    },
    'production',
    'normal',
    'high'
  );
});

add_action('admin_enqueue_scripts', function () {
  $screen = get_current_screen();
  if (! $screen || $screen->post_type !== 'production') return;

  $asset_file = CHANCE_CREDITS_PLUGIN_DIR . 'build/credits-manager/index.asset.php';
  if (! file_exists($asset_file)) return;

  $asset = require $asset_file;

  wp_enqueue_script(
    'chance-credits-manager',
    CHANCE_CREDITS_PLUGIN_URL . 'build/credits-manager/index.js',
    $asset['dependencies'],
    $asset['version'],
    true
  );

  wp_enqueue_style(
    'chance-credits-manager-editor',
    CHANCE_CREDITS_PLUGIN_URL . 'build/credits-manager/index.css',
    array(),
    $asset['version']
  );
});
