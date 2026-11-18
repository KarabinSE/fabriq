<?php

namespace Karabin\Fabriq\Search;

use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Revolution\Google\SearchConsole\Query\AbstractQuery;

class TopSearchTermsQuery extends AbstractQuery
{
    public function init(): void
    {
        $endDate = now();

        $this->setStartDate($endDate->copy()->subDays(28)->toDateString());
        $this->setEndDate($endDate->toDateString());
        $this->setDimensions(['query']);
        $this->setRowLimit(15);
        $this->setDataState(SearchAnalyticsQueryRequest::DATA_STATE_FINAL);
    }
}
