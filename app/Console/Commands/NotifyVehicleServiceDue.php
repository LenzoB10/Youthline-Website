<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\VehicleServiceDueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyVehicleServiceDue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vehicles:notify-service-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify Transport Officers about vehicles due for service this month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for vehicles due for service this month...');

        // Get vehicles due for service this month
        $vehiclesDue = Vehicle::getDueThisMonth();

        if ($vehiclesDue->isEmpty()) {
            $this->info('No vehicles are due for service this month.');
            return 0;
        }

        $this->info("Found {$vehiclesDue->count()} vehicle(s) due for service this month:");

        foreach ($vehiclesDue as $vehicle) {
            $nextServiceDate = $vehicle->getNextServiceDate();
            $this->line("- {$vehicle->reg_no} ({$vehicle->make} {$vehicle->model}) - Due: {$nextServiceDate->format('d M Y')}");
        }

        // Get users with 'assign trips' permission (Transport Officers)
        $transportOfficers = User::whereHas('roles.permissions', function ($query) {
            $query->where('name', 'assign trips');
        })->get();

        if ($transportOfficers->isEmpty()) {
            $this->warn('No Transport Officers found to notify.');
            return 0;
        }

        // Send notifications to Transport Officers
        Notification::send($transportOfficers, new VehicleServiceDueNotification($vehiclesDue));

        $this->info("Notifications sent to {$transportOfficers->count()} Transport Officer(s).");

        return 0;
    }
}
