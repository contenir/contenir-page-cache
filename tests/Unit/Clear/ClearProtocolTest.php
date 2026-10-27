<?php

declare(strict_types=1);

namespace Contenir\PageCache\Tests\Unit\Clear;

use Contenir\PageCache\Clear\ClearProtocol;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('clear')]
final class ClearProtocolTest extends TestCase
{
    #[Test]
    public function namesItsTokensLivingAMinuteAndTravellingInAHeader(): void
    {
        $type = ClearProtocol::tokenType();

        static::assertSame(
            ['contenir-cache+jwt', 1, 60, 8192],
            [$type->name, $type->version, $type->maxLifetime, $type->maxBytes],
        );
    }

    #[Test]
    public function postsToTheSitesFixedPath(): void
    {
        static::assertSame(
            ['https://www.example.test/api/cache', 'https://www.example.test/api/cache'],
            [ClearProtocol::endpoint('https://www.example.test'), ClearProtocol::endpoint('https://www.example.test/')],
        );
    }
}
