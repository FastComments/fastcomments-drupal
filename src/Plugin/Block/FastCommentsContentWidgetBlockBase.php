<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\fastcomments\Service\FastCommentsWidgetRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * Constructs a FastCommentsContentWidgetBlockBase.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FastCommentsWidgetRenderer $widgetRenderer,
    protected RouteMatchInterface $routeMatch,
    protected ConfigFactoryInterface $configFactory,
    protected CurrentPathStack $currentPath,
    protected RequestStack $requestStack,
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
      $container->get('fastcomments.widget_renderer'),
      $container->get('current_route_match'),
      $container->get('config.factory'),
      $container->get('path.current'),
      $container->get('request_stack'),
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
    $current_path = $this->currentPath->getPath();
    $url_id = 'drupal-path-' . md5($current_path);
    $request = $this->requestStack->getCurrentRequest();
    $url = $request->getSchemeAndHttpHost() . $request->getRequestUri();

    return $this->widgetRenderer->buildWidgetRenderArrayForPath($url_id, $url, $style);
  }

}
