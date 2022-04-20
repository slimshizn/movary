<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;

class Create
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function create(string $title, ?int $rating10, ?int $rating5, TraktId $traktId, string $imdbId, int $tmdbId) : Entity
    {
        return $this->repository->create($title, $rating10, $rating5, $traktId, $imdbId, $tmdbId);
    }
}
