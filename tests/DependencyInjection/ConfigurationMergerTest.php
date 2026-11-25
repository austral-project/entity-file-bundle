<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\EntityFileBundle\Tests\DependencyInjection;

use Austral\EntityFileBundle\DependencyInjection\ConfigurationMerger;
use PHPUnit\Framework\TestCase;

final class ConfigurationMergerTest extends TestCase
{
  public function testAListReplacesTheListItOverrides(): void
  {
    $merged = ConfigurationMerger::merge(
      ['mimeTypes' => ['image/jpg', 'image/jpeg', 'image/png', 'image/gif']],
      ['mimeTypes' => ['image/webp']],
    );

    self::assertSame(['mimeTypes' => ['image/webp']], $merged, 'Not the first item only, as with array_replace_recursive().');
  }

  public function testAnEmptyListIsNotSet(): void
  {
    $merged = ConfigurationMerger::merge(['mimeTypes' => ['image/png'], 'croppers' => ['desktop']], ['mimeTypes' => [], 'croppers' => []]);
    self::assertSame(['mimeTypes' => ['image/png'], 'croppers' => ['desktop']], $merged);

    self::assertSame(['croppers' => []], ConfigurationMerger::merge([], ['croppers' => []]));
  }

  public function testTheOtherKeysAreMergedRecursively(): void
  {
    $merged = ConfigurationMerger::merge(
      ['default_image' => ['size' => ['max' => '2M'], 'errors' => ['maxSize' => 'file.errors.maxSize']]],
      ['default_image' => ['size' => ['max' => '5M', 'min' => '1k']], 'avatar' => ['size' => ['max' => '1M']]],
    );

    self::assertSame([
      'default_image' => ['size' => ['max' => '5M', 'min' => '1k'], 'errors' => ['maxSize' => 'file.errors.maxSize']],
      'avatar' => ['size' => ['max' => '1M']],
    ], $merged);
  }

  public function testAScalarReplacesAnArrayAndTheReverse(): void
  {
    self::assertSame(['ratio' => '16/9'], ConfigurationMerger::merge(['ratio' => ['a' => 1]], ['ratio' => '16/9']));
    self::assertSame(['ratio' => ['a' => 1]], ConfigurationMerger::merge(['ratio' => ''], ['ratio' => ['a' => 1]]));
  }
}
