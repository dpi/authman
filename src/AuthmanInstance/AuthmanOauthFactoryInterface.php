<?php

declare(strict_types=1);

namespace Drupal\authman\AuthmanInstance;

/**
 * The OAuth provider instance factory.
 */
interface AuthmanOauthFactoryInterface {

  /**
   * Creates an OAuth provider instance.
   *
   * Produces a ready-to-use single-use instance.
   *
   * @param string $id
   *   ID of a 'authman_auth' configuration entity.
   *
   * @return \Drupal\authman\AuthmanInstance\AuthmanOauthInstanceInterface
   *   A ready-to-use single-use OAuth provider instance.
   */
  public function get(string $id): AuthmanOauthInstanceInterface;

}
