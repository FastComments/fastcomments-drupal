<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a FastComments Image Chat block.
 */
#[Block(
  id: 'fastcomments_image_chat',
  admin_label: new TranslatableMarkup('FastComments Image Chat'),
  category: new TranslatableMarkup('FastComments'),
)]
class FastCommentsImageChatBlock extends FastCommentsContentWidgetBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetStyle(): string {
    return 'imagechat';
  }

}
