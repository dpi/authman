<?php

declare(strict_types = 1);

namespace Drupal\authman\AuthmanInstance;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\authman\EntityHandlers\AuthmanAuthStorage;
use Drupal\authman\Exception\AuthmanClientCredentialsException;
use Drupal\authman\Exception\AuthmanInstanceException;
use Drupal\authman\Exception\AuthmanKeyException;
use Drupal\authman\Exception\AuthmanPluginException;
use Drupal\authman\Plugin\KeyType\OauthKeyTypeInterface;
use Drupal\authman\Token\AuthmanAccessToken;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\key\KeyInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * The OAuth provider instance factory.
 */
class AuthmanOauthFactory implements AuthmanOauthFactoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AuthmanOauthFactory constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $id): AuthmanOauthInstanceInterface {
    $authmanConfig = $this->authmanAuthStorage()->load($id);
    if (!$authmanConfig) {
      throw new \InvalidArgumentException('Invalid ID');
    }

    $redirectUri = Url::fromRoute('authman.authorization_code.receive', ['authman_auth' => $authmanConfig->id()]);
    $providerOptions = [
      'redirectUri' => $redirectUri->setAbsolute()->toString(TRUE)->getGeneratedUrl(),
    ];

    $clientKey = $this->keyStorage()->load($authmanConfig->getClientKeyId());
    if (!$clientKey instanceof KeyInterface) {
      throw new AuthmanKeyException('Client key does not exist.');
    }

    $values = $clientKey->getKeyValues(TRUE) ?? [];
    $keyType = $clientKey->getKeyType();
    assert($keyType instanceof OauthKeyTypeInterface);
    if ($keyType->isEmpty($values)) {
      throw new AuthmanClientCredentialsException('Missing client credentials');
    }

    $plugin = $authmanConfig->getPlugin();
    if (!$plugin) {
      throw new AuthmanPluginException('Missing plugin');
    }

    $grantType = $authmanConfig->getGrantType();
    if (!$grantType) {
      throw new AuthmanInstanceException('Missing grant type');
    }

    $authmanInstance = $plugin
      ->createInstance($providerOptions, $grantType, $clientKey)
      ->setAuthmanToken($this->createToken($authmanConfig));

    return $authmanInstance;
  }

  /**
   * Creates an access token.
   *
   * @param \Drupal\authman\Entity\AuthmanAuthInterface $authmanConfig
   *   An Authman config instance.
   *
   * @return \Drupal\authman\Token\AuthmanAccessToken
   *   An access token with reference its storage. This is useful for when an
   *   access token needs to be refreshed and re-saved to Key.
   */
  protected function createToken(AuthmanAuthInterface $authmanConfig): AccessTokenInterface {
    $accessTokenKey = $this->keyStorage()->load($authmanConfig->getAccessTokenKeyId());

    if (!$accessTokenKey) {
      throw new AuthmanKeyException('Access token key does not exist.');
    }

    $values = $accessTokenKey->getKeyValues(TRUE) ?? [];
    $keyType = $accessTokenKey->getKeyType();
    assert($keyType instanceof OauthKeyTypeInterface);
    if (!$keyType->isEmpty($values)) {
      $token = new AccessToken([
        'access_token' => $values['access_token'] ?? '',
        'refresh_token' => $values['refresh_token'] ?? '',
        'expires' => $values['expires'] ?? '',
      ]);
    }
    else {
      $token = NULL;
    }

    return new AuthmanAccessToken((string) $accessTokenKey->id(), $token);
  }

  /**
   * Get key config storage.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The key config storage.
   */
  protected function keyStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('key');
  }

  /**
   * Get authman_auth config storage.
   *
   * @return \Drupal\authman\EntityHandlers\AuthmanAuthStorage
   *   The authman_auth config storage.
   */
  protected function authmanAuthStorage(): AuthmanAuthStorage {
    $storage = $this->entityTypeManager->getStorage('authman_auth');
    assert($storage instanceof AuthmanAuthStorage);
    return $storage;
  }

}
