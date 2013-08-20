<?php defined('SYSPATH') OR die('No direct script access.');

class AWS_Queue_Email_AWS extends Queue_Email {

    protected function _push($recipient, $subject, $body, $config)
    {
        // Be sure to only profile if it's enabled
        if (Kohana::$profiling === TRUE)
        {
            // Start a new benchmark
            $benchmark = Profiler::start('AWS', 'SQS Email');
            
            $q = AWS::factory()->get(self::ENGINE);
            
            $data = array(
                'recipient' => $recipient,
                'subject'   => $subject,
                'body'      => $body,
                'config'    => $config
            );
            
        }
     
        // Do some stuff
     

    }
    
    protected function _shutdown_handler()
    {
        $queue_url = $this->config['queue_url'];
        $crypt_key = $this->config['queue_url'];
        
        $q->sendMessageBatch(
            array(
                'QueueUrl' => $this->config['queue_url'],
                'MessageBody' => serialize($data)
            )
        );
    }
    
}