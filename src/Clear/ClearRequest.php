<?php

declare(strict_types=1);

namespace Contenir\PageCache\Clear;

use DateTimeImmutable;

/**
 * A clear the site has accepted: who asked, the token's one-time id, when it was issued, and what
 * to clear.
 *
 * @api
 */
final readonly class ClearRequest
{
    public function __construct(
        public string $subject,
        public string $tokenId,
        public DateTimeImmutable $issuedAt,
        public string $scope,
    ) {}
}
