<?php

declare(strict_types=1);

namespace Contenir\PageCache\Tests\Unit\Clear;

use Contenir\PageCache\Clear\ClearSigner;
use Contenir\PageCache\Clear\ClearVerifier;
use Contenir\PageCache\Clear\RefusedClear;
use Contenir\PageCache\Tests\TestAsset\FrozenClock;
use Contenir\SiteToken\Replay\InMemoryReplayGuard;
use Contenir\SiteToken\SiteKeys;
use Contenir\SiteToken\TokenSigner;
use Contenir\SiteToken\TokenType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SensitiveParameter;

/**
 * The CMS signing a clear and a site checking it, from the request's Authorization header.
 */
#[Group('unit')]
#[Group('clear')]
final class ClearVerifierTest extends TestCase
{
    private const string TOKEN_ID = '0123456789abcdef0123456789abcdef';

    private FrozenClock $clock;

    /**
     * @return array<string, array{string|null}>
     */
    public static function noBearerTokenProvider(): array
    {
        return [
            'no header'          => [null],
            'an empty header'    => [''],
            'another scheme'     => ['Basic dXNlcjpwYXNz'],
            'the scheme alone'   => ['Bearer'],
            'two words after it' => ['Bearer abc def'],
            'text before it'     => ['x Bearer abc'],
        ];
    }

    private static function keys(): SiteKeys
    {
        return new SiteKeys(
            'https://admin.example.test',
            'https://www.example.test',
            ['2026-10' => 'a-secret-of-at-least-thirty-two-bytes!!'],
            '2026-10',
        );
    }

    #[Test]
    public function acceptsAClearTheCmsSigned(): void
    {
        $request = $this->verifier()->verify("Bearer {$this->signer()->sign('12')}");

        static::assertSame(
            ['12', self::TOKEN_ID, 1_792_236_000, 'all'],
            [$request->subject, $request->tokenId, $request->issuedAt->getTimestamp(), $request->scope],
        );
    }

    #[Test]
    public function readsTheSchemeInAnyCaseAndSpacing(): void
    {
        static::assertSame('all', $this->verifier()->verify("bearer   {$this->signer()->sign('12')}")->scope);
    }

    #[Test]
    public function refusesAClearNamingNoScope(): void
    {
        $none = (new TokenSigner(self::keys(), $this->clock))->sign(new TokenType('contenir-cache+jwt', 1, 60), '12');

        $this->assertRefused(400, 'The clear names no scope this site knows.', "Bearer {$none}");
    }

    #[Test]
    public function refusesAClearOfAScopeThisSiteDoesNotKnow(): void
    {
        $pages = (new TokenSigner(self::keys(), $this->clock))->sign(
            new TokenType('contenir-cache+jwt', 1, 60),
            '12',
            ['scope' => 'pages'],
        );

        $this->assertRefused(400, 'The clear names no scope this site knows.', "Bearer {$pages}");
    }

    #[Test]
    public function refusesAnotherProtocolsToken(): void
    {
        $preview = (new TokenSigner(self::keys(), $this->clock))->sign(
            new TokenType('contenir-preview+jwt', 1, 300),
            '12',
        );

        $this->assertRefused(401, 'The token is not signed the way this site expects.', "Bearer {$preview}");
    }

    #[Test]
    #[DataProvider('noBearerTokenProvider')]
    public function refusesARequestCarryingNoBearerToken(#[SensitiveParameter] ?string $authorization): void
    {
        $this->assertRefused(400, 'No token was sent.', $authorization);
    }

    #[Test]
    public function refusesATokenOlderThanAMinuteAndTheClocksLeeway(): void
    {
        $header = "Bearer {$this->signer()->sign('12')}";
        $this->clock->advance(91);

        $this->assertRefused(401, 'The token has expired.', $header);
    }

    #[Test]
    public function refusesATokenOnceUsed(): void
    {
        $verifier = $this->verifier();
        $header   = "Bearer {$this->signer()->sign('12')}";
        $verifier->verify($header);

        $this->assertRefused(409, 'The token has already been used.', $header, $verifier);
    }

    protected function setUp(): void
    {
        $this->clock = new FrozenClock();
    }

    private function assertRefused(
        int $status,
        string $reason,
        #[SensitiveParameter]
        ?string $authorization,
        ?ClearVerifier $verifier = null,
    ): void {
        try {
            ($verifier ?? $this->verifier())->verify($authorization);
            static::fail('The clear was accepted.');
        } catch (RefusedClear $refused) {
            static::assertSame([$status, $status, $reason], [
                $refused->status,
                $refused->getCode(),
                $refused->getMessage(),
            ]);
        }
    }

    private function signer(): ClearSigner
    {
        return new ClearSigner(self::keys(), $this->clock, static fn(): string => self::TOKEN_ID);
    }

    private function verifier(): ClearVerifier
    {
        return new ClearVerifier(self::keys(), $this->clock, new InMemoryReplayGuard($this->clock));
    }
}
