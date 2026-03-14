<?php

namespace Drupal\fastcomments\Plugin\Block;

/**
 * Provides a FastComments Live Chat block.
 *
 * @Block(
 *   id = "fastcomments_live_chat",
 *   admin_label = @Translation("FastComments Live Chat"),
 *   category = @Translation("FastComments"),
 * )
 */
class FastCommentsLiveChatBlock extends FastCommentsContentWidgetBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetStyle(): string {
    return 'livechat';
  }

}
