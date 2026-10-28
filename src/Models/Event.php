<?php

namespace Karabin\Fabriq\Models;

use Carbon\Carbon;
use Karabin\Fabriq\Database\Factories\EventFactory;
use Karabin\Fabriq\Traits\HasContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasContent, HasFactory;

    protected $fillable = ['start', 'end', 'start_time', 'end_time', 'date', 'title', 'daily_interval'];

    /**
     * @var array
     */
    protected $dates = ['start', 'end'];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    /**
     * Create a new factory.
     */
    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }

    protected function getContentTemplateSlug(): ?string
    {
        return 'event-item';
    }

    /**
     * Set the date attribute.
     *
     * @param  array|null  $value
     */
    public function setDateAttribute($value): void
    {
        $this->attributes['start'] = Carbon::parse($value['start']);
        $this->attributes['end'] = Carbon::parse($value['end']);
    }

    /**
     * Get the title for the event.
     *
     * @return string|null
     */
    public function getTitleAttribute()
    {
        return $this->getFieldContent('sv')->get('title');
    }

    /**
     * Scope events between dates.
     *
     * @param  string  ...$dates
     */
    public function scopeDateRange(Builder $query, ...$dates): Builder
    {
        $startDate = Carbon::parse($dates[0]);
        $endDate = Carbon::parse($dates[1]);

        return $query->where('start', '>=', $startDate)
            ->orWhereNotNull('daily_interval');
    }
}
