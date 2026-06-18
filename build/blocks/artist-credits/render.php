<?php

/**
 * Artist Credits Block - Server-side render callback
 *
 * Displays all productions an artist has been credited in,
 * ordered by credit_date descending. Year is derived from credit_date (Ymd format).
 */

$post_id = get_the_ID();

if (! $post_id) {
  return;
}

$credits = get_artist_productions($post_id);

if (empty($credits)) {
  return;
}

$items = array();

foreach ($credits as $row) {
  $production_id    = (int) $row->credit_production;
  $production_title = get_the_title($production_id);
  $production_url   = get_permalink($production_id);
  $display_role     = $row->credit_role ?: $row->credit_role_group;
  $year             = $row->credit_date ? date('Y', strtotime($row->credit_date)) : '';

  $item = '<li class="credit"><a href="' . esc_url($production_url) . '"><span class="title">' . esc_html($production_title) . '</span></a>';

  $parts = array();
  if (! empty($display_role)) $parts[] = '<span class="role">' . esc_html($display_role) . '</span>';
  if (! empty($year))         $parts[] = '<span class="date">' . esc_html($year) . '</span>';
  if (! empty($parts))        $item   .= '<p>' . implode(', ', $parts) . '</p>';

  $item   .= '</li>';
  $items[] = $item;
}

echo '<ul class="artist-credits-ul">' . implode('', $items) . '</ul>';
