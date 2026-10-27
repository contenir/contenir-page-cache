<?php

declare(strict_types=1);

namespace Contenir\PageCache\Clear;

use Contenir\SiteToken\Refusal;
use RuntimeException;

/**
 * A clear the site refuses, with the HTTP status to answer and a plain reason, which never names
 * the secret, the token or the values expected.
 *
 * @api
 */
final class RefusedClear extends RuntimeException
{
    /** The status a clear naming a scope this site doesn't know is answered with. */
    public const int UNKNOWN_SCOPE = 400;

    private function __construct(
        string $reason,
        public readonly int $status,
    ) {
        parent::__construct($reason, $status);
    }

    public static function because(Refusal $refusal): self
    {
        return new self($refusal->reason(), $refusal->status());
    }

    public static function unknownScope(): self
    {
        return new self('The clear names no scope this site knows.', self::UNKNOWN_SCOPE);
    }
}
