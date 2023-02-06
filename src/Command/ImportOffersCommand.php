<?php

namespace App\Command;

use App\Entity\Job;
use App\Entity\Offer;
use App\Service\PoleEmploiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'ImportOffersCommand',
    description: 'Add a short description for your command',
)]
class ImportOffersCommand extends Command
{
    protected static $defaultName = 'app:import-offers';

    private PoleEmploiService $poleEmploiService;
    private EntityManagerInterface $entityManager;

    public function __construct(PoleEmploiService $poleEmploiService, EntityManagerInterface $entityManager)
    {
        $this->poleEmploiService = $poleEmploiService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:import-offers')
            ->setDescription('Import offers from pole emploi API')
            ->setHelp('This command allows you to import offers from pole emploi API')
        ;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $jobRepository = $em->getRepository('App:Job');
        $offerRepository = $em->getRepository('App:Offer');

        $offers = $this->getContainer()->get('app.pole_emploi_service')->getOffers();

        $output->writeln([
            '============',
            ' Import jobs and offers in DB ...',
            '============',
        ]);

        $count = 0;
        foreach ($offers as $companyRef => $companyJobs) {
            $output->writeln([
                '============',
                ' Company : ' . $companyRef,
                '============',
            ]);

            foreach ($companyJobs as $ref => $offerArr) {
                $count++;

                if (isset($offerArr['title']) && isset($offerArr['location'])) {
                    $offer = $offerRepository->findOneBy(array('reference' => $ref));
                    if (!$offer) {
                        $offer = new Offer();
                    }

                    $job = $jobRepository->findOneBy(array('name' => $offerArr['title']));
                    if (!$job) {
                        $job = new Job();
                        $job->setCode($offerArr['romeCode']);
                        $job->setName($offerArr['title']);
                        $em->persist($job);
                    }

                    $offer->setJob($job);
                    $offer->setReference($ref);
                    $offer->setZipcode($offerArr['location']['codePostal']);
                    $offer->setDepartment(substr(0, 2, $offerArr['location']['codePostal']));
                    $offer->setCity($offerArr['location']['libelle']);
                    if (isset($offerArr['location']['latitude'])) {
                        $offer->setLatitude($offerArr['location']['latitude']);
                        $offer->setLongitude($offerArr['location']['longitude']);
                    }
                    $offer->setDescription($offerArr['description']);
                    $offer->setContactEmail('fake-email-' . $companyRef . '@lebonjob.com');
                    $em->persist($offer);

                    if ($count % 50 === 0) {
                        $em->flush();
                        $output->writeln([
                            $count . ' ...'
                        ]);
                    }
                }
            }
        }

        $em->flush();
        $output->writeln([
            '============',
            ' Done ! ',
            '============',
        ]);
    }

    public function getContainer()
    {
        return $this->getApplication()->getKernel()->getContainer();
    }
}
