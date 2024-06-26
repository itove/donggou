<?php

namespace App\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Vich\UploaderBundle\Form\Type\VichFileType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;

final class VichFileField implements FieldInterface
{
    use FieldTrait;

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(VichFileType::class)
            ->setFormTypeOptions(['allow_delete' => false])
            // ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-image.js'))
            // ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-file-upload.js'))
        ;
    }
}
