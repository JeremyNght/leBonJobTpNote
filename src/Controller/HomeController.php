<?php

namespace App\Controller;

use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/offers', name: 'app_offers')]
    public function offers(EntityManagerInterface $entityManager, int $page = 1): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offers = $repository->findBy(
            [],
            [],
            50,
            50 * ($page - 1)
        );

        return $this->render('offers/index.html.twig', [
            'offers' => $offers,
        ]);
    }

    // Une page /offers/department-{code département} listant l’ensemble des offres
    //dans un département (87,93,2A par exemple)
    #[Route('/offers/department-{code}', name: 'app_offers_department')]
    public function offersByDepartment(EntityManagerInterface $entityManager, string $code, int $page = 1): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offers = $repository->findBy(
            ['department' => $code],
            [],
            50,
            50 * ($page - 1)
        );

        return $this->render('offers/department.html.twig', [
            'offers' => $offers,
        ]);
    }

    // Une page /offers/job-{ID JOB} listant l’ensemble des offres pour un job donné
    //(Identifiant du métier en BDD)
    #[Route('/offers/job-{id}', name: 'app_offers_job')]
    public function offersByJob(EntityManagerInterface $entityManager, int $id, int $page = 1): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offers = $repository->findBy(
            ['job' => $id],
            [],
            50,
            50 * ($page - 1)
        );

        return $this->render('offers/job.html.twig', [
            'offers' => $offers,
        ]);
    }

    // Une page /offers/{ID OFFER} affichant tout le détail d’une offre
    #[Route('/offers/{id}', name: 'app_offer')]
    public function offer(EntityManagerInterface $entityManager, int $id): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offer = $repository->find($id);

        return $this->render('offers/show.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route('/offers/{id}/candidate', name: 'app_offers_candidate')]
    public function candidate(EntityManagerInterface $entityManager, int $id, Request $request): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offer = $repository->find($id);

        $form = $this->createForm(Offer::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement du formulaire, enregistrement des données, etc.
        }

        $this->addFlash('success', 'Form successfully submitted!');
        return $this->render('offers/candidate.html.twig', [
            'offers' => $offer,
        ]);
    }

}
