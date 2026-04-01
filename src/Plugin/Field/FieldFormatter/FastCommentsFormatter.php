<?php

namespace Drupal\fastcomments\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'fastcomments_comment' formatter.
 */
#[FieldFormatter(
  id: 'fastcomments_comment',
  label: new TranslatableMarkup('FastComments Widget'),
  field_types: ['fastcomments_comment'],
)]
class FastCommentsFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
    protected AccountProxyInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
  public static function defaultSettings(): array {
    return [
      'commenting_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $globalStyle = $this->configFactory->get('fastcomments.settings')->get('commenting_style') ?: 'comments';
    $styleLabels = [
      'comments' => $this->t('Comments'),
      'livechat' => $this->t('Streaming Chat'),
      'collabchat' => $this->t('Collab Chat'),
      'collabchat_comments' => $this->t('Collab Chat + Comments'),
    ];
    $globalLabel = $styleLabels[$globalStyle] ?? $globalStyle;

    $elements['commenting_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Commenting style'),
      '#options' => [
        '' => $this->t('Global default (@style)', ['@style' => $globalLabel]),
      ] + $styleLabels,
      '#default_value' => $this->getSetting('commenting_style'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $style = $this->getSetting('commenting_style');
    if ($style) {
      $labels = [
        'comments' => $this->t('Comments'),
        'livechat' => $this->t('Streaming Chat'),
        'collabchat' => $this->t('Collab Chat'),
        'collabchat_comments' => $this->t('Collab Chat + Comments'),
      ];
      $summary[] = $this->t('Style: @style', ['@style' => $labels[$style] ?? $style]);
    }
    else {
      $summary[] = $this->t('Style: Global default');
    }
    return $summary;
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
      try {
        $url = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
      }
      catch (\Drupal\Core\Entity\EntityMalformedException $e) {
      }
    }

    // Use formatter setting if set, otherwise fall back to global config.
    $commenting_style = $this->getSetting('commenting_style');
    if (empty($commenting_style)) {
      $config = $this->configFactory->get('fastcomments.settings');
      $commenting_style = $config->get('commenting_style') ?: 'comments';
    }
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
