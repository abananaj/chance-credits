<?php

/**
 * Production Credits Block - Server-side render callback
 *
 * Displays the artists credited for the current production.
 * Filters by role_group based on the `roleGroup` attribute:
 *   - "all"     → all credits (default)
 *   - "team"    → all credits excluding actor and producer
 *   - "cast"    → actor only
 *   - "partner" → producer only
 */

$post_id    = get_the_ID();
$role_group = isset($attributes['roleGroup']) ? $attributes['roleGroup'] : 'all';

if (! $post_id) {
  return;
}

$team_exclude = array('actor', 'producer');

if ('cast' === $role_group) {
  $credits = get_production_credits($post_id, array('role_group' => 'actor'));
} elseif ('partner' === $role_group) {
  $credits = get_production_credits($post_id, array('role_group' => 'producer'));
} else {
  $credits = get_production_credits($post_id);
  if ('team' === $role_group) {
    $credits = array_filter($credits, function ($row) use ($team_exclude) {
      return ! in_array($row->credit_role_group, $team_exclude, true);
    });
  }
}

if (empty($credits)) {
  return;
}

$html = '<ul class="production-credits-ul">';

foreach ($credits as $row) {
  $artist_id    = (int) $row->credit_artist;
  $artist_title = get_the_title($artist_id);
  $artist_url   = get_permalink($artist_id);
  $display_role = $row->credit_role ?: $row->credit_role_group;

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

$html .= '</ul>';

echo $html;
