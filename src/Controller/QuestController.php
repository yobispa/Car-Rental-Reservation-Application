<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QuestController extends AbstractController
{
    #[Route('/', name: 'app_quest')]
    public function index(): Response
    {
        return $this->render('quest/index.html.twig');
    }
}
