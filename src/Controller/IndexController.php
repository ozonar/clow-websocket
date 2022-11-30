<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
/**
 * Created by Ozonar
 * Date: 24.11.2022
 * Time: 9:53
 */
class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('sub/game.html.twig', ['token' => $_ENV['JWT_PASSPHRASE'] ]);
    }
}