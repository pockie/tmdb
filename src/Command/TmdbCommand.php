<?php

namespace App\Command;

use App\Dto\MovieResult;
use App\Service\TmdbService;
use InvalidArgumentException;
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
    private const OPTION_PAGE = 'page';

    public function __construct(private readonly TmdbService $tmdbService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::ARGUMENT_SEARCH, InputArgument::REQUIRED, 'Search term')
            ->addArgument(self::ARGUMENT_LIMIT, InputArgument::REQUIRED, 'Number of movies')
            ->addOption(self::OPTION_YEAR, null, InputOption::VALUE_REQUIRED, 'Filter by year')
            ->addOption(self::OPTION_PAGE, null, InputOption::VALUE_REQUIRED, 'Filter by page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $search = $this->filterArgumentString($input, self::ARGUMENT_SEARCH);
        $limit = $this->filterIntegerArgument($input, self::ARGUMENT_LIMIT);
        $year = $this->filterIntegerOption($input, self::OPTION_YEAR);
        $page = $this->filterPage($input);

        try {
            $result = $this->tmdbService->searchMovies($search, $limit, $page, $year);

            if (count($result->movies) === 0) {
                $io->error("No results found for '$search'.");
                return Command::FAILURE;
            }

            $movies = $result->movies;
            $totalPages = $result->totalPages ?? 0;

            $this->renderIntroduction($search, count($movies), $year, $limit, $totalPages, $page, $io);

            if ($page > $result->totalPages) {
                $io->warning("Page $page exceeds total pages of $totalPages. Please change the page option to maximum of $totalPages.");

            }

            $this->renderMovies($movies, $io);

        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function filterArgumentString(InputInterface $input, string $argumentName): string
    {
        $inputValue = $input->getArgument($argumentName);
        $filteredInput = filter_var($inputValue, FILTER_UNSAFE_RAW);
        if (empty($filteredInput)) {
            throw new InvalidArgumentException("$argumentName term must not be empty.");
        }

        return $filteredInput;
    }

    private function filterIntegerArgument(InputInterface $input, string $argumentName): int
    {
        $limitRaw = $input->getArgument($argumentName);
        $limit = filter_var($limitRaw, FILTER_VALIDATE_INT);
        if ($limit === false) {
            throw new InvalidArgumentException("$argumentName must be an integer.");
        }

        return $limit;
    }

    private function filterIntegerOption(InputInterface $input, string $optionName): ?int {
        $optionRaw = $input->getOption($optionName);

        if ($optionRaw === null) {
            return null;
        }

        $year = filter_var($optionRaw, FILTER_VALIDATE_INT);
        if ($year === false) {
            throw new InvalidArgumentException("$optionName must be an integer.");
        }

        return $year;
    }

    private function filterPage(InputInterface $input): int
    {
        $optionRaw = $input->getOption(self::OPTION_PAGE);
        $page = $this->filterIntegerOption($input, self::OPTION_PAGE);

        if ($page === null) {
            $page = 1;
        } elseif ($page < 1) {
            throw new InvalidArgumentException("Page number must be greater than 0.");
        }

        return $page;
    }

    private function renderIntroduction(string $search, int $countMovies, ?int $year, int $limit, int $totalPages, int $page, SymfonyStyle $io): void
    {
        $io->section("Search for: $search");
        if ($year) {
            $io->text("Filtered by year: $year");
        }
        $io->text("Limit: $limit");
        $io->text("Found: $countMovies movie(s)");
        $io->text("Page: $page/$totalPages");
    }

    /**
     * @param array<MovieResult> $movies
     */
    private function renderMovies(array $movies, SymfonyStyle $io): void
    {
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
    }
}
