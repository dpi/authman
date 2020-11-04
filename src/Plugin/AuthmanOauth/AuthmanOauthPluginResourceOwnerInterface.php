<?php

declare(strict_types = 1);

namespace Drupal\authman\Plugin\AuthmanOauth;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Interface for plugins where the resource owner can be retrieved and rendered.
 */
interface AuthmanOauthPluginResourceOwnerInterface {

  /**
   * Renders a resource owner retrieved by an instance of this plugin.
   *
   * @param \League\OAuth2\Client\Provider\ResourceOwnerInterface $resourceOwner
   *   A resource owner instance.
   *
   * @return array
   *   A render array.
   */
  public function renderResourceOwner(ResourceOwnerInterface $resourceOwner): array;

}
