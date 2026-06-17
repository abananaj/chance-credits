<?php

/**
 * Production Credits Block - Server-side render callback
 *
 * Displays the artists credited for the current production.
 * Filters by role-group based on the `roleGroup` attribute:
 *   - "all"     → all credits (default)
 *   - "team"    → all credits excluding actor and producer
 *   - "cast"    → actor
 *   - "partner" → producer
 */

$post_id    = get_the_ID();
$role_group = isset($attributes['roleGroup']) ? $attributes['roleGroup'] : 'all';

if (! $post_id) {
  return;
}

$team_exclude_groups = array('actor', 'producer');

$meta_query = array(
  'relation' => 'AND',
  array(
    'key'     => 'production',
    'value'   => $post_id,
    'compare' => '=',
  ),
);

if ('team' === $role_group) {
  $meta_query[] = array(
    'key'     => 'role-group',
    'value'   => $team_exclude_groups,
    'compare' => 'NOT IN',
  );
} elseif ('cast' === $role_group) {
  $meta_query[] = array(
    'key'     => 'role-group',
    'value'   => 'actor',
    'compare' => '=',
  );
} elseif ('partner' === $role_group) {
  $meta_query[] = array(
    'key'     => 'role-group',
    'value'   => 'producer',
    'compare' => '=',
  );
}

$args = array(
  'post_type'      => 'credit',
  'posts_per_page' => -1,
  'meta_query'     => $meta_query,
  'orderby'        => 'menu_order title',
  'order'          => 'ASC',
);

$query = new WP_Query($args);

if (! $query->have_posts()) {
  return;
}

$html = '<ul class="production-credits-ul">';

while ($query->have_posts()) {
  $query->the_post();
  $credit_id = get_the_ID();
  $artist_id = get_post_meta($credit_id, 'artist', true);
  $role      = get_post_meta($credit_id, 'role', true);

  if ($artist_id) {
    $artist_title = get_the_title($artist_id);
    $artist_url   = get_permalink($artist_id);

    $display_role = $role;
    if (empty($display_role)) {
      $display_role = get_post_meta($credit_id, 'role-group', true);
    }

    $html .= '<li class="credit">';
    $thumbnail_url = get_the_post_thumbnail_url($artist_id);
    if ($thumbnail_url) {
      $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($artist_title) . '" class="artist-headshot"/>';
    }
    $html .= '<p class="artist"><a href="' . esc_url($artist_url) . '">' . esc_html($artist_title) . '</a></p>';

    if (! empty($display_role)) {
      $html .= '<p class="role">' . esc_html($display_role) . '</p>';
    }

    $html .= '</li>';
  }
}

$html .= '</ul>';

wp_reset_postdata();

echo $html;
