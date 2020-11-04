<?php

declare(strict_types = 1);

namespace Drupal\authman\Form;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\authman\Plugin\AuthmanOauth\AuthmanOauthPluginResourceOwnerInterface;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for viewing information about the key.
 *
 * @method AuthmanAuthInterface getEntity()
 */
class AuthmanAuthInfoForm extends EntityForm {

  use AjaxFormHelperTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OAuth provider instance factory.
   *
   * @var \Drupal\authman\AuthmanInstance\AuthmanOauthFactoryInterface
   */
  protected $authmanOauthFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->authmanOauthFactory = $container->get('authman.oauth');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $clientKey = $this->getEntity()->getClientKey();
    $clientKeyIsEmpty = TRUE;
    if ($clientKey) {
      $values = $clientKey->getKeyValues(TRUE) ?? [];
      $clientKeyIsEmpty = $clientKey->getKeyType()->isEmpty($values);
    }
    else {
      $this->messenger->addError($this->t('Missing client credentials key configuration. Edit this instance and create a new client credentials key.'));
    }

    $accessTokenKey = $this->getEntity()->getAccessTokenKey();
    $accessTokenIsEmpty = TRUE;
    if ($accessTokenKey) {
      $values = $accessTokenKey->getKeyValues(TRUE) ?? [];
      $accessTokenIsEmpty = $accessTokenKey->getKeyType()->isEmpty($values);
    }
    else {
      $this->messenger->addError($this->t('Missing access token key configuration. Edit this instance and create a new access token key.'));
    }

    $grantType = $this->getEntity()->getGrantType();

    $form['client_credentials'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Client credentials'),
      '#access' => $clientKeyIsEmpty,
    ];
    $form['client_credentials']['help'] = [
      '#type' => 'inline_template',
      '#template' => '<p>{{ help }}</p>',
      '#context' => [
        'help' => $this->t('Missing client credentials.'),
      ],
    ];

    if ($grantType === AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE) {
      // @todo use the start link template. See #96.
      $startUrl = Url::fromRoute('authman.authorization_code.start', ['authman_auth' => $this->getEntity()->id()]);
      $form['authorize'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Authorize'),
        '#access' => !$clientKeyIsEmpty && $accessTokenIsEmpty && $startUrl->access(),
      ];
      $form['authorize']['authorize'] = [
        '#type' => 'submit',
        '#value' => $this->t('Connect'),
        '#submit' => ['::auth'],
      ];
      $form['authorize']['help'] = [
        '#type' => 'inline_template',
        '#template' => '<p>{{ help }}</p>',
        '#context' => [
          'help' => $this->t('You will be sent offsite to being the authorization process. If you are not sent offsite or receive an error, you may need to change plugin settings or settings of the oAuth provider.'),
        ],
      ];
    }

    $form['clear_access_token'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Reset access token'),
      '#access' => !$accessTokenIsEmpty,
    ];
    $form['clear_access_token']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset access token'),
      '#submit' => ['::resetAccessToken'],
      '#button_type' => 'danger',
    ];

    // @todo show this conditionally. See #135.
    $form['resource-owner'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Resource owner'),
    ];
    $form['resource-owner']['resource-owner-button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show Resource Owner'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::resourceOwner',
      ],
    ];
    $form['resource-owner']['resource-owner'] = [
      '#attributes' => [
        'id' => 'debug',
      ],
      '#type' => 'container',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * Tests OAuth connectivity by accessing resource owner endpoint.
   */
  public function resourceOwner(array &$form, FormStateInterface $form_state) {
    $build['status_messages'] = [
      '#type' => 'status_messages',
    ];

    try {
      $authmanInstance = $this->authmanOauthFactory->get($this->getEntity()->id());
    }
    catch (\Exception $e) {
      $this->messenger->addError('Failed to get instance: ' . $e->getMessage());
    }

    if ($authmanInstance) {
      $plugin = $this->getEntity()->getPlugin();
      if ($plugin instanceof AuthmanOauthPluginResourceOwnerInterface) {
        try {
          $resourceOwner = $authmanInstance->getResourceOwner();
          $build += $plugin->renderResourceOwner($resourceOwner);
        }
        catch (\Exception $e) {
          $this->messenger->addError('Failed to get resource owner: ' . $e->getMessage());
        }
      }
      else {
        $this->messenger->addError('Plugin doesnt support resource owners.');
      }
    }

    return (new AjaxResponse())
      ->addCommand(new AppendCommand('#debug', $build));
  }

  /**
   * Submit callback.
   */
  public function auth(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $form_state->setRedirectUrl(Url::fromRoute('authman.authorization_code.start', ['authman_auth' => $entity->id()]));
  }

  /**
   * {@inheritdoc}
   */
  public function resetAccessToken(array &$form, FormStateInterface $form_state) {
    $accessTokenKey = $this->getEntity()->getAccessTokenKey();
    $values = $accessTokenKey->getKeyValues(TRUE) ?? [];
    $values = $accessTokenKey->getKeyType()->clear($values);
    $accessTokenKey->setKeyValue($values);
    $accessTokenKey->save();

    $this->messenger->addMessage('Deleted access token.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return new AjaxResponse();
  }

}
