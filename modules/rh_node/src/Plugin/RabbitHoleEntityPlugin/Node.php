<?php

namespace Drupal\rh_node\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for nodes.
 *
 * @RabbitHoleEntityPlugin(
 *  id = "rh_node",
 *  label = @Translation("Node"),
 *  entityType = "node"
 * )
 */
class Node extends RabbitHoleEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBundleFormSubmitHandlerAttachLocations() {
    if (\Drupal::moduleHandler()->moduleExists('field_ui')) {
      return [['actions', 'save_continue', '#submit']];
    }
    return parent::getBundleFormSubmitHandlerAttachLocations();
  }

}
