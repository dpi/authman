<?php

declare(strict_types = 1);

namespace Drupal\authman_test_providers\Plugin\AuthmanOauth;

use Drupal\authman\AuthmanInstance\AuthmanOauthInstance;
use Drupal\authman\AuthmanInstance\AuthmanOauthInstanceInterface;
use Drupal\authman\Plugin\AuthmanOauth\AuthmanOauthPluginBase;
use Drupal\authman\Plugin\KeyType\OauthClientKeyType;
use Drupal\authman_test_providers\AuthmanTestClientCredentialsProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\key\KeyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Authman Authorization Code Test Provider.
 *
 * @AuthmanOauth(
 *   id = \Drupal\authman_test_providers\Plugin\AuthmanOauth\AuthmanTestAuthorizationCode::PLUGIN_ID,
 *   label = @Translation("Authman Authorization Code Test Provider"),
 *   grant_types = {
 *     \Drupal\authman\Entity\AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
 *   },
 * )
 *
 * @internal
 */
class AuthmanTestAuthorizationCode extends AuthmanOauthPluginBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * The plugin ID.
   */
  public const PLUGIN_ID = 'authman_test_providers_authorization_code';

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
  public function defaultConfiguration(): array {
    return [
      'foo' => '',
    ];
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

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['foo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Foo?'),
      '#default_value' => $this->getConfiguration()['foo'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['foo'] = $form_state->getValue('foo');
  }

}
