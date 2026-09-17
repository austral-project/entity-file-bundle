<?php
/*
 * This file is part of the Austral Form Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SvgSecurityValidator extends ConstraintValidator
{
  public function validate(mixed $value, Constraint $constraint): void
  {
    if (!$constraint instanceof SvgSecurity) {
      throw new UnexpectedTypeException($constraint, SvgSecurity::class);
    }

    // Si aucun fichier ou fichier invalide, on laisse la contrainte File gérée
    if (!$value instanceof UploadedFile || !$value->isValid()) {
      return;
    }

    // On ne vérifie le contenu que s'il s'agit d'un SVG
    $mimeType = $value->getMimeType();
    $extension = strtolower($value->getClientOriginalExtension());

    if ($mimeType === 'image/svg+xml' || $mimeType === 'image/svg' || $extension === 'svg') {
      $content = file_get_contents($value->getPathname());

      if (false === $content) {
        $this->context->buildViolation('Impossible de lire le fichier SVG.')->addViolation();
        return;
      }

      // 1. Détection rapide via Regex des éléments critiques
      if ($this->hasMaliciousCode($content)) {
        $this->context->buildViolation($constraint->message)->addViolation();
        return;
      }

      // 2. Double vérification avec HtmlSanitizer
      $sanitizer = $this->createSvgSanitizer();
      $cleanContent = $sanitizer->sanitize($content);

      // Si le sanitizer supprime des balises (ex: <script>), la longueur ou le contenu change
      if (strlen(trim($cleanContent)) !== strlen(trim($content))) {
        $this->context->buildViolation($constraint->message)->addViolation();
      }
    }
  }

  private function hasMaliciousCode(string $content): bool
  {
    // Recherche de balises <script>, iframe, embed, object ou d'évènements JS (onload, onclick...)
    $patterns = [
      '/<script/i',
      '/javascript\s:/i',
      '/on\w+\s*=/i', // Détecte onload=, onclick=, onerror=, etc.
      '/<iframe/i',
      '/<embed/i',
      '/<object/i',
    ];

    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $content)) {
        return true;
      }
    }

    return false;
  }

  private function createSvgSanitizer(): HtmlSanitizer
  {
    // Configuration stricte interdisant JS et scripts
    $config = (new HtmlSanitizerConfig())
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