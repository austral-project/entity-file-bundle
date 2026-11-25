<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\DependencyInjection;

/**
 * Merges a configuration into another one (the default values, the values of the bundles).
 *
 * Unlike array_replace_recursive(), a list (mimeTypes, croppers) replaces the list it overrides instead of
 * replacing its first items. An empty list is a value that is not set: the configuration processor adds one
 * for each list that the configuration does not give.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @internal
 */
final class ConfigurationMerger
{
  /**
   * @param array<array-key, mixed> $base
   * @param array<array-key, mixed> $override
   *
   * @return array<array-key, mixed>
   */
  public static function merge(array $base, array $override): array
  {
    foreach ($override as $key => $value)
    {
      if (\is_array($value) && array_is_list($value))
      {
        if ([] !== $value || !\array_key_exists($key, $base))
        {
          $base[$key] = $value;
        }
      }
      elseif (\is_array($value) && isset($base[$key]) && \is_array($base[$key]))
      {
        $base[$key] = self::merge($base[$key], $value);
      }
      else
      {
        $base[$key] = $value;
      }
    }

    return $base;
  }

  private function __construct()
  {
  }
}
