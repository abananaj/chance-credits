<?php

/**
 * Load critical plugin and theme textdomains early to prevent WordPress 6.7.0+ notices.
 *
 * Must-use plugins load before regular plugins, allowing us to load textdomains
 * at the appropriate `wp_loaded` hook instead of lazy-loading on first translation call.
 *
 * @since 1.0
 */

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Load WPForms textdomain at wp_loaded hook (before plugins_loaded for other plugins).
 */
function ct_load_wpforms_textdomain()
{
  if (function_exists('wpforms')) {
    load_plugin_textdomain(
      'wpforms-lite',
      false,
      WP_PLUGIN_DIR . '/wpforms/assets/languages'
    );
  }
}
add_action('wp_loaded', 'ct_load_wpforms_textdomain', 5);
