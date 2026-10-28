<?php

namespace Karabin\Fabriq\Listeners;

use Karabin\Fabriq\Models\Slug;

class UpdateSlugListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if (! $event->model->templateSluggable) {
            return;
        }
        foreach ($event->model->templateSluggable as $fieldKey) {
            if (! $event->content->has($fieldKey)) {
                continue;
            }

            $slug = Slug::firstOrNew([
                'model_id' => $event->model->id,
                'locale' => $event->locale,
                'model_type' => $event->model->getMorphClass(),
            ]);
            $slug->source_string = (string) ($event->content->get($fieldKey) ?: 'no_slug_yet_'.$event->model->getKey());
            $slug->source_key = $fieldKey;
            $slug->locale = $event->locale;
            $slug->model_id = $event->model->id;
            $slug->save();
            $event->model->unsetRelation('slugs');
        }
    }
}
