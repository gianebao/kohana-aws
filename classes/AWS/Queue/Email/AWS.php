<?php defined('SYSPATH') OR die('No direct script access.');

class AWS_Queue_Email_AWS extends Queue_Email {
    
    protected $encryption = null;
    
    /**
     * Initialize encryption property based on the configuration.
     */
    private function _init_encryption()
    {
        if (empty($this->config['encryption']) || !empty($this->encryption))
        {
            return true;
        }
        // initialize encryption class
        $class_name = $this->config['encryption']['class'];
        
        $class = new ReflectionClass($class_name);
        $method = new ReflectionMethod($class_name, 'instance');
        $this->encryption = $method->invokeArgs($class, $this->config['encryption']['params']);
    }
    
    /**
     * Push and email request to the AWS queue
     * 
     * @param  string  $recipient  Recipient Email
     * @param  string  $subject    Mail Subject
     * @param  string  $body       Mail Contents
     * @param  string  $config     Mail Configuration name.
     */
    protected function _push($recipient, $subject, $body, $config)
    {
        $data = array(
            'recipient'  => $recipient,
            'subject'    => $subject,
            'body'       => $body,
            'config'     => $config,
            'client_ip'  => Request::$client_ip
        );
        
        $this->_init_encryption();
        
        AWS_Queue::push($this->config['queue_url'], $data,  $this->encryption);
    }
    
    /**
     * Removes a request from the top of AWS queue
     *
     * @return array
     */
    protected function _shift()
    {
        $this->_init_encryption();
        $response = AWS_Queue::shift($this->config['queue_url'], $this->encryption);
        
        for ($i = 0, $count = count($response); $i < $count; $i ++)
        {
            if (false !== $response[$i])
            {
                $response[$i] = unserialize($response[$i]);
            }
        }
        
        return $response;
    }
}