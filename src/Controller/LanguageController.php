<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LanguageController extends AbstractController
{
    #[Route('/language/{locale}', name: 'app_language_switch', requirements: ['locale' => 'en|de'])]
    public function index(Request $request, string $locale): Response
    {
		$request->getSession()->set('_locale', $locale);

		$referer = $request->headers->get('referer');
		if ($referer) {
			return $this->redirect($referer);
		}

		return $this->redirectToRoute('app_bank_statement');
    }
}
