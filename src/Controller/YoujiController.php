<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class YoujiController extends AbstractController
{
    #[Route('/youji', name: 'app_youji')]
    public function index(): Response
    {
        return $this->render('youji/index.html.twig', [
            'controller_name' => 'YoujiController',
        ]);
    }
}
