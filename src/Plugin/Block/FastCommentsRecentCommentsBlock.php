<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a FastComments Recent Comments block.
 *
 * @Block(
 *   id = "fastcomments_recent_comments",
 *   admin_label = @Translation("FastComments Recent Comments"),
 *   category = @Translation("FastComments"),
 * )
 */
class FastCommentsRecentCommentsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a FastCommentsRecentCommentsBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
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
  public function defaultConfiguration() {
    return ['count' => 5];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of comments'),
      '#default_value' => $this->configuration['count'] ?? 5,
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
    $count = (int) ($this->configuration['count'] ?? 5);

    $widget_config = [
      'tenantId' => $tenant_id,
      'count' => $count,
    ];
    $config_json = json_encode($widget_config, JSON_UNESCAPED_SLASHES);

    $widget_element_id = 'fastcomments-recent-comments-' . md5((string) $count);

    $build = [
      '#theme' => 'fastcomments_simple_widget',
      '#widget_element_id' => $widget_element_id,
      '#config_json' => $config_json,
      '#init_function' => 'FastCommentsRecentComments',
      '#attached' => [
        'library' => ['fastcomments/styling'],
        'html_head' => [
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'script',
              '#attributes' => [
                'src' => $cdn_url . '/js/widget-recent-comments.min.js',
              ],
            ],
            'fastcomments_cdn_script_recent_comments',
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['config:fastcomments.settings'],
      ],
    ];

    return $build;
  }

}
