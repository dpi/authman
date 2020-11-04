<?php

declare(strict_types = 1);

namespace Drupal\authman\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyTypeMultivalueInterface;

/**
 * Defines a key for OAuth 2.
 *
 * @KeyType(
 *   id = "authman_oauth_client",
 *   label = @Translation("OAuth 2 client details"),
 *   description = @Translation("OAuth 2 client details"),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "authman_oauth_client",
 *     "accepted" = FALSE
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {
 *       "client_id" = {
 *         "label" = @Translation("Client ID"),
 *         "required" = true
 *       },
 *       "client_secret" = {
 *         "label" = @Translation("Client Secret"),
 *         "required" = true
 *       },
 *     }
 *   }
 * )
 */
class OauthClientKeyType extends KeyTypeBase implements KeyTypeMultivalueInterface, OauthKeyTypeInterface {

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration): string {
    return json_encode([
      'client_id' => '',
      'client_secret' => '',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value): void {
  }

  /**
   * {@inheritdoc}
   */
  public function serialize(array $array) {
    return json_encode($array);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($value) {
    return json_decode($value, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(array $values): bool {
    return !(!empty($values['client_id']) && !empty($values['client_secret']));
  }

  /**
   * {@inheritdoc}
   */
  public function clear(array $values): array {
    return [
      'client_id' => '',
      'client_secret' => '',
    ];
  }

}
