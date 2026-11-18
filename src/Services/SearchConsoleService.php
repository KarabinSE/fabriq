<?php

namespace Karabin\Fabriq\Services;

use Karabin\Fabriq\Search\TopPagesQuery;
use Karabin\Fabriq\Search\TopSearchTermsQuery;
use Revolution\Google\SearchConsole\Facades\SearchConsole;

class SearchConsoleService
{
    public function getTopPages(string $siteUrl): object
    {
        $query = new TopPagesQuery;

        // Service Account authentication is automatic
        return SearchConsole::query($siteUrl, $query);
    }

    public function getTopSearchTerms(string $siteUrl, string $period): object
    {
        $to = now()->utc();

        $from = match ($period) {
            '7d' => $to->copy()->subDays(7),
            '30d' => $to->copy()->subDays(30),
            '3m' => $to->copy()->subDays(90),
            default => throw new \InvalidArgumentException('Invalid analytics period'),
        };

        $query = new TopSearchTermsQuery;
        $query->setStartDate($from->toDateString());
        $query->setEndDate($to->toDateString());

        return SearchConsole::query($siteUrl, $query);
    }
}
