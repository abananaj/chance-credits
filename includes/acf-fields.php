<?php

/**
 * ACF Field Group: Production Credits
 */

add_action('acf/include_fields', function () {
  if (! function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group(array(
    'key' => 'group_production_cast_crew',
    'title' => 'Production Credits',
    'fields' => array(
      array(
        'key' => 'field_production_credits_repeater',
        // 'label' => 'Credits',
        'name' => 'production_credits_repeater',
        'type' => 'repeater',
        'instructions' => 'Add company members and credited roles. Credits will automatically be created when you save.',
        'min' => 0,
        'max' => 0,
        'layout' => 'table',
        'button_label' => 'Add Credit',
        'sub_fields' => array(
          array(
            'key' => 'field_repeater_artist',
            'label' => 'Artist',
            'name' => 'artist',
            'type' => 'post_object',
            'post_type' => array('artist'),
            'return_format' => 'id',
            'multiple' => 0,
            'ui' => 1,
            'wrapper' => array(
              'width' => '30',
            ),
          ),
          array(
            'key' => 'field_repeater_role_group',
            'label' => 'Role Group',
            'name' => 'role-group',
            'type' => 'select',
            'choices' => array(
              'playwright' => 'Playwright',
              'actor' => 'Actors',
              'director' => 'Director',
              'choreographer' => 'Choreographer',
              'designer' => 'Designers',
              'producer' => 'Producers',
              'other' => 'Others',
            ),
            'return_format' => 'value',
            'ui' => 0,
            'wrapper' => array(
              'width' => '30',
            ),
          ),
          array(
            'key' => 'field_repeater_role',
            'label' => 'Role',
            'name' => 'role',
            'type' => 'text',
            'placeholder' => 'e.g., Hamlet',
            'wrapper' => array(
              'width' => '30',
            ),
          ),
          // array(
          //   'key' => 'field_repeater_role2',
          //   'label' => 'Role 2',
          //   'name' => 'role2',
          //   'type' => 'text',
          //   'placeholder' => 'e.g., Guitar',
          //   'wrapper' => array(
          //     'width' => '20',
          //   ),
          // ),
          array(
            'key' => 'field_repeater_credit_id',
            'label' => 'Credit ID',
            'name' => 'credit_id',
            'type' => 'text',
            'readonly' => true,
            'disabled' => true,
            'wrapper' => array(
              'width' => '10',
            ),
          ),
        ),
      ),
    ),
    'location' => array(
      array(
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'production',
        ),
      ),
    ),
    'menu_order' => 5,
    'position' => 'normal',
    'style' => 'table',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'active' => true,
  ));
});
