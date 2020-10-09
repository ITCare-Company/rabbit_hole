<?php

namespace Drupal\Tests\rh_user\Functional;

use Drupal\Core\Url;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;
use Drupal\user\Entity\User;

/**
 * Test the functionality of the rabbit hole form additions to the user entity.
 *
 * @group rh_user
 */
class UserBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'user';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_user', 'user'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface
   */
  protected $behaviorSettingsManager;

  const DEFAULT_BUNDLE_ACTION = 'display_page';
  const DEFAULT_ACTION = 'bundle_default';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->behaviorSettingsManager = $this->container->get('rabbit_hole.behavior_settings_manager');
    $this->adminUser = $this->drupalCreateUser([
      'administer account settings',
      'administer users',
      'rabbit hole administer user',
    ]);
  }

  /**
   * Nothing to test here, user entity/bundle already exists.
   */
  public function testBundleCreation() {}

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    // There is nothing to create here. The user entity/bundle already exists.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundleFormSubmit($action, $override) {
    return $this->createEntityBundle();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->drupalCreateUser([], $this->randomMachineName(), FALSE, $values)->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function loadEntity($id) {
    return User::load($id);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('user.admin_create');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.user.edit_form', ['user' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.user.admin_form');
  }

}
