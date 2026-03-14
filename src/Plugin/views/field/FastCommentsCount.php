<?php

namespace Drupal\fastcomments\Plugin\views\field;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\fastcomments\Plugin\Field\FieldType\FastCommentsItem;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Displays the FastComments comment count for an entity.
 *
 * @ViewsField("fastcomments_count")
 */
class FastCommentsCount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Client-side computed field — no query needed.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    if (!$entity instanceof ContentEntityInterface) {
      return [];
    }

    $field_values = FastCommentsItem::getFieldValues($entity);
    if ($field_values === NULL || $field_values['status'] !== 1) {
      return [];
    }

    $identifier = $field_values['identifier'];
    if (empty($identifier)) {
      $identifier = 'drupal-' . $entity->getEntityTypeId() . '-' . $entity->id();
    }

    $config = \Drupal::config('fastcomments.settings');
    $tenant_id = $config->get('tenant_id');
    if (empty($tenant_id)) {
      return [];
    }

    $cdn_url = rtrim($config->get('cdn_url') ?: 'https://cdn.fastcomments.com', '/');

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['fast-comments-count'],
        'data-fast-comments-url-id' => $identifier,
      ],
      '#attached' => [
        'library' => ['fastcomments/comment_count'],
        'drupalSettings' => [
          'fastcomments' => [
            'tenantId' => $tenant_id,
            'cdnUrl' => $cdn_url,
          ],
        ],
      ],
    ];
  }

}
