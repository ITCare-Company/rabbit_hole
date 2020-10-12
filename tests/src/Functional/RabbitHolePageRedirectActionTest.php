<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the "Page redirect" action.
 *
 * @group rabbit_hole
 */
class RabbitHolePageRedirectActionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_node', 'user'];

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
   * Tests available redirect codes.
   */
  public function testRedirectCodes() {
    $this->assertRedirect(301);
    $this->assertRedirect(302);
    $this->assertRedirect(303);
    // TODO: Figure out what should happen on 304 code.
    // $this->assertUrlRedirect(304);.
    $this->assertRedirect(305);
    $this->assertRedirect(307);
  }

  /**
   * Test URL redirect with token value.
   */
  public function testTokenizedUrlRedirect() {
    $node = $this->createTestNode('page_redirect');
    $node->set('rh_redirect', '[site:url]');
    $node->set('rh_redirect_response', 301);
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $expected_url = Url::fromRoute('<front>');
    $this->assertSession()->addressEquals($expected_url);
  }

  /**
   * Test URL redirects (destination and redirect code).
   */
  protected function assertRedirect($redirect_code) {
    $target_entity = $this->createTestNode('display_page');
    $destination_path = $target_entity->toUrl()->toString();

    $entity = $this->createTestNode('page_redirect');
    $entity->set('rh_redirect', $destination_path);
    $entity->set('rh_redirect_response', $redirect_code);
    $entity->save();

    $this->drupalGet($entity->toUrl());
    $this->assertSession()->addressEquals($destination_path);
  }

  /**
   * Creates test node with provided action.
   *
   * @return \Drupal\node\NodeInterface
   *   Test node object.
   */
  protected function createTestNode($action = NULL) {
    $content_type = NodeType::load('test_bundle');

    if (empty($content_type)) {
      $content_type = $this->drupalCreateContentType([
        'type' => 'test_bundle',
      ]);
      if (isset($action)) {
        $this->behaviorSettingsManager->saveBehaviorSettings(['action' => $action, 'allow_override' => TRUE], 'node_type', $content_type->id());
      }
    }

    $values = [
      'type' => $content_type->id(),
    ];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->drupalCreateNode($values);
  }

}
