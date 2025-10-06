<?php
namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityTrait
{
    use LogsActivity;

    /**
     * This trait must be included in all the models that you want to log.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty() // only logs changed attributes on update
            ->dontSubmitEmptyLogs(); // skips empty logs
    }
}
