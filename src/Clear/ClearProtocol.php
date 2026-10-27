<?php

declare(strict_types=1);

namespace Contenir\PageCache\Clear;

use Contenir\SiteToken\TokenType;

use function rtrim;

/**
 * How the CMS asks a site to clear its caches: it signs a token of this protocol's type and posts
 * it to the site's fixed endpoint, `{audience}/api/cache`, in an `Authorization: Bearer` header.
 * The site clears its page cache, and the other caches it lists, through their own stores.
 *
 * A token lives a minute at most and is accepted once. Its `scope` is "all", the one scope this
 * version knows; a later version may name pages.
 *
 * @api
 */
final class ClearProtocol
{
    /** Where every site answers a clear; the protocol fixes it. */
    public const string ENDPOINT = '/api/cache';

    /** The `typ` of a clear token's header. */
    public const string TYPE = 'contenir-cache+jwt';

    /** The protocol's version, as a token's `ver` carries it. */
    public const int VERSION = 1;

    /** Seconds a token lives, at most. */
    public const int MAX_LIFETIME = 60;

    /** The largest token a site decodes, in bytes: it travels in a header. */
    public const int MAX_BYTES = 8192;

    /** The claim naming what to clear. */
    public const string SCOPE_CLAIM = 'scope';

    /** Every cache the site clears. */
    public const string SCOPE_ALL = 'all';

    /**
     * The URL the CMS posts a clear to: the site's origin with the protocol's fixed path.
     */
    public static function endpoint(string $audience): string
    {
        return rtrim($audience, characters: '/') . self::ENDPOINT;
    }

    /**
     * The protocol's tokens, as contenir-site-token signs and checks them.
     */
    public static function tokenType(): TokenType
    {
        return new TokenType(self::TYPE, self::VERSION, self::MAX_LIFETIME, self::MAX_BYTES);
    }
}
