<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Api\Tmdb\Dto\Cast;
use Movary\Api\Tmdb\Dto\Crew;
use Movary\Application\Company;
use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;
use Movary\Application\Person;
use Movary\ValueObject\DateTime;

class Update
{
    private Movie\Cast\Service\Create $movieCastCreateService;

    private Movie\Cast\Service\Delete $movieCasteDeleteService;

    private Movie\Crew\Service\Create $movieCrewCreateService;

    private Movie\Crew\Service\Delete $movieCrewDeleteService;

    private Movie\Genre\Service\Create $movieGenreCreateService;

    private Movie\Genre\Service\Delete $movieGenreDeleteService;

    private Movie\ProductionCompany\Service\Create $movieProductionCompanyCreateService;

    private Movie\ProductionCompany\Service\Delete $movieProductionCompanyDeleteService;

    private Person\Service\Create $personCreateService;

    private Person\Service\Select $personSelectService;

    private Repository $repository;

    public function __construct(
        Repository $repository,
        Movie\Genre\Service\Create $movieGenreCreateService,
        Movie\Genre\Service\Delete $movieGenreDeleteService,
        Movie\ProductionCompany\Service\Create $movieProductionCompanyCreateService,
        Movie\ProductionCompany\Service\Delete $movieProductionCompanyDeleteService,
        Person\Service\Select $personSelectService,
        Person\Service\Create $personCreateService,
        Movie\Cast\Service\Create $movieCastCreateService,
        Movie\Cast\Service\Delete $movieCasteDeleteService,
        Movie\Crew\Service\Create $movieCrewCreateService,
        Movie\Crew\Service\Delete $movieCrewDeleteService,
    ) {
        $this->repository = $repository;
        $this->movieGenreCreateService = $movieGenreCreateService;
        $this->movieGenreDeleteService = $movieGenreDeleteService;
        $this->movieProductionCompanyCreateService = $movieProductionCompanyCreateService;
        $this->movieProductionCompanyDeleteService = $movieProductionCompanyDeleteService;
        $this->personSelectService = $personSelectService;
        $this->personCreateService = $personCreateService;
        $this->movieCastCreateService = $movieCastCreateService;
        $this->movieCasteDeleteService = $movieCasteDeleteService;
        $this->movieCrewCreateService = $movieCrewCreateService;
        $this->movieCrewDeleteService = $movieCrewDeleteService;
    }

    public function updateCast(int $movieId, Cast $tmdbCast) : void
    {
        $this->movieCasteDeleteService->deleteByMovieId($movieId);

        foreach ($tmdbCast as $position => $castMember) {
            $person = $this->personSelectService->findByTmdbId($castMember->getPerson()->getTmdbId());

            if ($person === null) {
                $person = $this->personCreateService->create(
                    $castMember->getPerson()->getName(),
                    $castMember->getPerson()->getGender(),
                    $castMember->getPerson()->getKnownForDepartment(),
                    $castMember->getPerson()->getTmdbId(),
                );
            }

            $this->movieCastCreateService->create($movieId, $person->getId(), $castMember->getCharacter(), $position);
        }
    }

    public function updateCrew(int $movieId, Crew $tmdbCrew) : void
    {
        $this->movieCrewDeleteService->deleteByMovieId($movieId);

        foreach ($tmdbCrew as $position => $crewMember) {
            $person = $this->personSelectService->findByTmdbId($crewMember->getPerson()->getTmdbId());

            if ($person === null) {
                $person = $this->personCreateService->create(
                    $crewMember->getPerson()->getName(),
                    $crewMember->getPerson()->getGender(),
                    $crewMember->getPerson()->getKnownForDepartment(),
                    $crewMember->getPerson()->getTmdbId(),
                );
            }

            $this->movieCrewCreateService->create($movieId, $person->getId(), $crewMember->getJob(), $crewMember->getDepartment(), $position);
        }
    }

    public function updateDetails(
        int $id,
        ?string $tagline,
        ?string $overview,
        ?string $originalLanguage,
        ?DateTime $releaseDate,
        ?int $runtime,
        ?float $tmdbVoteAverage,
        ?int $tmdbVoteCount
    ) : Entity {
        return $this->repository->updateDetails($id, $tagline, $overview, $originalLanguage, $releaseDate, $runtime, $tmdbVoteAverage, $tmdbVoteCount);
    }

    public function updateGenres(int $movieId, Genre\EntityList $genres) : void
    {
        $this->movieGenreDeleteService->deleteByMovieId($movieId);

        foreach ($genres as $position => $genre) {
            $this->movieGenreCreateService->create($movieId, $genre->getId(), (int)$position);
        }
    }

    public function updateProductionCompanies(int $movieId, Company\EntityList $genres) : void
    {
        $this->movieProductionCompanyDeleteService->deleteByMovieId($movieId);

        foreach ($genres as $position => $genre) {
            $this->movieProductionCompanyCreateService->create($movieId, $genre->getId(), (int)$position);
        }
    }

    public function updateRating10(int $id, ?int $rating10) : Entity
    {
        return $this->repository->updateRating10($id, $rating10);
    }
}
