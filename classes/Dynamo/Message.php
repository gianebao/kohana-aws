<?php defined('SYSPATH') OR die('No direct script access.');

class Dynamo_Message extends AWS_Dynamo {
    
    protected $_fields = array(
        'id'        => AWS_Dynamo::T_STR,
        'sender_id' => AWS_Dynamo::T_NUM,
        'user_id'   => AWS_Dynamo::T_STR,
        'is_read'   => AWS_Dynamo::T_NUM,
        'type'      => AWS_Dynamo::T_NUM,
        'message'   => AWS_Dynamo::T_STR,
        'time'      => AWS_Dynamo::T_NUM,
    );
    
     protected $_key = array(
        AWS_Dynamo::K_HASH   => array('id'       => AWS_Dynamo::T_STR),
        AWS_Dynamo::K_RANGE  => array('time'     => AWS_Dynamo::T_NUM),
     );
    
    protected $_table_name = 'dynamo_messages';
    
    public function datafill($method, & $data)
    {
        if (self::M_ADD != $method)
        {
            return false;
        }
        
        $data['time'] = time();
    }
}