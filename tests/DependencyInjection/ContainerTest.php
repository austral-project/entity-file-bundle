<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Tests\DependencyInjection;

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Bridge\Admin\Listener\ConfigurationCheckerListener;
use Austral\EntityFileBundle\Bridge\Form\Listener\UploadFormListener;
use Austral\EntityFileBundle\Command\CompressCommand;
use Austral\EntityFileBundle\Controller\FileController;
use Austral\EntityFileBundle\DependencyInjection\AustralEntityFileExtension;
use Austral\EntityFileBundle\Listener\DoctrineListener;
use Austral\EntityFileBundle\TwigExtension\MediaTwig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * The services are defined explicitly: a missing or misnamed argument only shows up when the container
 * is compiled or the service is created. This test loads the extension, with and without the bridges,
 * compiles the container and creates every service of the bundle.
 */
final class ContainerTest extends TestCase
{
  /**
   * Bundles whose bridges are loaded when they are registered.
   */
  private const array BRIDGED_BUNDLES = [
    'AustralFormBundle' => 'Austral\\FormBundle\\AustralFormBundle',
    'AustralAdminBundle' => 'Austral\\AdminBundle\\AustralAdminBundle',
  ];

  /**
   * Public aliases: the supported way to get a service from the container.
   * Listeners are called by the dispatchers, never fetched: their aliases stay private.
   */
  private const array PUBLIC_ALIASES = [
    'austral.entity_file.compression',
    'austral.entity_file.compression.config',
    'austral.entity_file.cropper',
    'austral.entity_file.cropper.config',
    'austral.entity_file.downloader',
    'austral.entity_file.image',
    'austral.entity_file.image.render',
    'austral.entity_file.image_size.config',
    'austral.entity_file.link.generator',
    'austral.entity_file.uploader',
    'austral.entity_file.uploads.config',
  ];

  /**
   * Names available in Twig templates, as in 3.1.
   */
  private const array TWIG_FILTERS = [
    'austral_entity_file_download_url', 'austral_entity_file_exist', 'austral_entity_file_image_ratio',
    'austral_entity_file_image_size', 'austral_entity_file_image_url', 'austral_entity_file_is_image',
    'austral_entity_file_mime_type', 'austral_entity_file_parameters', 'austral_entity_file_path',
    'austral_entity_file_size',
  ];

  private const array TWIG_FUNCTIONS = [
    'austral_entity_file.exist', 'austral_entity_file.image_ratio', 'austral_entity_file.image_size',
    'austral_entity_file.is_image', 'austral_entity_file.mime_type', 'austral_entity_file.parameters',
    'austral_entity_file.path', 'austral_entity_file.size', 'download', 'image',
  ];

  /**
   * @return iterable<string, array{string, class-string, bool}>
   */
  public static function serviceIds(): iterable
  {
    foreach ([false, true] as $withBridges)
    {
      foreach (self::serviceClasses($withBridges) as $id => $class)
      {
        yield ($withBridges ? 'with bridges: ' : '').$id => [$id, $class, $withBridges];
      }
    }
  }

  /**
   * @param class-string $class
   */
  #[DataProvider('serviceIds')]
  public function testEveryServiceCanBeCreated(string $id, string $class, bool $withBridges): void
  {
    $container = $this->compiledContainer(publicServices: true, withBridges: $withBridges);

    self::assertInstanceOf($class, $container->get($id));
  }

  public function testTheBridgesAreLoadedOnlyWhenTheirBundleIsRegistered(): void
  {
    $withoutBridges = self::loadExtension(withBridges: false);
    $withBridges = self::loadExtension(withBridges: true);

    self::assertFalse($withoutBridges->hasDefinition(UploadFormListener::class));
    self::assertFalse($withoutBridges->hasDefinition(ConfigurationCheckerListener::class));
    self::assertTrue($withBridges->hasDefinition(UploadFormListener::class));
    self::assertTrue($withBridges->hasDefinition(ConfigurationCheckerListener::class));
  }

  public function testOnlyTheAustralAliasesArePublic(): void
  {
    $container = self::loadExtension(withBridges: true);

    foreach ($container->getDefinitions() as $id => $definition)
    {
      if (self::isServiceOfTheBundle($id))
      {
        self::assertFalse($definition->isPublic(), sprintf('The service "%s" must be private.', $id));
      }
    }
    $publicAliases = [];
    foreach ($container->getAliases() as $id => $alias)
    {
      if ($alias->isPublic())
      {
        $publicAliases[] = $id;
      }
    }
    sort($publicAliases);
    self::assertSame(self::PUBLIC_ALIASES, $publicAliases);
  }

  public function testPublicAliasesCanBeFetched(): void
  {
    $classes = self::aliasClasses();
    $container = $this->compiledContainer(publicServices: false, withBridges: false);

    foreach (self::PUBLIC_ALIASES as $alias)
    {
      self::assertTrue($container->has($alias), sprintf('The alias "%s" must be public.', $alias));
      self::assertInstanceOf($classes[$alias], $container->get($alias));
    }
  }

  public function testTheControllerIsRegisteredForItsRoutes(): void
  {
    $container = $this->compiledContainer(publicServices: true, withBridges: false);

    self::assertArrayHasKey(FileController::class, $container->findTaggedServiceIds('controller.service_arguments'));
  }

  public function testTheCommandIsRegisteredWithItsNameAndTheEventDispatcher(): void
  {
    $container = $this->compiledContainer(publicServices: true, withBridges: false);

    $tags = $container->findTaggedServiceIds('console.command');
    self::assertArrayHasKey('austral.file.compress.command', $tags);
    self::assertSame('austral:file:compress', $tags['austral.file.compress.command'][0]['command'] ?? null);

    $command = $container->get('austral.file.compress.command');
    self::assertInstanceOf(CompressCommand::class, $command);
    $dispatcher = new \ReflectionProperty($command, 'eventDispatcher')->getValue($command);
    self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
  }

  /**
   * A Doctrine event listener (not a subscriber), on every connection.
   */
  public function testTheDoctrineListenerListensToPreRemove(): void
  {
    $tags = self::loadExtension(withBridges: false)->getDefinition(DoctrineListener::class)->getTag('doctrine.event_listener');

    self::assertSame([['event' => 'preRemove']], $tags);
    self::assertSame([], self::loadExtension(withBridges: false)->findTaggedServiceIds('doctrine.event_subscriber'));
  }

  public function testTheTwigExtensionIsTaggedAndDeclaresTheTemplateNames(): void
  {
    $container = $this->compiledContainer(publicServices: true, withBridges: false);

    self::assertArrayHasKey(MediaTwig::class, $container->findTaggedServiceIds('twig.extension'));

    $twig = $container->get(MediaTwig::class);
    self::assertInstanceOf(MediaTwig::class, $twig);
    self::assertSame(self::TWIG_FILTERS, self::sortedNames($twig->getFilters()));
    self::assertSame(self::TWIG_FUNCTIONS, self::sortedNames($twig->getFunctions()));
    self::assertSame([], $twig->getTests());
  }

  /**
   * @param array<int|string, \Twig\TwigFilter|\Twig\TwigFunction|\Twig\TwigTest> $callables
   *
   * @return list<string>
   */
  private static function sortedNames(array $callables): array
  {
    $names = [];
    foreach ($callables as $callable)
    {
      $names[] = $callable->getName();
    }
    sort($names);

    return $names;
  }

  private static function isServiceOfTheBundle(string $id): bool
  {
    return str_starts_with($id, 'Austral\\EntityFileBundle\\') || str_starts_with($id, 'austral.entity_file') || str_starts_with($id, 'austral.file');
  }

  /**
   * @return array<string, class-string> the class of each service of the bundle, by service id
   */
  private static function serviceClasses(bool $withBridges): array
  {
    $classes = [];
    foreach (self::loadExtension($withBridges)->getDefinitions() as $id => $definition)
    {
      if (self::isServiceOfTheBundle($id))
      {
        $class = $definition->getClass() ?? $id;
        if (!class_exists($class))
        {
          self::fail(sprintf('The class "%s" of the service "%s" does not exist.', $class, $id));
        }
        $classes[$id] = $class;
      }
    }

    return $classes;
  }

  /**
   * @return array<string, class-string> the class of the service behind each public alias
   */
  private static function aliasClasses(): array
  {
    $container = self::loadExtension(withBridges: false);
    $services = self::serviceClasses(withBridges: false);
    $classes = [];
    foreach (self::PUBLIC_ALIASES as $alias)
    {
      self::assertTrue($container->hasAlias($alias), sprintf('The alias "%s" is not defined.', $alias));
      $target = (string) $container->getAlias($alias);
      self::assertArrayHasKey($target, $services, sprintf('The alias "%s" points to "%s", which is not a service of the bundle.', $alias, $target));
      $classes[$alias] = $services[$target];
    }

    return $classes;
  }

  /**
   * The services that the bundle needs from the application are added as minimal definitions:
   * the entity mapping and the router are set after the compilation.
   */
  private static function loadExtension(bool $withBridges): ContainerBuilder
  {
    $container = new ContainerBuilder();
    $container->setParameter('kernel.project_dir', sys_get_temp_dir());
    $container->setParameter('kernel.bundles', $withBridges ? self::BRIDGED_BUNDLES : []);
    $container->setParameter('kernel.default_locale', 'en');
    $container->setDefinition('event_dispatcher', new Definition(EventDispatcher::class));
    $container->setDefinition('parameter_bag', new Definition(ContainerBag::class, [new Reference('service_container')]));
    $container->setDefinition('austral.entity.mapping', new Definition(Mapping::class)->setSynthetic(true)->setPublic(true));
    $container->setDefinition('router', new Definition(RouterInterface::class)->setSynthetic(true)->setPublic(true));
    new AustralEntityFileExtension()->load([], $container);

    return $container;
  }

  private function compiledContainer(bool $publicServices, bool $withBridges): ContainerBuilder
  {
    $container = self::loadExtension($withBridges);
    if ($publicServices)
    {
      foreach ($container->getDefinitions() as $id => $definition)
      {
        if (self::isServiceOfTheBundle($id))
        {
          $definition->setPublic(true);
        }
      }
    }
    $container->compile();
    // Mapping is only stored by the services of the bundle: an instance without its own dependencies is enough.
    $container->set('austral.entity.mapping', new \ReflectionClass(Mapping::class)->newInstanceWithoutConstructor());
    $container->set('router', $this->createStub(RouterInterface::class));

    return $container;
  }
}
