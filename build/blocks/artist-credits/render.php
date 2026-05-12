<?php


/**
 * Helper function to get the year from season taxonomy
 * Assumes season terms are named like "2022-season"
 */
if (! function_exists('get_season_year')) :
  function get_season_year($post_id)
  {
    $seasons = get_the_terms($post_id, 'season');

    if (! $seasons || is_wp_error($seasons)) {
      return '';
    }

    // Get the first season term
    if (!empty($seasons)) {
      $season_name = $seasons[0]->name;
      // Extract year (first 4 characters, e.g., "2022" from "2022-season")
      $year = substr($season_name, 0, 4);
      if (is_numeric($year) && $year > 1900 && $year < 2100) {
        return $year;
      }
    }

    return '';
  }
endif;

/**
 * Artist Credits Block - file template
 */

$post_id = get_the_ID();

if (!$post_id) {
  return;
}

// Query for ct-credit posts where meta field 'artist' = current post ID
$args = array(
  'post_type' => 'ct-credit',
  'posts_per_page' => -1,
  'meta_query' => array(
    array(
      'key' => 'artist',
      'value' => $post_id,
      'compare' => '=',
    ),
  ),
);

$query = new WP_Query($args);

if (!$query->have_posts()) {
  return;
}

$html = '<ul class="artist-credits-ul">';

while ($query->have_posts()) {
  $query->the_post();
  $credit_id = get_the_ID();
  $production_id = get_post_meta($credit_id, 'production', true);
  $role = get_post_meta($credit_id, 'role', true);

  if ($production_id) {
    $production_title = get_the_title($production_id);
    $production_url = get_permalink($production_id);

    $html .= '<li class="credit"><a href="' . esc_url($production_url) . '"><p class="title">' . esc_html($production_title) . '</p></a>';

    // Display role, or fallback to role-group if role is blank
    $display_role = $role;
    if (empty($display_role)) {
      $display_role = get_post_meta($credit_id, 'role-group', true);
    }
    if (!empty($display_role)) {
      $html .= '<p><span class="role">' . esc_html($display_role) . '</span>, ';
    }

    // Display year from season taxonomy
    $year = get_season_year($credit_id);
    if (!empty($year)) {
      $html .= ' <span class="date">' . esc_html($year) . '</span></p>';
    }
    $html .= '</li>';
  }
}

$html .= '</ul>';

wp_reset_postdata();

echo $html;
