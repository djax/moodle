<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PHPUnit bootstrap function
 *
 * Note: these functions must be self contained and must not rely on any library or include
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('PHPUNIT_EXITCODE_PHPUNITMISSING', 129);
define('PHPUNIT_EXITCODE_PHPUNITWRONG', 130);
define('PHPUNIT_EXITCODE_PHPUNITEXTMISSING', 131);
define('PHPUNIT_EXITCODE_CONFIGERROR', 135);
define('PHPUNIT_EXITCODE_CONFIGWARNING', 136);
define('PHPUNIT_EXITCODE_INSTALL', 140);
define('PHPUNIT_EXITCODE_REINSTALL', 141);

/**
 * Print error and stop execution
 * @param int $errorcode The exit error code
 * @param string $text An error message to display
 * @return void stops code execution with error code
 */
function phpunit_bootstrap_error($errorcode, $text = '') {
    switch ($errorcode) {
        case 0:
            // this is not an error, just print information and exit
            break;
        case 1:
            $text = 'Error: '.$text;
            break;
        case PHPUNIT_EXITCODE_PHPUNITMISSING:
            $text = "Moodle can not find PHPUnit PEAR library";
            break;
        case PHPUNIT_EXITCODE_PHPUNITWRONG:
            $text = 'Moodle requires PHPUnit 3.6.x, '.$text.' is not compatible';
            break;
        case PHPUNIT_EXITCODE_PHPUNITEXTMISSING:
            $text = 'Moodle can not find required PHPUnit extension '.$text;
            break;
        case PHPUNIT_EXITCODE_CONFIGERROR:
            $text = "Moodle PHPUnit environment configuration error:\n".$text;
            break;
        case PHPUNIT_EXITCODE_CONFIGWARNING:
            $text = "Moodle PHPUnit environment configuration warning:\n".$text;
            break;
        case PHPUNIT_EXITCODE_INSTALL:
            $text = "Moodle PHPUnit environment is not initialised, please use:\n php admin/tool/phpunit/cli/init.php";
            break;
        case PHPUNIT_EXITCODE_REINSTALL:
            $text = "Moodle PHPUnit environment was initialised for different version, please use:\n php admin/tool/phpunit/cli/init.php";
            break;
        default:
            $text = empty($text) ? '' : ': '.$text;
            $text = 'Unknown error '.$errorcode.$text;
            break;
    }

    // do not write to error stream because we need the error message in PHP exec result from web ui
    echo($text."\n");
    exit($errorcode);
}

/**
 * Mark empty dataroot to be used for testing.
 * @param string $dataroot The dataroot directory
 * @return void
 */
function phpunit_bootstrap_initdataroot($dataroot) {
    global $CFG;
    umask(0);
    if (!file_exists("$dataroot/phpunittestdir.txt")) {
        file_put_contents("$dataroot/phpunittestdir.txt", 'Contents of this directory are used during tests only, do not delete this file!');
    }
    phpunit_boostrap_fix_file_permissions("$dataroot/phpunittestdir.txt");
    if (!file_exists("$CFG->phpunit_dataroot/phpunit")) {
        mkdir("$CFG->phpunit_dataroot/phpunit", $CFG->directorypermissions);
    }
}

/**
 * Try to change permissions to $CFG->dirroot or $CFG->dataroot if possible
 * @param string $file
 * @return bool success
 */
function phpunit_boostrap_fix_file_permissions($file) {
    global $CFG;

    $permissions = fileperms($file);
    if ($permissions & $CFG->filepermissions != $CFG->filepermissions) {
        $permissions = $permissions | $CFG->filepermissions;
        return chmod($file, $permissions);
    }

    return true;
}
