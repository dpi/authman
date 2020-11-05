<?php

declare(strict_types = 1);

namespace Drupal\authman;

use Drupal\authman\AuthmanInstance\AuthmanOauthInstanceInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Constructs a Guzzle client proxy for Authman.
 */
class AuthmanGuzzleClientProxy implements ClientInterface {

  /**
   * The authman instance.
   *
   * @var \Drupal\authman\AuthmanInstance\AuthmanOauthInstanceInterface
   */
  protected $authmanInstance;

  /**
   * Constructs a AuthmanGuzzleClientProxy.
   *
   * @param \Drupal\authman\AuthmanInstance\AuthmanOauthInstanceInterface $authmanInstance
   *   An authman instance.
   */
  public function __construct(AuthmanOauthInstanceInterface $authmanInstance) {
    $this->authmanInstance = $authmanInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function send(RequestInterface $request, array $options = []) {
    return $this->authmanInstance->authenticatedRequest($request->getMethod(), (string) $request->getUri(), $options);
  }

  /**
   * {@inheritdoc}
   */
  public function sendAsync(RequestInterface $request, array $options = []) {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function request($method, $uri, array $options = []) {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function requestAsync($method, $uri, array $options = []) {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($option = NULL) {
    return $this->authmanInstance->getProvider()->getHttpClient()->getConfig($option);
  }

}
