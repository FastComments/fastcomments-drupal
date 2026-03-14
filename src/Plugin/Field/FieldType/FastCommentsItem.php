<?php

namespace Drupal\fastcomments\Plugin\Field\FieldType;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'fastcomments_comment' field type.
 *
 * @FieldType(
 *   id = "fastcomments_comment",
 *   label = @Translation("FastComments"),
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

  /**
   * Finds the first field of type 'fastcomments_comment' on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to inspect.
   *
   * @return string|null
   *   The field name, or NULL if none found.
   */
  public static function getFieldName(ContentEntityInterface $entity): ?string {
    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->getType() === 'fastcomments_comment') {
        return $field_name;
      }
    }
    return NULL;
  }

  /**
   * Reads field values with default-value fallback.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to read from.
   *
   * @return array|null
   *   Array with 'field_name', 'status', and 'identifier' keys, or NULL.
   */
  public static function getFieldValues(ContentEntityInterface $entity): ?array {
    $field_name = static::getFieldName($entity);
    if ($field_name === NULL) {
      return NULL;
    }

    $items = $entity->get($field_name);
    $status = 1;
    $identifier = '';

    if (!$items->isEmpty()) {
      $item = $items->first();
      $status = (int) $item->status;
      $identifier = $item->identifier ?? '';
    }
    else {
      $defaults = $items->getFieldDefinition()->getDefaultValueLiteral();
      if (!empty($defaults[0]['status'])) {
        $status = (int) $defaults[0]['status'];
      }
    }

    return [
      'field_name' => $field_name,
      'status' => $status,
      'identifier' => $identifier,
    ];
  }

}
