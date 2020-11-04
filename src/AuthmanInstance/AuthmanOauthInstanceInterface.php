<?php

declare(strict_types = 1);

namespace Drupal\authman\AuthmanInstance;

use Drupal\authman\Token\AuthmanAccessToken;
use Drupal\Core\Url;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface representing an aOAuth provider instance.
 */
interface AuthmanOauthInstanceInterface {

  /**
   * Get the authorization server provider.
   *
   * @return \League\OAuth2\Client\Provider\AbstractProvider
   *   The authorization server provider.
   */
  public function getProvider(): AbstractProvider;

  /**
   * Get the access token.
   *
   * @param bool|null $autoRenew
   *   Whether to automatically renew token if necessary, or set to NULL to use
   *   the system default.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface|null
   *   The access token, or NULL if not set.
   */
  public function getToken(?bool $autoRenew = TRUE): ?AccessTokenInterface;

  /**
   * Set the access token.
   *
   * Use removeToken if the token should be removed.
   *
   * @param \Drupal\authman\Token\AuthmanAccessToken $token
   *   The access token.
   *
   * @return $this
   */
  public function setAuthmanToken(AuthmanAccessToken $token);

  /**
   * Remove the access token.
   */
  public function removeAuthmanToken(): void;

  /**
   * Determine if a token needs to be renewed.
   *
   * @param \League\OAuth2\Client\Token\AccessTokenInterface $token
   *   An access token.
   *
   * @return bool
   *   Whether a token needs to be renewed.
   */
  public function tokenNeedsRenewal(AccessTokenInterface $token): bool;

  /**
   * Determine whether tokens should be auto-renewed.
   *
   * @return bool
   *   whether tokens should be auto-renewed.
   */
  public function tokenAutoRenew(): bool;

  /**
   * Renews an access token.
   *
   * This will initiate a call to the authorization server.
   *
   * @param \League\OAuth2\Client\Token\AccessTokenInterface $token
   *   An access token.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface
   *   An access token with renewed lifetime.
   *
   * @throws \Drupal\authman\Exception\AuthmanTokenRenewalException
   *   Thrown when the token failed to renew.
   */
  public function tokenRenew(AccessTokenInterface $token): AccessTokenInterface;

  /**
   * Get the URL for authorization codes.
   *
   * @return \Drupal\Core\Url
   *   The authorization code URL.
   */
  public function authorizationCodeUrl(): Url;

  /**
   * Executes an authenticated request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response from the resource server.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown when the request fails.
   *
   * @see \GuzzleHttp\ClientInterface::send
   */
  public function authenticatedRequest(string $method, string $url, array $options = []): ResponseInterface;

  /**
   * Get an un-executed authenticated request.
   *
   * @param string $method
   *   The HTTP method.
   * @param string $url
   *   A resource server URL.
   * @param array $options
   *   Additional options to pass to Guzzle.
   *   Note: as of league/oauth2-client:2.5.0
   *   \League\OAuth2\Client\Tool\RequestFactory::getRequestWithOptions doesn't
   *   support any options except: headers/body/version, all other options are
   *   filtered out.
   *   See also https://github.com/thephpleague/oauth2-client/issues/863.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   an authenticated PSR-7 request instance.
   *
   * @see \League\OAuth2\Client\Provider\AbstractProvider::getAuthenticatedRequest
   */
  public function getAuthenticatedRequest(string $method, string $url, array $options = []): RequestInterface;

  /**
   * Sends a request instance and returns a response instance.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request to execute.
   * @param array $options
   *   Additional options.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   A response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Thrown when the request fails.
   *
   * @see \League\OAuth2\Client\Provider\AbstractProvider::getResponse
   */
  public function getResponse(RequestInterface $request, array $options = []): ResponseInterface;

  /**
   * Get the grant type.
   *
   * @return string
   *   The grant type.
   */
  public function getGrantType(): string;

}
