<?php

namespace Drupal\fastcomments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
    $form['tenant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tenant ID'),
      '#description' => $this->t('Your FastComments Tenant ID. Find this under <a href="https://fastcomments.com/auth/my-account/api" target="_blank">Settings &gt; API/SSO</a> (or <a href="https://eu.fastcomments.com/auth/my-account/api" target="_blank">EU</a>).'),
      '#config_target' => 'fastcomments.settings:tenant_id',
      '#required' => TRUE,
    ];

    $form['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Secret'),
      '#description' => $this->t('Your FastComments API Secret. Required for Secure SSO, webhook verification, and page sync. Find this under <a href="https://fastcomments.com/auth/my-account/api" target="_blank">Settings &gt; API/SSO</a> (or <a href="https://eu.fastcomments.com/auth/my-account/api" target="_blank">EU</a>).'),
      '#config_target' => 'fastcomments.settings:api_secret',
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
      '#config_target' => 'fastcomments.settings:sso_mode',
    ];

    $form['commenting_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Commenting Style'),
      '#description' => $this->t('The type of commenting widget to display.'),
      '#options' => [
        'comments' => $this->t('Live Comments'),
        'livechat' => $this->t('Streaming Chat'),
        'collabchat' => $this->t('Collab Chat'),
        'collabchat_comments' => $this->t('Collab Chat + Comments'),
      ],
      '#config_target' => 'fastcomments.settings:commenting_style',
    ];

    $form['cdn_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN URL'),
      '#description' => $this->t('FastComments CDN URL. For EU data residency, use https://cdn-eu.fastcomments.com'),
      '#config_target' => 'fastcomments.settings:cdn_url',
      '#required' => TRUE,
    ];

    $form['site_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site URL'),
      '#description' => $this->t('FastComments site URL. For EU data residency, use https://eu.fastcomments.com'),
      '#config_target' => 'fastcomments.settings:site_url',
      '#required' => TRUE,
    ];

    $form['email_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Email notifications'),
      '#description' => $this->t('Send an email to content authors when a new comment is posted on their content.'),
      '#config_target' => 'fastcomments.settings:email_notifications',
    ];

    $webhookUrl = Url::fromRoute('fastcomments.webhook', [], ['absolute' => TRUE])->toString();
    $form['webhook_url'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('To receive comment notifications, configure this webhook URL in your <a href="https://fastcomments.com/auth/my-account/manage-webhooks" target="_blank">FastComments dashboard</a>: <code>@url</code>', ['@url' => $webhookUrl]) . '</p>',
    ];

    $form['field_setup_help'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('To enable FastComments on a content type, add the "FastComments" field via Structure &gt; Content types &gt; [type] &gt; Manage fields.') . '</p>',
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

}
