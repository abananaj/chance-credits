<?php

/**
 * One-time migration: credit posts → ct_credits table
 *
 * Run via WP-CLI from wp_root:
 *   wp eval-file wp-content/mu-plugins/chance-credits/includes/migration.php
 *
 * Set $dry_run = false to perform the actual migration.
 * Dry run logs what would happen without writing anything.
 */

if (! defined('ABSPATH')) {
  exit;
}

$dry_run = false; // set to true to test without writing

global $wpdb;
$table = CHANCE_CREDITS_TABLE;

// Ensure the table exists.
chance_credits_create_table();

$credit_posts = get_posts(array(
  'post_type'      => 'credit',
  'posts_per_page' => -1,
  'post_status'    => 'publish',
  'orderby'        => 'menu_order title',
  'order'          => 'ASC',
));

if (empty($credit_posts)) {
  echo "No credit posts found. Nothing to migrate.\n";
  return;
}

$total = count($credit_posts);
echo "Found $total credit posts.\n";
$dry_run && print "[DRY RUN — set \$dry_run = false to write]\n\n";

$production_credits_map = array(); // production_id => ordered array of new credit_IDs
$migrated     = 0;
$skipped      = 0;
$skipped_ids  = array();

foreach ($credit_posts as $post) {
  $production_id = (int) get_post_meta($post->ID, 'production', true);
  $artist_id     = (int) get_post_meta($post->ID, 'artist', true);
  $role          = get_post_meta($post->ID, 'role', true) ?: '';
  $role_group    = get_post_meta($post->ID, 'role-group', true) ?: '';
  $credit_date   = get_field('opening', $production_id) ?: '';

  if (! $production_id || ! $artist_id) {
    echo "  SKIP  credit {$post->ID} ({$post->post_title}): missing production or artist.\n";
    $skipped_ids[] = $post->ID;
    $skipped++;
    continue;
  }

  echo "  MIGRATE  credit {$post->ID}: {$post->post_title} (production: $production_id, artist: $artist_id)\n";

  if (! $dry_run) {
    $wpdb->insert(
      $table,
      array(
        'credit_title'      => $post->post_title,
        'credit_name'       => $post->post_name,
        'credit_artist'     => $artist_id,
        'credit_production' => $production_id,
        'credit_role'       => $role,
        'credit_role_group' => $role_group,
        'credit_date'       => $credit_date,
      ),
      array('%s', '%s', '%d', '%d', '%s', '%s', '%s')
    );

    $new_id = (int) $wpdb->insert_id;

    if ($new_id) {
      $production_credits_map[$production_id][] = $new_id;
      $migrated++;
    } else {
      echo "    ERROR inserting credit {$post->ID}: {$wpdb->last_error}\n";
      $skipped++;
    }
  } else {
    $production_credits_map[$production_id][] = 0;
    $migrated++;
  }
}

echo "\n";

// Write credit_ids postmeta to each production and trash old credit posts.
if (! $dry_run) {
  foreach ($production_credits_map as $prod_id => $ids) {
    update_post_meta($prod_id, 'credit_ids', wp_json_encode($ids));
    echo "  SET credit_ids on production $prod_id: [" . implode(', ', $ids) . "]\n";
  }

  echo "\nTrashing $total old credit posts...\n";
  foreach ($credit_posts as $post) {
    wp_trash_post($post->ID);
  }
  echo "Done.\n";
}

$mode = $dry_run ? '[DRY RUN]' : '[COMPLETE]';
echo "\n$mode  Migrated: $migrated  |  Skipped: $skipped  |  Total: $total\n";

if (! empty($skipped_ids)) {
  echo "\nSkipped IDs: " . implode(', ', $skipped_ids) . "\n";
}
