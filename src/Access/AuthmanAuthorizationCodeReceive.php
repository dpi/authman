<?php

declare(strict_types = 1);

namespace Drupal\authman\Access;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Determines access for Authorization Code receive route.
 */
class AuthmanAuthorizationCodeReceive implements AccessInterface {

  /**
   * The private tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateStoreFactory;

  /**
   * AuthmanAuthorizationCodeReceive constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $privateStoreFactory
   *   The private tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $privateStoreFactory) {
    $this->privateStoreFactory = $privateStoreFactory;
  }

  /**
   * Protect against CSRF on authorization code receive route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\authman\Entity\AuthmanAuthInterface $authman_auth
   *   The upcasted authman_auth config entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request, AuthmanAuthInterface $authman_auth): AccessResultInterface {
    $state = $request->query->get('state');
    $code = $request->query->get('code');
    if (empty($state) || empty($code)) {
      // DefaultExceptionHtmlSubscriber::on4xx captures this.
      throw new BadRequestHttpException('Missing query arguments');
    }

    $store = $this->privateStoreFactory->get('authman.oauth.' . $authman_auth->uuid());
    if ($state !== $store->get('state')) {
      $store->delete('state');
      // DefaultExceptionHtmlSubscriber::on4xx captures this.
      throw new BadRequestHttpException('Invalid state');
    }

    return AccessResult::allowed();
  }

}
