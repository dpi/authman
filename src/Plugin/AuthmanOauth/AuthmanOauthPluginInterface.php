<?php

declare(strict_types = 1);

namespace Drupal\authman\Plugin\AuthmanOauth;

use Drupal\authman\AuthmanInstance\AuthmanOauthInstanceInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\key\KeyInterface;

/**
 * Defines an interface for authman plugins.
 */
interface AuthmanOauthPluginInterface extends ConfigurableInterface, DependentPluginInterface, PluginWithFormsInterface {

  /**
   * Create an instance for interacting with OAuth.
   *
   * A \League\OAuth2\Client\Provider\AbstractProvider provider will be created
   * here and configured with appropriate settings and scopes. A client key is
   * also provided, but the access token is not yet known. An access token will
   * be fed to the returned instance soon after.
   *
   * @param array $providerOptions
   *   Options that must be passed directly to the AbstractProvider instance
   *   in AbstractProvider::__construct($options). These are not options related
   *   to this plugin instance; plugin configuration should be used instead.
   * @param string $grantType
   *   The grant type, any of value of constants
   *   \Drupal\authman\Entity\AuthmanAuthInterface::GRANT_*.
   * @param \Drupal\key\KeyInterface $clientKey
   *   A client credentials key.
   *
   * @return \Drupal\authman\AuthmanInstance\AuthmanOauthInstanceInterface
   *   A single-use OAuth provider instance.
   *
   * @see \Drupal\authman\AuthmanInstance\AuthmanOauthFactory::get
   */
  public function createInstance(array $providerOptions, string $grantType, KeyInterface $clientKey): AuthmanOauthInstanceInterface;

}
