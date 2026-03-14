<?php

namespace Drupal\fastcomments\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;

/**
 * Handles SSO configuration for the FastComments widget.
 */
class FastCommentsSsoService {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Constructs a FastCommentsSsoService.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Build SSO config based on the configured mode.
   *
   * @return array|null
   *   SSO config array, or NULL if SSO is disabled.
   */
  public function buildSsoConfig(): ?array {
    $config = $this->configFactory->get('fastcomments.settings');
    $sso_mode = $config->get('sso_mode');

    if ($sso_mode === 'secure') {
      return $this->buildSecureSso();
    }
    if ($sso_mode === 'simple') {
      return $this->buildSimpleSso();
    }

    return NULL;
  }

  /**
   * Returns the SSO mode from config.
   *
   * @return string
   *   The SSO mode: 'none', 'simple', or 'secure'.
   */
  public function getSsoMode(): string {
    return $this->configFactory->get('fastcomments.settings')->get('sso_mode') ?: 'none';
  }

  /**
   * Returns the SSO config key name for the widget config.
   *
   * @return string|null
   *   'sso' for secure, 'simpleSSO' for simple, NULL for none.
   */
  public function getSsoConfigKey(): ?string {
    $mode = $this->getSsoMode();
    if ($mode === 'secure') {
      return 'sso';
    }
    if ($mode === 'simple') {
      return 'simpleSSO';
    }
    return NULL;
  }

  /**
   * Build Secure SSO config using HMAC-SHA256.
   *
   * @return array
   *   SSO config with timestamp, verification hash, and user data.
   */
  protected function buildSecureSso(): array {
    $config = $this->configFactory->get('fastcomments.settings');
    $api_secret = $config->get('api_secret');
    $timestamp = time() * 1000;

    $login_url = Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString();
    $logout_url = Url::fromRoute('user.logout', [], ['absolute' => TRUE])->toString();

    $result = [
      'timestamp' => $timestamp,
      'loginURL' => $login_url,
      'logoutURL' => $logout_url,
    ];

    if ($this->currentUser->isAuthenticated() && !empty($api_secret)) {
      $user_data = [
        'id' => (string) $this->currentUser->id(),
        'email' => $this->currentUser->getEmail(),
        'username' => $this->currentUser->getDisplayName(),
        'isAdmin' => $this->currentUser->hasPermission('administer fastcomments'),
        'optedInNotifications' => TRUE,
      ];

      $avatar_url = $this->getUserAvatarUrl();
      if ($avatar_url) {
        $user_data['avatar'] = $avatar_url;
      }

      \Drupal::moduleHandler()->alter('fastcomments_user_data', $user_data, $this->currentUser);
      $user_data_json_base64 = base64_encode(json_encode($user_data));
      $verification_hash = hash_hmac('sha256', $timestamp . $user_data_json_base64, $api_secret);

      $result['userDataJSONBase64'] = $user_data_json_base64;
      $result['verificationHash'] = $verification_hash;
    }

    return $result;
  }

  /**
   * Build Simple SSO config (client-side user data, no HMAC).
   *
   * @return array
   *   Simple SSO config with username, email, and avatar for logged-in users,
   *   or loginURL for anonymous users.
   */
  protected function buildSimpleSso(): array {
    $login_url = Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString();

    if (!$this->currentUser->isAuthenticated()) {
      return [
        'loginURL' => $login_url,
      ];
    }

    $sso = [
      'username' => $this->currentUser->getDisplayName(),
      'email' => $this->currentUser->getEmail(),
    ];

    $avatar_url = $this->getUserAvatarUrl();
    if ($avatar_url) {
      $sso['avatar'] = $avatar_url;
    }

    \Drupal::moduleHandler()->alter('fastcomments_user_data', $sso, $this->currentUser);

    return $sso;
  }

  /**
   * Get the current user's avatar URL from the user_picture field.
   *
   * @return string|null
   *   The absolute avatar URL, or NULL if no picture is set.
   */
  protected function getUserAvatarUrl(): ?string {
    try {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      if ($user && $user->hasField('user_picture') && !$user->get('user_picture')->isEmpty()) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $user->get('user_picture')->entity;
        if ($file) {
          return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
      }
    }
    catch (\Exception $e) {
      // Silently fail if user picture cannot be loaded.
    }
    return NULL;
  }

}
