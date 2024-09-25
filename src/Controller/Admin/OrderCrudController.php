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
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class OrderCrudController extends AbstractCrudController
{
    const STATUSES = [
        '待支付' => 1,
        '已支付' => 2,
        '已核销' => 3,
        '已取消' => 4,
        '已退款' => 5,
        // '已删除' => 6,
    ];

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add('index', 'detail')
            ->disable('delete', 'new', 'edit')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('sn')->setDisabled();
        yield AssociationField::new('consumer')->setDisabled();
        yield AssociationField::new('node')->setDisabled();
        yield MoneyField::new('price')->setCurrency('CNY');
        yield IntegerField::new('quantity');
        yield MoneyField::new('amount')->setCurrency('CNY');
        yield ChoiceField::new('status')
            ->setChoices(self::STATUSES)
            // ->setDisabled()
        ;
        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('paidAt')->hideOnForm();
        yield DateTimeField::new('usedAt')->hideOnForm();
        yield DateTimeField::new('cancelledAt')->hideOnForm();
        yield DateTimeField::new('refundedAt')->hideOnForm();
        yield BooleanField::new('deleted')->setDisabled();
        yield DateTimeField::new('deletedAt')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('consumer')
            ->add('node')
            ->add('amount')
            ->add(ChoiceFilter::new('status')->setChoices(self::STATUSES))
            ->add('createdAt')
            ->add('paidAt')
            ->add('usedAt')
            ->add('deleted')
        ;
    }
}
