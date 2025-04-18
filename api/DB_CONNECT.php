<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

date_default_timezone_set("Africa/Lagos");

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'fms';
const TOKEN_SECRET = 'ilovephp';
const API_ROOT_URL = 'http://localhost/finhive/api/';
const BASE_URL = 'http://localhost/finhive';
const EMAIL_LOGO_ALT = 'Finovate';
const EMAIL_HEADER = 'FINHIVE UPDATE';
const APP_NAME = 'FINHIVE';
//const GMAIL_USER = 'immigrationevaluator@gmail.com';
const GMAIL_USER = 'finhive247@gmail.com';
//const GMAIL_PASSWORD = 'gtrwlryzsvtqblaw';
const GMAIL_PASSWORD = 'grlrtjmmzumjhtxv';

define('ROOT_PATH', realpath(dirname(__FILE__)));

//connect to database
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

//Check Connection
if ($mysqli->connect_errno) {
    echo "<p>MySQL error no{$mysqli->connect_errno}:{$mysqli->connect_error}</p>";
    exit();
}
