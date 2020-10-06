<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for the Rabbit Hole form additions tests.
 */
abstract class RabbitHoleBehaviorSettingsFormTestBase extends BrowserTestBase {

  const DEFAULT_BUNDLE_ACTION = 'display_page';
  const DEFAULT_ACTION = 'bundle_default';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rabbit_hole'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The name of bundle entity type.
   *
   * @var string
   */
  protected $bundleEntityTypeName;

  /**
   * The behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface
   */
  protected $behaviorSettingsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->behaviorSettingsManager = $this->container->get('rabbit_hole.behavior_settings_manager');
  }

  /**
   * Test that bundle form contains Rabbit Hole settings and required fields.
   */
  public function testDefaultBundleForm() {
    $bundle_id = $this->createEntityBundle();
    $this->loadEntityBundleForm($bundle_id);

    $this->assertSession()->fieldValueEquals('rh_override', BehaviorSettings::OVERRIDE_ALLOW);
    $this->assertSession()->pageTextContains('Rabbit Hole settings');
    $this->assertSession()->fieldExists('rh_action');
    $this->assertSession()->fieldExists('edit-rh-action-access-denied');
    $this->assertSession()->fieldExists('edit-rh-action-display-page');
    $this->assertSession()->fieldExists('edit-rh-action-page-not-found');
    $this->assertSession()->fieldExists('edit-rh-action-page-redirect');
    $this->assertSession()->checkboxChecked($this->getOptionId(static::DEFAULT_BUNDLE_ACTION));
  }

  public function testBundleFormFirstSave() {
    $test_bundle_id = $this->createEntityBundle();
    $this->loadEntityBundleForm($test_bundle_id);

    $override = BehaviorSettings::OVERRIDE_DISALLOW;
    $action = 'access_denied';

    $this->submitForm([
      'rh_override' => $override,
      'rh_action' => $action,
    ], 'edit-submit');

    $saved_config = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig($this->bundleEntityTypeName, $test_bundle_id);
    $this->assertEquals($action, $saved_config->get('action'));
    $this->assertEquals($override, $saved_config->get('allow_override'));
  }

  /**
   * Test that bundle form with a configured bundle behaviour loads config.
   */
  public function testBundleFormExistingBehavior() {
    $action = 'page_not_found';
    $override = BehaviorSettings::OVERRIDE_DISALLOW;

    $test_bundle_id = $this->createEntityBundle();
    $this->behaviorSettingsManager->saveBehaviorSettings([
      'action' => $action,
      'allow_override' => $override,
      'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
    ], $this->bundleEntityTypeName, $test_bundle_id);

    $this->loadEntityBundleForm($test_bundle_id);

    $this->assertSession()->fieldValueEquals('rh_override', $override);
    $this->assertSession()->checkboxChecked($this->getOptionId($action));
  }

  /**
   * Test new changes to bundle with existing rabbit hole settings changes key.
   *
   * Test that saving changes to a bundle form which already has
   * configured rabbit hole behavior settings changes the existing key.
   */
  public function testBundleFormSave() {
    $test_bundle_id = $this->createEntityBundle();

    $this->behaviorSettingsManager->saveBehaviorSettings([
      'action' => 'access_denied',
      'allow_override' => BehaviorSettings::OVERRIDE_DISALLOW,
      'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
    ], $this->bundleEntityTypeName, $test_bundle_id);

    $this->loadEntityBundleForm($test_bundle_id);

    $action = 'page_not_found';
    $override = BehaviorSettings::OVERRIDE_ALLOW;

    $this->submitForm([
      'rh_override' => $override,
      'rh_action' => $action,
    ], 'edit-submit');

    $saved_config = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig($this->bundleEntityTypeName, $test_bundle_id);

    $this->assertEquals($action, $saved_config->get('action'));
    $this->assertEquals($override, $saved_config->get('allow_override'));
  }

  /**
   * Test that we can save settings for entity that did not previously have them.
   *
   * Test that an existing entity that previously didn't have settings will have
   * settings saved when the entity form is saved.
   */
  public function testExistingEntityNoConfigSave() {
    $this->createEntityBundle();
    $entity_id = $this->createEntity();
    $this->loadEditEntityForm($entity_id);
    $action = 'access_denied';

    $this->submitForm([
      'rh_action' => $action,
    ], 'Save');

    $entity = $this->loadEntity($entity_id);
    $this->assertEquals($action, $entity->get('rh_action')->value);
  }

  /**
   * Test that existing entity is edited on saving the entity form.
   */
  public function testExistingEntitySave() {
    $this->createEntityBundle();
    $entity_id = $this->createEntity('display_page');
    $this->loadEditEntityForm($entity_id);
    $action = 'access_denied';

    $this->submitForm([
      'rh_action' => $action,
    ], 'Save');

    $entity = $this->loadEntity($entity_id);
    $this->assertEquals($action, $entity->get('rh_action')->value);
  }

  /**
   * Test that when entity form is loaded it defaults the bundle configuration.
   */
  public function testDefaultEntitySettingsLoad() {
    $this->createEntityBundle();
    $this->loadCreateEntityForm();

    $this->assertSession()->fieldExists('rh_action');
    $this->assertSession()->fieldExists('edit-rh-action-access-denied');
    $this->assertSession()->fieldExists('edit-rh-action-display-page');
    $this->assertSession()->fieldExists('edit-rh-action-page-not-found');
    $this->assertSession()->fieldExists('edit-rh-action-page-redirect');
    $this->assertSession()->checkboxChecked($this->getOptionId(static::DEFAULT_ACTION));
  }

  /**
   * Test that entity form correctly loads previously saved behavior settings.
   */
  public function testExistingEntitySettingsLoad() {
    $this->createEntityBundle();

    $action = 'access_denied';
    $entity_id = $this->createEntity($action);
    $this->loadEditEntityForm($entity_id);

    $this->assertSession()->checkboxChecked($this->getOptionId($action));
  }

  /**
   * Loads the bundle configuration form.
   */
  protected function loadEntityBundleForm($bundle) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getEditBundleUrl($bundle));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Loads the "Create" entity form.
   */
  protected function loadCreateEntityForm() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getCreateEntityUrl());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Loads the "Edit" entity form.
   */
  protected function loadEditEntityForm($entity_id) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getEditEntityUrl($entity_id));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Formats selector of the action input.
   *
   * @param $action
   *   Rabbit hole action.
   *
   * @return string
   *   Selector for the given behavior option.
   */
  protected function getOptionId($action) {
    return 'edit-rh-action-' . str_replace('_', '-', $action);
  }

  /**
   * Returns URL of the "Edit" entity bundle page.
   *
   * @param $bundle
   *   Entity bundle id.
   *
   * @return \Drupal\Core\Url
   *   URL object.
   */
  abstract protected function getEditBundleUrl($bundle);

  /**
   * Returns URL of the "Create" entity page.
   *
   * @return \Drupal\Core\Url
   *   URL object.
   */
  abstract protected function getCreateEntityUrl();

  /**
   * Creates new entity bundle.
   *
   * @return string
   *   ID of the created bundle.
   */
  abstract protected function createEntityBundle();

  /**
   * Creates new entity.
   *
   * @param $action
   *   Rabbit Hole action.
   *
   * @return int
   *   ID of the created entity.
   */
  abstract protected function createEntity($action = NULL);

  /**
   * Loads test entity.
   *
   * @param mixed
   *   ID of loaded entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Loaded entity.
   */
  abstract protected function loadEntity($id);

}
