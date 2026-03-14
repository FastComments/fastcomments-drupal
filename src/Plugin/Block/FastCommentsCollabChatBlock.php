<?php

namespace Drupal\fastcomments\Plugin\Block;

/**
 * Provides a FastComments Collab Chat block.
 *
 * @Block(
 *   id = "fastcomments_collab_chat",
 *   admin_label = @Translation("FastComments Collab Chat"),
 *   category = @Translation("FastComments"),
 * )
 */
class FastCommentsCollabChatBlock extends FastCommentsContentWidgetBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetStyle(): string {
    return 'collabchat';
  }

}
