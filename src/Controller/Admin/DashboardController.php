<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use App\Entity\Order;
use App\Entity\Check;
use App\Entity\Node;
use App\Entity\Region;
use App\Entity\Page;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\Feedback;
use App\Entity\Language;
use App\Entity\Conf;
use App\Entity\Category;
use App\Entity\Menu;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

class DashboardController extends AbstractDashboardController
{
    private $doctrine;
    private $nodes;
    private $conf;
    private $regions;

    public function __construct(ManagerRegistry $doctrine)
    {
      $this->doctrine = $doctrine;
      $this->nodes = $doctrine->getRepository(Node::class);
      $this->conf = $doctrine->getRepository(Conf::class)->find(1);
      $this->regions = $doctrine->getRepository(Region::class)->findAll();
    }
    
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator
                    ->setController(NodeCrudController::class)
                    ->set('region', '3')
                    // ->setAction('detail')
                    // ->setEntityId(1)
                    ->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        if ($this->conf) {
            $title = $this->conf->getSitename();
        } else {
            $title = 'Sitename';
        }
        return Dashboard::new()
            ->renderContentMaximized()
            // ->renderSidebarMinimized()
            ->setLocales(['en', 'zh_CN'])
            ->setTitle($title);
    }
    
    public function configureCrud(): Crud
    {
        return Crud::new()
            ->showEntityActionsInlined()
            ->setTimezone('Asia/Shanghai')
            ->setDateTimeFormat('yyyy/MM/dd HH:mm')
            ->setDefaultSort(['id' => 'DESC'])
        ;
    }

    public function configureActions(): Actions
    {
        return Actions::new()
            // ->disable('delete')
            ->add('detail', 'edit')
            ->add('index', 'edit')
            ->add('index', 'new')
            ->add('index', 'delete')
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
        ;
    }

    public function configureMenuItems(): iterable
    {
        $pages = $this->doctrine->getRepository(Page::class)->findBy([], ['id' => 'ASC']);
        
        yield MenuItem::linkToUrl('Back to Site', 'fas fa-arrow-circle-left', '/');
        
        // yield MenuItem::section('Content Management');
        // yield MenuItem::linkToCrud('Product Management', 'fas fa-truck', Node::class)
        //     ->setQueryParameter('region', 'product')
        // ;
        // yield MenuItem::linkToCrud('News', 'fas fa-newspaper', Node::class)
        //     ->setQueryParameter('region', 'news')
        // ;
        
        yield MenuItem::section('Content Management');
        
        foreach ($pages as $p) {
            $items = [];
            foreach ($p->getRegions() as $region) {
                $item = MenuItem::linkToCrud($region->getName(), "fas fa-{$region->getIcon()}", Node::class)
                    ->setQueryParameter('region', $region->getId())
                ;
                array_push($items, $item);
            }
            yield MenuItem::subMenu($p->getName(), 'fa fa-file-image-o')->setSubItems($items);
        }
        
        $footer = $this->doctrine->getRepository(Region::class)->findOneBy(['label' => 'footer']);
        $video = $this->doctrine->getRepository(Region::class)->findOneBy(['label' => 'video']);
        yield MenuItem::linkToCrud('Footer', "fas fa-{$footer->getIcon()}", Node::class)
            ->setQueryParameter('region', $footer->getId())
        ;
        yield MenuItem::linkToCrud('Video', "fas fa-{$video->getIcon()}", Node::class)
            ->setQueryParameter('region', $video->getId())
        ;

        yield MenuItem::section('');
        yield MenuItem::linkToCrud('Order Management', 'fas fa-book-open', Order::class);
        yield MenuItem::linkToCrud('Check Records', 'fas fa-book-open', Check::class);
        yield MenuItem::linkToCrud('Refund Records', 'fas fa-book-open', Check::class);
        yield MenuItem::linkToCrud('Menu Management', 'fas fa-link', Menu::class);
        yield MenuItem::linkToCrud('Tag Management', 'fas fa-tags', Tag::class);
        yield MenuItem::linkToCrud('Category Management', 'fas fa-table-cells-large', Category::class);
        
        // admin menu of regions
        // foreach ($this->regions as $region) {
        //     yield MenuItem::linkToCrud($region->getName(), "fas fa-{$region->getIcon()}", Node::class)
        //         ->setQueryParameter('region', $region->getId())
        //     ;
        // }
        
        yield MenuItem::section('Settings');
        yield MenuItem::linkToCrud('Change Password', 'fas fa-key', User::class)
            ->setQueryParameter('action', 'chpw')
            ->setAction('edit')
            ->setEntityId($this->getUser()->getId())
            ;
        if ($this->isGranted('ROLE_ADMIN')) {
            // yield MenuItem::linkToCrud('Category Management', 'fas fa-list', Category::class);
            // yield MenuItem::linkToCrud('Tag Management', 'fas fa-list', Tag::class);
            yield MenuItem::linkToCrud('Feedback', 'fas fa-message', Feedback::class);
            yield MenuItem::linkToCrud('User Management', 'fas fa-users', User::class);
            if ($_ENV['IS_MULTILINGUAL']) {
                yield MenuItem::linkToCrud('Settings', 'fas fa-cog', Conf::class);
            } else {
                yield MenuItem::linkToCrud('Settings', 'fas fa-cog', Conf::class)
                    ->setAction('detail')
                    ->setEntityId(1)
                ;
            }
            yield MenuItem::linkToUrl('Changelog', 'fas fa-note-sticky', '/changelog/');
        }
        
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            yield MenuItem::section('Super Admin');
            yield MenuItem::linkToCrud('Page', 'fas fa-cog', Page::class);
            yield MenuItem::linkToCrud('Region', 'fas fa-cog', Region::class);
            yield MenuItem::linkToCrud('Node', 'fas fa-cog', Node::class);
            if ($_ENV['IS_MULTILINGUAL']) {
                yield MenuItem::linkToCrud('Language', 'fas fa-cog', Language::class);
            }
            // yield MenuItem::linkToExitImpersonation('Stop impersonation', 'fa fa-exit');
            // yield MenuItem::linkToCrud('Conf', 'fas fa-cog', Conf::class);
        }
    }
}
