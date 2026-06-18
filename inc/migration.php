<?php

/**
 * One-time migration: credit posts → ct_credits table
 *
 * Run via WP-CLI from wp_root:
 *   wp eval-file wp-content/mu-plugins/chance-credits/inc/migration.php
 *
 * Set $dry_run = false to perform the actual migration.
 * Dry run logs what would happen without writing anything.
 *
 * After running: verify with
 *   wp db query "SELECT COUNT(*) FROM ct_credits;"
 */

if (! defined('ABSPATH')) {
  exit;
}

$dry_run = false;

global $wpdb;
$table = CHANCE_CREDITS_TABLE;

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
if ($dry_run) echo "[DRY RUN — set \$dry_run = false to write]\n";
echo "\n";

$order_counters = array(); // production_id => next credit_order value
$migrated       = 0;
$skipped        = 0;
$skipped_ids    = array();

foreach ($credit_posts as $post) {
  // Skip already-migrated posts
  if (get_post_meta($post->ID, '_ct_credit_id', true)) {
    continue;
  }

  $production_id = (int) get_post_meta($post->ID, 'production', true);
  $artist_id     = (int) get_post_meta($post->ID, 'artist', true);
  $role          = get_post_meta($post->ID, 'role', true) ?: '';
  $role_group    = get_post_meta($post->ID, 'role_group', true) ?: '';
  $credit_date   = get_field('opening', $production_id) ?: '';

  if (! $production_id || ! $artist_id) {
    echo "  SKIP  #{$post->ID} ({$post->post_title}): missing production or artist\n";
    $skipped_ids[] = $post->ID;
    $skipped++;
    continue;
  }

  if (! isset($order_counters[$production_id])) {
    $order_counters[$production_id] = 1 + (int) $wpdb->get_var($wpdb->prepare(
      "SELECT MAX(credit_order) FROM $table WHERE credit_production = %d",
      $production_id
    ));
  }
  $credit_order = $order_counters[$production_id]++;

  echo "  MIGRATE  #{$post->ID} → production $production_id | artist $artist_id | group $role_group | role $role | order $credit_order | {$post->post_title}\n";

  if (! $dry_run) {
    $inserted = $wpdb->insert(
      $table,
      array(
        'credit_title'      => $post->post_title,
        'credit_name'       => $post->post_name,
        'credit_artist'     => $artist_id,
        'credit_production' => $production_id,
        'credit_role'       => $role,
        'credit_role_group' => $role_group,
        'credit_date'       => $credit_date,
        'credit_order'      => $credit_order,
      ),
      array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d')
    );

    if ($inserted && $wpdb->insert_id) {
      $new_credit_id = (int) $wpdb->insert_id;
      // Write the ct_credits ID back to the credit post so the Phase 8
      // backwards-compat hook can upsert the correct row if the post is edited.
      update_post_meta($post->ID, '_ct_credit_id', $new_credit_id);
      $migrated++;
    } else {
      echo "    ERROR inserting #{$post->ID}: {$wpdb->last_error}\n";
      $skipped++;
      $skipped_ids[] = $post->ID;
    }
  } else {
    $migrated++;
  }
}

$mode = $dry_run ? '[DRY RUN]' : '[COMPLETE]';
echo "\n$mode  Migrated: $migrated  |  Skipped: $skipped  |  Total: $total\n";

if (! empty($skipped_ids)) {
  echo "Skipped IDs: " . implode(', ', $skipped_ids) . "\n";
}
