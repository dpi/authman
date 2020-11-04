<?php

declare(strict_types = 1);

namespace Drupal\authman;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages OAuth plugins.
 */
class AuthmanPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $moduleHandler) {
    parent::__construct(
      'Plugin/AuthmanOauth',
      $namespaces,
      $moduleHandler,
      'Drupal\authman\Plugin\AuthmanOauth\AuthmanOauthPluginInterface',
      'Drupal\authman\Annotation\AuthmanOauth'
    );
    $this->setCacheBackend($cacheBackend, 'authman_info_plugins');
  }

}
