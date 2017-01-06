<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Potsky\Tools;
use Symfony\Component\Console\Input\InputOption;

class FilesCleanCommand extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'files:clean';

	/**
	 * The console command description.
	 *
	 * @var string
	 */

	protected $description = "Clean files according to configuration parameters";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->info( 'Starts at ' . date( 'Y/m/d H:i:s' ) );

		$dry_run    = $this->option( 'dry-run' );
		$prune_only = $this->option( 'prune-only' );
		$users      = Tools::getFilesUsers();

		if ( $dry_run === true )
		{
			$this->warn( 'Running in dry mode' );
		}

		if ( $prune_only === true )
		{
			$this->warn( 'Running in prune only mode' );
		}

		foreach ( $users as $user )
		{
			$this->line( 'User <info>' . $user . '</info>' );


			// Backup data
			if ( $prune_only === false )
			{
				$directory = Tools::ensureBackupDirectory( $user , $dry_run );
				$this->line( '  Backup in directory <info>' . $directory . '</info>' );

				$count     = Tools::backupFiles( $user , $directory , $dry_run );
				$counts    = ( $count > 1 ) ? 's' : '';
				$size      = Tools::dirSize( Tools::getFilesUserBackupDirectory( $user , $directory ) );
				$humanSize = ( $size < 1000 ) ? '0B' : Tools::humanFilesize( $size );
				$this->line( '  <info>' . $count . '</info> file' . $counts . ' moved from user root directory (<info>' . $humanSize . '</info>)' );
			}

			// Prune old backup
			$count  = Tools::removeBackupFiles( $user , Tools::getFilesRetentionDays() , $dry_run );
			$counts = ( $count > 1 ) ? 's' : '';
			$this->line( '  <info>' . $count . '</info> backup folder' . $counts . ' removed' );
		}

		$this->info( 'Finished at ' . date( 'Y/m/d H:i:s' ) );

	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array( 'dry-run' , 'r' , InputOption::VALUE_NONE , 'Dry run: run process but do not write anything' ) ,
			array( 'prune-only' , 'p' , InputOption::VALUE_NONE , 'Prune only: only remove old backup directories' ) ,
		);
	}
}