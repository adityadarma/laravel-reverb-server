<?php

namespace App\Observers;

use App\Models\App;
use App\Models\AuditLog;

class AppObserver
{
    /**
     * Fields to exclude from diff (sensitive or irrelevant).
     */
    private array $hidden = ['key', 'secret', 'updated_at'];

    public function created(App $app): void
    {
        AuditLog::record('created', $app, [], $this->sanitize($app->getAttributes()));
    }

    public function updated(App $app): void
    {
        $dirty = $app->getDirty();

        // Skip if only timestamps changed
        $relevant = array_diff_key($dirty, array_flip(['updated_at']));
        if (empty($relevant)) {
            return;
        }

        $old = array_intersect_key($app->getOriginal(), $relevant);
        $new = array_intersect_key($app->getAttributes(), $relevant);

        AuditLog::record('updated', $app, $this->sanitize($old), $this->sanitize($new));
    }

    public function deleted(App $app): void
    {
        AuditLog::record('deleted', $app);
    }

    private function sanitize(array $values): array
    {
        return array_diff_key($values, array_flip($this->hidden));
    }
}
