<?php

namespace App\Command;

use App\Repository\ArchivesRepository;
use App\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:purge-archives',
    description: 'Purge automatique des archives de plus de 5 ans (Conformité RGPD)',
)]
class PurgeArchivesCommand extends Command
{
    public function __construct(
        private ArchivesRepository $archivesRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Lancement de la purge des archives RGPD');

        // 1. On calcule la date limite : Aujourd'hui moins 5 ans
        $limitDate = new \DateTime();
        $limitDate->modify('-5 years');

        $io->text('Recherche des archives antérieures au : ' . $limitDate->format('d/m/Y'));

        // 2. On exécute la suppression en base de données
        $deletedCount = $this->archivesRepository->deleteOlderThan($limitDate);

        // 3. On crée un Log système pour tracer l'action (le système devient son propre utilisateur : ID 0)
        if ($deletedCount > 0) {
            $log = new Logs();
            $log->setUserId(0); // ID 0 = Le Système / Le Serveur
            $log->setAction("CRON : Purge RGPD automatique. $deletedCount archive(s) de plus de 5 ans supprimée(s).");
            $log->setActionDate(new \DateTime());
            $this->em->persist($log);
            $this->em->flush();
        }

        $io->success(sprintf('%d archive(s) obsolète(s) ont été définitivement supprimée(s).', $deletedCount));

        return Command::SUCCESS;
    }
}