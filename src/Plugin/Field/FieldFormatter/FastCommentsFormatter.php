<?php

namespace Drupal\fastcomments\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'fastcomments_comment' formatter.
 *
 * @FieldFormatter(
 *   id = "fastcomments_comment",
 *   label = @Translation("FastComments comment"),
 *   field_types = {"fastcomments_comment"},
 * )
 */
class FastCommentsFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a FastCommentsFormatter.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $entity = $items->getEntity();

    // Apply defaults for existing entities (Field API only applies defaults
    // to new entities). If no items exist, pull default from field definition.
    $status = 1;
    $identifier = '';
    if (!$items->isEmpty()) {
      $item = $items->first();
      $status = (int) $item->status;
      $identifier = $item->identifier ?? '';
    }
    else {
      $defaults = $this->fieldDefinition->getDefaultValueLiteral();
      if (!empty($defaults[0]['status'])) {
        $status = (int) $defaults[0]['status'];
      }
    }

    if ($status !== 1) {
      return $elements;
    }

    if (!$this->currentUser->hasPermission('view fastcomments')) {
      return $elements;
    }

    // Build identifier.
    if (empty($identifier)) {
      $identifier = 'drupal-' . $entity->getEntityTypeId() . '-' . $entity->id();
    }

    // Build URL.
    $url = '';
    if ($entity->hasLinkTemplate('canonical')) {
      $url = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    }

    $config = $this->configFactory->get('fastcomments.settings');
    $commenting_style = $config->get('commenting_style') ?: 'comments';
    $title = $entity->label() ?: '';

    $elements[0] = [
      '#type' => 'fastcomments',
      '#title' => $title,
      '#url' => $url,
      '#identifier' => $identifier,
      '#commenting_style' => $commenting_style,
    ];

    return $elements;
  }

}
