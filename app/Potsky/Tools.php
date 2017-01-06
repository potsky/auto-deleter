<?php

namespace Potsky;


use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Tools
{
	const BACKUP_DATE_FORMAT = "Ymd_His";
	const BACKUP_DATE_REGEXP = "[0-9]{8}_[0-9]{6}";

	/**
	 * @return int
	 */
	public static function getLaravelMajorVersion()
	{
		$versions = explode( '.' , self::getLaravelVersion() , 1 );

		return @(int)$versions[ 0 ];
	}

	/**
	 * @return string
	 */
	public static function getLaravelVersion()
	{
		$laravel = app();

		return strval( $laravel::VERSION );
	}

	/**
	 * @return bool
	 */
	public static function isLaravel5()
	{
		return ( self::getLaravelMajorVersion() === 5 );
	}

	/**
	 * @return mixed|string
	 */
	public static function getLogPath()
	{
		$log_path = getenv( 'FILES_LOG_FILE_NAME' );
		$log_path = realpath( str_replace( '%STORAGE%' , storage_path() , $log_path ) );

		return $log_path;
	}

	/**
	 * @return string
	 */
	public static function getFilesCron()
	{
		return getenv( 'FILES_CRON' );
	}

	/**
	 * @return string
	 */
	public static function getFilesUserHomeDirectory()
	{
		return getenv( 'FILES_USER_HOME_DIRECTORY' );
	}

	/**
	 * @return string
	 */
	public static function getFilesBackupDirectoryName()
	{
		return str_replace( '%DATE%' , date( self::BACKUP_DATE_FORMAT ) , self::getFilesBackupDirectoryModel() );
	}

	/**
	 * @return string
	 */
	public static function getFilesRetentionDays()
	{
		return getenv( 'FILES_RETENTION_DAYS' );
	}

	/**
	 * @return string
	 */
	private static function getFilesBackupDirectoryModel()
	{
		return getenv( 'FILES_BACKUP_DIRECTORY_NAME' );
	}

	/**
	 * @return array
	 */
	public static function getFilesUserHomeBlacklist()
	{
		$blacklist = explode( ',' , getenv( 'FILES_USER_HOME_BLACKLIST' ) );

		foreach ( $blacklist as $key => $value )
		{
			$blacklist[ $key ] = trim( $value );
		}

		return $blacklist;
	}

	/**
	 * @return array
	 */
	public static function getFilesBlacklist()
	{
		$blacklist = explode( ',' , getenv( 'FILES_BLACKLIST' ) );

		foreach ( $blacklist as $key => $value )
		{
			$blacklist[ $key ] = trim( $value );
		}

		return $blacklist;
	}

	/**
	 * @return array
	 */
	public static function getFilesUsers()
	{
		chdir( self::getFilesUserHomeDirectory() );

		$users     = array();
		$files     = glob( '*' );
		$blacklist = self::getFilesUserHomeBlacklist();

		foreach ( $files as $file )
		{
			if ( in_array( $file , $blacklist ) )
			{
				continue;
			}

			if ( is_dir( $file ) )
			{
				$users[] = $file;
			}
		}

		return $users;
	}

	/**
	 * @param string  $user
	 * @param boolean $dry
	 *
	 * @return string
	 */
	public static function ensureBackupDirectory( $user , $dry )
	{
		chdir( self::getFilesUserDirectory( $user ) );

		$directory = self::getFilesBackupDirectoryName();

		if ( $dry === false )
		{
			mkdir( $directory );
		}

		return $directory;
	}


	/**
	 * @param $user
	 *
	 * @return string
	 */
	public static function getFilesUserDirectory( $user )
	{
		return self::getFilesUserHomeDirectory() . DIRECTORY_SEPARATOR . $user;
	}

	/**
	 * @param $user
	 * @param $directory
	 *
	 * @return string
	 */
	public static function getFilesUserBackupDirectory( $user , $directory )
	{
		return self::getFilesUserDirectory( $user ) . DIRECTORY_SEPARATOR . $directory;
	}

	/**
	 * Move all non blacklisted files from the user root directory to the provided directory
	 *
	 * @param string  $user
	 * @param string  $directory
	 * @param boolean $dry
	 *
	 * @return int The count of root files/directories moved from root
	 */
	public static function backupFiles( $user , $directory , $dry )
	{
		chdir( self::getFilesUserDirectory( $user ) );

		$count              = 0;
		$backup_directories = glob( str_replace( '%DATE%' , '*' , self::getFilesBackupDirectoryModel() ) );
		$files              = glob( '*' );
		$blacklist          = self::getFilesBlacklist();

		foreach ( $files as $file )
		{
			if ( in_array( $file , $backup_directories ) )
			{
				continue;
			}

			$ignore = false;

			foreach ( $blacklist as $b )
			{
				if ( ! ( strpos( $file , $b ) === false ) )
				{
					$ignore = true;
					break;
				}
			}

			if ( $ignore === true )
			{
				continue;
			}

			if ( $dry === false )
			{
				rename( $file , $directory . DIRECTORY_SEPARATOR . $file );
			}

			$count++;
		}

		return $count;
	}

	/**
	 * @param string  $user
	 * @param int     $days
	 * @param boolean $dry
	 *
	 * @return int
	 */
	public static function removeBackupFiles( $user , $days , $dry )
	{
		chdir( self::getFilesUserDirectory( $user ) );

		$count              = 0;
		$backup_directories = glob( str_replace( '%DATE%' , '*' , self::getFilesBackupDirectoryModel() ) );

		foreach ( $backup_directories as $backup_directory )
		{
			$backup_date = self::getBackupFileDate( $backup_directory );

			// Should not happen
			if ( is_null( $backup_date ) )
			{
				continue;
			}

			if ( self::isDateOlderThanDays( $backup_date , $days ) )
			{
				if ( $dry === false )
				{
					self::delTree( $backup_directory );
				}
				$count++;
			}

		}

		return $count;
	}


	/**
	 * @param $directory
	 *
	 * @return int
	 */
	public static function dirSize( $directory )
	{
		$size = 0;

		try
		{
			/** @var \SplFileInfo $file */
			foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ) ) as $file )
			{
				$size += $file->getSize();
			}
		}
		catch ( Exception $e )
		{
			$size = -1;
		}

		return $size;
	}


	/**
	 * @param $dir
	 *
	 * @return bool
	 */
	private static function delTree( $dir )
	{
		$files = array_diff( scandir( $dir ) , array( '.' , '..' ) );
		foreach ( $files as $file )
		{
			( is_dir( "$dir/$file" ) ) ? self::delTree( "$dir/$file" ) : unlink( "$dir/$file" );
		}

		return rmdir( $dir );
	}

	/**
	 * @param     $bytes
	 * @param int $decimals
	 *
	 * @return string
	 */
	public static function humanFilesize( $bytes , $decimals = 2 )
	{
		$sz     = 'BKMGTP';
		$factor = (int)floor( ( strlen( $bytes ) - 1 ) / 3 );

		return sprintf( "%.{$decimals}f" , $bytes / pow( 1024 , $factor ) ) . @$sz[ $factor ];
	}


	/**
	 * @param \DateTime $date
	 * @param int       $days
	 *
	 * @return bool
	 */
	public static function isDateOlderThanDays( \DateTime $date , $days )
	{
		$now = new \DateTime();

		return ( $now->diff( $date )->format( '%a' ) >= $days );
	}


	/**
	 * Return the date of a backup file
	 *
	 * @param string $file a backup file path
	 *
	 * @return \DateTime|null
	 */
	private static function getBackupFileDate( $file )
	{
		$matches = array();

		if ( preg_match( '/^(.*)(' . self::BACKUP_DATE_REGEXP . ')(.*)$/' , $file , $matches ) === 1 )
		{
			return \DateTime::createFromFormat( self::BACKUP_DATE_FORMAT , $matches[ 2 ] );
		}
		// @codeCoverageIgnoreStart
		// Cannot happen because of glob but safer
		else
		{
			return null;
		}
		// @codeCoverageIgnoreEnd
	}


}