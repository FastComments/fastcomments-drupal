<?php

namespace Drupal\fastcomments\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Builds the render array for the FastComments widget.
 */
class FastCommentsWidgetRenderer implements TrustedCallbackInterface {

  /**
   * Constructs a FastCommentsWidgetRenderer.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected FastCommentsSsoService $ssoService,
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['renderLazyWidget'];
  }

  /**
   * Lazy builder callback: renders the FastComments widget.
   *
   * @param string $title
   *   The page/entity title.
   * @param string $url
   *   The canonical URL.
   * @param string $identifier
   *   The comment thread identifier.
   * @param string $commenting_style
   *   The commenting style.
   *
   * @return array
   *   A Drupal render array.
   */
  public function renderLazyWidget(string $title, string $url, string $identifier, string $commenting_style): array {
    $config = $this->configFactory->get('fastcomments.settings');
    $tenant_id = $config->get('tenant_id');
    $cdn_url = rtrim($config->get('cdn_url') ?: 'https://cdn.fastcomments.com', '/');
    $site_url = rtrim($config->get('site_url') ?: 'https://fastcomments.com', '/');

    if (empty($tenant_id)) {
      return [];
    }

    // Build widget config.
    $locale = $this->languageManager->getCurrentLanguage()->getId();
    $widget_config = [
      'tenantId' => $tenant_id,
      'urlId' => $identifier,
      'url' => $url,
      'locale' => $locale,
    ];

    // Add SSO config.
    $sso_config = $this->ssoService->buildSsoConfig();
    $sso_config_key = $this->ssoService->getSsoConfigKey();
    if ($sso_config !== NULL && $sso_config_key !== NULL) {
      $widget_config[$sso_config_key] = $sso_config;
    }

    // Build noscript URL (only for comments and livechat modes).
    $show_noscript = in_array($commenting_style, ['comments', 'livechat'], TRUE);
    $noscript_url = '';
    if ($show_noscript) {
      $noscript_params = [
        'tenantId' => $tenant_id,
        'urlId' => $identifier,
        'url' => $url,
      ];
      if ($sso_config !== NULL && $sso_config_key === 'sso') {
        $noscript_params['sso'] = json_encode($sso_config);
      }
      $noscript_url = $site_url . '/ssr/comments?' . http_build_query($noscript_params);
    }

    $widget_element_id = 'fastcomments-widget-' . md5($commenting_style . '-' . $identifier);

    $build = [
      '#theme' => 'fastcomments_widget',
      '#commenting_style' => $commenting_style,
      '#widget_element_id' => $widget_element_id,
      '#noscript_url' => $noscript_url,
      '#show_noscript' => $show_noscript,
      '#attached' => [
        'library' => [
          'fastcomments/styling',
          'fastcomments/widget',
        ],
        'drupalSettings' => [
          'fastcommentsWidgets' => [
            $widget_element_id => [
              'commentingStyle' => $commenting_style,
              'config' => $widget_config,
              'elementId' => $widget_element_id,
              'cdnUrl' => $cdn_url,
            ],
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['config:fastcomments.settings'],
      ],
    ];

    return $build;
  }

  /**
   * Build a render element for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to attach comments to.
   * @param string|null $styleOverride
   *   Optional commenting style override.
   *
   * @return array
   *   A render element array.
   */
  public function buildWidgetRenderArray(ContentEntityInterface $entity, ?string $styleOverride = NULL): array {
    $config = $this->configFactory->get('fastcomments.settings');
    $commenting_style = $styleOverride ?? ($config->get('commenting_style') ?: 'comments');

    $identifier = 'drupal-' . $entity->getEntityTypeId() . '-' . $entity->id();
    $url = '';
    if ($entity->hasLinkTemplate('canonical')) {
      try {
        $url = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
      }
      catch (\Drupal\Core\Entity\EntityMalformedException $e) {
      }
    }

    return [
      '#type' => 'fastcomments',
      '#title' => $entity->label() ?: '',
      '#url' => $url,
      '#identifier' => $identifier,
      '#commenting_style' => $commenting_style,
    ];
  }

  /**
   * Build a render element for a URL path (non-entity pages).
   *
   * @param string $url_id
   *   The URL ID to use.
   * @param string $url
   *   The canonical URL.
   * @param string|null $styleOverride
   *   Optional commenting style override.
   *
   * @return array
   *   A render element array.
   */
  public function buildWidgetRenderArrayForPath(string $url_id, string $url, ?string $styleOverride = NULL): array {
    $config = $this->configFactory->get('fastcomments.settings');
    $commenting_style = $styleOverride ?? ($config->get('commenting_style') ?: 'comments');

    return [
      '#type' => 'fastcomments',
      '#title' => '',
      '#url' => $url,
      '#identifier' => $url_id,
      '#commenting_style' => $commenting_style,
    ];
  }

}
