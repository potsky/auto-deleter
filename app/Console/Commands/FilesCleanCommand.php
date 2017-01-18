<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Potsky\Tools;
use Symfony\Component\Console\Input\InputOption;

class FilesCleanCommand extends Command
{

	private $messages = array();

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
		Tools::sendSlackMessage( 'Auto deleter starts' );

		try
		{

			$dry_run    = $this->option( 'dry-run' );
			$prune_only = $this->option( 'prune-only' );
			$users      = Tools::getFilesUsers();

			$this->info( 'Starts at ' . date( 'Y/m/d H:i:s' ) );


			if ( $dry_run === true )
			{
				$this->warn( 'Running in dry mode' );
			}

			if ( $prune_only === true )
			{
				$this->warn( 'Running in prune only mode' );
			}

			$do = true;

			if ( $this->option( 'force' ) !== true )
			{
				$this->line( '' );
				$do = ( $this->ask( 'Do you want to continue? [yes|no]' ) === 'yes' );
				$this->line( '' );
			}

			if ( $do === true )
			{
				if ( empty( $users ) )
				{
					$this->bag( '<error>No user</error>' );
				}
				else
				{
					foreach ( $users as $user )
					{
						$this->bag( '- User <info>' . $user . '</info>' );

						// Backup data
						if ( $prune_only === false )
						{
							$directory = Tools::ensureBackupDirectory( $user , $dry_run );
							$this->bag( '  Backup in directory <info>' . $directory . '</info>' );

							$count     = Tools::backupFiles( $user , $directory , $dry_run );
							$counts    = ( $count > 1 ) ? 's' : '';
							$size      = Tools::dirSize( Tools::getFilesUserBackupDirectory( $user , $directory ) );
							$humanSize = ( $size < 1000 ) ? '0B' : Tools::humanFilesize( $size );
							$this->bag( '  <info>' . $count . '</info> file' . $counts . ' moved from user root directory (<info>' . $humanSize . '</info>)' );
						}

						// Prune old backup
						$count  = Tools::removeBackupFiles( $user , Tools::getFilesRetentionDays() , $dry_run );
						$counts = ( $count > 1 ) ? 's' : '';
						$this->bag( '  <info>' . $count . '</info> backup folder' . $counts . ' removed' );
					}

				}
			}

			$this->info( 'Finished at ' . date( 'Y/m/d H:i:s' ) );

		}
		catch ( \Exception $fff )
		{
			$this->bag( '<error>' . $fff->getMessage() . '</error>' );
		}

		Tools::sendSlackMessage( $this->messages );
	}

	/**
	 * @param string $message
	 */
	private function bag( $message )
	{
		$this->line( $message );
		$this->messages[] = str_replace( array( '<info>' , '</info>' , '<error>' , '</error>' ) , array( '*' , '*' , '`' , '`' ) , $message );
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
			array( 'force' , 'f' , InputOption::VALUE_NONE , 'Force: do not ask human confirmation' ) ,
			array( 'prune-only' , 'p' , InputOption::VALUE_NONE , 'Prune only: only remove old backup directories' ) ,
		);
	}
}