<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\Functional;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\authman_test_providers\Plugin\AuthmanOauth\AuthmanTestAuthorizationCode;
use Drupal\Core\Url;
use Drupal\Tests\authman\Traits\AuthmanConfigTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests instance add/edit form.
 *
 * @group authman
 * @coversDefaultClass \Drupal\authman\Form\AuthmanAuthForm
 */
final class AuthmanFormTest extends BrowserTestBase {

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
   * Tests plugin can be changed on edit form.
   */
  public function testPluginChangeableAddForm() {
    $addForm = Url::fromRoute('entity.authman_auth.add_form');
    $this->drupalGet($addForm);
    $pluginElement = $this->assertSession()->elementExists('named', [
      'field',
      'plugin',
    ]);
    $this->assertFalse($pluginElement->hasAttribute('disabled'));
  }

  /**
   * Tests plugin cannot be changed on edit form.
   */
  public function testPluginNotChangeableEditForm() {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      NULL,
      NULL,
    );
    $this->drupalGet($authmanConfig->toUrl('edit-form'));
    $this->assertSession()->elementAttributeContains('named', [
      'field',
      'plugin',
    ], 'disabled', 'disabled');
  }

}
