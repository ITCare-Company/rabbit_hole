<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the "Page redirect" action.
 *
 * @requires module token
 * @group rabbit_hole
 */
class RabbitHolePageRedirectActionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_node', 'user', 'media', 'token'];

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
    $this->behaviorSettingsManager->saveBehaviorSettings(['action' => 'display_page', 'allow_override' => TRUE], 'node_type', 'article');
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
    // Test redirect with default system token.
    $node = $this->createTestNode('page_redirect');
    $node->set('rh_redirect', '[site:url]');
    $node->set('rh_redirect_response', 301);
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $expected_url = Url::fromRoute('<front>');
    $this->assertSession()->addressEquals($expected_url);

    // Test more complex scenarios with nested entities.
    // Attach media field to Article content type.
    $storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_related_media',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $storage->save();
    FieldConfig::create([
      'field_storage' => $storage,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Related media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'document' => 'document',
          ],
        ],
      ],
    ])->save();

    $file = $this->createTestFile('first');

    $media = Media::create([
      'bundle' => 'document',
      'name' => $this->randomString(),
      'field_media_document' => $file->id(),
    ]);
    $media->save();

    $node = Node::create([
      'title' => $this->randomString(),
      'type' => 'article',
      'field_related_media' => [
        'target_id' => $media->id(),
      ],
      'rh_action' => 'page_redirect',
      'rh_redirect' => '[node:field_related_media:entity:field_media_document:entity:url]',
      'rh_redirect_response' => 301,
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());
    $expected_url = file_create_url($file->getFileUri());
    $this->assertSession()->addressEquals($expected_url);
    $this->assertSession()->responseContains('first');

    // Change the file in media entity and verify that destination changed.
    $file2 = $this->createTestFile('second file');
    $media->set('field_media_document', $file2->id());
    $media->save();

    $this->drupalGet($node->toUrl());
    $expected_url = file_create_url($file2->getFileUri());
    $this->assertSession()->addressEquals($expected_url);
    $this->assertSession()->responseContains('second');
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
    $values = [
      'type' => 'article',
    ];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->drupalCreateNode($values);
  }

  /**
   * Creates test file.
   *
   * @return \Drupal\file\FileInterface
   *   Test file object.
   */
  protected function createTestFile($filename) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uid' => 1,
      'filename' => "{$filename}.txt",
      'uri' => "public://{$filename}.txt",
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), $filename);
    $file->save();

    return $file;
  }

}
