<?php

declare(strict_types=1);

namespace Drupal\rabbit_hole\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Rabbit hole behavior plugin item attribute object.
 *
 * @see \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class RabbitHoleBehaviorPlugin extends Plugin {

  /**
   * Constructs a RabbitHoleBehaviorPlugin attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of the plugin.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?string $deriver = NULL,
  ) {}

}
