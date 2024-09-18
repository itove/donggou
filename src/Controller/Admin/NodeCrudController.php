<?php

namespace App\Controller\Admin;

use App\Entity\Node;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
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
use App\Admin\Field\VichImageField;
use App\Admin\Field\VichFileField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\Region;
use App\Service\Data;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
// use Doctrine\Common\Collections\ArrayCollection;
// use Doctrine\Common\Collections\Criteria;

class NodeCrudController extends AbstractCrudController
{
    private $region;
    private $parent;
    private $query;
    private $adminUrlGenerator;

    public function __construct(Data $data, RequestStack $requestStack, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->requestStack = $requestStack;
        $request = $requestStack->getCurrentRequest();
        $regionId = $request->query->get('region');
        $pid = $request->query->get('parent');
        if (!is_null($regionId)) {
            $this->region = $data->getRegion($regionId);
        }
        if (!is_null($pid)) {
            $this->parent = $data->getNode($pid);
        }
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->data = $data;
        $this->request = $request;
    }
    
    public static function getEntityFqcn(): string
    {
        return Node::class;
    }
    
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (!is_null($this->region)) {
            $regionId = $this->region->getId();
            $qb
                ->andWhere("r.id = $regionId")
                ->leftJoin('entity.regions', 'r')
            ;
        }
        if (!is_null($this->parent)) {
            $pid = $this->parent->getId();
            $qb
                ->andWhere("entity.parent = $pid")
            ;
        }
        return $qb;
    }
    
    public function createEntity(string $entityFqcn)
    {
        $node = new Node();
        if (!is_null($this->region)) {
            $lastNode = $this->data->findNodesByRegion($this->region, $this->request->getLocale(), 1);
            if (!empty($lastNode)) {
                foreach ($lastNode[0]->getRegions() as $r) {
                    $node->addRegion($r);
                }
            } else {
                $node->addRegion($this->region);
            }
        }
        
        if (!is_null($this->parent)) {
            $node->setParent($this->parent);
        }

        return $node;
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewRooms = Action::new('viewRooms', 'Rooms')
            ->linkToUrl(function (Node $entity){
                return $this->adminUrlGenerator
                    ->setController(NodeCrudController::class)
                    ->setDashboard(DashboardController::class)
                    ->setAction('index')
                    // ->set('menuIndex', 1)
                    ->set('parent', $entity->getId())
                    ->set('region', $this->data->getRegionByLabel('rooms')->getId())
                    ->generateUrl();
            })
            // ->displayAsLink()
            // ->displayAsButton()
            // ->setHtmlAttributes(['data-foo' => 'bar', 'target' => '_blank'])
            ->setCssClass('btn btn-info')
            // ->addCssClass('some-custom-css-class text-danger')
        ;

        $newFn = fn (Action $action) => $action->linkToUrl(
            fn () => $this->adminUrlGenerator
                          ->setAction('new')
                          ->generateUrl()
        );
        $editFn = fn (Action $action) => $action->linkToUrl(
            fn (Node $entity) => $this->adminUrlGenerator
                          ->setAction('edit')
                          ->set('entityId', $entity->getId())
                          ->generateUrl()
        );
        
        if (!is_null($this->region)) {
            $r = $this->region;
            if ($r->getLabel() === 'zhuzai') {
                $actions->add('index', $viewRooms);
            }
        }
        return $actions
            ->update('index', 'new', $newFn)
            ->update('index', 'edit', $editFn)
            ->update('detail', 'edit', $editFn)
        ;
    }
    
    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile(
                Asset::new('/js/ckeditor.js')
                    ->onlyOnForms()
            )
            ->addJsFile(
                Asset::new('/js/initCKEditor.js')
                    ->defer()
                    ->onlyOnForms()
            )
        ;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        if (!is_null($this->region)) {
            $crud
                ->setPageTitle('index', $this->region)
            ;
        }
        if (!is_null($this->parent)) {
            $crud
                ->setPageTitle('index', $this->parent . ' 房间')
                ->setPageTitle('new', $this->parent . ' 新增房间')
                ->setPageTitle('edit', $this->parent . ' 编辑房间')
            ;
        }
        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        $idField = IdField::new('id')->onlyOnIndex();
        $titleField = TextField::new('title');
        $phoneField = TextField::new('phone');
        $addressField = TextField::new('address');
        // $imageField = ImageField::new('image')
        //     ->onlyOnIndex()
        //     ->setBasePath('images/')
        //     ->setUploadDir('public/images/')
        // ;
        // $videoField = ImageField::new('video')
        //     ->onlyOnIndex()
        //     ->hideOnIndex()
        //     ->setBasePath('images/')
        //     ->setUploadDir('public/images/')
        // ;

        $imageField = VichImageField::new('imageFile', 'Image')->hideOnIndex();
        $priceField = MoneyField::new('price')->setCurrency('CNY')->hideOnIndex();
        $videoField = VichFileField::new('videoFile', 'Video')->hideOnIndex();
        $audioField = VichFileField::new('audioFile', 'Audio')->hideOnIndex();
        $qrField = VichImageField::new('qrFile', 'Qr')->hideOnIndex();
        $tagsFieldOnIndex = ArrayField::new('tags')->onlyOnIndex();
        $tagsField = AssociationField::new('tags')
            ->onlyOnForms()
            // ->setRequired(true)
        ;
        $categoryFieldOnIndex = ArrayField::new('category')->onlyOnIndex();
        $categoryField = AssociationField::new('category')->onlyOnForms();
        $parentField = AssociationField::new('parent')->onlyOnForms()->setDisabled();
        $childrenField = AssociationField::new('children')->setDisabled();
        $summaryField = TextareaField::new('summary')
            // ->setMaxLength(15)
            ;
        $bodyField = TextareaField::new('body')->setNumOfRows(10)->onlyOnForms();
        $createdAtField = DateTimeField::new('createdAt')->onlyOnIndex();
        $updatedAtField = DateTimeField::new('updatedAt')->onlyOnIndex();
        $languageField = AssociationField::new('language');
        $regionsField = AssociationField::new('regions')->onlyOnForms();
        $specsField = CollectionField::new('specs')->useEntryCrudForm()->hideOnIndex();
        $imagesField = CollectionField::new('images')->useEntryCrudForm()->hideOnIndex();
        $latitudeField = NumberField::new('latitude')->setNumDecimals(12)->hideOnIndex();
        $longitudeField = NumberField::new('longitude')->setNumDecimals(12)->hideOnIndex();
        
        $fields = [];
        if (!is_null($this->region)) {
            $fields = $this->region->getFields();
            // $vichImageField->setHelp("推荐尺寸{$this->region->getDescription()}，或宽高比与之相同的尺寸。");
        } else if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $fields = Data::GetProperties(new Node());
            array_push($fields, 'regions');
        }
        
        yield $titleField;
        foreach ($fields as $f) {
            $ff = $f . "Field";
            yield $$ff;
        }
        // yield ArrayField::new('regions')->onlyOnIndex();

        // if (in_array('image', $fields)) {
        //     yield $vichImageField;
        // }
        if (in_array('tags', $fields)) {
            yield $tagsFieldOnIndex;
        }
        if (in_array('category', $fields)) {
            yield $categoryFieldOnIndex;
        }
    }
}
