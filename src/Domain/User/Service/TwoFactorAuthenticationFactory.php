<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Service\ServerSettings;
use OTPHP\Factory;
use OTPHP\OTPInterface;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class TwoFactorAuthenticationFactory
{
    private const SECRET_LENGTH = 32;

    private const REGENERATION_TIME = 30;

    private const DIGEST_ALGORITHM = 'sha1';

    private const DIGITS = 6;

    public function __construct(
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function createTotp(string $userName) : TOTP
    {
        $secret = $this->generateSecret();
        $issuer = $this->serverSettings->getTotpIssuer();

        if ($secret === '') {
            throw new \RuntimeException('Secret must not be empty string');
        }
        if ($userName === '') {
            throw new \RuntimeException('Username must not be empty string');
        }
        if ($issuer === '') {
            throw new \RuntimeException('Issuer must not be empty string');
        }

        $totp = TOTP::createFromSecret($secret);
        $totp->setPeriod(self::REGENERATION_TIME);
        $totp->setDigest(self::DIGEST_ALGORITHM);
        $totp->setDigits(self::DIGITS);
        $totp->setLabel($userName);
        $totp->setIssuer($issuer);

        return $totp;
    }

    private function generateSecret() : string
    {
        return Base32::encodeUpper(random_bytes(self::SECRET_LENGTH));
    }

    public function createOtpFromProvisioningUri(string $totpUri) : OTPInterface
    {
        if ($totpUri === '') {
            throw new \RuntimeException('Totp uri not valid because it is empty');
        }

        return Factory::loadFromProvisioningUri($totpUri);
    }
}
