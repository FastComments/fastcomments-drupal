<?php

namespace Drupal\fastcomments\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Provides a FastComments render element.
 */
#[RenderElement('fastcomments')]
class FastComments extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#title' => '',
      '#url' => '',
      '#identifier' => '',
      '#commenting_style' => 'comments',
      '#pre_render' => [
        [static::class, 'generatePlaceholder'],
      ],
    ];
  }

  /**
   * Pre-render callback: wraps the widget in a lazy builder placeholder.
   */
  public static function generatePlaceholder(array $element): array {
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('view fastcomments')) {
      return [];
    }

    $element['widget'] = [
      '#lazy_builder' => [
        'fastcomments.widget_renderer:renderLazyWidget',
        [
          $element['#title'],
          $element['#url'],
          $element['#identifier'],
          $element['#commenting_style'],
        ],
      ],
      '#create_placeholder' => TRUE,
    ];

    return $element;
  }

}
