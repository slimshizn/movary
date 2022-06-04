<?php declare(strict_types=1);

namespace Movary\Application\Movie;

use Matriphe\ISO639\ISO639;
use Movary\Api\Tmdb\Dto\Cast;
use Movary\Api\Tmdb\Dto\Crew;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Company;
use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;

class Api
{
    public function __construct(
        private readonly Service\Create $movieCreateService,
        private readonly Service\Select $movieSelectService,
        private readonly Service\Update $movieUpdateService,
        private readonly Movie\History\Service\Create $historyCreateService,
        private readonly Movie\History\Service\Delete $historyDeleteService,
        private readonly Movie\History\Service\Select $historySelectService,
        private readonly Movie\Genre\Service\Select $genreSelectService,
        private readonly Movie\Cast\Service\Select $castSelectService,
        private readonly Movie\Crew\Service\Select $crewSelectService,
        private readonly ISO639 $ISO639
    ) {
    }

    public function create(string $title, ?int $rating10, ?int $rating5, TraktId $traktId, string $imdbId, int $tmdbId) : Entity
    {
        return $this->movieCreateService->create($title, $rating10, $rating5, $traktId, $imdbId, $tmdbId);
    }

    public function deleteById(int $id) : void
    {
        $this->historyDeleteService->deleteByMovieId($id);
    }

    public function fetchAll() : EntityList
    {
        return $this->movieSelectService->fetchAll();
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbDesc() : EntityList
    {
        return $this->movieSelectService->fetchAllOrderedByLastUpdatedAtTmdbDesc();
    }

    public function fetchHistoryByMovieId(int $movieId) : array
    {
        return $this->historySelectService->fetchHistoryByMovieId($movieId);
    }

    public function fetchHistoryMoviePlaysOnDate(int $id, Date $watchedAt) : int
    {
        return $this->historySelectService->fetchPlaysForMovieIdOnDate($id, $watchedAt);
    }

    public function fetchHistoryOrderedByWatchedAtDesc() : array
    {
        return $this->historySelectService->fetchHistoryOrderedByWatchedAtDesc();
    }

    public function fetchWithActor(int $personId) : EntityList
    {
        return $this->movieSelectService->fetchWithActor($personId);
    }

    public function fetchWithDirector(int $personId) : EntityList
    {
        return $this->movieSelectService->fetchWithDirector($personId);
    }

    public function findById(int $movieId) : ?array
    {
        $entity = $this->movieSelectService->findById($movieId);

        if ($entity === null) {
            return null;
        }

        $renderedRuntime = '';
        $hours = floor($entity->getRuntime() / 60);
        if ($hours > 0) {
            $renderedRuntime .= $hours . 'h';
        }
        $minutes = $entity->getRuntime() % 60;
        if ($minutes > 0) {
            $renderedRuntime .= ' ' . $minutes . 'm';
        }

        return [
            'title' => $entity->getTitle(),
            'releaseDate' => $entity->getReleaseDate(),
            'tmdbPosterPath' => $entity->getTmdbPosterPath(),
            'rating5' => $entity->getRating5(),
            'rating10' => $entity->getRating10(),
            'tagline' => $entity->getTagline(),
            'overview' => $entity->getOverview(),
            'runtime' => $renderedRuntime,
            'originalLanguage' => $this->ISO639->languageByCode1($entity->getOriginalLanguage()),
        ];
    }

    public function findByLetterboxdId(string $letterboxdId) : ?Entity
    {
        return $this->movieSelectService->findByLetterboxdId($letterboxdId);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->movieSelectService->findByTmdbId($tmdbId);
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        return $this->movieSelectService->findByTraktId($traktId);
    }

    public function findCastByMovieId(int $movieId) : ?array
    {
        return $this->castSelectService->findByMovieId($movieId);
    }

    public function findDirectorsByMovieId(int $movieId) : ?array
    {
        return $this->crewSelectService->findDirectorsByMovieId($movieId);
    }

    public function findGenresByMovieId(int $movieId) : ?array
    {
        return $this->genreSelectService->findByMovieId($movieId);
    }

    public function replaceHistoryForMovieByDate(int $movieId, Date $watchedAt, int $playsPerDate) : void
    {
        $this->historyCreateService->createOrUpdatePlaysForDate($movieId, $watchedAt, $playsPerDate);
    }

    public function updateCast(int $movieId, Cast $tmdbCast) : void
    {
        $this->movieUpdateService->updateCast($movieId, $tmdbCast);
    }

    public function updateCrew(int $movieId, Crew $tmdbCrew) : void
    {
        $this->movieUpdateService->updateCrew($movieId, $tmdbCrew);
    }

    public function updateDetails(
        int $movieId,
        ?string $tagline,
        ?string $overview,
        ?string $originalLanguage,
        ?DateTime $releaseDate,
        ?int $runtime,
        ?float $tmdbVoteAverage,
        ?int $tmdbVoteCount,
        ?string $tmdbPosterPath,
    ) : Entity {
        return $this->movieUpdateService->updateDetails(
            $movieId,
            $tagline,
            $overview,
            $originalLanguage,
            $releaseDate,
            $runtime,
            $tmdbVoteAverage,
            $tmdbVoteCount,
            $tmdbPosterPath
        );
    }

    public function updateGenres(int $movieId, Genre\EntityList $genres) : void
    {
        $this->movieUpdateService->updateGenres($movieId, $genres);
    }

    public function updateLetterboxdId(int $movieId, string $letterboxdId) : void
    {
        $this->movieUpdateService->updateLetterboxdId($movieId, $letterboxdId);
    }

    public function updateProductionCompanies(int $movieId, Company\EntityList $companies) : void
    {
        $this->movieUpdateService->updateProductionCompanies($movieId, $companies);
    }

    public function updateRating10(int $movieId, ?int $rating10) : void
    {
        $this->movieUpdateService->updateRating10($movieId, $rating10);
    }

    public function updateRating5(int $movieId, ?int $rating5) : void
    {
        $this->movieUpdateService->updateRating5($movieId, $rating5);
    }
}