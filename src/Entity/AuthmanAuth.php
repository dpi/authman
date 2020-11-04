<?php

declare(strict_types = 1);

namespace Drupal\authman\Entity;

use Drupal\authman\Plugin\AuthmanOauthPluginCollection;
use Drupal\authman\Plugin\AuthmanOauth\AuthmanOauthPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\key\Entity\Key;
use Drupal\key\KeyInterface;

/**
 * Represents a OAuth plugin instance.
 *
 * @ConfigEntityType(
 *   id = "authman_auth",
 *   label = @Translation("Authman instance"),
 *   label_collection = @Translation("Authman instances"),
 *   label_singular = @Translation("Authman instance"),
 *   label_plural = @Translation("Authman instances"),
 *   label_count = @PluralTranslation(
 *     singular = "@count auth instance",
 *     plural = "@count auth instances",
 *   ),
 *   config_prefix = "authman_auth",
 *   admin_permission = "configure authman",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   handlers = {
 *     "storage" = "Drupal\authman\EntityHandlers\AuthmanAuthStorage",
 *     "route_provider" = {
 *       "html" = "Drupal\authman\EntityHandlers\AuthmanAuthRouteProvider",
 *     },
 *     "list_builder" = "Drupal\authman\AuthmanAuthListBuilder",
 *     "form" = {
 *       "add" = "Drupal\authman\Form\AuthmanAuthForm",
 *       "default" = "Drupal\authman\Form\AuthmanAuthForm",
 *       "edit" = "Drupal\authman\Form\AuthmanAuthForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "information" = "Drupal\authman\Form\AuthmanAuthInfoForm",
 *     }
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/authman/instances/{authman_auth}",
 *     "delete-form" = "/admin/config/authman/instances/{authman_auth}/delete",
 *     "collection" = "/admin/config/authman/instances",
 *     "add-form" = "/admin/config/authman/create-instance",
 *     "information" = "/admin/config/authman/instances/{authman_auth}/information",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "settings",
 *     "grant_type",
 *     "client_key",
 *     "access_token_key",
 *   },
 * )
 */
class AuthmanAuth extends ConfigEntityBase implements AuthmanAuthInterface, EntityWithPluginCollectionInterface {

  /**
   * Provider plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * Plugin settings.
   *
   * @var array
   */
  public $settings = [];

  /**
   * OAuth flow (grant type).
   *
   * @var string
   */
  protected $grant_type;

  /**
   * ID of client key.
   *
   * @var string
   */
  protected $client_key;

  /**
   * ID of access token key.
   *
   * @var string
   */
  protected $access_token_key;

  /**
   * Encapsulates the creation of the plugin collection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection|null
   *   The plugin collection, or NULL if no plugin collection was created.
   */
  protected function getPluginCollection(): ?AuthmanOauthPluginCollection {
    if (!$this->getPluginId()) {
      return NULL;
    }

    if (!isset($this->pluginCollection)) {
      $this->pluginCollection = new AuthmanOauthPluginCollection(
        \Drupal::service('plugin.manager.authman'),
        $this->getPluginId(),
        $this->settings,
      );
    }

    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['settings' => $this->getPluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin(): ?AuthmanOauthPluginInterface {
    if (!($plugin_id = $this->getPluginId())) {
      return NULL;
    }
    return $this->getPluginCollection()->get($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() : ?string {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantType(): ?string {
    return $this->grant_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientKeyId(): ?string {
    return $this->client_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientKey(): ?KeyInterface {
    return $this->getClientKeyId()
      ? Key::load($this->getClientKeyId())
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessTokenKeyId(): ?string {
    return $this->access_token_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessTokenKey(): ?KeyInterface {
    return $this->getAccessTokenKeyId()
      ? Key::load($this->getAccessTokenKeyId())
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    if ($this->client_key && $client_key = Key::load($this->client_key)) {
      $this->addDependency($client_key->getConfigDependencyKey(), $client_key->getConfigDependencyName());
    }
    if ($this->access_token_key && $accessTokenKey = Key::load($this->access_token_key)) {
      $this->addDependency($accessTokenKey->getConfigDependencyKey(), $accessTokenKey->getConfigDependencyName());
    }
    return $this;
  }

}
