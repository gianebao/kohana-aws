<?php defined('SYSPATH') OR die('No direct script access.');

define('AWS_MOD_PATH', dirname(__FILE__));

/**
 * Use dynamo db when dynamo is specified.
 */
if (!empty($_SERVER['AWS_DYNAMO_SESSION']))
{
    $aws = parse_ini_file($_SERVER['AWS_DYNAMO_SESSION'], true);
    $handler = AWS_Dynamo::register_session_handler($aws);
}
