<?php

/**
 * Populate production repeaters from ct_credits data.
 *
 * Reads each production's credits from ct_credits and writes them into
 * the production_credits_repeater ACF field, so the edit page reflects
 * the migrated data.
 *
 * Run via WP-CLI from wp_root:
 *   wp eval-file wp-content/mu-plugins/chance-credits/inc/populate-repeaters.php
 *
 * Set $dry_run = false to actually write repeater data.
 */

if (! defined('ABSPATH')) {
  exit;
}

$dry_run = true;

global $wpdb;
$table = CHANCE_CREDITS_TABLE;

// Get all production IDs that have rows in ct_credits.
$production_ids = $wpdb->get_col(
  "SELECT DISTINCT credit_production FROM $table ORDER BY credit_production ASC"
);

if (empty($production_ids)) {
  echo "No credits found in ct_credits. Nothing to do.\n";
  return;
}

echo "Found " . count($production_ids) . " productions with credits.\n";
$dry_run && print "[DRY RUN — set \$dry_run = false to write]\n\n";

$updated  = 0;
$skipped  = 0;

foreach ($production_ids as $production_id) {
  $production_id = (int) $production_id;

  // Get ordered credit IDs for this production.
  $credit_ids_json = get_post_meta($production_id, 'credit_ids', true);
  $ordered_ids     = $credit_ids_json ? json_decode($credit_ids_json, true) : array();

  // Fetch credits from ct_credits.
  $rows = $wpdb->get_results(
    $wpdb->prepare("SELECT * FROM $table WHERE credit_production = %d", $production_id)
  );

  if (empty($rows)) {
    continue;
  }

  // Sort by ordered_ids if available.
  if (! empty($ordered_ids)) {
    $order_map = array_flip($ordered_ids);
    usort($rows, function ($a, $b) use ($order_map) {
      $pos_a = $order_map[$a->credit_ID] ?? PHP_INT_MAX;
      $pos_b = $order_map[$b->credit_ID] ?? PHP_INT_MAX;
      return $pos_a <=> $pos_b;
    });
  }

  // Build repeater rows.
  $repeater_rows = array();
  foreach ($rows as $row) {
    $repeater_rows[] = array(
      'artist'     => (int) $row->credit_artist,
      'role_group' => $row->credit_role_group,
      'role'       => $row->credit_role,
    );
  }

  $production_title = get_the_title($production_id);
  echo "  " . ($dry_run ? 'WOULD UPDATE' : 'UPDATING') . "  production $production_id ($production_title): " . count($repeater_rows) . " rows\n";

  if (! $dry_run) {
    update_field('production_credits_repeater', $repeater_rows, $production_id);
  }

  $updated++;
}

$mode = $dry_run ? '[DRY RUN]' : '[COMPLETE]';
echo "\n$mode  Updated: $updated  |  Skipped: $skipped\n";
