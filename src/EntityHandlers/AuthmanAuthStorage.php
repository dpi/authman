<?php

declare(strict_types = 1);

namespace Drupal\authman\EntityHandlers;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage handler for "authman_auth" configuration entities.
 *
 * @method \Drupal\authman\Entity\AuthmanAuthInterface|null load($id)
 */
class AuthmanAuthStorage extends ConfigEntityStorage {}
