<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\fastcomments\Service\FastCommentsWidgetRenderer;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a FastComments widget block.
 *
 * @Block(
 *   id = "fastcomments_block",
 *   admin_label = @Translation("FastComments Widget"),
 *   category = @Translation("FastComments"),
 * )
 */
class FastCommentsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The widget renderer.
   *
   * @var \Drupal\fastcomments\Service\FastCommentsWidgetRenderer
   */
  protected FastCommentsWidgetRenderer $widgetRenderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a FastCommentsBlock.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FastCommentsWidgetRenderer $widget_renderer,
    RouteMatchInterface $route_match,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->widgetRenderer = $widget_renderer;
    $this->routeMatch = $route_match;
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
      $container->get('fastcomments.widget_renderer'),
      $container->get('current_route_match'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('fastcomments.settings');

    if (empty($config->get('tenant_id'))) {
      return [];
    }

    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof NodeInterface) {
      // Skip if this node's content type already has auto-injection enabled.
      $enabled_types = $config->get('enabled_content_types') ?: [];
      if (in_array($node->bundle(), $enabled_types, TRUE)) {
        return [];
      }

      return $this->widgetRenderer->buildWidgetRenderArray($node);
    }

    // Fallback for non-node pages: use URL path hash as urlId.
    $current_path = \Drupal::service('path.current')->getPath();
    $url_id = 'drupal-path-' . md5($current_path);
    $request = \Drupal::request();
    $url = $request->getSchemeAndHttpHost() . $request->getRequestUri();

    return $this->widgetRenderer->buildWidgetRenderArrayForPath($url_id, $url);
  }

}
