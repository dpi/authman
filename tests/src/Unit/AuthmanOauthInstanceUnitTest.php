<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\Unit;

use Drupal\authman\AuthmanInstance\AuthmanOauthInstance;
use Drupal\authman\Token\AuthmanAccessToken;
use Drupal\Tests\UnitTestCase;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Instance testing.
 *
 * @group authman
 */
final class AuthmanOauthInstanceUnitTest extends UnitTestCase {

  /**
   * Test a new token is fetched if it has not been before.
   */
  public function testEmptyTokenRenewed() {
    // Token not set.
    $token = new AuthmanAccessToken('keyid', NULL);

    $authmanInstance = $this->createPartialMock(AuthmanOauthInstance::class, [
      'tokenAutoRenew',
      'tokenRenew',
      'tokenNeedsRenewal',
    ]);
    $authmanInstance->setAuthmanToken($token);
    $authmanInstance->expects($this->once())
      ->method('tokenAutoRenew')
      ->willReturn(TRUE);
    // Method not called since token not set.
    $authmanInstance->expects($this->never())
      ->method('tokenNeedsRenewal');
    $authmanInstance->expects($this->once())
      ->method('tokenRenew')
      ->willReturnArgument(0);

    $authmanInstance->getToken();
  }

  /**
   * Tests a token is fetched if it has expired.
   */
  public function testTokenExpired() {
    $token = new AccessToken([
      'access_token' => 'test_access_token',
      'refresh_token' => 'test_refresh_token',
      'expires' => (new \Datetime('-1 day'))->getTimestamp(),
    ]);
    $token = new AuthmanAccessToken('keyid', $token);

    $provider = $this->createMock(AbstractProvider::class);
    $authmanInstance = $this->getMockBuilder(AuthmanOauthInstance::class)
      ->setConstructorArgs([$provider, 'test_grant_type'])
      ->setMethods([
        'tokenAutoRenew',
        'tokenRenew',
      ])
      ->getMock();

    $authmanInstance->setAuthmanToken($token);
    $authmanInstance->expects($this->once())
      ->method('tokenAutoRenew')
      ->willReturn(TRUE);
    $authmanInstance->expects($this->once())
      ->method('tokenRenew')
      ->willReturnArgument(0);

    $authmanInstance->getToken();
  }

  /**
   * Tests a token is not fetched if it has not expired.
   */
  public function testTokenNotExpired() {
    $token = new AccessToken([
      'access_token' => 'test_access_token',
      'refresh_token' => 'test_refresh_token',
      'expires' => (new \Datetime('+1 day'))->getTimestamp(),
    ]);
    $token = new AuthmanAccessToken('keyid', $token);

    $provider = $this->createMock(AbstractProvider::class);
    $authmanInstance = $this->getMockBuilder(AuthmanOauthInstance::class)
      ->setConstructorArgs([$provider, 'test_grant_type'])
      ->setMethods([
        'tokenAutoRenew',
        'tokenRenew',
      ])
      ->getMock();

    $authmanInstance->setAuthmanToken($token);
    $authmanInstance->expects($this->once())
      ->method('tokenAutoRenew')
      ->willReturn(TRUE);
    $authmanInstance->expects($this->never())
      ->method('tokenRenew');

    $authmanInstance->getToken();
  }

}
