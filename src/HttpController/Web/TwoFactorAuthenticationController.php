<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\Service\TwoFactorAuthenticationFactory;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class TwoFactorAuthenticationController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly TwoFactorAuthenticationApi $twoFactorAuthenticationApi,
        private readonly TwoFactorAuthenticationFactory $twoFactorAuthenticationFactory,
        private readonly SessionWrapper $sessionWrapper,
    ) {
    }

    public function createTotpUri() : Response
    {
        $currentUserName = $this->authenticationService->getCurrentUser()->getName();
        $totp = $this->twoFactorAuthenticationFactory->createTotp($currentUserName);

        $response = Json::encode([
            'uri' => $totp->getProvisioningUri(),
            'secret' => $totp->getSecret()
        ]);

        return Response::createJson($response);
    }

    public function disableTOTP() : Response
    {
        $this->twoFactorAuthenticationApi->deleteTotp($this->authenticationService->getCurrentUserId());
        $this->sessionWrapper->set('twoFactorAuthenticationDisabled', true);

        return Response::createOk();
    }

    public function enableTOTP(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $requestData = Json::decode($request->getBody());
        $input = (int)$requestData['input'];
        $uri = $requestData['uri'];

        $valid = $this->twoFactorAuthenticationApi->verifyTotpUri($userId, $input, $uri);
        if ($valid === false) {
            return Response::createBadRequest();
        }

        $this->twoFactorAuthenticationApi->updateTotpUri($userId, $uri);
        $this->sessionWrapper->set('twoFactorAuthenticationEnabled', true);

        return Response::createOk();
    }

    public function verifyTotp(Request $request) : Response
    {
        $userTotpInput = $request->getPostParameters()['totpCode'];
        $rememberMe = $this->sessionWrapper->find('rememberMe') ?? false;
        $userId = (int)$this->sessionWrapper->find('totpUserId');

        if ($this->twoFactorAuthenticationApi->verifyTotpUri($userId, (int)$userTotpInput) === false) {
            $this->sessionWrapper->set('invalidTotpCode', true);

            return Response::create(
                StatusCode::createSeeOther(),
                null,
                [Header::createLocation($_SERVER['HTTP_REFERER'])],
            );
        }

        $this->authenticationService->createAuthenticationCookie($userId, $rememberMe);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])],
        );
    }
}
