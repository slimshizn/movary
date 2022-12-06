<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Cast;

use Doctrine\DBAL\Connection;

class CastRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(int $movieId, int $personId, string $character, int $position) : void
    {
        $this->dbConnection->insert(
            'movie_cast',
            [
                'movie_id' => $movieId,
                'person_id' => $personId,
                'character_name' => $character,
                'position' => $position,
            ],
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_cast', ['movie_id' => $movieId]);
    }

    public function findByMovieId(int $movieId) : CastEntityList
    {
        $data = $this->dbConnection->fetchAllAssociative('SELECT * FROM `movie_cast` WHERE movie_id = ?', [$movieId]);

        return CastEntityList::createFromArray($data);
    }
}