<?php
date_default_timezone_set("Africa/Lagos");

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'fms';
const TOKEN_SECRET = 'ilovephp';
const API_ROOT_URL = 'http://localhost:8080/finovate-bank/api/';
const BASE_URL = 'http://localhost:8080/finovate-bank';
const EMAIL_LOGO_ALT = 'Finovate';
const EMAIL_HEADER = 'FINOVATE UPDATE';
const APP_NAME = 'FINOVATE';
const GMAIL_USER = 'immigrationevaluator@gmail.com';
const GMAIL_PASSWORD = 'gtrwlryzsvtqblaw';

define('ROOT_PATH', realpath(dirname(__FILE__)));

//connect to database
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

//Check Connection
if ($mysqli->connect_errno) {
    echo "<p>MySQL error no{$mysqli->connect_errno}:{$mysqli->connect_error}</p>";
    exit();
}
