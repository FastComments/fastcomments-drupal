<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a FastComments Collab Chat block.
 */
#[Block(
  id: 'fastcomments_collab_chat',
  admin_label: new TranslatableMarkup('FastComments Collab Chat'),
  category: new TranslatableMarkup('FastComments'),
)]
class FastCommentsCollabChatBlock extends FastCommentsContentWidgetBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetStyle(): string {
    return 'collabchat';
  }

}
