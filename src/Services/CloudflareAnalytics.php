<?php

namespace Karabin\Fabriq\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

class CloudflareAnalytics
{
    private const ENDPOINT = 'https://api.cloudflare.com/client/v4/graphql';

    public function getAnalytics(string $period = '30d'): array
    {
        $to = now()->utc();

        $from = match ($period) {
            '7d' => $to->copy()->subDays(7),
            '30d' => $to->copy()->subDays(30),
            '3m' => $to->copy()->subDays(90),
            default => throw new \InvalidArgumentException('Invalid analytics period'),
        };

        $query = <<<'GRAPHQL'
        query GetAnalytics(
            $accountTag: string,
            $filter: AccountRumPageloadEventsAdaptiveGroupsFilter_InputObject
        ) {
            viewer {
                accounts(filter: { accountTag: $accountTag }) {

                    daily: rumPageloadEventsAdaptiveGroups(
                        limit: 100
                        filter: $filter
                    ) {
                        dimensions {
                            date
                        }
                        sum {
                            visits
                        }
                        count
                    }

                    total: rumPageloadEventsAdaptiveGroups(
                        limit: 1
                        filter: $filter
                    ) {
                        count
                        sum {
                            visits
                        }
                    }

                    browsers: rumPageloadEventsAdaptiveGroups(
                        limit: 15
                        orderBy: [sum_visits_DESC]
                        filter: $filter
                    ) {
                        dimensions {
                            userAgentBrowser
                        }
                        sum {
                            visits
                        }
                    }

                    operatingSystems: rumPageloadEventsAdaptiveGroups(
                        limit: 15
                        orderBy: [sum_visits_DESC]
                        filter: $filter
                    ) {
                        dimensions {
                            userAgentOS
                        }
                        sum {
                            visits
                        }
                    }

                    devices: rumPageloadEventsAdaptiveGroups(
                        limit: 15
                        orderBy: [sum_visits_DESC]
                        filter: $filter
                    ) {
                        dimensions {
                            deviceType
                        }
                        sum {
                            visits
                        }
                    }

                    countries: rumPageloadEventsAdaptiveGroups(
                        limit: 200
                        orderBy: [sum_visits_DESC]
                        filter: $filter
                    ) {
                        dimensions {
                            countryName
                        }
                        sum {
                            visits
                        }
                    }

                    referers: rumPageloadEventsAdaptiveGroups(
                        limit: 15
                        orderBy: [sum_visits_DESC]
                        filter: $filter
                    ) {
                        dimensions {
                            refererHost
                        }
                        sum {
                            visits
                        }
                    }

                    paths: rumPageloadEventsAdaptiveGroups(
                        limit: 15
                        orderBy: [sum_visits_DESC]
                        filter: $filter
                    ) {
                        dimensions {
                            requestPath
                        }
                        sum {
                            visits
                        }
                    }
                }
            }
        }
        GRAPHQL;

        $response = Http::withToken(config('services.cloudflare.api_token'))
            ->acceptJson()
            ->post(self::ENDPOINT, [
                'query' => $query,

                'variables' => [
                    'accountTag' => config('services.cloudflare.account_id'),

                    'filter' => [
                        'AND' => [
                            [
                                'datetime_geq' => $from->toIso8601ZuluString(),
                                'datetime_leq' => $to->toIso8601ZuluString(),
                            ],
                            [
                                'bot' => 0,
                            ],
                            [
                                'siteTag_in' => [
                                    config('services.cloudflare.analytics_site_tag'),
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $response->throw();

        $json = $response->json();

        if (! is_array($json)) {
            throw new \RuntimeException('Invalid Cloudflare GraphQL response');
        }

        if (! empty($json['errors'])) {
            if (! is_array($json['errors'])) {
                throw new \RuntimeException('Invalid Cloudflare GraphQL errors');
            }

            throw new \RuntimeException(
                'Cloudflare GraphQL error: '.
                collect($json['errors'])
                    ->pluck('message')
                    ->implode(', ')
            );
        }

        $data = $json['data']['viewer']['accounts'][0] ?? [];

        return $this->transform($data, $period, $from, $to);
    }

    private function transform(
        array $data,
        string $period,
        Carbon $from,
        Carbon $to
    ): array {
        $countries = $data['countries'] ?? [];
        if (! is_array($countries)) {
            throw new \RuntimeException('Invalid Cloudflare GraphQL countries');
        }

        return [
            'period' => $period,

            'total' => [
                'visits' => data_get($data, 'total.0.sum.visits', 0),
                'pageViews' => data_get($data, 'total.0.count', 0),
            ],

            'daily' => $this->transformDaily(
                $data['daily'] ?? [],
                $from,
                $to
            ),

            'browsers' => $this->transformBreakdown(
                $data['browsers'] ?? [],
                'userAgentBrowser'
            ),

            'operatingSystems' => $this->transformBreakdown(
                $data['operatingSystems'] ?? [],
                'userAgentOS'
            ),

            'devices' => $this->transformBreakdown(
                $data['devices'] ?? [],
                'deviceType'
            ),

            'referers' => $this->transformBreakdown(
                $data['referers'] ?? [],
                'refererHost'
            ),

            'paths' => $this->transformBreakdown(
                $data['paths'] ?? [],
                'requestPath'
            ),

            'countries' => collect($countries)
                ->map(fn ($row) => [
                    'code' => $row['dimensions']['countryName'],
                    'visits' => $row['sum']['visits'],
                ])
                ->values()
                ->all(),
        ];
    }

    private function transformDaily(array $rows, Carbon $from, Carbon $to): array
    {
        $metricsByDate = collect($rows)
            ->mapWithKeys(fn ($row) => [
                $row['dimensions']['date'] => [
                    'visits' => $row['sum']['visits'] ?? 0,
                    'pageViews' => $row['count'] ?? 0,
                ],
            ]);

        $daily = [];

        foreach (CarbonPeriod::create(
            $from->copy()->startOfDay(),
            '1 day',
            $to->copy()->startOfDay()
        ) as $date) {
            $dateString = $date->toDateString();
            $metrics = $metricsByDate->get($dateString, [
                'visits' => 0,
                'pageViews' => 0,
            ]);

            $daily[] = [
                'date' => $dateString,
                'visits' => $metrics['visits'],
                'pageViews' => $metrics['pageViews'],
            ];
        }

        return $daily;
    }

    private function transformBreakdown(array $rows, string $dimension): array
    {
        return collect($rows)
            ->map(fn ($row) => [
                'name' => $row['dimensions'][$dimension],
                'visits' => $row['sum']['visits'],
            ])
            ->values()
            ->all();
    }
}
