<?php
/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Entity\Interfaces;

/**
 * Kept for backward compatibility with 3.x projects.
 * @deprecated since 4.0, use \Austral\Contracts\File\FileInterface instead.
 * @template T of object
 *
 * @extends \Austral\Contracts\File\FileInterface<T>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface FileInterface extends \Austral\Contracts\File\FileInterface
{
}
