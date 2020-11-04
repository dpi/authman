<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\Functional;

use Drupal\authman\Entity\AuthmanAuth;
use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\authman_test_providers\Plugin\AuthmanOauth\AuthmanTestAuthorizationCode;
use Drupal\Core\Url;
use Drupal\Tests\authman\Traits\AuthmanConfigTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests UI CRUD.
 *
 * @group authman
 */
class AuthmanCrudUiTest extends BrowserTestBase {

  use AuthmanConfigTrait;

  /**
   * User interface.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'key',
    'key_test',
    'authman',
    'block',
    'authman_test_provider',
    'authman_test_providers',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->adminUser = $this->createUser([
      'configure authman',
      'access administration pages',
    ]);
    $this->drupalPlaceBlock('local_actions_block');

    $state = \Drupal::state();
    $state->set('key_test:client', json_encode([
      'client_id' => '407a66da-7f12-4e09-be22-209596c6991f',
      'client_secret' => 'efc0b581-d149-41bc-87b0-90d8afbd555e',
      'account_id' => '',
    ]));
    $state->set('key_test:access_token', json_encode([
      'access_token' => '',
      'refresh_token' => '',
      'token_type' => 'bearer',
      'expires' => 0,
    ]));
  }

  /**
   * Tests authman instance administration.
   */
  public function testAuthmanInstanceAdministration() {
    $this->assertThatAnonymousUserCannotAdministerAuthmanInstances();
    $authmanConfig = $this->assertThatAdminCanAddAuthmanInstances();
    $this->assertThatAdminCanViewTokenInformationForm($authmanConfig);
    $authmanConfig = $this->assertThatAdminCanEditAuthmanInstances($authmanConfig);
    $this->assertThatAdminCanDeleteAuthmanInstances($authmanConfig);
  }

  /**
   * Tests anonymous users can't access instance admin routes.
   */
  private function assertThatAnonymousUserCannotAdministerAuthmanInstances() : void {
    $instance = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
    );
    $urls = [
      Url::fromRoute('entity.authman_auth.collection'),
      $instance->toUrl('edit-form'),
      $instance->toUrl('delete-form'),
      $instance->toUrl('information'),
      Url::fromRoute('entity.authman_auth.add_form'),
    ];
    foreach ($urls as $url) {
      $this->drupalGet($url);
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Assert that admin can add an authman instance.
   *
   * @return \Drupal\authman\Entity\AuthmanAuthInterface
   *   The added instance.
   */
  private function assertThatAdminCanAddAuthmanInstances() : AuthmanAuthInterface {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('system.admin_config'));
    $assert = $this->assertSession();
    $assert->linkExists('Authman instances');
    $collection_url = Url::fromRoute('entity.authman_auth.collection');
    $this->drupalGet($collection_url);
    $assert->statusCodeEquals(200);
    $assert->linkExists('Add instance');
    $this->clickLink('Add instance');
    $this->assertUrl(Url::fromRoute('entity.authman_auth.add_form'));
    $instance_name = $this->randomMachineName();
    $id = mb_strtolower($this->randomMachineName());

    foreach ([
      'id' => $id,
      'label' => $instance_name,
      'plugin' => AuthmanTestAuthorizationCode::PLUGIN_ID,
      // Keys from authman_test_provider/config/install/.
      'access_token_key' => 'authman_test_access_token',
      'client_key' => 'authman_test_client',
    ] as $field => $value) {
      $element = $assert->fieldExists($field);
      $element->setValue($value);
    }

    $change = $assert->buttonExists('Update plugin');
    $change->click();
    $field = $assert->fieldExists('grant_type');
    $field->setValue(AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE);
    $field = $assert->fieldExists('plugin_configuration[plugin_form][foo]');
    $field->setValue('http://example.com');

    $assert->buttonExists('Save')->click();
    $assert->pageTextContains(sprintf('Authman instance %s has been added.', $instance_name));
    $authmanConfig = AuthmanAuth::load($id);
    $assert->addressEquals($authmanConfig->toUrl('information'));
    $this->drupalGet($collection_url);
    $assert->linkExists($instance_name);
    return $authmanConfig;
  }

  /**
   * Assert that admins can view token form.
   */
  private function assertThatAdminCanViewTokenInformationForm(AuthmanAuthInterface $authmanConfig) {
    $this->drupalGet(Url::fromRoute('entity.authman_auth.collection'));
    $assert = $this->assertSession();
    $information = $authmanConfig->toUrl('information');
    $assert->linkByHrefExists($information->toString());
    $this->drupalGet($information);
    $assert->statusCodeEquals(200);
    $assert->buttonExists('Connect');
  }

  /**
   * Assert that admin can edit instances.
   *
   * @param \Drupal\authman\Entity\AuthmanAuthInterface $authmanConfig
   *   Instance to edit.
   *
   * @return \Drupal\authman\Entity\AuthmanAuthInterface
   *   The edited instance.
   */
  private function assertThatAdminCanEditAuthmanInstances(AuthmanAuthInterface $authmanConfig) : AuthmanAuthInterface {
    $collection_url = Url::fromRoute('entity.authman_auth.collection');
    $this->drupalGet($collection_url);
    $assert = $this->assertSession();
    $edit = $authmanConfig->toUrl('edit-form');
    $assert->linkByHrefExists($edit->toString());
    $this->drupalGet($edit);
    $assert->fieldValueEquals('label', $authmanConfig->label());
    $assert->fieldValueEquals('plugin', $authmanConfig->getPluginId());
    $assert->fieldValueEquals('client_key', $authmanConfig->getClientKeyId());
    $assert->fieldValueEquals('access_token_key', $authmanConfig->getAccessTokenKeyId());
    $assert->fieldValueEquals('plugin_configuration[plugin_form][foo]', 'http://example.com');
    $new_name = $this->randomMachineName();
    $this->submitForm([
      'label' => $new_name,
    ], 'Save');
    $assert->pageTextContains(sprintf('Authman instance %s has been updated.', $new_name));
    $this->assertUrl($collection_url);
    return \Drupal::entityTypeManager()->getStorage('authman_auth')->loadUnchanged($authmanConfig->id());
  }

  /**
   * Assert that admin can delete authman instances.
   *
   * @param \Drupal\authman\Entity\AuthmanAuthInterface $authmanConfig
   *   The instance to delete.
   */
  private function assertThatAdminCanDeleteAuthmanInstances(AuthmanAuthInterface $authmanConfig) : void {
    $this->drupalGet(Url::fromRoute('entity.authman_auth.collection'));
    $assert = $this->assertSession();
    $delete = $authmanConfig->toUrl('delete-form');
    $assert->linkByHrefExists($delete->toString());
    $this->drupalGet($delete);
    $this->submitForm([], 'Delete');
    $assert->pageTextContains(sprintf('The authman instance %s has been deleted.', $authmanConfig->label()));
  }

}
