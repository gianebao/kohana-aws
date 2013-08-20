<?php defined('SYSPATH') OR die('No direct script access.');

class AWS_Service_Queue {
    
    const ENGINE = 'sqs';
    
    static protected $_data = array();
    static protected $_initialized = false;
    
    /**
     * Push a queue request.
     *
     * @param string $queue_url   The URL of the SQS queue to take action on.
     * @param mixed  $data        Data message of the queue.
     */
    static public function push($queue_url, $data)
    {
        // Initialize variable.
        if (empty(self::$_data[$queue_url]))
        {
            self::$_data[$queue_url] = array(
                'QueueUrl' => $queue_url,
                'Entries'  => array()
            );
        }
        
        if (true !== self::$_initialized)
        {
            register_shutdown_function(array('AWS_Queue', 'shutdown_handler'));
            self::$_initialized = true;
        }
        
        
        $queue_url = $this->config['queue_url'];
        $crypt_key = pack('H*', $this->config['crypt']);
        
        // Push the request to the queue.
        array_push(self::$_data[$queue_url]['Entries'],
            array(
                'Id'            => count(self::$_data[$queue_url]['Entries']),
                'MessageBody'   => !is_string($data) ? serialize($data): $data
            )
        );
    }
    
    /**
     * Push a queue request.
     * Alias
     *
     * @param string $data   The URL of the SQS queue to take action on.
     */
    static protected function _send(& $data)
    {

    }
    
    static public function shutdown_handler()
    {
        
        if (empty(self::$_data) && !is_array(self::$_data))
        {
            return true;
        }
        
        // Profile initialization.
        if (TRUE === Kohana::$profiling)
        {
            $benchmark = Profiler::start(__CLASS__, 'Initialize');
        }
        
        // Get engine instance
        $q = AWS::factory()->get(self::ENGINE);
        
        if (isset($benchmark))
        {
            // Stop the benchmark
            Profiler::stop($benchmark);
        }
        
        if (empty($q) && $)
        {
            $keys = array_keys(self::$_data);
            Kohana::$log->add(Log::ALERT, 'Queue Data NOT SENT TO (:url)', array(
                ':url' => implode(',', $keys)
            ));
            return true;
        }
        
        // Get engine instance
        foreach (self::$_data as $url => $data)
        {
            // Be sure to only profile if it's enabled
            if (TRUE === Kohana::$profiling)
            {
                // Start a new benchmark
                $benchmark = Profiler::start(__CLASS__, $url);
            }
            
            
            return $q->sendMessageBatch($data);
    
            self::_send($url);
        }
    }
}