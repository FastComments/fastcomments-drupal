<?php

namespace Drupal\fastcomments\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\EntityOwnerInterface;

/**
 * Handles incoming webhooks from FastComments.
 */
class FastCommentsWebhookController extends ControllerBase {

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
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a FastCommentsWebhookController.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, MailManagerInterface $mailManager, LoggerInterface $logger) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->mailManager = $mailManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('logger.channel.fastcomments'),
    );
  }

  /**
   * Handles incoming webhook requests from FastComments.
   */
  public function handleWebhook(Request $request): JsonResponse {
    $config = $this->configFactory->get('fastcomments.settings');
    $apiSecret = $config->get('api_secret');

    if (empty($apiSecret)) {
      $this->logger->error('FastComments webhook received but API secret is not configured.');
      return new JsonResponse(['error' => 'Not configured'], 500);
    }

    // Verify HMAC signature.
    $timestamp = $request->headers->get('X-FastComments-Timestamp');
    $signature = $request->headers->get('X-FastComments-Signature');

    if (empty($timestamp) || empty($signature)) {
      return new JsonResponse(['error' => 'Missing signature'], 403);
    }

    $body = $request->getContent();
    $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $apiSecret);

    if (!hash_equals($expected, $signature)) {
      $this->logger->warning('FastComments webhook signature verification failed.');
      return new JsonResponse(['error' => 'Invalid signature'], 403);
    }

    // No email on comment deletion.
    if ($request->getMethod() === 'DELETE') {
      return new JsonResponse(['status' => 'ok']);
    }

    $payload = json_decode($body, TRUE);
    if (!is_array($payload) || empty($payload['urlId'])) {
      return new JsonResponse(['error' => 'Invalid payload'], 400);
    }

    // Check if email notifications are enabled.
    if (!$config->get('email_notifications')) {
      return new JsonResponse(['status' => 'ok']);
    }

    $urlId = $payload['urlId'];

    // Parse urlId: expect 'drupal-{entityType}-{entityId}'.
    $parts = explode('-', $urlId, 3);
    if (count($parts) !== 3 || $parts[0] !== 'drupal') {
      // Custom identifier — can't map to a Drupal entity.
      return new JsonResponse(['status' => 'ok']);
    }

    $entityType = $parts[1];
    $entityId = $parts[2];

    try {
      $entity = $this->entityTypeManager->getStorage($entityType)->load($entityId);
      if (!$entity) {
        return new JsonResponse(['status' => 'ok']);
      }

      if (!($entity instanceof EntityOwnerInterface)) {
        return new JsonResponse(['status' => 'ok']);
      }

      $owner = $entity->getOwner();
      if (!$owner) {
        return new JsonResponse(['status' => 'ok']);
      }

      $email = $owner->getEmail();
      if (empty($email)) {
        return new JsonResponse(['status' => 'ok']);
      }

      $params = [
        'entity_title' => $entity->label(),
        // commenterName and commentHTML are sanitized by FastComments server-side.
        'commenter_name' => $payload['commenterName'] ?? 'Someone',
        'comment_text' => $payload['commentHTML'] ?? '',
      ];

      if ($entity->hasLinkTemplate('canonical')) {
        $params['entity_url'] = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
      }
      else {
        $params['entity_url'] = '';
      }

      $this->mailManager->mail('fastcomments', 'new_comment', $email, $owner->getPreferredLangcode(), $params);
    }
    catch (\Exception $e) {
      $this->logger->error('FastComments webhook error processing entity @type/@id: @message', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@message' => $e->getMessage(),
      ]);
    }

    return new JsonResponse(['status' => 'ok']);
  }

}
