<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\Functional;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\authman_test_providers\Plugin\AuthmanOauth\AuthmanTestAuthorizationCode;
use Drupal\Tests\authman\Traits\AuthmanConfigTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests instance info form.
 *
 * @group authman
 * @coversDefaultClass \Drupal\authman\Form\AuthmanAuthInfoForm
 */
final class AuthmanInfoFormTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'configure authman',
    ]));
  }

  /**
   * Test clearing values of access token with UI.
   */
  public function testAccessTokenClearValues(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      $this->createClientKey(),
      $accessTokenKey = $this->createAccessTokenKey([
        'access_token' => $this->randomMachineName(),
      ]),
    );

    $this->drupalGet($authmanConfig->toUrl('information'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], 'edit-reset');
    $this->assertSession()->pageTextContains('Deleted access token');

    // Token values were cleared.
    $accessTokenKey = $accessTokenKey::load($accessTokenKey->id());
    $this->assertEquals([], $accessTokenKey->getKeyValues());
  }

  /**
   * Test message when client credentials are missing.
   */
  public function testClientCredentialsValuesMissing(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      $this->createClientKey(),
      NULL,
    );

    $this->drupalGet($authmanConfig->toUrl('information'));
    $this->assertSession()->statusCodeEquals(200);

    // Empty client credentials shows a message.
    $this->assertSession()->pageTextContains('Missing client credentials.');
  }

  /**
   * Test message when client credentials are filled.
   */
  public function testClientCredentialsValues(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      $this->createClientKey([
        'client_id' => $this->randomMachineName(),
        'client_secret' => $this->randomMachineName(),
      ]),
      NULL,
    );

    $this->drupalGet($authmanConfig->toUrl('information'));
    $this->assertSession()->statusCodeEquals(200);

    // Empty client credentials shows a message.
    $this->assertSession()->pageTextNotContains('Missing client credentials.');
  }

  /**
   * Tests info page when access token is empty.
   */
  public function testAccessTokenValuesMissing(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      NULL,
      $this->createAccessTokenKey([]),
    );

    $this->drupalGet($authmanConfig->toUrl('information'));
    $this->assertSession()->statusCodeEquals(200);

    // Reset access token button wont display.
    $this->assertSession()->buttonNotExists('Reset access token');
  }

  /**
   * Tests info page when access token is filled.
   */
  public function testAccessTokenValues(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      NULL,
      $this->createAccessTokenKey([
        'access_token' => $this->randomMachineName(),
      ]),
    );

    $this->drupalGet($authmanConfig->toUrl('information'));
    $this->assertSession()->statusCodeEquals(200);

    // Reset access token button will display.
    $this->assertSession()->buttonExists('Reset access token');
  }

  /**
   * Test message prompting user to recreate missing keys.
   */
  public function testKeyMissingMessages(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      // The missing keys:
      NULL,
      NULL,
    );

    $this->drupalGet($authmanConfig->toUrl('information'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Missing client credentials key configuration. Edit this instance and create a new client credentials key.');
    $this->assertSession()->pageTextContains('Missing access token key configuration. Edit this instance and create a new access token key.');
  }

}
