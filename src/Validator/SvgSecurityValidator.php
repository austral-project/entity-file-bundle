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

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SvgSecurityValidator extends ConstraintValidator
{
  public function validate(mixed $value, Constraint $constraint): void
  {
    if (!$constraint instanceof SvgSecurity)
    {
      throw new UnexpectedTypeException($constraint, SvgSecurity::class);
    }

    // No file or invalid file: let the File constraint handle it
    if (!$value instanceof UploadedFile || !$value->isValid())
    {
      return;
    }

    // Only check the content of SVG files
    $mimeType = $value->getMimeType();
    $extension = strtolower($value->getClientOriginalExtension());

    if ('image/svg+xml' === $mimeType || 'image/svg' === $mimeType || 'svg' === $extension)
    {
      $content = file_get_contents($value->getPathname());

      if (false === $content)
      {
        $this->context->buildViolation($constraint->unreadableMessage)->addViolation();

        return;
      }

      // 1. Fast regex detection of dangerous elements
      if ($this->hasMaliciousCode($content))
      {
        $this->context->buildViolation($constraint->message)->addViolation();

        return;
      }

      // 2. Second check with HtmlSanitizer
      $sanitizer = $this->createSvgSanitizer();
      $cleanContent = $sanitizer->sanitize($content);

      // If the sanitizer removes tags (e.g. <script>), the length or the content changes
      if (strlen(trim($cleanContent)) !== strlen(trim($content)))
      {
        $this->context->buildViolation($constraint->message)->addViolation();
      }
    }
  }

  private function hasMaliciousCode(string $content): bool
  {
    // Look for <script>, iframe, embed, object tags or JS event handlers (onload, onclick...)
    $patterns = [
      '/<script/i',
      '/javascript\s:/i',
      '/on\w+\s*=/i', // Matches onload=, onclick=, onerror=, etc.
      '/<iframe/i',
      '/<embed/i',
      '/<object/i',
    ];

    foreach ($patterns as $pattern)
    {
      if (preg_match($pattern, $content))
      {
        return true;
      }
    }

    return false;
  }

  private function createSvgSanitizer(): HtmlSanitizer
  {
    // Configuration stricte interdisant JS et scripts
    $config = new HtmlSanitizerConfig()
      ->allowElement('svg', ['width', 'height', 'viewbox', 'xmlns', 'fill', 'stroke', 'class'])
      ->allowElement('path', ['d', 'fill', 'stroke', 'stroke-width', 'class'])
      ->allowElement('g', ['fill', 'stroke', 'class'])
      ->allowElement('circle', ['cx', 'cy', 'r', 'fill', 'stroke'])
      ->allowElement('rect', ['x', 'y', 'width', 'height', 'rx', 'ry', 'fill', 'stroke'])
      ->allowElement('polygon', ['points', 'fill', 'stroke'])
      ->allowElement('line', ['x1', 'y1', 'x2', 'y2', 'stroke']);

    return new HtmlSanitizer($config);
  }
}
