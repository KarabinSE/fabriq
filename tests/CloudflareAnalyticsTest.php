<?php

namespace Karabin\Fabriq\Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Karabin\Fabriq\Services\CloudflareAnalytics;

class CloudflareAnalyticsTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_transforms_daily_metrics_and_fills_missing_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-10 12:00:00', 'UTC'));

        Http::fake([
            'https://api.cloudflare.com/client/v4/graphql' => Http::response([
                'data' => [
                    'viewer' => [
                        'accounts' => [[
                            'daily' => [[
                                'dimensions' => ['date' => '2026-01-09'],
                                'sum' => ['visits' => 3],
                                'count' => 4,
                            ]],
                        ]],
                    ],
                ],
            ]),
        ]);

        $analytics = new CloudflareAnalytics;

        $this->assertSame([
            ['date' => '2026-01-03', 'visits' => 0, 'pageViews' => 0],
            ['date' => '2026-01-04', 'visits' => 0, 'pageViews' => 0],
            ['date' => '2026-01-05', 'visits' => 0, 'pageViews' => 0],
            ['date' => '2026-01-06', 'visits' => 0, 'pageViews' => 0],
            ['date' => '2026-01-07', 'visits' => 0, 'pageViews' => 0],
            ['date' => '2026-01-08', 'visits' => 0, 'pageViews' => 0],
            ['date' => '2026-01-09', 'visits' => 3, 'pageViews' => 4],
            ['date' => '2026-01-10', 'visits' => 0, 'pageViews' => 0],
        ], $analytics->getAnalytics('7d')['daily']);
    }

    public function test_it_transforms_referers_and_paths_without_dropping_rows(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-17 12:00:00', 'UTC'));

        Http::fake([
            'https://api.cloudflare.com/client/v4/graphql' => Http::response([
                'data' => [
                    'viewer' => [
                        'accounts' => [[
                            'referers' => [
                                [
                                    'dimensions' => ['refererHost' => ''],
                                    'sum' => ['visits' => 11],
                                ],
                                [
                                    'dimensions' => ['refererHost' => 'googleads.g.doubleclick.net'],
                                    'sum' => ['visits' => 4],
                                ],
                            ],
                            'paths' => [
                                [
                                    'dimensions' => ['requestPath' => '/integrationer/fortnox'],
                                    'sum' => ['visits' => 15],
                                ],
                                [
                                    'dimensions' => ['requestPath' => '/case/watt-a-day'],
                                    'sum' => ['visits' => 0],
                                ],
                            ],
                            'countries' => [[
                                'dimensions' => ['countryName' => 'SE'],
                                'sum' => ['visits' => 7],
                            ]],
                        ]],
                    ],
                ],
            ]),
        ]);

        $analytics = new CloudflareAnalytics;
        $result = $analytics->getAnalytics('7d');

        $this->assertSame([
            ['name' => '', 'visits' => 11],
            ['name' => 'googleads.g.doubleclick.net', 'visits' => 4],
        ], $result['referers']);
        $this->assertSame([
            ['name' => '/integrationer/fortnox', 'visits' => 15],
            ['name' => '/case/watt-a-day', 'visits' => 0],
        ], $result['paths']);
        $this->assertSame([['code' => 'SE', 'visits' => 7]], $result['countries']);
    }

    public function test_it_reports_graphql_errors(): void
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/graphql' => Http::response([
                'errors' => [['message' => 'Invalid token'], ['message' => 'Access denied']],
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cloudflare GraphQL error: Invalid token, Access denied');

        (new CloudflareAnalytics)->getAnalytics();
    }

    public function test_it_rejects_an_invalid_graphql_response(): void
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/graphql' => Http::response('not json'),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Cloudflare GraphQL response');

        (new CloudflareAnalytics)->getAnalytics();
    }

    public function test_it_rejects_invalid_graphql_errors(): void
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/graphql' => Http::response(['errors' => 'invalid']),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Cloudflare GraphQL errors');

        (new CloudflareAnalytics)->getAnalytics();
    }

    public function test_it_rejects_invalid_countries(): void
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/graphql' => Http::response([
                'data' => ['viewer' => ['accounts' => [['countries' => 'invalid']]]],
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid Cloudflare GraphQL countries');

        (new CloudflareAnalytics)->getAnalytics();
    }
}
