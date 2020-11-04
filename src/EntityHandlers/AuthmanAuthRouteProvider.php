<?php

declare(strict_types = 1);

namespace Drupal\authman\EntityHandlers;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Defines a route provider for AuthmanAuth config entities.
 */
class AuthmanAuthRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);

    if ($route = $this->getInformationRoute($entity_type)) {
      $route_collection->add('entity.authman_auth.information', $route);
    }

    return $route_collection;
  }

  /**
   * Gets information route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   Information route if applicable.
   */
  protected function getInformationRoute(EntityTypeInterface $entity_type): ?Route {
    if (!$entity_type->hasLinkTemplate('information')) {
      return NULL;
    }
    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('information'));
    // Use the edit form handler, if available, otherwise default.
    $operation = 'default';
    if ($entity_type->getFormClass('information')) {
      $operation = 'information';
    }
    $route
      ->setDefaults([
        '_entity_form' => "{$entity_type_id}.{$operation}",
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ])
      ->setRequirement('_entity_access', "{$entity_type_id}.update")
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);
    return $route;
  }

}
