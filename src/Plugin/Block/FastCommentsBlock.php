<?php

namespace Drupal\fastcomments\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\fastcomments\Plugin\Field\FieldType\FastCommentsItem;
use Drupal\fastcomments\Service\FastCommentsWidgetRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a FastComments widget block.
 */
#[Block(
  id: 'fastcomments_block',
  admin_label: new TranslatableMarkup('FastComments Widget'),
  category: new TranslatableMarkup('FastComments'),
)]
class FastCommentsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a FastCommentsBlock.
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
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('fastcomments.settings');

    if (empty($config->get('tenant_id'))) {
      return [];
    }

    // Look for any content entity on the current route.
    $entity = NULL;
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof ContentEntityInterface) {
        $entity = $parameter;
        break;
      }
    }

    if ($entity instanceof ContentEntityInterface) {
      // If the entity has a fastcomments_comment field (regardless of name),
      // the formatter handles rendering — skip the block to avoid duplicates.
      if (FastCommentsItem::getFieldName($entity) !== NULL) {
        return [];
      }

      return $this->widgetRenderer->buildWidgetRenderArray($entity);
    }

    // Fallback for non-entity pages: use URL path hash as urlId.
    $current_path = $this->currentPath->getPath();
    $url_id = 'drupal-path-' . md5($current_path);
    $request = $this->requestStack->getCurrentRequest();
    $url = $request->getSchemeAndHttpHost() . $request->getRequestUri();

    return $this->widgetRenderer->buildWidgetRenderArrayForPath($url_id, $url);
  }

}
