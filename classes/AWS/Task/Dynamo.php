<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Dynamo tools.
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
class AWS_Task_Dynamo extends Minion_Task {
    /**
     * Parameters that are accepted by this task.
     */
    protected $_options = array(
        'do' => null,
    );
    
    
    /**
     * Validate parameters passed to the task.
     */
    public function build_validation(Validation $validation)
    {
        return parent::build_validation($validation)
            ->rule('do', 'not_empty')
            ->rule('do', 'in_array', array(':value', array('migrate')));
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
}