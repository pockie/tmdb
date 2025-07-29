# Simple Symfony cli app to list movies from TMDB

## Features
- Fetches movies from TMDB (The Movie Database)
- Supports filtering by search term, limit, year (optional) and page (optional)

## Usage
- Add your TMDB API key in the `.env` file
- To create your API key, visit [TMDB](https://www.themoviedb.org/documentation/api). You need to create an account to generate an API key.
- The application show maximum 20 results per page. You can specify the page number to navigate through results.
- Run the command to list movies:

```bash
bin/console tmdb "Matrix" 10 --year=1999 --page=1
```
- The command has the folowing arguments:
  - `search`: The search term for the movie
  - `limit`: The maximum number of results to return
- The command accepts the following options:
  - `--year`: Filter movies by year (optional)
  - `--page`: Specify the page number for pagination (optional, default is 1, total of pages are shown in the output)
  - `--help`: Show help message for the command

## Installation
- After cloning the repository, run the following command to install dependencies:

```bash
composer install
```

## Tests
- To run the tests, use the following command:

```bash
bin/phpunit
```

## PHPStan
- To run PHPStan, use the following command:

```bash
vendor/bin/phpstan analyse -c phpstan.neon
```
