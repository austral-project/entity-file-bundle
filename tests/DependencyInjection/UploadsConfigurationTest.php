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

use Austral\EntityFileBundle\DependencyInjection\AustralEntityFileExtension;
use Austral\EntityFileBundle\DependencyInjection\Compiler\EntityFileCompiler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The configuration of the application is merged with the default values, then with the values of the bundles
 * (parameters austral_entity_file.uploads.<bundle>, for example the avatar of security-bundle).
 */
final class UploadsConfigurationTest extends TestCase
{
  public function testTheMimeTypesOfTheApplicationReplaceTheDefaultOnes(): void
  {
    $uploads = $this->uploads(['uploads' => ['default_image' => ['mimeTypes' => ['image/webp', 'image/avif']]]]);

    self::assertSame(['image/webp', 'image/avif'], $uploads['default_image']['mimeTypes']);
  }

  public function testTheDefaultValuesAreKeptForWhatTheApplicationDoesNotSet(): void
  {
    $uploads = $this->uploads(['uploads' => ['default_image' => ['size' => ['max' => '5M']]]]);

    self::assertSame('5M', $uploads['default_image']['size']['max']);
    self::assertSame(['image/jpg', 'image/jpeg', 'image/png', 'image/gif', 'image/svg', 'image/svg+xml'], $uploads['default_image']['mimeTypes']);
    self::assertSame('file.errors.maxSize', $uploads['default_image']['errors']['maxSize']);
    self::assertSame('2M', $uploads['default_file']['size']['max']);
  }

  public function testTheApplicationOverridesTheValuesOfABundle(): void
  {
    $bundle = ['avatar' => ['size' => ['max' => '10M'], 'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml']]];

    $uploads = $this->uploads(['uploads' => ['avatar' => ['mimeTypes' => ['image/webp']]]], ['austral_entity_file.uploads.security_bundle' => $bundle]);
    self::assertSame(['image/webp'], $uploads['avatar']['mimeTypes']);
    self::assertSame('10M', $uploads['avatar']['size']['max']);

    $uploads = $this->uploads(['uploads' => ['avatar' => ['size' => ['max' => '1M']]]], ['austral_entity_file.uploads.security_bundle' => $bundle]);
    self::assertSame('1M', $uploads['avatar']['size']['max']);
    self::assertSame($bundle['avatar']['mimeTypes'], $uploads['avatar']['mimeTypes'], 'Not replaced by the empty list of the processor.');
  }

  public function testTwoBundlesWithTheSameNameDoNotTurnTheValuesIntoLists(): void
  {
    $uploads = $this->uploads([], [
      'austral_entity_file.uploads.a_bundle' => ['avatar' => ['size' => ['max' => '10M'], 'mimeTypes' => ['image/png']]],
      'austral_entity_file.uploads.b_bundle' => ['avatar' => ['size' => ['max' => '5M']]],
    ]);

    self::assertSame('5M', $uploads['avatar']['size']['max'], 'It was [10M, 5M] with array_merge_recursive().');
    self::assertSame(['image/png'], $uploads['avatar']['mimeTypes']);
  }

  /**
   * @param array<string, mixed> $config
   * @param array<string, mixed> $bundleParameters
   *
   * @return array<string, array<string, mixed>>
   */
  private function uploads(array $config, array $bundleParameters = []): array
  {
    $container = new ContainerBuilder();
    $container->setParameter('kernel.bundles', []);
    new AustralEntityFileExtension()->load([$config], $container);
    foreach ($bundleParameters as $name => $value)
    {
      $container->setParameter($name, $value);
    }
    new EntityFileCompiler()->process($container);

    /** @var array<string, array<string, mixed>> $uploads */
    $uploads = $container->getParameter('austral_entity_file.uploads');

    return $uploads;
  }
}
