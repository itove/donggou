<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        $statuses = [
            'Pending' => 0,
            'Paid' => 1,
            'Used' => 2,
            'Cancelled' => 4,
            'Deleted' => 5,
        ];
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('consumer')->setDisabled();
        yield AssociationField::new('node')->setDisabled();
        yield IntegerField::new('quantity');
        yield MoneyField::new('amount')->setCurrency('CNY');
        yield ChoiceField::new('status')
            ->setChoices($statuses)
            ->setDisabled()
        ;
        yield DateTimeField::new('createdAt')->onlyOnIndex();
        yield DateTimeField::new('paidAt')->onlyOnIndex();
        yield DateTimeField::new('usedAt')->onlyOnIndex();
    }
}
