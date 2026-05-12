<?php

/**
 * Production Credits Model
 * 
 * Handles all credit-related queries for ct-credit posts
 */

/**
 * Get all credits for a production
 * 
 * @param int   $production_id Production post ID
 * @param array $args          Optional arguments
 * @return \WP_Query|int Query object or count
 */
function get_production_credits($production_id, $args = array())
{
  $defaults = array(
    'role_group'  => '',
    'orderby'     => 'menu_order title',
    'order'       => 'ASC',
    'per_page'    => 200,
    'count_only'  => false,
    'fields'      => '',
  );

  $args = wp_parse_args($args, $defaults);

  $meta_query = array(
    array(
      'key'   => 'production',
      'value' => $production_id,
      'compare' => '=',
    ),
  );

  if (! empty($args['role_group'])) {
    $meta_query[] = array(
      'key'   => 'role-group',
      'value' => $args['role_group'],
      'compare' => '=',
    );
  }

  $query_args = array(
    'post_type'      => 'ct-credit',
    'posts_per_page' => $args['per_page'],
    'meta_query'     => $meta_query,
    'order'          => $args['order'],
    'orderby'        => $args['orderby'],
    'fields'         => $args['fields'],
  );

  if ($args['count_only']) {
    $query_args['posts_per_page'] = -1;
    $query_args['fields'] = 'ids';
    $query = new \WP_Query($query_args);
    return $query->found_posts;
  }

  return new \WP_Query($query_args);
}

/**
 * Get all productions for an artist
 * 
 * @param int   $artist_id Artist post ID
 * @param array $args      Optional arguments
 * @return \WP_Query|int Query object or count
 */
function get_artist_productions($artist_id, $args = array())
{
  $defaults = array(
    'role_group'  => '',
    'orderby'     => 'date',
    'order'       => 'DESC',
    'per_page'    => 200,
    'count_only'  => false,
    'fields'      => '',
  );

  $args = wp_parse_args($args, $defaults);

  $meta_query = array(
    array(
      'key'   => 'artist',
      'value' => $artist_id,
      'compare' => '=',
    ),
  );

  if (! empty($args['role_group'])) {
    $meta_query[] = array(
      'key'   => 'role-group',
      'value' => $args['role_group'],
      'compare' => '=',
    );
  }

  $query_args = array(
    'post_type'      => 'ct-credit',
    'posts_per_page' => $args['per_page'],
    'meta_query'     => $meta_query,
    'order'          => $args['order'],
    'orderby'        => $args['orderby'],
    'fields'         => $args['fields'],
  );

  if ($args['count_only']) {
    $query_args['posts_per_page'] = -1;
    $query_args['fields'] = 'ids';
    $query = new \WP_Query($query_args);
    return $query->found_posts;
  }

  return new \WP_Query($query_args);
}

/**
 * Sync production repeater to ct-credit posts
 */
function sync_repeater_to_credits($production_id)
{
  $repeater_data = get_field('production_credits_repeater', $production_id);
  $production_post = get_post($production_id);

  if (! $repeater_data || ! is_array($repeater_data)) {
    $repeater_data = array();
  }

  // Get production opening date
  $opening_date = get_field('opening', $production_id);

  // Get existing ct-credit posts
  $existing_credits = get_production_credits($production_id, array('per_page' => -1));
  $existing_credit_ids = wp_list_pluck($existing_credits->posts, 'ID');

  $processed_credit_ids = array();

  // Process repeater rows
  foreach ($repeater_data as $index => $row) {
    $artist_id    = $row['artist'] ?? 0;
    $role_group   = $row['role-group'] ?? '';
    $role         = $row['role'] ?? '';
    $stored_credit_id = $row['credit_id'] ?? 0;

    if (! $artist_id) {
      continue;
    }

    $artist_post = get_post($artist_id);
    if (! $artist_post) {
      continue;
    }

    // Generate title
    $credit_title = $production_post->post_title . ' / ' . $artist_post->post_title;

    // Check if credit already exists
    $credit_id = null;

    // First, try to match using the stored credit_id from repeater
    if ($stored_credit_id && in_array($stored_credit_id, $existing_credit_ids, true)) {
      $credit_id = $stored_credit_id;
    } else {
      // Fall back to matching by artist ID (for new rows or if credit was deleted externally)
      foreach ($existing_credit_ids as $existing_id) {
        $existing_artist = get_field('artist', $existing_id);
        $existing_artist_id = $existing_artist->ID ?? $existing_artist;

        if ((int) $existing_artist_id === (int) $artist_id && $existing_id !== $credit_id) {
          $credit_id = $existing_id;
          break;
        }
      }
    }

    if ($credit_id) {
      // Update existing
      wp_update_post(array(
        'ID'           => $credit_id,
        'post_title'   => $credit_title,
        'post_type'    => 'ct-credit',
        'post_status'  => 'publish',
      ));
    } else {
      // Create new
      $credit_id = wp_insert_post(array(
        'post_title'   => $credit_title,
        'post_type'    => 'ct-credit',
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
      ));
    }

    if ($credit_id && ! is_wp_error($credit_id)) {
      // Update ACF fields
      update_field('production', $production_id, $credit_id);
      update_field('artist', $artist_id, $credit_id);
      update_field('role-group', $role_group, $credit_id);
      update_field('role', $role, $credit_id);

      // Also save role and date as post meta
      update_post_meta($credit_id, 'role', $role);
      if ($opening_date) {
        update_post_meta($credit_id, 'date', $opening_date);
      }

      // Sync season terms from production to credit (exclude Previous, Next, Current)
      $season_terms = get_the_terms($production_id, 'season');
      if ($season_terms && ! is_wp_error($season_terms)) {
        $season_term_ids = array();
        $excluded_slugs = array('previous', 'next', 'current');
        foreach ($season_terms as $term) {
          if (! in_array(strtolower($term->slug), $excluded_slugs, true)) {
            $season_term_ids[] = $term->term_id;
          }
        }
        if (! empty($season_term_ids)) {
          wp_set_post_terms($credit_id, $season_term_ids, 'season');
        }
      }

      // Sync series terms from production to credit
      $series_terms = get_the_terms($production_id, 'series');
      if ($series_terms && ! is_wp_error($series_terms)) {
        $series_term_ids = wp_list_pluck($series_terms, 'term_id');
        if (! empty($series_term_ids)) {
          wp_set_post_terms($credit_id, $series_term_ids, 'series');
        }
      }

      // Record the credit_id in the repeater row
      if (isset($repeater_data[$index])) {
        $repeater_data[$index]['credit_id'] = $credit_id;
      }

      $processed_credit_ids[] = $credit_id;
    }
  }

  // Update repeater with credit IDs
  update_field('production_credits_repeater', $repeater_data, $production_id);

  // Delete credits that were removed
  $credits_to_delete = array_diff($existing_credit_ids, $processed_credit_ids);
  foreach ($credits_to_delete as $credit_id) {
    wp_delete_post($credit_id, true);
  }
}

// Hook to sync when Production is saved
add_action('acf/save_post', function ($post_id) {
  if ('ct-production' === get_post_type($post_id)) {
    sync_repeater_to_credits($post_id);
  }
}, 20);

/**
 * Get production credits organized by role group
 */
function get_credits_by_group($production_id)
{
  $query = get_production_credits($production_id);
  $organized = array();

  while ($query->have_posts()) {
    $query->the_post();

    $credit_id      = get_the_ID();
    $artist_id      = get_field('artist');
    $group          = get_field('role-group');
    $role           = get_field('role');

    if (! isset($organized[$group])) {
      $organized[$group] = array();
    }

    $organized[$group][] = array(
      'credit_id'     => $credit_id,
      'artist_id'     => $artist_id->ID ?? $artist_id,
      'artist_name'   => $artist_id->post_title ?? get_the_title($artist_id),
      'artist_link'   => get_permalink($artist_id->ID ?? $artist_id),
      'role'          => $role,
      'role-group'    => $group,
      'thumbnail_id'  => get_post_thumbnail_id($artist_id->ID ?? $artist_id),
    );
  }

  wp_reset_postdata();

  return $organized;
}

/**
 * Get productions for artist with opening dates
 */
function get_artist_productions_with_dates($artist_id)
{
  $query = get_artist_productions($artist_id);
  $productions = array();

  while ($query->have_posts()) {
    $query->the_post();

    $credit_id      = get_the_ID();
    $production_id  = get_field('production');
    $prod_open_date = (int) get_field('opening', $production_id->ID ?? $production_id);

    $productions[] = array(
      'credit_id'       => $credit_id,
      'production_id'   => $production_id->ID ?? $production_id,
      'production_title' => $production_id->post_title ?? get_the_title($production_id),
      'production_link' => get_permalink($production_id->ID ?? $production_id),
      'opening_date'    => $prod_open_date,
      'role'            => get_field('role', $credit_id),
      'role-group'      => get_field('role-group', $credit_id),
    );
  }

  wp_reset_postdata();

  // Sort by opening date descending
  usort($productions, function ($a, $b) {
    return $b['opening_date'] <=> $a['opening_date'];
  });

  return $productions;
}

/**
 * Count productions for an artist
 */
function count_artist_productions($artist_id)
{
  $query = new \WP_Query(array(
    'post_type'      => 'ct-credit',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => array(
      array(
        'key'   => 'artist',
        'value' => $artist_id,
      ),
    ),
  ));

  $production_ids = array();
  foreach ($query->posts as $credit_id) {
    $prod_id = get_field('production', $credit_id);
    $prod_id = $prod_id->ID ?? $prod_id;
    $production_ids[] = $prod_id;
  }

  return count(array_unique($production_ids));
}

/**
 * Count credits for a production
 */
function count_production_credits($production_id, $role_group = '')
{
  return get_production_credits($production_id, array(
    'role_group'  => $role_group,
    'count_only'  => true,
  ));
}
