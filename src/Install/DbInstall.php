<?php
/**
 * @author Tomáš Vojík <xvojik00@stud.fit.vutbr.cz>, <vojik@wboy.cz>
 */

namespace App\Install;

use App\Core\Info;
use Dibi\Exception;
use Lsr\Core\DB;
use Lsr\Core\Exceptions\CyclicDependencyException;
use Lsr\Core\Migrations\MigrationLoader;
use Lsr\Core\Models\Model;
use Lsr\Exceptions\FileException;
use Nette\Utils\AssertionException;
use ReflectionClass;
use ReflectionException;

/**
 * @version 0.1
 */
class DbInstall implements InstallInterface
{

	/** @var array{definition:string, modifications?:array<string,string[]>}[] */
	public const TABLES = [
	];

	/** @var array<class-string, string> */
	protected static array $classTables = [];

	/**
	 * Install all database tables
	 *
	 * @param bool $fresh
	 *
	 * @return bool
	 */
	public static function install(bool $fresh = false) : bool {
		// Load migration files
		$loader = new MigrationLoader(ROOT.'config/migrations.neon');
		try {
			$loader->load();
		} catch (CyclicDependencyException|FileException|\Nette\Neon\Exception|AssertionException $e) {
			echo "\e[0;31m".$e->getMessage()."\e[m\n".$e->getTraceAsString()."\n";
			return false;
		}

		/** @var array{definition:string, modifications?:array<string,string[]>}[] $tables */
		$tables = array_merge($loader->migrations, self::TABLES);

		try {
			if ($fresh) {
				// Drop all tables in reverse order
				foreach (array_reverse($tables) as $tableName => $definition) {
					if (class_exists($tableName)) {
						$tableName = static::getTableNameFromClass($tableName);
						if ($tableName === null) {
							continue;
						}
					}
					DB::getConnection()->query("DROP TABLE IF EXISTS %n;", $tableName);
				}
			}

			// Create tables
			foreach ($tables as $tableName => $info) {
				if (class_exists($tableName)) {
					$tableName = static::getTableNameFromClass($tableName);
					if ($tableName === null) {
						continue;
					}
				}
				$definition = $info['definition'];
				DB::getConnection()->query("CREATE TABLE IF NOT EXISTS %n $definition", $tableName);
			}

			if (!$fresh) {
				/** @var array<string,string> $tableVersions */
				$tableVersions = (array) Info::get('db_version', []);

				// Update all tables if there have been any changes to the tables
				foreach ($tables as $tableName => $info) {
					if (class_exists($tableName)) {
						$tableName = static::getTableNameFromClass($tableName);
						if ($tableName === null) {
							continue;
						}
					}
					$currTableVersion = $tableVersions[$tableName] ?? '0.0';
					$maxVersion = $currTableVersion;
					foreach ($info['modifications'] ?? [] as $version => $queries) {
						// Check versions
						if (version_compare($currTableVersion, $version) > 0) {
							// Skip if this version have already been processed
							continue;
						}
						if (version_compare($maxVersion, $version) < 0) {
							$maxVersion = $version;
						}

						// Run ALTER TABLE queries for current version
						foreach ($queries as $query) {
							echo 'Altering table: '.$tableName.' - '.$query.PHP_EOL;
							try {
								DB::getConnection()->query("ALTER TABLE %n $query;", $tableName);
							} catch (Exception $e) {
								if ($e->getCode() === 1060 || $e->getCode() === 1061) {
									// Duplicate column <-> already created
									continue;
								}
								throw $e;
							}
						}
					}
					$tableVersions[$tableName] = $maxVersion;
				}

				// Update table version cache
				try {
					Info::set('db_version', $tableVersions);
				} catch (Exception) {
				}
			}
		} catch (Exception $e) {
			echo "\e[0;31m".$e->getMessage()."\e[m\n".$e->getSql()."\n";
			return false;
		}

		return true;
	}

	/**
	 * Get a table name for a Model class
	 *
	 * @param class-string $className
	 *
	 * @return string|null
	 */
	protected static function getTableNameFromClass(string $className) : ?string {
		// Check static cache
		if (isset(static::$classTables[$className])) {
			return static::$classTables[$className];
		}

		// Try to get table name from reflection
		try {
			$reflection = new ReflectionClass($className);
		} catch (ReflectionException) { // @phpstan-ignore-line
			// Class not found
			return null;
		}

		// Check if the class is instance of Model
		while ($parent = $reflection->getParentClass()) {
			if ($parent->getName() === Model::class) {
				// Cache result
				static::$classTables[$className] = $className::TABLE;
				return $className::TABLE;
			}
			$reflection = $parent;
		}

		// Class is not a Model
		return null;
	}

}