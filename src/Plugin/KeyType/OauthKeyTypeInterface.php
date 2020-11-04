<?php

declare(strict_types = 1);

namespace Drupal\authman\Plugin\KeyType;

use Drupal\key\Plugin\KeyTypeInterface;

/**
 * Interface for OAuth key types.
 */
interface OauthKeyTypeInterface extends KeyTypeInterface {

  /**
   * Determines if values are considered empty.
   *
   * @param array $values
   *   Key values.
   *
   * @return bool
   *   Whether values are considered empty.
   */
  public function isEmpty(array $values): bool;

  /**
   * Clears values of a Key.
   *
   * @param array $values
   *   Key values.
   *
   * @return array
   *   Cleared values.
   */
  public function clear(array $values): array;

}
