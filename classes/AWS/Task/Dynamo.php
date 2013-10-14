<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Dynamo tools.
 *
 * Examples:
 * # Creates the table in Dynamo
 * Dynamo --do=migrate
 * Dynamo --do=init_session --config=<file> --r_iop=1 --w_iop=1
 * 
 * Options:
 *   --do       Function to be done.
 *   
 *   --config  Session configuration file path.
 *   --r_iop   Read IOPS.
 *   --w_iop   Write IOPS.
 * 
 * Functions:
 *   migrate       Creates dynamo DB table
 *   init_session  Initialize session table
 * 
 * @author     Gian Carlo Val Ebao
 * @version    1.2
 */
class AWS_Task_Dynamo extends Minion_Task {
    /**
     * Parameters that are accepted by this task.
     */
    protected $_options = array(
        'do'      => null,
        'config'  => null,
        'r_iop'   => null,
        'w_iop'   => null,
    );
    
    protected static function _message($message)
    {
        ob_end_flush();
        echo $message ."\n";
        ob_start();
    }
    
    /**
     * Validate parameters passed to the task.
     */
    public function build_validation(Validation $validation)
    {
        return parent::build_validation($validation)
            ->rule('do', 'not_empty')
            ->rule('do', 'in_array', array(':value', array('migrate', 'init_session')));
    }
    
    /**
     * To be executed by Minion
     *
     * @param  array  $params  Parameters received by Minion
     */
    protected function _execute(array $params)
    {
        $method = $params['do'];
        $this->$method($params);
    }
    
    public function init_session($params)
    {
        $config = Arr::get($params, 'config', null);
        $r_iop  = Arr::get($params, 'r_iop', 1);
        $w_iop  = Arr::get($params, 'w_iop', 1);
        
        if (empty($config))
        {
            return self::_message('`--config` is not specified.');
        }
        
        if (!file_exists($config))
        {
            return self::_message('`' . $config . '` is not a file.');
        }
        
        self::_message('Reading .ini file...');
        $aws = parse_ini_file($config, true);
        self::_message('Getting handler object...');
        $handler = AWS_Dynamo::register_session_handler($aws);
        self::_message('Creating session table...');
        $handler->createSessionsTable($r_iop, $w_iop);
        
        self::_message('Done!');
    }
}