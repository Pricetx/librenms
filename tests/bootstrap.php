<?php
/**
 * bootstrap.php
 *
 * Initialize the Autoloader and includes for phpunit to be able to run tests
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2016 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

global $config;

$install_dir = realpath(__DIR__ . '/..');

$init_modules = array('web');

if (!getenv('SNMPSIM')) {
    $init_modules[] = 'mocksnmp';
}

if (getenv('DBTEST')) {
    if (!is_file($install_dir . '/config.php')) {
        exec("cp $install_dir/tests/config/config.test.php $install_dir/config.php");
    }
} else {
    $init_modules[] = 'nodb';
}

require $install_dir . '/includes/init.php';
chdir($install_dir);

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_WARNING);


if (getenv('DBTEST')) {
    global $empty_db, $schema;

    $sql_mode = dbFetchCell("SELECT @@global.sql_mode as sql_mode");
    $empty_db = (dbFetchCell("SELECT count(*) FROM `information_schema`.`tables` WHERE `table_type` = 'BASE TABLE' AND `table_schema` = ?", array($config['db_name'])) == 0);
    dbQuery("SET GLOBAL sql_mode='ONLY_FULL_GROUP_BY,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

    if ($empty_db) {
        $cmd = $config['install_dir'] . '/build-base.php';
    } else {
        $cmd = '/usr/bin/env php ' . $config['install_dir'] . '/includes/sql-schema/update.php';
    }
    exec($cmd, $schema);
}
