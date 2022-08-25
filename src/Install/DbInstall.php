<?php
/**
 * @author Tomáš Vojík <xvojik00@stud.fit.vutbr.cz>, <vojik@wboy.cz>
 */

namespace App\Install;

use App\Core\Info;
use App\Models\Transaction;
use Dibi\DriverException;
use Dibi\Exception;
use Lsr\Core\DB;

/**
 * @version 0.1
 */
class DbInstall implements InstallInterface
{

	/** @var array{definition:string, modifications:array<string,string[]>}[] */
	public const TABLES = [
		'page_info' => [
			'definition'    => "(
				`key` varchar(30) NOT NULL DEFAULT '',
				`value` text DEFAULT NULL,
				PRIMARY KEY (`key`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
			'modifications' => [],
		],
	];

	/**
	 * Install all database tables
	 *
	 * @param bool $fresh
	 *
	 * @return bool
	 */
	public static function install(bool $fresh = false) : bool {
		try {
			if ($fresh) {
				foreach (array_reverse(self::TABLES) as $tableName => $definition) {
					DB::getConnection()->query("DROP TABLE IF EXISTS %n;", $tableName);
				}
			}

			foreach (self::TABLES as $tableName => $info) {
				$definition = $info['definition'];
				DB::getConnection()->query("CREATE TABLE IF NOT EXISTS %n $definition", $tableName);
			}
			if (!$fresh) {
				try {
					$currVersion = Info::get('db_version', 0.0);
				} catch (DriverException) {
					$currVersion = 0.0;
				}

				$maxVersion = $currVersion;
				foreach (self::TABLES as $tableName => $info) {
					foreach ($info['modifications'] as $version => $queries) {
						$version = (float) $version;
						if ($version <= $currVersion) {
							continue;
						}
						if ($version > $maxVersion) {
							$maxVersion = $version;
						}
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
				}
				try {
					Info::set('db_version', $maxVersion);
				} catch (Exception) {
				}
			}
		} catch (Exception $e) {
			echo $e->getCode().' - '.$e->getMessage().PHP_EOL.$e->getSql().PHP_EOL;
			return false;
		}

		return true;
	}

}