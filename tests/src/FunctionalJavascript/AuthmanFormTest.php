<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\FunctionalJavascript;

use Drupal\authman_test_providers\Plugin\AuthmanOauth\AuthmanTestAuthorizationCode;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\authman\Traits\AuthmanConfigTrait;

/**
 * Tests instance add/edit form.
 *
 * @group authman
 * @coversDefaultClass \Drupal\authman\Form\AuthmanAuthForm
 */
final class AuthmanFormTest extends WebDriverTestBase {

  use AuthmanConfigTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
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
   * Tests plugin configuration form loads in with AJAX.
   */
  public function testPluginForm() {
    $addForm = Url::fromRoute('entity.authman_auth.add_form');
    $this->drupalGet($addForm);

    $session = $this->getSession();
    $this->assertSession()->elementNotExists('css', '[name="grant_type"]');
    $session->getPage()->find('css', '[name="plugin"]')->selectOption(AuthmanTestAuthorizationCode::PLUGIN_ID);
    $this->assertSession()->waitForElement('css', '[name="grant_type"]');
    $this->assertSession()->elementExists('css', '[name="plugin_configuration[plugin_form][foo]"]');
  }

}
