<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;

class UserCrudController extends AbstractCrudController
{
    private $action;
    public function __construct(RequestStack $requestStack)
    {
        $this->query = $requestStack->getCurrentRequest()->query;
        $this->action = $this->query->get('action');
    }
    
    public static function getEntityFqcn(): string
    {
        return User::class;
    }
    
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $uid = $this->getUser()->getId();
        $response = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $response
            ->andWhere("entity.id != $uid")
            // ->andWhere("entity.roles LIKE '%ROLE_SUPER_ADMIN%'")
        ;
        return $response;
    }

    public function configureFields(string $pageName): iterable
    {
        if ($this->action === 'chpw') {
            yield TextField::new('username')
                ->setDisabled()
            ;
            yield TextField::new('plainPassword')
                ->setFormType(RepeatedType::class)
                ->setRequired(true)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'Password'],
                    'second_options' => ['label' => 'Repeat password'],
                    // 'required' => 'required',
                ]);
        } else {
            yield TextField::new('username');
            yield TextField::new('name');
            yield ChoiceField::new('roles')
                ->onlyOnIndex()
                ->setChoices([
                    'user' => 'ROLE_USER',
                    'admin' => 'ROLE_ADMIN',
                    'cashier' => 'ROLE_CASHIER',
                    'Super Admin' => 'ROLE_SUPER_ADMIN',
                ])
                ;
            yield ChoiceField::new('roles')
                ->onlyOnForms()
                ->allowMultipleChoices()
                ->setChoices([
                    'admin' => 'ROLE_ADMIN',
                    'cashier' => 'ROLE_CASHIER',
                ])
                ->setRequired(false)
            ;
            yield TextField::new('plainPassword')
                ->onlyOnForms()
            ;
        }
    }
}
