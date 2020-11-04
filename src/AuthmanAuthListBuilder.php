<?php

declare(strict_types = 1);

namespace Drupal\authman;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds a list of Authman auth instances.
 */
class AuthmanAuthListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    assert($entity instanceof AuthmanAuthInterface);
    $row['label'] = $entity->toLink(NULL, 'information');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['token-information'] = [
      'title' => $this->t('Token information'),
      'weight' => -20,
      'url' => $entity->toUrl('information'),
    ];

    return $operations;
  }

}
