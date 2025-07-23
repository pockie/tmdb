<?php

namespace App\Command;

use App\Service\TmdbService;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tmdb',
    description: 'Search movies and TV shows on TMDB',
)]
class TmdbCommand extends Command
{
    private const ARGUMENT_SEARCH = 'search';
    private const ARGUMENT_LIMIT = 'limit';
    private const OPTION_YEAR = 'year';

    public function __construct(private readonly TmdbService $tmdbService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::ARGUMENT_SEARCH, InputArgument::REQUIRED, 'Search term')
            ->addArgument(self::ARGUMENT_LIMIT, InputArgument::REQUIRED, 'Number of movies')
            ->addOption(self::OPTION_YEAR, null, InputOption::VALUE_REQUIRED, 'Filter by year');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $search = $input->getArgument('search');
        $limit = (int)$input->getArgument('limit');
        $year = $input->getOption('year');


        try {
            $movies = $this->tmdbService->searchMovies($search, $limit, $year);

            $io->section("Search for: $search");
            if ($year) {
                $io->text("Filtered by year: $year");
            }
            $io->text("Limit: $limit");
            $io->text("Found: " . count($movies) . " movies:");

            foreach ($movies as $movie) {
                $title = $movie->title ?? 'N/A';
                $date = $movie->releaseDate ?? 'N/A';
                $desc = $movie->overview?? 'No description available';
                $io->writeln(sprintf(
                    '  <fg=green>🎬</> <info>%s</info> <comment>(%s)</comment>',
                    $title,
                    $date
                ));
                $io->writeln('      <fg=gray>' . wordwrap($desc, 80, "\n      ") . '</>');
                $io->newLine();
            }

        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
