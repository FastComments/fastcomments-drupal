<?php

namespace Drupal\fastcomments\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'fastcomments_comment' widget.
 *
 * @FieldWidget(
 *   id = "fastcomments_comment",
 *   label = @Translation("FastComments Settings"),
 *   field_types = {"fastcomments_comment"},
 * )
 */
class FastCommentsWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a FastCommentsWidget.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $current_user;
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
      $configuration['third_party_settings'],
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $item = $items[$delta];

    $default_status = $item->status ?? $this->fieldDefinition->getDefaultValueLiteral()[0]['status'] ?? 1;

    $element['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('FastComments Commenting'),
      '#default_value' => $default_status,
      '#access' => $this->currentUser->hasPermission('toggle fastcomments'),
    ];

    $element['identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FastComments identifier'),
      '#default_value' => $item->identifier ?? '',
      '#description' => $this->t('An optional unique identifier for this comment thread. Changing this will detach existing comments. Leave blank to auto-generate.'),
      '#access' => $this->currentUser->hasPermission('administer fastcomments'),
    ];

    // Place in advanced group when available\.
    $element['#group'] = 'advanced';

    return $element;
  }

}
