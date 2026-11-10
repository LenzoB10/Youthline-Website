<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $fillable = [
        'backup_enabled',
        'backup_time',
        'backup_retention_days',
        'monthly_backup_enabled',
        'monthly_backup_retention_months',
    ];

    protected function casts(): array
    {
        return [
            'backup_enabled' => 'boolean',
            'backup_retention_days' => 'integer',
            'monthly_backup_enabled' => 'boolean',
            'monthly_backup_retention_months' => 'integer',
        ];
    }

    public static function defaults(): array
    {
        return [
            'backup_enabled' => true,
            'backup_time' => '01:00',
            'backup_retention_days' => 30,
            'monthly_backup_enabled' => true,
            'monthly_backup_retention_months' => 12,
        ];
    }

    /**
     * Returns the single-row backup settings record, creating it with defaults
     * on first use.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], static::defaults());
    }

    /**
     * Returns the settings record without throwing when the table is not yet
     * available (e.g. before migrations have run). Falls back to defaults.
     */
    public static function safeCurrent(): self
    {
        try {
            return static::current();
        } catch (\Throwable) {
            return (new static)->forceFill(static::defaults());
        }
    }
}
