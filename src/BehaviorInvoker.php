<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default implementation of Rabbit Hole behaviors invoker.
 */
class BehaviorInvoker implements BehaviorInvokerInterface {

  /**
   * Behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManager
   */
  protected $rhBehaviorSettingsManager;

  /**
   * Behavior plugin manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager
   */
  protected $rhBehaviorPluginManager;

  /**
   * Entity plugin manager.
   *
   * @var \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
   */
  protected $rhEntityPluginManager;

  /**
   * Entity extender service.
   *
   * @var \Drupal\rabbit_hole\EntityExtender
   */
  protected $rhEntityExtender;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * BehaviorInvoker constructor.
   *
   * @param \Drupal\rabbit_hole\BehaviorSettingsManager $rabbit_hole_behavior_settings_manager
   *   Behavior settings manager.
   * @param \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager $plugin_manager_rabbit_hole_behavior_plugin
   *   Behavior plugin manager.
   * @param \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager $plugin_manager_rabbit_hole_entity_plugin
   *   Entity plugin manager.
   * @param \Drupal\rabbit_hole\EntityExtender $entity_extender
   *   Entity extender service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    BehaviorSettingsManager $rabbit_hole_behavior_settings_manager,
    RabbitHoleBehaviorPluginManager $plugin_manager_rabbit_hole_behavior_plugin,
    RabbitHoleEntityPluginManager $plugin_manager_rabbit_hole_entity_plugin,
    EntityExtender $entity_extender,
    AccountProxyInterface $current_user
  ) {
    $this->rhBehaviorSettingsManager = $rabbit_hole_behavior_settings_manager;
    $this->rhBehaviorPluginManager = $plugin_manager_rabbit_hole_behavior_plugin;
    $this->rhEntityPluginManager = $plugin_manager_rabbit_hole_entity_plugin;
    $this->rhEntityExtender = $entity_extender;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function processEntity(ContentEntityInterface $entity, Response $current_response = NULL) {
    $permission = 'rabbit hole bypass ' . $entity->getEntityTypeId();
    if ($this->currentUser->hasPermission($permission)) {
      return NULL;
    }

    $values = $this->getRabbitHoleValuesForEntity($entity);

    if (empty($values['rh_action'])) {
      // No action set; do nothing.
      return NULL;
    }

    $plugin = $this->rhBehaviorPluginManager
      ->createInstance($values['rh_action'], $values);

    $resp_use = $plugin->usesResponse();
    $response_required = $resp_use == RabbitHoleBehaviorPluginInterface::USES_RESPONSE_ALWAYS;
    $response_allowed = $resp_use == $response_required
      || $resp_use == RabbitHoleBehaviorPluginInterface::USES_RESPONSE_SOMETIMES;

    // Most plugins never make use of the response and only run when it's not
    // provided (i.e. on a request event).
    if ((!$response_allowed && $current_response == NULL)
      // Some plugins may or may not make use of the response so they'll run in
      // both cases and work out the logic of when to return NULL internally.
      || $response_allowed
      // Though none exist at the time of this writing, some plugins could
      // require a response so that case is handled.
      || $response_required && $current_response != NULL) {

      $response = $plugin->performAction($entity, $current_response);

      // Execute a fallback action until we have correct response object.
      // It allows us to have a chain of fallback actions until we execute the
      // final one.
      while (!$response instanceof Response && is_string($response) && $this->rhBehaviorPluginManager->getDefinition($response, FALSE) !== NULL) {
        $fallback_plugin = $this->rhBehaviorPluginManager->createInstance($response, []);
        $response = $fallback_plugin->performAction($entity, $current_response);
      }
      return $response;
    }
    // All other cases return NULL, meaning the response is unchanged.
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleEntityTypeKeys() {
    $entity_type_keys = [];
    foreach ($this->rhEntityPluginManager->getDefinitions() as $def) {
      $entity_type_keys[] = $def['entityType'];
    }
    return $entity_type_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getRabbitHoleValuesForEntity(ContentEntityInterface $entity) {
    $field_keys = array_keys($this->rhEntityExtender->getGeneralExtraFields());
    $values = [];

    $config = $this->rhBehaviorSettingsManager->loadBehaviorSettingsAsConfig(
      $entity->getEntityType()->getBundleEntityType()
        ?: $entity->getEntityType()->id(),
      $entity->getEntityType()->getBundleEntityType()
        ? $entity->bundle()
        : NULL
    );

    // We trigger the default bundle action under the following circumstances:
    $trigger_default_bundle_action =
    // Bundle settings do not allow override.
      !$config->get('allow_override')
    // Entity does not have rh_action field.
      || !$entity->hasField('rh_action')
    // Entity has rh_action field but it's null (hasn't been set).
      || $entity->get('rh_action')->value == NULL
    // Entity has been explicitly set to use the default bundle action.
      || $entity->get('rh_action')->value == 'bundle_default';

    if ($trigger_default_bundle_action) {
      foreach ($field_keys as $field_key) {
        $config_field_key = substr($field_key, 3);
        $values[$field_key] = $config->get($config_field_key);
      }
    }
    else {
      foreach ($field_keys as $field_key) {
        if ($entity->hasField($field_key)) {
          $values[$field_key] = $entity->{$field_key}->value;
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getRabbitHoleValuesForEntityType($entity_type_id, $bundle_id = NULL) {
    $field_keys = array_keys($this->rhEntityExtender->getGeneralExtraFields());
    $values = [];

    $config = $this->rhBehaviorSettingsManager->loadBehaviorSettingsAsConfig($entity_type_id, $bundle_id);
    foreach ($field_keys as $field_key) {
      $config_field_key = substr($field_key, 3);
      $values[$field_key] = $config->get($config_field_key);
    }
    return $values;
  }

}
