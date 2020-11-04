<?php

declare(strict_types = 1);

namespace Drupal\authman\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for AuthmanOauth plugins.
 *
 * @Annotation
 */
class AuthmanOauth extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Description.
   *
   * @var \Drupal\Core\Annotation\Translation|null
   *
   * @ingroup plugin_translatable
   */
  public $description = NULL;

  /**
   * Supported grant types.
   *
   * Valid values are \Drupal\authman\Entity\AuthmanAuthInterface::GRANT_*
   *
   * @var string[]
   */
  public $grant_types = [];

}
