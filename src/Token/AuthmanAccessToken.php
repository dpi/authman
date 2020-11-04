<?php

declare(strict_types = 1);

namespace Drupal\authman\Token;

use Drupal\authman\Exception\AuthmanAccessTokenException;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\key\KeyInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Wraps an access token for storage with Key.
 */
class AuthmanAccessToken implements AccessTokenInterface {

  use AuthmanAccessTokenProxyTrait;

  /**
   * A Key config entity ID for an 'authman_oauth_access_token' key type.
   *
   * @var string
   */
  protected $accessKeyId;

  /**
   * The access token.
   *
   * @var \League\OAuth2\Client\Token\AccessTokenInterface|null
   */
  protected $token;

  /**
   * The key storage, or NULL to get directly from container.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface|null
   */
  protected $keyStorage;

  /**
   * The time machine, or NULL to get directly from container.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|null
   */
  protected $time;

  /**
   * Constructs a new AuthmanAccessToken.
   *
   * @param string $accessKeyId
   *   A Key config entity ID for an 'authman_oauth_access_token' key type.
   * @param \League\OAuth2\Client\Token\AccessTokenInterface|null $token
   *   The access token.
   */
  public function __construct(string $accessKeyId, ?AccessTokenInterface $token = NULL) {
    $this->accessKeyId = $accessKeyId;
    $this->token = $token;
  }

  /**
   * Saves the access token to a Key configuration entity.
   *
   * @return \Drupal\key\KeyInterface
   *   The saved Key config entity for an 'authman_oauth_access_token' key type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If token could not be saved to Key.
   */
  public function saveToKey(): KeyInterface {
    if (!isset($this->token)) {
      throw new AuthmanAccessTokenException('Token not set.');
    }

    /** @var \Drupal\key\KeyInterface $accessKey */
    $accessKey = $this->keyStorage()->load($this->accessKeyId);
    // See \Drupal\authman\Plugin\KeyType\OauthAccessTokenKeyType.
    $accessKey->setKeyValue([
      'access_token' => $this->token->getToken(),
      'refresh_token' => $this->token->getRefreshToken(),
      'token_type' => $this->token->getValues()['token_type'] ?? NULL,
      'expires' => $this->token->getExpires(),
    ]);
    $accessKey->save();
    return $accessKey;
  }

  /**
   * Sets the access token.
   *
   * @param \League\OAuth2\Client\Token\AccessTokenInterface $token
   *   The access token.
   */
  public function setAccessToken(AccessTokenInterface $token): void {
    $this->token = $token;
  }

  /**
   * Get the access token.
   *
   * @return \League\OAuth2\Client\Token\AccessTokenInterface|null
   *   The access token.
   */
  public function getAccessToken(): ?AccessTokenInterface {
    return $this->token;
  }

  /**
   * Get key config storage.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The key config storage.
   */
  protected function keyStorage(): ConfigEntityStorageInterface {
    $keyStorage = $this->keyStorage ?? \Drupal::entityTypeManager()->getStorage('key');
    assert($keyStorage instanceof ConfigEntityStorageInterface);
    return $keyStorage;
  }

  /**
   * Set key config storage.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $keyStorage
   *   The key config storage.
   */
  public function setKeyStorage(ConfigEntityStorageInterface $keyStorage): void {
    $this->keyStorage = $keyStorage;
  }

}
