<?php defined('SYSPATH') OR die('No direct script access.');

require_once AWS_MOD_PATH . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'aws'
    . DIRECTORY_SEPARATOR . 'build'
    . DIRECTORY_SEPARATOR . 'aws-autoloader.php';

class AWS_Core extends \Aws\Common\Aws {
    /**
     * Default configuration path.
     */
    const DEFAULT_CONFIG_PATH = 'aws';
    
    /**
     * Configuration group to be used.
     */
    protected static $_config_group = 'default';
    
    /**
     * @param string $group configuration group to be used.
     * @param string $global_params
     */
    public static function factory($group = null, array $global_params = array())
    {
        if (empty($group))
        {
            $group = self::$_config_group;
        }
        
        if (is_string($group))
        {
            $config = Kohana::$config->load(self::DEFAULT_CONFIG_PATH)->as_array();
            
            // load configuration from kohana config file.
            if (empty($config[$group]))
            {
                throw new Kohana_Exception('Cannot find :class configuration group `:group` on file `:file`',
                    array(':class' => __CLASS__, ':group' => $group, ':file' => self::DEFAULT_CONFIG_PATH));
                
                return false;
            }
            
            $group = $config[$group];
        }
        
        return parent::factory($group, $config);
    }
}