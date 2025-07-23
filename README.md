# Simple Symfony cli app to list movies from TMDB


## Features
- Fetches movies from TMDB (The Movie Database)
- Supports filtering by year (optional), search term and limit

## Usage
- Add your TMDB API key in the `.env` file
- To create your API key, visit [TMDB](https://www.themoviedb.org/documentation/api)
- Run the command to list movies:

```bash
bin/console tmdb "Matrix" 10 --year=1999 
```

## Tests
- To run the tests, use the following command:

```bash
bin/phpunit
```

## PHPStan
- To run PHPStan, use the following command:

```bash
vendor/bin/phpstan analyse phpstan.neon
```
