<?php

namespace Drupal\fastcomments\Plugin\Block;

/**
 * Provides a FastComments Image Chat block.
 *
 * @Block(
 *   id = "fastcomments_image_chat",
 *   admin_label = @Translation("FastComments Image Chat"),
 *   category = @Translation("FastComments"),
 * )
 */
class FastCommentsImageChatBlock extends FastCommentsContentWidgetBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetStyle(): string {
    return 'imagechat';
  }

}
