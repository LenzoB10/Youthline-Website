<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsActivityTrait
{
    use LogsActivity;

    /**
     * This trait must be included in all the models that you want to log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty() // only logs changed attributes on update
            ->dontLogEmptyChanges(); // skips empty logs
    }
}
