<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CalculatorService;
use App\Service\FileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route("/test", name: 'action')]
    public function mainAction(Request $request, FileService $fileService, CalculatorService $calculatorService): Response
    {
        if ($file = $request->files->get('inputFile')) {
            $data = $fileService->process($file);
            $calculatorService->process(data: $data);
        }

        $a = $this->render('main.html.twig', ['history' => $calculatorService->getHistory()]);
        return $a;
    }
}
