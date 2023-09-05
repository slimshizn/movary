<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\HttpController\Web\Dto\MoviesRequestDto;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;

class WatchlistRequestMapper
{
    private const DEFAULT_RELEASE_YEAR = null;

    private const DEFAULT_LIMIT = 24;

    private const DEFAULT_PAGE = 1;

    private const DEFAULT_SORT_BY = 'addedAt';

    public function __construct(
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
    ) {
    }

    public function mapRenderPageRequest(Request $request) : MoviesRequestDto
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);

        $getParameters = $request->getGetParameters();

        $searchTerm = $getParameters['search'] ?? null;
        $page = $getParameters['page'] ?? self::DEFAULT_PAGE;
        $limit = $getParameters['limit'] ?? self::DEFAULT_LIMIT;
        $sortBy = $getParameters['sortBy'] ?? self::DEFAULT_SORT_BY;
        $sortOrder = $this->mapSortOrder($getParameters);
        $releaseYear = $getParameters['releaseYear'] ?? self::DEFAULT_RELEASE_YEAR;
        $releaseYear = empty($releaseYear) === false ? Year::createFromString($releaseYear) : null;

        return MoviesRequestDto::createFromParameters(
            $userId,
            $searchTerm,
            (int)$page,
            (int)$limit,
            $sortBy,
            $sortOrder,
            $releaseYear,
        );
    }

    private function mapSortOrder(array $getParameters) : SortOrder
    {
        if (isset($getParameters['sortOrder']) === false) {
            return SortOrder::createDesc();
        }

        return match ($getParameters['sortOrder']) {
            'asc' => SortOrder::createAsc(),
            'desc' => SortOrder::createDesc(),

            default => throw new \RuntimeException('Not supported sort order: ' . $getParameters['sortOrder'])
        };
    }
}
