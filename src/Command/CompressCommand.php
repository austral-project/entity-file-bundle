<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Command;

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\File\Compression\Compression;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Command\Base\Command;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Austral Compress Command.
 *
 * @template T of object
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class CompressCommand extends Command
{
  /**
   * @var string
   */
  protected string $titleCommande = 'Compress files uploaded';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void
  {
    $this
      ->setDefinition([
        new InputOption('--force', '-f', InputOption::VALUE_NONE, 'Force compress files'),
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(
        <<<'EOF'
          The <info>%command.name%</info> command to compress picture file upload

            <info>php %command.full_name%</info>
            <info>php %command.full_name% --force</info>
            <info>php %command.full_name% -f</info>
          EOF
      )
    ;
  }

  /**
   * CompressCommand constructor
   *
   * @param Compression     $compression
   * @param Mapping<object> $mapping
   * @param string|null     $name
   * @param callable|null   $code
   */
  public function __construct(
    protected Compression $compression,
    protected Mapping $mapping,
    ?string $name = null,
    ?callable $code = null
  ) {
    parent::__construct($name, $code);
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @throws NonUniqueResultException
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    foreach ($this->mapping->getEntitiesMapping() as $entityMapping)
    {
      if ($fieldFileMappings = $entityMapping->getFieldsMappingByClass(FieldFileMapping::class))
      {
        /** @var FieldFileMapping<object> $fieldFileMapping */
        foreach ($fieldFileMappings as $fieldFileMapping)
        {
          if (file_exists($fieldFileMapping->path->upload))
          {
            $this->viewMessage("Start Scan to compress : {$fieldFileMapping->path->upload}", 'title');
            $this->scanDir($fieldFileMapping->path->upload);
          }
          if (file_exists($fieldFileMapping->path->thumbnail))
          {
            $this->viewMessage("Start Scan to compress : {$fieldFileMapping->path->thumbnail}", 'title');
            $this->scanDir($fieldFileMapping->path->thumbnail);
          }
        }
      }
    }
  }

  /**
   * @param string $path
   *
   * @return $this
   */
  protected function scanDir(string $path): CompressCommand
  {
    foreach (scandir($path) ?: [] as $value)
    {
      if ('.' !== $value && '..' !== $value)
      {
        $subPath = AustralTools::join($path, $value);
        if (is_dir($subPath))
        {
          $this->scanDir($subPath);
        }
        elseif (is_file($subPath))
        {
          if (AustralTools::isImage($subPath))
          {
            $this->viewMessage("Compress : {$subPath}", 'note');
            $this->compression->compress($subPath, ['webp']);
          }
        }
      }
    }

    return $this;
  }
}
