<?php

namespace Drupal\fastcomments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configuration form for FastComments settings.
 */
class FastCommentsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fastcomments.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fastcomments_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fastcomments.settings');

    $form['tenant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tenant ID'),
      '#description' => $this->t('Your FastComments Tenant ID. Find this in your FastComments dashboard under My Account.'),
      '#default_value' => $config->get('tenant_id'),
      '#required' => TRUE,
    ];

    $form['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Secret'),
      '#description' => $this->t('Your FastComments API Secret. Required when SSO Mode is set to "Secure". Find this in your FastComments dashboard under My Account.'),
      '#default_value' => $config->get('api_secret'),
    ];

    $form['sso_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('SSO Mode'),
      '#description' => $this->t('Single Sign-On mode. "Secure" uses HMAC verification (recommended). "Simple" passes user data without verification. "None" disables SSO.'),
      '#options' => [
        'none' => $this->t('None'),
        'simple' => $this->t('Simple'),
        'secure' => $this->t('Secure'),
      ],
      '#default_value' => $config->get('sso_mode'),
    ];

    $form['commenting_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Commenting Style'),
      '#description' => $this->t('The type of commenting widget to display.'),
      '#options' => [
        'comments' => $this->t('Comments'),
        'livechat' => $this->t('Streaming Chat'),
        'collabchat' => $this->t('Collab Chat'),
        'collabchat_comments' => $this->t('Collab Chat + Comments'),
      ],
      '#default_value' => $config->get('commenting_style'),
    ];

    $form['cdn_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN URL'),
      '#description' => $this->t('FastComments CDN URL. For EU data residency, use https://cdn-eu.fastcomments.com'),
      '#default_value' => $config->get('cdn_url'),
      '#required' => TRUE,
    ];

    $form['site_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site URL'),
      '#description' => $this->t('FastComments site URL. For EU data residency, use https://eu.fastcomments.com'),
      '#default_value' => $config->get('site_url'),
      '#required' => TRUE,
    ];

    $node_types = NodeType::loadMultiple();
    $options = [];
    foreach ($node_types as $type) {
      $options[$type->id()] = $type->label();
    }

    $form['enabled_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Content Types'),
      '#description' => $this->t('Select which content types should display the FastComments widget. Native Drupal comments will be hidden on these content types.'),
      '#options' => $options,
      '#default_value' => $config->get('enabled_content_types') ?: [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('sso_mode') === 'secure' && empty($form_state->getValue('api_secret'))) {
      $form_state->setErrorByName('api_secret', $this->t('API Secret is required when SSO Mode is set to "Secure".'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled_types = array_values(array_filter($form_state->getValue('enabled_content_types')));

    $this->config('fastcomments.settings')
      ->set('tenant_id', $form_state->getValue('tenant_id'))
      ->set('api_secret', $form_state->getValue('api_secret'))
      ->set('sso_mode', $form_state->getValue('sso_mode'))
      ->set('commenting_style', $form_state->getValue('commenting_style'))
      ->set('cdn_url', $form_state->getValue('cdn_url'))
      ->set('site_url', $form_state->getValue('site_url'))
      ->set('enabled_content_types', $enabled_types)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
