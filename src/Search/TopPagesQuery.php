<?php

namespace Karabin\Fabriq\Search;

use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Revolution\Google\SearchConsole\Query\AbstractQuery;

class TopPagesQuery extends AbstractQuery
{
    public function init(): void
    {
        $this->setStartDate(now()->subDays(28)->toDateString());
        $this->setEndDate(now()->toDateString());
        $this->setDimensions(['page']);
        $this->setRowLimit(50);
        $this->setDataState(SearchAnalyticsQueryRequest::DATA_STATE_FINAL);
    }
}
