<?php

declare(strict_types=1);

namespace Contenir\PageCache\Clear;

use Closure;
use Contenir\SiteToken\SiteKeys;
use Contenir\SiteToken\TokenSigner;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;

/**
 * The CMS's side: signs a token asking the site to clear all its caches, for whoever asked.
 *
 * @api
 */
final readonly class ClearSigner
{
    private TokenSigner $signer;

    /**
     * @param (Closure(): string)|null $tokenId Makes each token's id; 128 random bits as hex by default
     */
    public function __construct(SiteKeys $keys, ClockInterface $clock, ?Closure $tokenId = null)
    {
        $this->signer = new TokenSigner($keys, $clock, $tokenId);
    }

    /**
     * @param string $subject Who asked, such as the CMS user saving; for the site's logs only
     *
     * @throws InvalidArgumentException Never: `scope` is not one of the shared claims.
     */
    public function sign(string $subject): string
    {
        return $this->signer->sign(ClearProtocol::tokenType(), $subject, [
            ClearProtocol::SCOPE_CLAIM => ClearProtocol::SCOPE_ALL,
        ]);
    }
}
