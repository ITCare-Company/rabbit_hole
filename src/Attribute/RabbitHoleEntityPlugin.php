<?php

declare(strict_types=1);

namespace Drupal\rabbit_hole\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Rabbit hole entity plugin item attribute object.
 *
 * @see \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class RabbitHoleEntityPlugin extends Plugin {

  /**
   * Constructs a RabbitHoleEntityPlugin attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of the plugin.
   * @param string $entityType
   *   The string id of the affected entity.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly string $entityType,
    public readonly ?string $deriver = NULL,
  ) {}

}
