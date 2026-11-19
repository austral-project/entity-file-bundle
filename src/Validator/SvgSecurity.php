<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that an uploaded SVG file contains no script nor JavaScript event handler.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SvgSecurity extends Constraint
{
  public string $unreadableMessage = 'The SVG file cannot be read.';

  public string $message = 'The SVG file contains potentially dangerous code (scripts or JavaScript events).';
}
