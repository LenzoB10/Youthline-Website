<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function homeVisits(): HasMany
    {
        return $this->hasMany(HomeVisit::class);
    }

    public function repairProjects(): HasMany
    {
        return $this->hasMany(RepairProject::class);
    }
}
