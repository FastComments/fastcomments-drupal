<?php

/**
 * @file
 * Views integration for the FastComments module.
 */

/**
 * Implements hook_views_data_alter().
 */
function fastcomments_views_data_alter(array &$data): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $field_map = \Drupal::service('entity_field.manager')->getFieldMap();

  foreach ($field_map as $entity_type_id => $fields) {
    $has_fc_field = FALSE;
    foreach ($fields as $field_info) {
      if ($field_info['type'] === 'fastcomments_comment') {
        $has_fc_field = TRUE;
        break;
      }
    }

    if (!$has_fc_field) {
      continue;
    }

    $entity_type = $entity_type_manager->getDefinition($entity_type_id, FALSE);
    if ($entity_type === NULL) {
      continue;
    }

    $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
    if ($base_table === NULL || !isset($data[$base_table])) {
      continue;
    }

    $data[$base_table]['fastcomments_count'] = [
      'title' => t('FastComments Comment Count'),
      'help' => t('Displays the comment count from FastComments.'),
      'field' => [
        'id' => 'fastcomments_count',
      ],
    ];
  }
}
