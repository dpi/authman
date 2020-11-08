<?php

declare(strict_types = 1);

namespace Drupal\authman;

use Drupal\Component\Datetime\TimeInterface;
use League\OAuth2\Client\Provider\Clock;

/**
 * Allows OAuth providers to use the Drupal clock.
 */
class AuthmanClock extends Clock {

  /**
   * Time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * AuthmanClock constructor.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time.
   */
  public function __construct(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function now() {
    return new \DateTimeImmutable('@' . $this->time->getRequestTime());
  }

}
