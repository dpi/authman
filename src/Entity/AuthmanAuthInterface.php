<?php

declare(strict_types = 1);

namespace Drupal\authman\Entity;

use Drupal\authman\Plugin\AuthmanOauth\AuthmanOauthPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\key\KeyInterface;

/**
 * Interface for Authman instances.
 */
interface AuthmanAuthInterface extends ConfigEntityInterface {

  /**
   * Represents the Authorization grant type.
   */
  public const GRANT_AUTHORIZATION_CODE = 'authorization_code';

  /**
   * Represents the Client Credentials grant type.
   */
  public const GRANT_CLIENT_CREDENTIALS = 'client_credentials';

  /**
   * Represents the Device Code grant type.
   */
  public const GRANT_DEVICE_CODE = 'device_code';

  /**
   * Represents the Refresh Token grant type.
   */
  public const GRANT_REFRESH_TOKEN = 'refresh_token';

  /**
   * Returns the plugin instance.
   */
  public function getPlugin(): ?AuthmanOauthPluginInterface;

  /**
   * Gets the plugin ID.
   *
   * @return string
   *   Plugin ID.
   */
  public function getPluginId(): ?string;

  /**
   * Get the grant type for this authman configuration.
   *
   * Any of static::GRANT_*, or NULL.
   */
  public function getGrantType(): ?string;

  /**
   * Gets the client key ID.
   *
   * Holds fixed client IDs.
   *
   * @return string|null
   *   Client Key ID.
   */
  public function getClientKeyId(): ?string;

  /**
   * Gets the client key.
   *
   * @return \Drupal\key\KeyInterface
   *   Client Key, or NULL if it does not exist.
   */
  public function getClientKey(): ?KeyInterface;

  /**
   * Gets the access token Key ID.
   *
   * Holds tokens with potentially rotating values.
   *
   * @return string|null
   *   Access key ID.
   */
  public function getAccessTokenKeyId(): ?string;

  /**
   * Gets the access token Key.
   *
   * @return \Drupal\key\KeyInterface
   *   Client Key, or NULL if it does not exist.
   */
  public function getAccessTokenKey(): ?KeyInterface;

}
