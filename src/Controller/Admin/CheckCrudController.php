<?php

namespace App\Controller\Admin;

use App\Entity\Check;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class CheckCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Check::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new', 'edit', 'delete')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('ord')->setDisabled();
        yield AssociationField::new('checker')->setDisabled();
        yield DateTimeField::new('createdAt')->onlyOnIndex();
    }
}
