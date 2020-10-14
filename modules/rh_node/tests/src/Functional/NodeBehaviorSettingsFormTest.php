<?php

namespace Drupal\Tests\rh_node\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;

/**
 * Test the functionality of the rabbit hole form additions to the node form.
 *
 * @group rh_node
 */
class NodeBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * Test content type.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'node_type';

  const TEST_BUNDLE = 'rh_node_test_content_type';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_node', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // TODO: These tests should be expanded for users with different types of
    // permissions.
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access', 'administer content types',
      'rabbit hole administer node',
    ]);
  }

  /**
   * Test that Rabbit Hole settings are created with "Field UI" enabled.
   */
  public function testBundleCreationWithFieldUi() {
    \Drupal::service('module_installer')->install(['field_ui']);
    $this->testBundleCreation();
  }

  /**
   * Test that Rabbit Hole settings are created with "Field UI" enabled.
   */
  public function testBundleEditWithFieldUi() {
    \Drupal::service('module_installer')->install(['field_ui']);
    $this->testBundleFormFirstSave();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    $this->bundle = $this->drupalCreateContentType([
      'type' => self::TEST_BUNDLE,
    ]);
    return $this->bundle->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundleFormSubmit($action, $override) {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'name' => $this->randomString(),
      'type' => mb_strtolower($this->randomMachineName()),
      'rh_action' => $action,
      'rh_override' => $override,
    ];
    $this->drupalGet(Url::fromRoute('node.type_add'));
    $this->assertRabbitHoleSettings();
    $button_label = \Drupal::moduleHandler()->moduleExists('field_ui') ? 'Save and manage fields' : 'Save content type';
    $this->submitForm($edit, $button_label);
    return $edit['type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [
      'type' => self::TEST_BUNDLE,
      'title' => 'Test Behavior Settings Node',
    ];

    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->drupalCreateNode($values)->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function loadEntity($id) {
    return Node::load($id);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('node.add_page');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.node.edit_form', ['node' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.node_type.edit_form', ['node_type' => $bundle]);
  }

}
