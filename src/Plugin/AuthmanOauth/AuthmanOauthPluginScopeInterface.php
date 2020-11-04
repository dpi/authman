<?php

declare(strict_types = 1);

namespace Drupal\authman\Plugin\AuthmanOauth;

/**
 * Interface for authman plugins where scopes can be retrieved.
 */
interface AuthmanOauthPluginScopeInterface {

  /**
   * An unordered array of scopes.
   *
   * @return string[]
   *   An unordered array of scopes.
   */
  public function getScopes(): array;

}
