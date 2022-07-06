<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\EntityFileBundle\Configuration;

use Austral\ToolsBundle\Configuration\BaseConfiguration;

/**
 * Austral File Entity Cropper Parameters.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
Class CropperConfiguration extends BaseConfiguration
{
  /**
   * @var int|null
   */
  protected ?int $niveauMax = null;

  /**
   * @var string|null
   */
  protected ?string $prefix = "entity_file_cropper";


  /**
   * Initialize the service
   *
   * @param array Config
   */
  public function __construct(array $config)
  {
    parent::__construct($config);
  }

}