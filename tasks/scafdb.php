<?php
namespace Fuel\Tasks;

/**
 * Generate scaffold or model for database tables.
 *
 * @author     Mamoru Otsuka http://madroom-project.blogspot.jp/
 * @copyright  2013 Mamoru Otsuka
 * @license    WTFPL http://sam.zoy.org/wtfpl/COPYING
 */
class Scafdb
{
	private static $ignore_fields = array(
		'id',
		'created_at',
		'updated_at',
	);

	private static $ignore_tables = array(
		'migration',
	);

	public function __construct()
	{
		\Cli::write('Notice:', 'yellow');
		\Cli::write('If you use v1.5+, you can use the task "fromdb" instead of this.', 'yellow');
		\Cli::write('Let\'s try the command "php oil r fromdb:help"', 'yellow');
	}

	/**
	 * Show help.
	 *
	 * Usage (from command line):
	 *
	 * php oil r scafdb
	 */
	public static function run()
	{
		static::help();
	}

	/**
	 * Generate scaffold for a database table.
	 *
	 * Usage (from command line):
	 *
	 * php oil r scafdb:scaf <table_name,table_name...>
	 */
	public static function scaf($tables = '')
	{
		if ( ! strlen($tables))
		{
			\Cli::write("Usage : php oil r scafdb:scaf <table_name,table_name...>");
			exit();
		}

		$tables = explode(',', $tables);

		foreach ($tables as $table)
		{
			\Oil\Generate::$create_files = array();

			$subfolder = \Cli::option('crud') ? 'crud' : 'orm';

			call_user_func(static::is_admin() ?
				'Oil\Generate_Admin::forge' : 'Oil\Generate_Scaffold::forge', static::mk_args($table), $subfolder);
		}
	}

	/**
	 * Generate scaffold for all database tables.
	 *
	 * Usage (from command line):
	 *
	 * php oil r scafdb:scaf_all
	 */
	public static function scaf_all()
	{
		try
		{
			$tables = \DB::list_tables();
		}
		catch (\FuelException $e)
		{
			\Cli::write('PDO driver cannot be used on this method. Please use other drivers.', 'red');
			exit();
		}

		foreach ($tables as $table)
		{
			$table = static::remove_table_prefix($table);

			if (in_array($table, static::$ignore_tables))
			{
				continue;
			}

			static::scaf($table);
		}
	}

	/**
	 * Generate model for a database table.
	 *
	 * Usage (from command line):
	 *
	 * php oil r scafdb:model <table_name,table_name...>
	 */
	public static function model($tables = '')
	{
		if ( ! strlen($tables))
		{
			\Cli::write("Usage : php oil r scafdb:model <table_name,table_name...>");
			exit();
		}

		$tables = explode(',', $tables);

		foreach ($tables as $table)
		{
			\Oil\Generate::$create_files = array();

			call_user_func('Oil\Generate::model', static::mk_args($table));
		}
	}

	/**
	 * Generate model for all database tables.
	 *
	 * Usage (from command line):
	 *
	 * php oil r scafdb:model_all
	 */
	public static function model_all()
	{
		try
		{
			$tables = \DB::list_tables();
		}
		catch (\FuelException $e)
		{
			\Cli::write('PDO driver cannot be used on this method. Please use other drivers.', 'red');
			exit();
		}

		foreach ($tables as $table)
		{
			$table = static::remove_table_prefix($table);

			if (in_array($table, static::$ignore_tables))
			{
				continue;
			}

			static::model($table);
		}
	}

	/**
	 * Show help.
	 *
	 * Usage (from command line):
	 *
	 * php oil r scafdb:help
	 */
	public static function help()
	{
		$output = <<<HELP

Description:
  Generate scaffold or model for exists database tables.
  Database settings must be configured.

Runtime options:
  -f, [--force]    # Overwrite files that already exist
  -a, [--admin]    # Generate admin

Commands:
  php oil r scafdb:scaf <table_name,table_name...>
  php oil r scafdb:scaf_all
  php oil r scafdb:model <table_name,table_name...>
  php oil r scafdb:model_all
  php oil r scafdb:help

HELP;
		\Cli::write($output);
	}

	/*******************************************************
	 * Private Methods
	 ******************************************************/
	private static function mk_args($table)
	{
		try
		{
			$cols = \DB::list_columns($table);
		}
		catch (\Exception $e)
		{
			\Cli::write($e->getMessage(), 'red');
			exit();
		}

		$args = array();
		foreach ($cols as $col)
		{
			if (in_array($col['name'], static::$ignore_fields))
			{
				continue;
			}

			$constraint = ''; //TODO:
			$args[] = $col['name'].':'.$col['data_type'].$constraint;
		}

		array_unshift($args, $table);

		return $args;
	}

	private static function is_admin()
	{
		return \Cli::option('admin') || \Cli::option('a');
	}

	private static function remove_table_prefix($table)
	{
		return preg_replace('/^'.\DB::table_prefix().'/', '', $table);
	}
}

/* End of file tasks/scafdb.php */
