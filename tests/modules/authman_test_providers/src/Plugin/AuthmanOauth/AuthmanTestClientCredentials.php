<?php

declare(strict_types = 1);

namespace Drupal\authman_test_providers\Plugin\AuthmanOauth;

use Drupal\authman\AuthmanInstance\AuthmanOauthInstance;
use Drupal\authman\AuthmanInstance\AuthmanOauthInstanceInterface;
use Drupal\authman\Plugin\AuthmanOauth\AuthmanOauthPluginBase;
use Drupal\authman\Plugin\KeyType\OauthClientKeyType;
use Drupal\authman_test_providers\AuthmanTestClientCredentialsProvider;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\key\KeyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Authman Client Credential Test Provider.
 *
 * @AuthmanOauth(
 *   id = \Drupal\authman_test_providers\Plugin\AuthmanOauth\AuthmanTestClientCredentials::PLUGIN_ID,
 *   label = @Translation("Authman Client Credential Test Provider"),
 *   grant_types = {
 *     \Drupal\authman\Entity\AuthmanAuthInterface::GRANT_CLIENT_CREDENTIALS,
 *   },
 * )
 *
 * @internal
 */
class AuthmanTestClientCredentials extends AuthmanOauthPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The plugin ID.
   */
  public const PLUGIN_ID = 'authman_test_providers_client_credentials';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance(array $providerOptions, string $grantType, KeyInterface $clientKey): AuthmanOauthInstanceInterface {
    $keyType = $clientKey->getKeyType();
    assert($keyType instanceof OauthClientKeyType);
    $values = $clientKey->getKeyValues();
    $provider = (new AuthmanTestClientCredentialsProvider($providerOptions + [
      'clientId' => $values['client_id'],
      'clientSecret' => $values['client_secret'],
    ]))->setHttpClient($this->httpClient);
    return new AuthmanOauthInstance($provider, $grantType);
  }

}
