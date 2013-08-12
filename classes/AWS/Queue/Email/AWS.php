<?php defined('SYSPATH') OR die('No direct script access.');

class AWS_Queue_Email_AWS extends Queue_Email {
    
    protected function _push($recipient, $subject, $body, $config)
    {
        $q = AWS::factory()->get('sqs');
        
        $data = array(
            'recipient' => $recipient,
            'subject'   => $subject,
            'body'      => $body,
            'config'    => $config
        );
        
        $q->sendMessage($this->config['queue_url'], serialize($data));
    }
    
}