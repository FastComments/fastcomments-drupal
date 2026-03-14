<?php

namespace Drupal\fastcomments\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * API client for the FastComments Pages API.
 */
class FastCommentsApiClient {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a FastCommentsApiClient.
   */
  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory, LoggerInterface $logger) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * Checks whether the API client is configured with required credentials.
   */
  private function isConfigured(): bool {
    $config = $this->configFactory->get('fastcomments.settings');
    return !empty($config->get('tenant_id')) && !empty($config->get('api_secret'));
  }

  /**
   * Returns authentication query parameters.
   *
   * @return array
   *   Array with API_KEY and tenantId.
   */
  private function getAuthParams(): array {
    $config = $this->configFactory->get('fastcomments.settings');
    return [
      'API_KEY' => $config->get('api_secret'),
      'tenantId' => $config->get('tenant_id'),
    ];
  }

  /**
   * Returns the FastComments base URL.
   */
  private function getBaseUrl(): string {
    $config = $this->configFactory->get('fastcomments.settings');
    $siteUrl = $config->get('site_url');
    if (!empty($siteUrl)) {
      return rtrim($siteUrl, '/');
    }
    return 'https://fastcomments.com';
  }

  /**
   * Fetches a page by its URL ID.
   *
   * @param string $urlId
   *   The URL identifier for the page.
   *
   * @return array|null
   *   The page data array, or NULL if not found or on failure.
   */
  public function getPageByUrlId(string $urlId): ?array {
    if (!$this->isConfigured()) {
      return NULL;
    }

    try {
      $response = $this->httpClient->request('GET', $this->getBaseUrl() . '/api/v1/pages/by-url-id', [
        'query' => $this->getAuthParams() + ['urlId' => $urlId],
        'http_errors' => FALSE,
      ]);

      if ($response->getStatusCode() !== 200) {
        return NULL;
      }

      $data = json_decode((string) $response->getBody(), TRUE);
      if (!is_array($data)) {
        return NULL;
      }

      // The API nests the page data under a 'page' key.
      if (!empty($data['page']['id'])) {
        return $data['page'];
      }

      // Fallback: check if 'id' is at root level.
      if (!empty($data['id'])) {
        return $data;
      }

      return NULL;
    }
    catch (GuzzleException $e) {
      $this->logger->warning('FastComments API error fetching page by urlId @urlId: @message', [
        '@urlId' => $urlId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Updates a page.
   *
   * @param string $pageId
   *   The page ID.
   * @param array $data
   *   The data to update (url, title, isClosed).
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function updatePage(string $pageId, array $data): bool {
    if (!$this->isConfigured()) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->request('PATCH', $this->getBaseUrl() . '/api/v1/pages/' . $pageId, [
        'query' => $this->getAuthParams(),
        'json' => $data,
        'http_errors' => FALSE,
      ]);

      return $response->getStatusCode() === 200;
    }
    catch (GuzzleException $e) {
      $this->logger->warning('FastComments API error updating page @pageId: @message', [
        '@pageId' => $pageId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Deletes a page.
   *
   * @param string $pageId
   *   The page ID.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function deletePage(string $pageId): bool {
    if (!$this->isConfigured()) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->request('DELETE', $this->getBaseUrl() . '/api/v1/pages/' . $pageId, [
        'query' => $this->getAuthParams(),
        'http_errors' => FALSE,
      ]);

      return $response->getStatusCode() === 200;
    }
    catch (GuzzleException $e) {
      $this->logger->warning('FastComments API error deleting page @pageId: @message', [
        '@pageId' => $pageId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
