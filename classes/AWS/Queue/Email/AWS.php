<?php defined('SYSPATH') OR die('No direct script access.');

class AWS_Queue_Email_AWS extends Queue_Email {
    
    protected $encryption = null;
    
    /**
     * Push and email request to the AWS queue
     * 
     * @param  string  $recipient  Recipient Email
     * @param  string  $subject    Mail Subject
     * @param  string  $body       Mail Contents
     * @param  string  $config     Configuration name.
     */
    protected function _push($recipient, $subject, $body, $config)
    {
        $data = array(
            'recipient' => $recipient,
            'subject'   => $subject,
            'body'      => $body,
            'config'    => $config
        );
        
        if (!empty($this->config['encryption']) && empty($this->encryption))
        {
            // initialize encryption class
            $class_name = $this->config['encryption']['class'];
            
            $class = new ReflectionClass($class_name);
            $method = new ReflectionMethod($class_name, 'instance');
            $this->encryption = $method->invokeArgs($class, $this->config['encryption']['params']);
        }
        
        AWS_Queue::push($this->config['queue_url'], $data,  $this->encryption);
    }
}