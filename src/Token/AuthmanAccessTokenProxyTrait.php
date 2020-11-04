<?php

declare(strict_types = 1);

namespace Drupal\authman\Token;

use Drupal\authman\Exception\AuthmanAccessTokenException;

/**
 * Provides a trait to proxy methods of AccessTokenInterface via a property.
 *
 * PHP8: optimise token isset guard to use throw expression on right side of
 * null coalescing op.
 */
trait AuthmanAccessTokenProxyTrait {

  /**
   * The access token.
   *
   * @var \League\OAuth2\Client\Token\AccessTokenInterface|null
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    if (!isset($this->token)) {
      throw new AuthmanAccessTokenException('Token not set.');
    }
    return $this->token->getToken();
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshToken() {
    if (!isset($this->token)) {
      throw new AuthmanAccessTokenException('Token not set.');
    }
    return $this->token->getRefreshToken();
  }

  /**
   * {@inheritdoc}
   */
  public function getExpires() {
    if (!isset($this->token)) {
      throw new AuthmanAccessTokenException('Token not set.');
    }
    return $this->token->getExpires();
  }

  /**
   * {@inheritdoc}
   */
  public function hasExpired() {
    if (!isset($this->token)) {
      throw new AuthmanAccessTokenException('Token not set.');
    }
    return $this->token->hasExpired();
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    if (!isset($this->token)) {
      throw new AuthmanAccessTokenException('Token not set.');
    }
    return $this->token->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    if (!isset($this->token)) {
      throw new AuthmanAccessTokenException('Token not set.');
    }
    return $this->token->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    if (!isset($this->token)) {
      throw new AuthmanAccessTokenException('Token not set.');
    }
    return $this->token->jsonSerialize();
  }

}
