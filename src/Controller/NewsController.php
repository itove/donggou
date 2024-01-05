<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/news')]
class NewsController extends AbstractController
{
    #[Route('/', name: 'app_news')]
    public function index(): Response
    {
        $data = [
          'class' => '',
          'sitename' => 'test',
        ];
        return $this->render('news/index.html.twig', $data);
    }
    
    #[Route('/', name: 'app_news')]
    public function show(): Response
    {
        $data = [
          'class' => '',
          'sitename' => 'test',
        ];
        return $this->render('news/detail.html.twig', $data);
    }
}
