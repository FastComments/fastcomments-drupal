<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a FastComments Top Pages block.
 */
#[Block(
  id: 'fastcomments_top_pages',
  admin_label: new TranslatableMarkup('FastComments Top Pages'),
  category: new TranslatableMarkup('FastComments'),
)]
class FastCommentsTopPagesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a FastCommentsTopPagesBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('fastcomments.settings');
    $tenant_id = $config->get('tenant_id');

    if (empty($tenant_id)) {
      return [];
    }

    $cdn_url = rtrim($config->get('cdn_url') ?: 'https://cdn.fastcomments.com', '/');

    $widget_config = [
      'tenantId' => $tenant_id,
    ];
    $config_json = json_encode($widget_config, JSON_UNESCAPED_SLASHES);

    $widget_element_id = 'fastcomments-top-pages';

    return [
      '#theme' => 'fastcomments_simple_widget',
      '#widget_element_id' => $widget_element_id,
      '#config_json' => $config_json,
      '#init_function' => 'FastCommentsTopPages',
      '#attached' => [
        'library' => ['fastcomments/styling'],
        'html_head' => [
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'script',
              '#attributes' => [
                'src' => $cdn_url . '/js/widget-top-pages.min.js',
              ],
            ],
            'fastcomments_cdn_script_top_pages',
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['config:fastcomments.settings'],
      ],
    ];
  }

}
