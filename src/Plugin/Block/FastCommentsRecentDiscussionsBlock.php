<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a FastComments Recent Discussions block.
 */
#[Block(
  id: 'fastcomments_recent_discussions',
  admin_label: new TranslatableMarkup('FastComments Recent Discussions'),
  category: new TranslatableMarkup('FastComments'),
)]
class FastCommentsRecentDiscussionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a FastCommentsRecentDiscussionsBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ConfigFactoryInterface $configFactory,
    protected LanguageManagerInterface $languageManager,
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
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['count' => 20];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of discussions'),
      '#default_value' => $this->configuration['count'] ?? 20,
      '#min' => 1,
      '#max' => 50,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['count'] = $form_state->getValue('count');
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
    $count = (int) ($this->configuration['count'] ?? 20);

    $locale = $this->languageManager->getCurrentLanguage()->getId();
    $widget_config = [
      'tenantId' => $tenant_id,
      'count' => $count,
      'locale' => $locale,
    ];
    $config_json = json_encode($widget_config, JSON_UNESCAPED_SLASHES);

    $widget_element_id = 'fastcomments-recent-discussions-' . md5((string) $count);

    return [
      '#theme' => 'fastcomments_simple_widget',
      '#widget_element_id' => $widget_element_id,
      '#config_json' => $config_json,
      '#init_function' => 'FastCommentsRecentDiscussionsV2',
      '#attached' => [
        'library' => ['fastcomments/styling'],
        'html_head' => [
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'script',
              '#attributes' => [
                'src' => $cdn_url . '/js/widget-recent-discussions-v2.min.js',
              ],
            ],
            'fastcomments_cdn_script_recent_discussions',
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['config:fastcomments.settings'],
      ],
    ];
  }

}
