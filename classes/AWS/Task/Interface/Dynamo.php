<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Dynamo tools interface.
 *
 * Examples:
 * # Creates the table in Dynamo
 * Dynamo --do=migrate
 * 
 * Options:
 *   --do      Function to be done.
 * 
 * Functions:
 *   migrate     Creates dynamo DB table
 * 
 * @author     Gian Carlo Val Ebao
 * @version    1.0
 */
interface AWS_Task_Interface_Dynamo
{
    /**
     * You can put initializations here.
     *
     * AWS_Dynamo::factory('Message')->build_table();
     */
    function migrate($params);
}