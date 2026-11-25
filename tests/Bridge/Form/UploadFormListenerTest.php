<?php

/*
 * This file is part of the Austral EntityFile Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\EntityFileBundle\Tests\Bridge\Form;

use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityFileBundle\Attribute\Cropper;
use Austral\EntityFileBundle\Attribute\Croppers;
use Austral\EntityFileBundle\Attribute\ImageSize;
use Austral\EntityFileBundle\Attribute\Path;
use Austral\EntityFileBundle\Attribute\UploadParameters;
use Austral\EntityFileBundle\Bridge\Form\Listener\UploadFormListener;
use Austral\EntityFileBundle\Exception\FormUploadException;
use Austral\EntityFileBundle\File\Mapping\FieldFileMapping;
use Austral\EntityFileBundle\File\Upload\FileUploader;
use Austral\EntityFileBundle\Tests\Fixtures\FileEntity;
use Austral\EntityFileBundle\Validator\SvgSecurity;
use Austral\FormBundle\Constraint\DoctrineConstraintGuesser;
use Austral\FormBundle\Event\FormEvents;
use Austral\FormBundle\Field\TextField;
use Austral\FormBundle\Field\UploadField;
use Austral\FormBundle\Form\Extension\PresentationTypeExtension;
use Austral\FormBundle\Form\Type\AustralFormType;
use Austral\FormBundle\Handler\FormHandler;
use Austral\FormBundle\Layout\Fieldset;
use Austral\FormBundle\Layout\Modal;
use Austral\FormBundle\Mapper\FormMapper;
use Austral\FormBundle\Mapper\FormMapperFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;

/**
 * The bridge with form-bundle: the upload fields configured from the file mapping, the hidden fields, the
 * constraints, and the upload on save.
 */
final class UploadFormListenerTest extends TestCase
{
  private EventDispatcher $dispatcher;

  /**
   * @var FileUploader<object>&MockObject
   */
  private FileUploader&MockObject $fileUploader;

  /**
   * @var FieldFileMapping<object>|null Mapping of the field "image" (the only file field of the tests)
   */
  private ?FieldFileMapping $imageMapping = null;

  protected function setUp(): void
  {
    $this->fileUploader = $this->createMock(FileUploader::class);
    $mapping = $this->createStub(Mapping::class);
    $mapping->method('getFieldsMappingByFieldname')->willReturnCallback(
      fn (string $class, string $mappingClass, string $fieldname): ?FieldFileMapping => 'image' === $fieldname ? $this->imageMapping : null,
    );
    $listener = new UploadFormListener($this->fileUploader, $mapping);
    $this->dispatcher = new EventDispatcher();
    $this->dispatcher->addListener(FormEvents::CONFIGURE_AFTER, $listener->configure(...));
    $this->dispatcher->addListener(FormEvents::FIELD_BUILD, $listener->buildField(...));
    $this->dispatcher->addListener(FormEvents::SUBMIT, $listener->validate(...));
    $this->dispatcher->addListener(FormEvents::SAVE_BEFORE, $listener->uploads(...));
  }

  public function testUploadFieldConfiguredFromTheMapping(): void
  {
    $mapper = $this->mapper(UploadField::create('image'), $this->fieldMapping(
      new UploadParameters('image', false, ['image/png', 'image/jpeg'], '2M'),
      new ImageSize(null, 800, null, 600),
      new Croppers([new Cropper('wide', 'austral-picto-wide', '16/9')]),
    ));
    $field = $mapper->getField('image');

    self::assertInstanceOf(UploadField::class, $field);
    self::assertSame(['image/png', 'image/jpeg'], $field->getMimeTypes());
    self::assertSame('picture', $field->getTypeFile());
    self::assertSame('2M', $field->getMaxSize());
    self::assertSame('file.errors.maxSize', $field->getMaxSizeMessage());
    self::assertSame('file.errors.mimeTypes', $field->getMimeTypesMessage());
    self::assertSame(['min_width' => 800, 'max_width' => null, 'min_height' => 600, 'max_height' => null], $field->getImageSizes());
    self::assertSame('wide', $field->getCropper()['wide']['name']);
    self::assertEqualsWithDelta(16 / 9, $field->getCropper()['wide']['aspect_ratio'], 0.0001);
    self::assertFalse($field->isRequired());
  }

  public function testWhatTheAdminGaveIsKept(): void
  {
    $mapper = $this->mapper(
      UploadField::create('image', ['mime_types' => ['application/pdf'], 'max_size' => '5M', 'max_size_message' => 'errors.too_big']),
      $this->fieldMapping(new UploadParameters('image', false, ['image/png'], '2M')),
    );
    $field = $mapper->getField('image');

    self::assertInstanceOf(UploadField::class, $field);
    self::assertSame(['application/pdf'], $field->getMimeTypes());
    self::assertSame('5M', $field->getMaxSize());
    self::assertSame('errors.too_big', $field->getMaxSizeMessage());
    self::assertSame('file.errors.mimeTypes', $field->getMimeTypesMessage());
  }

  public function testHiddenFieldsAndEditor(): void
  {
    $mapper = $this->mapper(UploadField::create('image', ['attr' => ['data-popin-update-input' => 'image']]), $this->fieldMapping(
      new UploadParameters('image'),
      croppers: new Croppers([new Cropper('wide', null, '16/9')]),
    ));

    $hidden = $mapper->getFieldset(UploadFormListener::HIDDEN_FIELDSET);
    self::assertInstanceOf(Fieldset::class, $hidden);
    self::assertSame(Fieldset::POSITION_NONE, $hidden->getPosition());
    self::assertSame(['imageDeleteFile', 'cropperData', 'generateCropperByKey'], $hidden->getFieldNames());
    self::assertSame(['autocomplete' => 'off', 'data-delete-file' => '', 'data-popin-update-input' => 'image-delete'], $mapper->getField('imageDeleteFile')?->getOption('attr'));

    $modal = $mapper->findNode('modal-popup-editor-image');
    self::assertInstanceOf(Modal::class, $modal);
    self::assertSame('image', $modal->getFieldname());
    self::assertSame('actions.picture.edit', $modal->getButton()['label']);
    self::assertSame('uploadEditor', $modal->getModalOptions()['template']);
  }

  public function testNoEditorForARequiredFileWithoutCropper(): void
  {
    $mapper = $this->mapper(UploadField::create('image'), $this->fieldMapping(new UploadParameters('image', true)));

    self::assertNull($mapper->findNode('modal-popup-editor-image'));
    self::assertFalse($mapper->hasField('cropperData'));
    self::assertTrue($mapper->hasField('imageDeleteFile'));
  }

  public function testOtherFormsAreLeftAlone(): void
  {
    $mapper = new FormMapperFactory($this->dispatcher)->create('form_austral', \stdClass::class, null, static function (FormMapper $mapper): void {
      $mapper->add(UploadField::create('image'));
    });

    self::assertNull($mapper->getFieldset(UploadFormListener::HIDDEN_FIELDSET));
    self::assertSame(['image'], array_keys($mapper->getFields()));
  }

  public function testFieldNotMappedAsAFileIsLeftAlone(): void
  {
    $mapper = $this->mapper(UploadField::create('image'), null);

    self::assertNull($mapper->getFieldset(UploadFormListener::HIDDEN_FIELDSET));
  }

  public function testConstraintsOfTheUploadField(): void
  {
    $mapper = $this->mapper(UploadField::create('image', ['required' => true]), $this->fieldMapping(
      new UploadParameters('image', false, ['image/png'], '1M'),
      new ImageSize(null, 800),
    ));

    $field = $this->form($mapper)->get('image');
    $constraints = $field->getConfig()->getOption('constraints');

    self::assertFalse($field->getConfig()->getOption('required'));
    self::assertSame([Length::class, SvgSecurity::class, File::class, Image::class], self::classes($constraints));
    self::assertNotContains(NotNull::class, self::classes($constraints));
    $file = $constraints[2];
    self::assertInstanceOf(File::class, $file);
    self::assertSame(['image/png'], $file->mimeTypes);
    self::assertSame(1000000, $file->maxSize);
    self::assertSame('file.errors.maxSize', $file->maxSizeMessage);
  }

  public function testRequiredFilesCheckedOnSubmit(): void
  {
    $object = new FileEntity();
    $mapper = $this->mapper(UploadField::create('image'), $this->fieldMapping(new UploadParameters('image', true)), $object);
    $form = $this->form($mapper, $object);
    $this->fileUploader->expects(self::once())->method('validateRequiredFiles')->with($form, $object)->willReturn(true);
    $this->fileUploader->expects(self::once())->method('uploadFiles')->with($form, $object)->willReturnSelf();

    $result = new FormHandler($this->dispatcher)->process($form, Request::create('/', 'POST', ['form_austral' => ['title' => 'A']]), static function (): void {
    });

    self::assertTrue($result->isSaved());
  }

  public function testUploadErrorIsAFormUploadException(): void
  {
    $object = new FileEntity();
    $mapper = $this->mapper(UploadField::create('image'), $this->fieldMapping(new UploadParameters('image')), $object);
    $form = $this->form($mapper, $object);
    $this->fileUploader->method('validateRequiredFiles')->willReturn(true);
    $this->fileUploader->method('uploadFiles')->willThrowException(new \RuntimeException('Disk full'));

    $this->expectException(FormUploadException::class);
    $this->expectExceptionMessage('Disk full');

    new FormHandler($this->dispatcher)->process($form, Request::create('/', 'POST', ['form_austral' => ['title' => 'A']]), static function (): void {
    });
  }

  /**
   * @param UploadField                   $field
   * @param FieldFileMapping<object>|null $fieldMapping
   * @param FileEntity|null               $object
   *
   * @return FormMapper
   */
  private function mapper(UploadField $field, ?FieldFileMapping $fieldMapping, ?FileEntity $object = null): FormMapper
  {
    $this->imageMapping = $fieldMapping;

    return new FormMapperFactory($this->dispatcher)->create('form_austral', FileEntity::class, $object, static function (FormMapper $mapper) use ($field): void {
      $mapper->addFieldset('main')
        ->add(TextField::create('title', ['mapped' => false]))
        ->add($field);
    });
  }

  /**
   * @param UploadParameters      $uploadParameters
   * @param ImageSize|null        $imageSize
   * @param Croppers<object>|null $croppers
   *
   * @return FieldFileMapping<object>
   */
  private function fieldMapping(UploadParameters $uploadParameters, ?ImageSize $imageSize = null, ?Croppers $croppers = null): FieldFileMapping
  {
    return new FieldFileMapping($uploadParameters, new Path(), $imageSize, $croppers);
  }

  /**
   * @param FormMapper      $mapper
   * @param FileEntity|null $object
   *
   * @return FormInterface<mixed>
   */
  private function form(FormMapper $mapper, ?FileEntity $object = null): FormInterface
  {
    return Forms::createFormFactoryBuilder()
      ->addExtension(new HttpFoundationExtension())
      ->addExtension(new ValidatorExtension(Validation::createValidator()))
      ->addType(new AustralFormType(new DoctrineConstraintGuesser(), $this->dispatcher))
      ->addTypeExtension(new PresentationTypeExtension())
      ->getFormFactory()
      ->createNamed($mapper->getName(), AustralFormType::class, $object ?? new FileEntity(), ['mapper' => $mapper]);
  }

  /**
   * @param mixed $constraints
   *
   * @return list<class-string<Constraint>>
   */
  private static function classes(mixed $constraints): array
  {
    return array_map(static fn (Constraint $constraint): string => $constraint::class, (array) $constraints);
  }
}
