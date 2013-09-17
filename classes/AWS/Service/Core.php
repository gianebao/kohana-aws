<?php defined('SYSPATH') OR die('No direct script access.');

class AWS_Service_Core {
    static protected $_engine = null;
    static protected $_engines = array();
    
    /**
     * Declare and queue engine
     */
    static public function engine($engine)
    {
        if (empty(self::$_engines[$engine]))
        {
            self::$_engines[$engine] = AWS::factory()->get($engine);
        }
        
        return self::$_engines[$engine];
    }
}