<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\fastcomments\Service\FastCommentsWidgetRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for content-centric FastComments widget blocks.
 *
 * Subclasses define a widget style via getWidgetStyle(). The block auto-detects
 * the current entity from the route and renders the widget with that style,
 * falling back to path-based urlId on non-entity pages.
 *
 * Unlike FastCommentsBlock, these blocks do NOT skip entities that have the
 * FastComments field — they are independent widgets.
 */
abstract class FastCommentsContentWidgetBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a FastCommentsContentWidgetBlockBase.
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
   * Returns the widget style string for this block.
   *
   * @return string
   *   The commenting style (e.g. 'imagechat', 'collabchat', 'livechat').
   */
  abstract protected function getWidgetStyle(): string;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('fastcomments.settings');

    if (empty($config->get('tenant_id'))) {
      return [];
    }

    $style = $this->getWidgetStyle();

    // Look for any content entity on the current route.
    $entity = NULL;
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof ContentEntityInterface) {
        $entity = $parameter;
        break;
      }
    }

    if ($entity instanceof ContentEntityInterface) {
      return $this->widgetRenderer->buildWidgetRenderArray($entity, $style);
    }

    // Fallback for non-entity pages: use URL path hash as urlId.
    $current_path = \Drupal::service('path.current')->getPath();
    $url_id = 'drupal-path-' . md5($current_path);
    $request = \Drupal::request();
    $url = $request->getSchemeAndHttpHost() . $request->getRequestUri();

    return $this->widgetRenderer->buildWidgetRenderArrayForPath($url_id, $url, $style);
  }

}
