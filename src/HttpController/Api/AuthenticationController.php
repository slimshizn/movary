<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\Exception\InvalidCredentials;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\CreateUserController;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class AuthenticationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createToken(Request $request) : Response
    {
        $tokenRequestBody = Json::decode($request->getBody());

        if ($tokenRequestBody['email'] === null || $tokenRequestBody['password'] === null) {
            return Response::createBadRequest('Email or password is missing');
        }

        $headers = $request->getHeaders();
        if (isset($headers['X-Movary-Client']) === false) {
            return Response::createBadRequest('Missing request header X-Movary-Client');
        }

        $requestClient = $headers['X-Movary-Client'];
        $totpCode = $tokenRequestBody['totpCode'] ?? 0;
        $rememberMe = $tokenRequestBody['rememberMe'] ?? false;

        try {
            $authToken = $this->authenticationService->login(
                $tokenRequestBody['email'],
                $tokenRequestBody['password'],
                (bool)$rememberMe,
                $requestClient,
                $request->getUserAgent(),
                (int)$totpCode,
            );
        } catch (InvalidCredentials $e) {
            return Response::createBadRequest(Json::encode([
                'error' => basename(str_replace('\\', '/', get_class($e))),
                'message' => $e->getMessage()
            ]));
        }

        if ($requestClient !== CreateUserController::MOVARY_WEB_CLIENT) {
            $user = $this->userApi->findByToken($authToken);

            return Response::createJson(
                Json::encode([
                    'userId' => $user?->getId(),
                    'authToken' => $authToken
                ]),
            );
        }

        $redirect = empty($tokenRequestBody['redirect']) === true ? null : $tokenRequestBody['redirect'];
        $target = $redirect ?? $_SERVER['HTTP_REFERER'];

        $urlParts = parse_url($target);
        if (is_array($urlParts) === false) {
            $urlParts = ['path' => '/'];
        }
        $query = $urlParts['query'] ?? '';

        /* @phpstan-ignore-next-line */
        $targetRelativeUrl = $urlParts['path'] . $query;

        return Response::createSeeOther($targetRelativeUrl);
    }

    public function destroyToken(Request $request) : Response
    {
        if($this->authenticationService->isUserAuthenticated() === true) {
            $this->authenticationService->logout();
        } 
        else {
            $apiToken = $this->authenticationService->getUserIdByApiToken($request);
            if($apiToken === null) {
                return Response::createForbidden();
            }
            $this->userApi->deleteApiToken($apiToken);
        }
        return Response::createOk();
    }
}
