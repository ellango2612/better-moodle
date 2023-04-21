<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'testuser';
$CFG->dbpass    = 'badpassword';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 5432,
  'dbsocket' => '',
);

$CFG->wwwroot   = 'http://localhost:8888';
$CFG->dataroot  = '/Users/bmorrell/git/moodledata';
$CFG->admin     = 'admin';

$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '/Users/bmorrell/git/phpu_moodledata';

$CFG->dirroot = '/Users/bmorrell/git/better-moodle';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
