<?php namespace Cviebrock\EloquentSluggable;

use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SluggableObserver
 *
 * @package Cviebrock\EloquentSluggable
 */
class SluggableObserver
{

    /**
     * @var \Cviebrock\EloquentSluggable\Services\SlugService
     */
    private SlugService $slugService;

    /**
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    private Dispatcher $events;

    /**
     * SluggableObserver constructor.
     *
     * @param \Cviebrock\EloquentSluggable\Services\SlugService $slugService
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function __construct(SlugService $slugService, Dispatcher $events)
    {
        $this->slugService = $slugService;
        $this->events = $events;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return boolean
     */
    public function saved(Model $model): bool
    {
        if ($this->generateSlug($model, 'saved')) {
            return $model->saveQuietly();
        }
        return false;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $event
     * @return boolean
     */
    protected function generateSlug(Model $model, string $event): bool
    {
        // If the "slugging" event returns false, abort
        if ($this->fireSluggingEvent($model, $event) === false) {
            return false;
        }
        $wasSlugged = $this->slugService->slug($model);

        $this->fireSluggedEvent($model, $wasSlugged);

        return $wasSlugged;
    }

    /**
     * Fire the namespaced validating event.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $event
     * @return array|null
     */
    protected function fireSluggingEvent(Model $model, string $event): ?array
    {
        return $this->events->until('eloquent.slugging: ' . get_class($model), [$model, $event]);
    }

    /**
     * Fire the namespaced post-validation event.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $status
     * @return void
     */
    protected function fireSluggedEvent(Model $model, string $status): void
    {
        $this->events->dispatch('eloquent.slugged: ' . get_class($model), [$model, $status]);
    }
}
