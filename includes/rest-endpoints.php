<?php

/**
 * REST API endpoints for Chance Credits blocks.
 * Registers editor-facing endpoints for artist-credits and production-credits blocks.
 */

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Permission callback: editor-level access only.
 */
function chance_credits_editor_permission_check()
{
  return current_user_can('edit_posts');
}

/* -----------------------------------------------------------------------
 * Artist Credits
 * -------------------------------------------------------------------- */

function chance_credits_register_artist_credits_endpoint()
{
  register_rest_route('chance/v1', '/artist-credits/(?P<post_id>\d+)', array(
    'methods'             => 'GET',
    'callback'            => 'chance_credits_get_artist_credits_callback',
    'permission_callback' => 'chance_credits_editor_permission_check',
  ));
}
add_action('rest_api_init', 'chance_credits_register_artist_credits_endpoint');

function chance_credits_get_artist_credits_callback($request)
{
  $post_id = intval($request['post_id']);

  $args = array(
    'post_type'      => 'ct-credit',
    'posts_per_page' => -1,
    'meta_query'     => array(
      array('key' => 'artist', 'value' => $post_id, 'compare' => '='),
    ),
  );

  $query   = new WP_Query($args);
  $credits = array();

  while ($query->have_posts()) {
    $query->the_post();
    $credit_id     = get_the_ID();
    $production_id = get_post_meta($credit_id, 'production', true);
    $role          = get_post_meta($credit_id, 'role', true);

    if ($production_id) {
      $display_role = $role ?: get_post_meta($credit_id, 'role-group', true);

      // get_season_year is defined in artist-credits/render.php; guard for REST-only context
      $year = function_exists('get_season_year') ? get_season_year($credit_id) : '';

      $credits[] = array(
        'id'               => $credit_id,
        'production_title' => get_the_title($production_id),
        'production_url'   => get_permalink($production_id),
        'role'             => $display_role,
        'date'             => $year,
      );
    }
  }

  wp_reset_postdata();
  return new WP_REST_Response(array('credits' => $credits), 200);
}

/* -----------------------------------------------------------------------
 * Production Credits
 * -------------------------------------------------------------------- */

function chance_credits_register_production_credits_endpoint()
{
  register_rest_route('chance/v1', '/production-credits/(?P<post_id>\d+)', array(
    'methods'             => 'GET',
    'callback'            => 'chance_credits_get_production_credits_callback',
    'permission_callback' => 'chance_credits_editor_permission_check',
  ));
}
add_action('rest_api_init', 'chance_credits_register_production_credits_endpoint');

function chance_credits_get_production_credits_callback($request)
{
  $post_id = intval($request['post_id']);

  $args = array(
    'post_type'      => 'ct-credit',
    'posts_per_page' => -1,
    'meta_query'     => array(
      array('key' => 'production', 'value' => $post_id, 'compare' => '='),
    ),
  );

  $query   = new WP_Query($args);
  $credits = array();

  while ($query->have_posts()) {
    $query->the_post();
    $credit_id = get_the_ID();
    $artist_id = get_post_meta($credit_id, 'artist', true);
    $role      = get_post_meta($credit_id, 'role', true);

    if ($artist_id) {
      $display_role = $role ?: get_post_meta($credit_id, 'role-group', true);
      $role_group   = get_post_meta($credit_id, 'role-group', true);
      $credits[]    = array(
        'id'               => $credit_id,
        'artist_title'     => get_the_title($artist_id),
        'artist_url'       => get_permalink($artist_id),
        'artist_thumbnail' => get_the_post_thumbnail_url($artist_id) ?: '',
        'role'             => $display_role,
        'role_group'       => $role_group,
      );
    }
  }

  wp_reset_postdata();
  return new WP_REST_Response(array('credits' => $credits), 200);
}
