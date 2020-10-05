<?php

namespace Drupal\Tests\rh_media\Functional;

use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorSettingsFormTestBase;

/**
 * Test the functionality of the rabbit hole form additions to the media.
 *
 * @group rh_media
 */
class MediaBehaviorSettingsFormTest extends RabbitHoleBehaviorSettingsFormTestBase {

  use MediaTypeCreationTrait;

  /**
   * Test media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  protected $bundleEntityTypeName = 'media_type';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_media', 'media', 'media_test_source'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access media overview',
      'administer media',
      'administer media types',
      'view media',
      'rabbit hole administer media',
      'rabbit hole bypass media',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle() {
    // TODO: Remove 2nd parameter once https://www.drupal.org/node/3174874 is
    // resolved.
    $this->bundle = $this->createMediaType('test', [
      'id' => mb_strtolower($this->randomMachineName()),
    ]);
    return $this->bundle->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [
      'bundle' => $this->bundle->id(),
      'name' => $this->randomString(),
      'field_media_test' => $this->randomMachineName(),
    ];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }

    $media = Media::create($values);
    $media->save();

    return $media->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function loadEntity($id) {
    return Media::load($id);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateEntityUrl() {
    return Url::fromRoute('entity.media.add_form', ['media_type' => $this->bundle->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditEntityUrl($id) {
    return Url::fromRoute('entity.media.edit_form', ['media' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditBundleUrl($bundle) {
    return Url::fromRoute('entity.media_type.edit_form', ['media_type' => $bundle]);
  }

}
