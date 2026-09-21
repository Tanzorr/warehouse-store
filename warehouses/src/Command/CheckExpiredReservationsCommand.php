<?php

namespace App\Command;

use App\Repository\ProductReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-expired-reservations',
    description: 'Add a short description for your command',
)]
class CheckExpiredReservationsCommand extends Command
{
    public function __construct(
        private ProductReservationRepository $productReservationRepository,
        private EntityManagerInterface $em
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $now = new \DateTimeImmutable();
        $expiredReservations = $this->productReservationRepository->findExpiredPendingReservation($now);

        foreach ($expiredReservations as $reservation) {
            $reservation->setStatus('expired');
            $this->productReservationRepository->save($reservation);
        }

        $output->writeln('Expired reservations updated successfully.', count($expiredReservations));

        return Command::SUCCESS;
    }
}
