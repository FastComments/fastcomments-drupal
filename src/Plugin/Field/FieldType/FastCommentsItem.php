<?php

namespace Drupal\fastcomments\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'fastcomments_comment' field type.
 *
 * @FieldType(
 *   id = "fastcomments_comment",
 *   label = @Translation("FastComments comment"),
 *   description = @Translation("Adds a FastComments commenting widget to an entity."),
 *   default_widget = "fastcomments_comment",
 *   default_formatter = "fastcomments_comment",
 * )
 */
class FastCommentsItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'status' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 1,
        ],
        'identifier' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = [];

    $properties['status'] = DataDefinition::create('integer')
      ->setLabel(t('Commenting status'))
      ->setRequired(TRUE);

    $properties['identifier'] = DataDefinition::create('string')
      ->setLabel(t('Comment thread identifier'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $status = $this->get('status')->getValue();
    return $status === NULL || $status === '';
  }

}
