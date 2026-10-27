<?php

declare(strict_types=1);

namespace Contenir\PageCache\Clear;

use Contenir\SiteToken\RefusedToken;
use Contenir\SiteToken\Replay\ReplayGuardInterface;
use Contenir\SiteToken\SiteKeys;
use Contenir\SiteToken\TokenVerifier;
use Psr\Clock\ClockInterface;
use SensitiveParameter;

use function preg_match;

/**
 * A site's side: checks the token in a clear's `Authorization: Bearer` header, as contenir-site-token
 * checks every token, then that it names a scope this site knows.
 *
 * @api
 */
final readonly class ClearVerifier
{
    private TokenVerifier $verifier;

    public function __construct(SiteKeys $keys, ClockInterface $clock, ReplayGuardInterface $replay)
    {
        $this->verifier = new TokenVerifier($keys, $clock, $replay);
    }

    /**
     * @param string|null $authorization The request's Authorization header
     *
     * @throws RefusedClear
     */
    public function verify(#[SensitiveParameter] ?string $authorization): ClearRequest
    {
        $match = [];
        $token = 1 === preg_match('/^Bearer +(\S+)\z/i', (string) $authorization, $match) ? $match[1] ?? null : null;

        try {
            $verified = $this->verifier->verify(ClearProtocol::tokenType(), $token);
        } catch (RefusedToken $refused) {
            throw RefusedClear::because($refused->refusal);
        }

        if (ClearProtocol::SCOPE_ALL !== ($verified->claims[ClearProtocol::SCOPE_CLAIM] ?? null)) {
            throw RefusedClear::unknownScope();
        }

        return new ClearRequest($verified->subject, $verified->tokenId, $verified->issuedAt, ClearProtocol::SCOPE_ALL);
    }
}
