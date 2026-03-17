<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a FastComments Live Chat block.
 */
#[Block(
  id: 'fastcomments_live_chat',
  admin_label: new TranslatableMarkup('FastComments Live Chat'),
  category: new TranslatableMarkup('FastComments'),
)]
class FastCommentsLiveChatBlock extends FastCommentsContentWidgetBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetStyle(): string {
    return 'livechat';
  }

}
