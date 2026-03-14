<?php

namespace Drupal\fastcomments\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Builds the render array for the FastComments widget.
 */
class FastCommentsWidgetRenderer {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The SSO service.
   *
   * @var \Drupal\fastcomments\Service\FastCommentsSsoService
   */
  protected FastCommentsSsoService $ssoService;

  /**
   * Constructs a FastCommentsWidgetRenderer.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    FastCommentsSsoService $sso_service,
  ) {
    $this->configFactory = $config_factory;
    $this->ssoService = $sso_service;
  }

  /**
   * Build the render array for the FastComments widget.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to attach comments to.
   *
   * @return array
   *   A Drupal render array.
   */
  public function buildWidgetRenderArray(NodeInterface $node): array {
    $config = $this->configFactory->get('fastcomments.settings');
    $tenant_id = $config->get('tenant_id');
    $cdn_url = rtrim($config->get('cdn_url') ?: 'https://cdn.fastcomments.com', '/');
    $site_url = rtrim($config->get('site_url') ?: 'https://fastcomments.com', '/');
    $commenting_style = $config->get('commenting_style') ?: 'comments';

    $url_id = 'drupal-node-' . $node->id();
    $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Build widget config.
    $widget_config = [
      'tenantId' => $tenant_id,
      'urlId' => $url_id,
      'url' => $url,
    ];

    // Add SSO config.
    $sso_config = $this->ssoService->buildSsoConfig();
    $sso_config_key = $this->ssoService->getSsoConfigKey();
    if ($sso_config !== NULL && $sso_config_key !== NULL) {
      $widget_config[$sso_config_key] = $sso_config;
    }

    $config_json = json_encode($widget_config, JSON_UNESCAPED_SLASHES);

    // Build noscript URL (only for comments and livechat modes).
    $show_noscript = in_array($commenting_style, ['comments', 'livechat'], TRUE);
    $noscript_url = '';
    if ($show_noscript) {
      $noscript_params = [
        'tenantId' => $tenant_id,
        'urlId' => $url_id,
        'url' => $url,
      ];
      if ($sso_config !== NULL && $sso_config_key === 'sso') {
        $noscript_params['sso'] = json_encode($sso_config);
      }
      $noscript_url = $site_url . '/ssr/comments?' . http_build_query($noscript_params);
    }

    // Determine which CDN scripts to load.
    $scripts = $this->getScriptsForStyle($commenting_style, $cdn_url);

    $build = [
      '#theme' => 'fastcomments_widget',
      '#config_json' => $config_json,
      '#commenting_style' => $commenting_style,
      '#cdn_url' => $cdn_url,
      '#url_id' => $url_id,
      '#noscript_url' => $noscript_url,
      '#show_noscript' => $show_noscript,
      '#attached' => [
        'library' => ['fastcomments/styling'],
        'html_head' => [],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.path'],
        'tags' => ['config:fastcomments.settings'],
        'max-age' => ($this->ssoService->getSsoMode() !== 'none') ? 0 : -1,
      ],
    ];

    // Attach CDN scripts via html_head since the URLs are dynamic config.
    foreach ($scripts as $index => $script_url) {
      $build['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => [
            'src' => $script_url,
          ],
        ],
        'fastcomments_cdn_script_' . $index,
      ];
    }

    return $build;
  }

  /**
   * Build a render array using a URL path as the identifier (for non-node pages).
   *
   * @param string $url_id
   *   The URL ID to use.
   * @param string $url
   *   The canonical URL.
   *
   * @return array
   *   A Drupal render array.
   */
  public function buildWidgetRenderArrayForPath(string $url_id, string $url): array {
    $config = $this->configFactory->get('fastcomments.settings');
    $tenant_id = $config->get('tenant_id');
    $cdn_url = rtrim($config->get('cdn_url') ?: 'https://cdn.fastcomments.com', '/');
    $site_url = rtrim($config->get('site_url') ?: 'https://fastcomments.com', '/');
    $commenting_style = $config->get('commenting_style') ?: 'comments';

    $widget_config = [
      'tenantId' => $tenant_id,
      'urlId' => $url_id,
      'url' => $url,
    ];

    $sso_config = $this->ssoService->buildSsoConfig();
    $sso_config_key = $this->ssoService->getSsoConfigKey();
    if ($sso_config !== NULL && $sso_config_key !== NULL) {
      $widget_config[$sso_config_key] = $sso_config;
    }

    $config_json = json_encode($widget_config, JSON_UNESCAPED_SLASHES);

    $show_noscript = in_array($commenting_style, ['comments', 'livechat'], TRUE);
    $noscript_url = '';
    if ($show_noscript) {
      $noscript_params = [
        'tenantId' => $tenant_id,
        'urlId' => $url_id,
        'url' => $url,
      ];
      if ($sso_config !== NULL && $sso_config_key === 'sso') {
        $noscript_params['sso'] = json_encode($sso_config);
      }
      $noscript_url = $site_url . '/ssr/comments?' . http_build_query($noscript_params);
    }

    $scripts = $this->getScriptsForStyle($commenting_style, $cdn_url);

    $build = [
      '#theme' => 'fastcomments_widget',
      '#config_json' => $config_json,
      '#commenting_style' => $commenting_style,
      '#cdn_url' => $cdn_url,
      '#url_id' => $url_id,
      '#noscript_url' => $noscript_url,
      '#show_noscript' => $show_noscript,
      '#attached' => [
        'library' => ['fastcomments/styling'],
        'html_head' => [],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.path'],
        'tags' => ['config:fastcomments.settings'],
        'max-age' => ($this->ssoService->getSsoMode() !== 'none') ? 0 : -1,
      ],
    ];

    foreach ($scripts as $index => $script_url) {
      $build['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => [
            'src' => $script_url,
          ],
        ],
        'fastcomments_cdn_script_' . $index,
      ];
    }

    return $build;
  }

  /**
   * Get the CDN script URLs for a given commenting style.
   *
   * @param string $style
   *   The commenting style.
   * @param string $cdn_url
   *   The CDN base URL.
   *
   * @return string[]
   *   Array of script URLs.
   */
  protected function getScriptsForStyle(string $style, string $cdn_url): array {
    $scripts = [];
    switch ($style) {
      case 'livechat':
        $scripts[] = $cdn_url . '/js/embed-live-chat.min.js';
        break;

      case 'collabchat':
        $scripts[] = $cdn_url . '/js/embed-collab-chat.min.js';
        break;

      case 'collabchat_comments':
        $scripts[] = $cdn_url . '/js/embed-v2.min.js';
        $scripts[] = $cdn_url . '/js/embed-collab-chat.min.js';
        break;

      case 'comments':
      default:
        $scripts[] = $cdn_url . '/js/embed-v2.min.js';
        break;
    }
    return $scripts;
  }

}
