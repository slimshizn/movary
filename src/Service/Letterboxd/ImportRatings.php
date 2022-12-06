<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;
use Movary\Api\Letterboxd\LetterboxdWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Service\Letterboxd\ValueObject\CsvLineRating;
use Movary\ValueObject\Job;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ImportRatings
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly LetterboxdWebScrapper $webScrapper,
        private readonly LoggerInterface $logger,
        private readonly ImportRatingsFileValidator $fileValidator,
    ) {
    }

    public function execute(int $userId, string $ratingsCsvPath, bool $verbose = false, bool $overwriteExistingData = false) : void
    {
        $this->ensureValidCsvFile($ratingsCsvPath);

        $ratings = Reader::createFromPath($ratingsCsvPath);
        $ratings->setHeaderOffset(0);

        foreach ($ratings->getRecords() as $rating) {
            $csvLineRating = CsvLineRating::createFromCsvLine($rating);

            $movie = $this->findMovieByLetterboxdUri($csvLineRating->getLetterboxdUri());

            if ($movie === null) {
                $this->logger->info('Ignoring rating for movie which is not in history: ' . $csvLineRating->getName());

                continue;
            }

            $userRating = $csvLineRating->getRating() * 2;
            $personalRating = PersonalRating::create((int)$userRating);

            if ($overwriteExistingData === false && $this->movieApi->findUserRating($movie->getId(), $userId) !== null) {
                $this->logger->info('Ignoring rating for movie which already has one: ' . $movie->getTitle());

                $this->outputMessage("Ignoring {$movie->getTitle()} rating: " . $personalRating . PHP_EOL, $verbose);

                continue;
            }

            $this->logger->info("Updating {$movie->getTitle()} with rating: " . $personalRating);
            $this->outputMessage("Updating {$movie->getTitle()} with rating: " . $personalRating . PHP_EOL, $verbose);

            $this->movieApi->updateUserRating($movie->getId(), $userId, $personalRating);
        }

        unlink($ratingsCsvPath);
    }

    public function executeJob(Job $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId, $job->getParameters()['importFile']);
    }

    public function findMovieByLetterboxdUri(string $letterboxdUri) : ?MovieEntity
    {
        $letterboxdId = basename($letterboxdUri);
        $movie = $this->movieApi->findByLetterboxdId($letterboxdId);

        if ($movie === null) {
            $tmdbId = $this->webScrapper->getProviderTmdbId($letterboxdUri);

            $movie = $this->movieApi->findByTmdbId($tmdbId);
            if ($movie === null) {
                return null;
            }

            $this->movieApi->updateLetterboxdId($movie->getId(), $letterboxdId);
        }

        return $movie;
    }

    private function ensureValidCsvFile(string $ratingsCsvPath) : void
    {
        if ($this->fileValidator->isValid($ratingsCsvPath) === false) {
            throw new RuntimeException('Invalid letterboxed ratings csv file.');
        }
    }

    private function outputMessage(string $message, bool $verbose) : void
    {
        if ($verbose === true) {
            echo $message;
        }
    }
}