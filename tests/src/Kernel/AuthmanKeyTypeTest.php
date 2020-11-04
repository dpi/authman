<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\authman\Traits\AuthmanConfigTrait;

/**
 * Tests key types.
 *
 * @covers \Drupal\authman\Plugin\KeyType\OauthAccessTokenKeyType
 * @covers \Drupal\authman\Plugin\KeyType\OauthClientKeyType
 * @covers \Drupal\authman\Plugin\KeyType\OauthKeyTypeInterface
 */
final class AuthmanKeyTypeTest extends KernelTestBase {

  use AuthmanConfigTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'key',
    'user',
    'authman',
    'authman_test_providers',
  ];

  /**
   * Tests empty detection and emptying action have consistent behavior.
   *
   * @covers \Drupal\authman\Plugin\KeyType\OauthClientKeyType::clear
   * @covers \Drupal\authman\Plugin\KeyType\OauthClientKeyType::isEmpty
   */
  public function testClearClientKey(): void {
    $clientKey = $this->createClientKey([
      'client_id' => 'testClientId',
      'client_secret' => 'testClientSecret',
    ]);
    $values = $clientKey->getKeyValues(TRUE);
    $this->assertFalse($clientKey->getKeyType()->isEmpty($values));

    $values = $clientKey->getKeyType()->clear($values);
    $this->assertEquals([
      'client_id' => '',
      'client_secret' => '',
    ], $values);
    $this->assertTrue($clientKey->getKeyType()->isEmpty($values));
  }

  /**
   * Tests empty detection and emptying action have consistent behavior.
   *
   * @covers \Drupal\authman\Plugin\KeyType\OauthAccessTokenKeyType::clear
   * @covers \Drupal\authman\Plugin\KeyType\OauthAccessTokenKeyType::isEmpty
   */
  public function testClearAccessToken(): void {
    $accessToken = $this->createAccessTokenKey(['access_token' => $this->randomMachineName()]);
    $values = $accessToken->getKeyValues(TRUE);
    $this->assertFalse($accessToken->getKeyType()->isEmpty($values));

    $values = $accessToken->getKeyType()->clear($values);
    $this->assertEquals([], $values);
    $this->assertTrue($accessToken->getKeyType()->isEmpty($values));
  }

}
