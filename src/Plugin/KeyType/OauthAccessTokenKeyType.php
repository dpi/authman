<?php

declare(strict_types = 1);

namespace Drupal\authman\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyTypeMultivalueInterface;

/**
 * Storage for an OAuth access token.
 *
 * @KeyType(
 *   id = "authman_oauth_access_token",
 *   label = @Translation("OAuth 2 Access Token"),
 *   description = @Translation("OAuth 2 Access Token"),
 *   group = "authentication",
 *   key_value = {
 *     "plugin" = "none",
 *     "accepted" = FALSE
 *   },
 *   multivalue = {
 *     "enabled" = true,
 *     "fields" = {
 *       "access_token" = {
 *         "label" = @Translation("Access Token"),
 *         "required" = true
 *       },
 *       "refresh_token" = {
 *         "label" = @Translation("Refresh token"),
 *         "required" = true
 *       },
 *       "token_type" = {
 *         "label" = @Translation("Token type"),
 *         "required" = true
 *       },
 *       "expires" = {
 *         "label" = @Translation("Expires"),
 *         "required" = true
 *       },
 *     }
 *   }
 * )
 */
class OauthAccessTokenKeyType extends KeyTypeBase implements KeyTypeMultivalueInterface, OauthKeyTypeInterface {

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration): string {
    return json_encode([
      'access_token' => '',
      'refresh_token' => '',
      'token_type' => '',
      'expires' => 0,
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
    return empty($values['access_token']);
  }

  /**
   * {@inheritdoc}
   */
  public function clear(array $values): array {
    return [];
  }

}
