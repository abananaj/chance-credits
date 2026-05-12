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

// Load models
require_once CHANCE_CREDITS_PLUGIN_DIR . 'models/credits.php';

// Load ACF fields
require_once CHANCE_CREDITS_PLUGIN_DIR . 'includes/acf-fields.php';

// Load REST endpoints for block editor previews
require_once CHANCE_CREDITS_PLUGIN_DIR . 'includes/rest-endpoints.php';

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
