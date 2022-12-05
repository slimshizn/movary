<?php declare(strict_types=1);

namespace Movary\Domain\Company;

use Doctrine\DBAL\Connection;
use RuntimeException;

class CompanyRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(string $name, ?string $originCountry, int $tmdbId) : CompanyEntity
    {
        $this->dbConnection->insert(
            'company',
            [
                'name' => $name,
                'origin_country' => $originCountry,
                'tmdb_id' => $tmdbId,
            ],
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function delete(int $tmdbId) : void
    {
        $this->dbConnection->delete('company', ['tmdb_id' => $tmdbId]);
    }

    public function findByNameAndOriginCountry(string $name, ?string $originCountry) : ?CompanyEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE name = ? AND origin_country = ?', [$name, $originCountry]);

        if ($data === false) {
            return null;
        }

        return CompanyEntity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?CompanyEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return CompanyEntity::createFromArray($data);
    }

    public function update(int $id, string $name, ?string $originCountry) : CompanyEntity
    {
        $this->dbConnection->update(
            'company',
            [
                'name' => $name,
                'origin_country' => $originCountry,
            ],
            [
                'id' => $id,
            ],
        );

        return $this->fetchById($id);
    }

    private function fetchById(int $id) : CompanyEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No company found by id: ' . $id);
        }

        return CompanyEntity::createFromArray($data);
    }
}
