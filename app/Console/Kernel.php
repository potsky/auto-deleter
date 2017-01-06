<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Potsky\Tools;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\FilesCleanCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
		/**
		 * Cront tab configuration
		 *
		 *    * * * * * * command to be executed
		 *    - - - - - -
		 *    | | | | | |
		 *    | | | | | --- Year (optional)
		 *    | | | | ----- Day of week (0 - 7) (Sunday=0 or 7)
		 *    | | | ------- Month (1 - 12)
		 *    | | --------- Day of month (1 - 31)
		 *    | ----------- Hour (0 - 23)
		 *    ------------- Minute (0 - 59)
		 */
		$log_path = Tools::getLogPath();

		// Weekly schedules
		$schedule->command('files:clean')
			->cron( Tools::getFilesCron() )
			->sendOutputTo( $log_path );

	}
}
