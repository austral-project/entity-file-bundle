<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Tests\File\Upload;

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Attribute\Path;
use Austral\EntityFileBundle\Attribute\UploadParameters;
use Austral\EntityFileBundle\Configuration\CropperConfiguration;
use Austral\EntityFileBundle\File\Cropper\Cropper;
use Austral\EntityFileBundle\File\Image\Image;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;
use Austral\EntityFileBundle\Tests\Fixtures\FileEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

final class FileUploaderTest extends TestCase
{
  public function testRequiredFileMissing(): void
  {
    $form = $this->form(withImage: true);

    self::assertFalse($this->uploader()->validateRequiredFiles($form, new FileEntity()));
    self::assertSame('required', $form->get('image')->getErrors()[0]->getMessage());
  }

  /**
   * A required file whose field is not in the form (not visible for the user) is not checked by this form.
   */
  public function testRequiredFileNotInTheForm(): void
  {
    self::assertTrue($this->uploader()->validateRequiredFiles($this->form(withImage: false), new FileEntity()));
  }

  /**
   * @return FileUploader<object>
   */
  private function uploader(): FileUploader
  {
    $fieldMapping = new FieldFileMapping(new UploadParameters('image', true), new Path());
    $fieldMapping->setFieldname('image');
    $mapping = $this->createStub(Mapping::class);
    $mapping->method('getFieldsMappingByClass')->willReturn([$fieldMapping]);

    return new FileUploader($mapping, $this->createStub(Cropper::class), $this->createStub(CropperConfiguration::class), $this->createStub(Image::class));
  }

  /**
   * @param bool $withImage
   *
   * @return FormInterface<mixed>
   */
  private function form(bool $withImage): FormInterface
  {
    $builder = Forms::createFormFactory()->createNamedBuilder('form_austral', FormType::class, null, ['data_class' => null]);
    if ($withImage)
    {
      $builder->add('image', FileType::class, ['mapped' => false]);
    }

    return $builder->getForm();
  }
}
