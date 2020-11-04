<?php

declare(strict_types = 1);

namespace Drupal\authman\AuthmanInstance;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\authman\Exception\AuthmanAccessTokenException;
use Drupal\authman\Exception\AuthmanTokenRenewalException;
use Drupal\authman\Token\AuthmanAccessToken;
use Drupal\Core\Url;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An OAuth provider instance.
 *
 * Represents a collection of a authorization server and access token.
 *
 * This object must not be serialized.
 *
 * Methods of provider can be called from this class, if a token is set then
 * token arguments are automatically set before proxying to provider.
 *
 * @method static setGrantFactory(\League\OAuth2\Client\Grant\GrantFactory $factory)
 * @method \League\OAuth2\Client\Grant\GrantFactory getGrantFactory()
 * @method static setRequestFactory(\League\OAuth2\Client\Tool\RequestFactory $factory)
 * @method \League\OAuth2\Client\Tool\RequestFactory getRequestFactory()
 * @method static setHttpClient(\GuzzleHttp\ClientInterface $client)
 * @method \GuzzleHttp\ClientInterface getHttpClient()
 * @method static setOptionProvider(\League\OAuth2\Client\OptionProvider\OptionProviderInterface $provider)
 * @method \League\OAuth2\Client\OptionProvider\OptionProviderInterface getOptionProvider()
 * @method string getState()
 * @method string getBaseAuthorizationUrl()
 * @method string getBaseAccessTokenUrl(array $params)
 * @method string getResourceOwnerDetailsUrl(\League\OAuth2\Client\Token\AccessToken $token)
 * @method string getAuthorizationUrl(array $options = [])
 * @method mixed authorize(array $options, ?callable $redirectHandler)
 * @method AccessTokenInterface getAccessToken($grant, array $options = [])
 * @method \Psr\Http\Message\RequestInterface getRequest(string $method, string $url, array $options = [])
 * @method mixed getParsedResponse(\Psr\Http\Message\RequestInterface $request)
 * @method \League\OAuth2\Client\Provider\ResourceOwnerInterface getResourceOwner()
 * @method array getHeaders(?mixed $token)
 */
class AuthmanOauthInstance implements AuthmanOauthInstanceInterface {

  /**
   * An instance of an authorization server provider.
   *
   * @var \League\OAuth2\Client\Provider\AbstractProvider
   */
  protected $provider;

  /**
   * An access token.
   *
   * @var \Drupal\authman\Token\AuthmanAccessToken|null
   */
  protected $authmanToken;

  /**
   * The grant type.
   *
   * @var string
   */
  protected $grantType;

  /**
   * Constructs a new AuthmanOauthInstance.
   *
   * @param \League\OAuth2\Client\Provider\AbstractProvider $provider
   *   An instance of an authorization server provider.
   * @param string $grantType
   *   The grant type.
   */
  public function __construct(AbstractProvider $provider, string $grantType) {
    $this->provider = $provider;
    $this->grantType = $grantType;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider(): AbstractProvider {
    return $this->provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken(?bool $autoRenew = NULL): ?AccessTokenInterface {
    $autoRenew = $autoRenew ?? $this->tokenAutoRenew();
    $token = $this->authmanToken;
    if ($token && $autoRenew) {
      if (!$token->getAccessToken() || $this->tokenNeedsRenewal($token)) {
        $token = $this->tokenRenew($token);
      }
    }
    return $token;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthmanToken(AuthmanAccessToken $token) {
    $this->authmanToken = $token;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAuthmanToken(): void {
    $this->authmanToken = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function tokenNeedsRenewal(AccessTokenInterface $token): bool {
    return $token->getExpires() && $token->hasExpired();
  }

  /**
   * {@inheritdoc}
   */
  public function tokenAutoRenew(): bool {
    // @todo Make this configurable see #33
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function tokenRenew(AccessTokenInterface $token): AccessTokenInterface {
    $refreshToken = NULL;

    if ($this->grantType === AuthmanAuthInterface::GRANT_CLIENT_CREDENTIALS) {
      $grant = AuthmanAuthInterface::GRANT_CLIENT_CREDENTIALS;
      $options = [];
    }
    else {
      if (!$token->getAccessToken()) {
        throw new AuthmanTokenRenewalException('Cant refresh an authorization code grant when the initial code has not been fetched.');
      }
      $grant = AuthmanAuthInterface::GRANT_REFRESH_TOKEN;
      $refreshToken = $token->getRefreshToken();
      $options = [
        'refresh_token' => $refreshToken,
      ];
    }

    try {
      $freshToken = $this->provider->getAccessToken($grant, $options);
    }
    catch (IdentityProviderException $e) {
      throw new AuthmanTokenRenewalException('', 0, $e);
    }

    $newValues = $freshToken->jsonSerialize();
    if ($refreshToken) {
      $newValues['refresh_token'] = $refreshToken;
    }

    $newToken = new $freshToken($newValues);

    $this->authmanToken->setAccessToken($newToken);
    $this->authmanToken->saveToKey();

    return $this->authmanToken;
  }

  /**
   * {@inheritdoc}
   */
  public function authorizationCodeUrl(): Url {
    return Url::fromUri($this->provider->getAuthorizationUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function authenticatedRequest(string $method, string $url, array $options = []): ResponseInterface {
    $request = $this->getAuthenticatedRequest($method, $url, $options);
    unset($options['headers']);
    unset($options['body']);
    unset($options['version']);
    return $this->getResponse($request, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticatedRequest(string $method, string $url, array $options = []): RequestInterface {
    $token = $this->getToken();
    return $this->provider->getAuthenticatedRequest($method, $url, $token, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options = []): ResponseInterface {
    // This does not defer to AbstractProvider::getResponse because it doesnt
    // currently support passing $options.
    return $this->provider->getHttpClient()->send($request, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantType(): string {
    return $this->grantType;
  }

  /**
   * Passes undefined methods to provider.
   *
   * Methods requiring a token should omit token from the argument list.
   */
  public function __call(string $name, array $arguments) {
    $reflector = new \ReflectionClass($this->provider);
    if (!$reflector->hasMethod($name)) {
      throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', get_class($this->provider), $name));
    }

    // Method must have parameters, first must be token.
    $method = $reflector->getMethod($name);

    $parameters = $method->getParameters();
    // Must have provided one less argument than the proxied method takes.
    if (count($parameters) >= 1 && count($arguments) === (count($parameters) - 1)) {
      // First parameter must be a token.
      $firstParameter = reset($parameters);
      $firstParameterType = $firstParameter->getType() ? $firstParameter->getType()->getName() : NULL;
      if ($firstParameterType === AccessToken::class) {
        // Some methods of AbstractProvider use AccessToken not the interface,
        // so get the original token when dealing with provider.
        $token = $this->getToken();
        if (!$token) {
          throw new AuthmanAccessTokenException('Token not set.');
        }
        $token = $token->getAccessToken();
        array_unshift($arguments, $token);
      }
    }

    return call_user_func_array([$this->provider, $name], $arguments);
  }

}
