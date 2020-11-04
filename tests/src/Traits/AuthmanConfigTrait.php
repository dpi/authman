<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\Traits;

use Drupal\authman\AuthmanInstance\AuthmanOauthFactoryInterface;
use Drupal\authman\Entity\AuthmanAuth;
use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\key\Entity\Key;
use Drupal\key\KeyInterface;
use Drupal\Tests\RandomGeneratorTrait;

/**
 * Common utiltiies for creating Authman related configuration.
 */
trait AuthmanConfigTrait {

  use RandomGeneratorTrait;

  /**
   * Creates a client credentials Key.
   *
   * @param array|null $keyValues
   *   Optional values.
   *
   * @return \Drupal\key\KeyInterface
   *   A saved access token key.
   */
  protected function createClientKey(?array $keyValues = []): KeyInterface {
    /** @var \Drupal\key\KeyInterface $clientKey */
    $clientKey = Key::create([
      'id' => 'client_' . $this->randomMachineName(),
      'key_type' => 'authman_oauth_client',
    ]);
    if (isset($keyValues)) {
      $clientKey->setKeyValue($keyValues);
    }
    $clientKey->save();
    return $clientKey;
  }

  /**
   * Creates a access token Key.
   *
   * @param array|null $keyValues
   *   Optional values.
   *
   * @return \Drupal\key\KeyInterface
   *   A saved access token key.
   */
  protected function createAccessTokenKey(?array $keyValues = []): KeyInterface {
    /** @var \Drupal\key\KeyInterface $accessTokenKey */
    $accessTokenKey = Key::create([
      'id' => 'access_token_' . $this->randomMachineName(),
      'key_type' => 'authman_oauth_access_token',
    ]);
    if (isset($keyValues)) {
      $accessTokenKey->setKeyValue($keyValues);
    }
    $accessTokenKey->save();
    return $accessTokenKey;
  }

  /**
   * Creates a Authman config instance and optionally related keys.
   *
   * @param string $plugin
   *   The ID of an Authman plguin.
   * @param string $grantType
   *   The grant type.
   * @param \Drupal\key\KeyInterface|null $clientKey
   *   Optionally associate a client Key.
   * @param \Drupal\key\KeyInterface|null $accessTokenKey
   *   Optionally associate an access token Key.
   *
   * @return \Drupal\authman\Entity\AuthmanAuthInterface
   *   A saved Authman config instance.
   */
  protected function createAuthmanConfig(string $plugin, string $grantType, ?KeyInterface $clientKey = NULL, ?KeyInterface $accessTokenKey = NULL): AuthmanAuthInterface {
    $values = [
      'id' => $this->randomMachineName(),
      'plugin' => $plugin,
      'grant_type' => $grantType,
    ];
    if ($clientKey) {
      $values['client_key'] = $clientKey->id();
    }
    if ($accessTokenKey) {
      $values['access_token_key'] = $accessTokenKey->id();
    }

    /** @var \Drupal\authman\Entity\AuthmanAuthInterface $authmanConfig */
    $authmanConfig = AuthmanAuth::create($values);
    $authmanConfig->save();
    return $authmanConfig;
  }

  /**
   * Get the authman instance factory.
   *
   * @return \Drupal\authman\AuthmanInstance\AuthmanOauthFactoryInterface
   *   The authman instance factory.
   */
  protected function authmanInstanceFactory(): AuthmanOauthFactoryInterface {
    return \Drupal::service('authman.oauth');
  }

}
