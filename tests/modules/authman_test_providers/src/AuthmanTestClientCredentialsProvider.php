<?php

declare(strict_types = 1);

namespace Drupal\authman_test_providers;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

/**
 * A provider for client credentials.
 */
final class AuthmanTestClientCredentialsProvider extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public function getBaseAuthorizationUrl(): string {
    return 'http://example.com/oauth2/authorize';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseAccessTokenUrl(array $params): string {
    return 'http://example.com/oauth2/token';
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceOwnerDetailsUrl(AccessToken $token): string {
    return 'http://example.com/oauth2/resource-owner';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultScopes(): array {
    return [
      'access-resource-owner',
      'foos-read',
      'write-write',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function checkResponse(ResponseInterface $response, $data): void {
  }

  /**
   * {@inheritdoc}
   */
  protected function createResourceOwner(array $response, AccessToken $token) {
    throw new \LogicException('Provider doesnt support resource owners.');
  }

}
