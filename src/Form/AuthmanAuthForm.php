<?php

declare(strict_types = 1);

namespace Drupal\authman\Form;

use Drupal\authman\AuthmanPluginManager;
use Drupal\authman\Entity\AuthmanAuth;
use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\key\Entity\Key;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Authman instance entity form.
 *
 * @method AuthmanAuthInterface getEntity()
 */
class AuthmanAuthForm extends EntityForm {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Plugin manager.
   *
   * @var \Drupal\authman\AuthmanPluginManager
   */
  protected $pluginManager;

  /**
   * The plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('authman'),
      $container->get('messenger'),
      $container->get('plugin.manager.authman'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * Constructs a AuthmanAuthForm.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\authman\AuthmanPluginManager $pluginManager
   *   Plugin manager.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $pluginFormFactory
   *   The plugin form factory.
   */
  public function __construct(LoggerInterface $logger, MessengerInterface $messenger, AuthmanPluginManager $pluginManager, PluginFormFactoryInterface $pluginFormFactory) {
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->pluginManager = $pluginManager;
    $this->pluginFormFactory = $pluginFormFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $authmanConfig = $this->getEntity();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $authmanConfig->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $authmanConfig->id(),
      '#machine_name' => [
        'exists' => [AuthmanAuth::class, 'load'],
        'source' => ['label'],
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$authmanConfig->isNew(),
    ];

    $form['keys'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Keys'),
    ];

    $form['keys']['client_key'] = [
      '#type' => 'key_select',
      '#required' => TRUE,
      '#default_value' => $authmanConfig->getClientKeyId(),
      '#key_filters' => [
        'type' => 'authman_oauth_client',
      ],
      '#key_description' => FALSE,
      '#title' => new TranslatableMarkup('Client ID and secret'),
      '#description' => new TranslatableMarkup('Select the key to get client credentials from.'),
    ];

    $form['keys']['access_token_key'] = [
      '#type' => 'key_select',
      '#required' => TRUE,
      '#default_value' => $authmanConfig->getAccessTokenKeyId(),
      '#key_filters' => [
        'type' => 'authman_oauth_access_token',
      ],
      '#key_description' => FALSE,
      '#title' => new TranslatableMarkup('Access token'),
      '#description' => new TranslatableMarkup('Select the key to store access tokens in.'),
    ];

    // Auto-create.
    $canAddKeys = Url::fromRoute('entity.key.add_form')->access();
    if ($canAddKeys) {
      $form['keys']['client_key']['#empty_option'] = $this->t('- Create -');
      $form['keys']['client_key']['#empty_value'] = ':create:';
      $form['keys']['access_token_key']['#empty_option'] = $this->t('- Create -');
      $form['keys']['access_token_key']['#empty_value'] = ':create:';
    }

    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider'),
      '#options' => array_map(function (array $definition) {
        return $definition['label'];
      }, $this->pluginManager->getDefinitions()),
      '#required' => TRUE,
      '#empty_option' => '- Select -',
      '#default_value' => $authmanConfig->getPluginId(),
      '#description' => $this->t('Select a provider to use'),
      '#ajax' => [
        'callback' => '::ajaxPluginConfiguration',
        'trigger_as' => ['name' => 'update_plugin'],
        'effect' => 'fade',
        'method' => 'replaceWith',
        'wrapper' => 'plugin-settings',
      ],
      '#disabled' => !$this->getEntity()->isNew(),
      '#submit' => ['::submitPluginConfiguration'],
      '#executes_submit_callback' => TRUE,
    ];

    $form['update_plugin'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update plugin'),
      '#name' => 'update_plugin',
      '#attributes' => ['class' => ['js-hide']],
      '#limit_validation_errors' => [['update_plugin']],
      '#ajax' => [
        'callback' => '::ajaxPluginConfiguration',
        'method' => 'replaceWith',
        'effect' => 'fade',
        'wrapper' => 'plugin-settings',
      ],
      '#submit' => ['::submitPluginConfiguration'],
    ];

    $form['plugin_configuration'] = [
      '#prefix' => '<div id="plugin-settings">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Provider settings'),
      '#group' => 'provider_settings',
    ];

    $plugin = $authmanConfig->getPlugin();
    if ($plugin) {
      $form['plugin_configuration']['grant_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Grant type'),
        '#required' => TRUE,
        '#options' => [
          AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE => $this->t('Authorization code'),
          AuthmanAuthInterface::GRANT_DEVICE_CODE => $this->t('Device code'),
          AuthmanAuthInterface::GRANT_CLIENT_CREDENTIALS => $this->t('Client credentials'),
        ],
        '#default_value' => $authmanConfig->getGrantType(),
        '#parents' => ['grant_type'],
      ];

      // Filter out grant types not defined by the plugin definition.
      $pluginGrantTypes = $plugin->getPluginDefinition()['grant_types'] ?? [];
      $form['plugin_configuration']['grant_type']['#options'] = array_intersect_key(
        $form['plugin_configuration']['grant_type']['#options'],
        array_flip($pluginGrantTypes),
      );
    }

    $form['plugin_configuration']['plugin_form'] = [];

    if ($plugin && $plugin instanceof PluginWithFormsInterface && $plugin->hasFormClass('configure')) {
      $subform_state = SubformState::createForSubform($form['plugin_configuration']['plugin_form'], $form, $form_state);
      $form['plugin_configuration']['plugin_form'] += $this->pluginFormFactory
        ->createInstance($plugin, 'configure')
        ->buildConfigurationForm($form['plugin_configuration']['plugin_form'], $subform_state);
    }
    else {
      $form['plugin_configuration']['no_settings']['#markup'] = $this->t('No settings for this plugin.');
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $plugin = $this->getEntity()->getPlugin();
    if ($plugin instanceof PluginWithFormsInterface && $plugin->hasFormClass('configure')) {
      $subformState = SubformState::createForSubform($form['plugin_configuration']['plugin_form'], $form, $form_state);
      $this->pluginFormFactory
        ->createInstance($plugin, 'configure')
        ->validateConfigurationForm($form['plugin_configuration']['plugin_form'], $subformState);
    }
  }

  /**
   * Reload contents of plugin configuration subform.
   */
  public function ajaxPluginConfiguration(array &$form, FormStateInterface $form_state): array {
    return $form['plugin_configuration'];
  }

  /**
   * Reload contents of plugin configuration subform.
   */
  public function submitPluginConfiguration(array &$form, FormStateInterface $form_state): void {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $authmanConfig = $this->getEntity();

    $plugin = $authmanConfig->getPlugin();
    if ($plugin->hasFormClass('configure')) {
      $subformState = SubformState::createForSubform($form['plugin_configuration']['plugin_form'], $form, $form_state);
      $this->pluginFormFactory
        ->createInstance($plugin, 'configure')
        ->submitConfigurationForm($form['plugin_configuration']['plugin_form'], $subformState);

      if ($plugin instanceof ConfigurableInterface) {
        $authmanConfig->settings = $plugin->getConfiguration();
      }
    }

    $id = $form_state->getValue('id');
    $label = $form_state->getValue('label');
    if (':create:' === $form_state->getValue('client_key')) {
      $clientKey = Key::create([
        'id' => $this->generateUniqueKeyId('authman_' . $id . '_client'),
        'label' => 'Authman Client IDs for ' . $label,
        'key_type' => 'authman_oauth_client',
      ]);
      $clientKey->save();
      $form_state->setValue('client_key', $clientKey->id());
    }

    if (':create:' === $form_state->getValue('access_token_key')) {
      $accessTokenKey = Key::create([
        'id' => $this->generateUniqueKeyId('authman_' . $id . '_access_token'),
        'label' => 'Authman Access Token for ' . $label,
        'key_type' => 'authman_oauth_access_token',
      ]);
      $accessTokenKey->save();
      $form_state->setValue('access_token_key', $accessTokenKey->id());
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $authmanConfig = $this->getEntity();

    $status = $authmanConfig->save();

    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    if ($status == SAVED_UPDATED) {
      $form_state->setRedirectUrl($authmanConfig->toUrl('collection'));
      $this->messenger()->addStatus($this->t('Authman instance %label has been updated.', ['%label' => $authmanConfig->label()]));
      $this->logger->notice('Authman instance %label has been updated.', [
        '%label' => $authmanConfig->label(),
        'link' => $edit_link,
      ]);
      return $status;
    }

    $form_state->setRedirectUrl($authmanConfig->toUrl('information'));
    $this->messenger()->addStatus($this->t('Authman instance %label has been added.', ['%label' => $authmanConfig->label()]));
    $this->logger->notice('Authman instance %label has been added.', [
      '%label' => $authmanConfig->label(),
      'link' => $edit_link,
    ]);

    return $status;
  }

  /**
   * Generates a unique Key ID based on a template.
   *
   * @param string $baseKeyId
   *   A ID template.
   *
   * @return string
   *   A unique Key ID.
   */
  protected function generateUniqueKeyId(string $baseKeyId): string {
    $i = 1;
    do {
      $keyId = ($i === 1 ? $baseKeyId : sprintf('%s_%d', $baseKeyId, $i));
      $i++;
      /** @var \Drupal\key\KeyInterface|null $key */
      $key = Key::load($keyId);
    } while ($key);
    return $keyId;
  }

}
