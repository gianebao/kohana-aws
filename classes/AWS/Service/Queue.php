<?php defined('SYSPATH') OR die('No direct script access.');

class AWS_Service_Queue {
    
    const ENGINE = 'sqs';
    
    static protected $_data = array();
    static protected $_initialized = false;
    
    /**
     * Push a queue request.
     *
     * @param string  $queue_url  The URL of the SQS queue to take action on.
     * @param mixed   $data       Data message of the queue.
     * @param object  $encrypt    Instance of an encryption class that has an `encode` & `decode` method.
     */
    static public function push($queue_url, $data, $encrypt = null)
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
        
        $data = !is_string($data) ? serialize($data): $data;
        
        // encrypt data when instance is provided
        if (!empty($encrypt))
        {
            if (!method_exists($encrypt, 'encode'))
            {
                // Invalid encrytion class
                throw new Kohana_Exception('Not a valid Encryption Class');
            }
            
            $data = $encrypt->encode($data);
        }
        
        // Push the request to the queue.
        array_push(self::$_data[$queue_url]['Entries'],
            array(
                'Id'            => count(self::$_data[$queue_url]['Entries']),
                'MessageBody'   => $data
            )
        );
    }
    
    /**
     * Push a queue request.
     * Alias
     *
     * @param string $data   The URL of the SQS queue to take action on.
     */
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
        
        if (empty($q) && is_object($q) && !is_callable($q, 'sendMessageBatch'))
        {
            $keys = implode(',', array_keys(self::$_data));
            
            Kohana::$log->add(Log::ALERT, 'Queue Data NOT SENT TO (:url)', array(
                ':url' => empty($keys) ? '`EMPTY`': $keys
            ));
            
            return true;
        }
        
        foreach (self::$_data as $url => $data)
        {
            // Profile sending.
            if (TRUE === Kohana::$profiling)
            {
                // Start a new benchmark
                $benchmark = Profiler::start(__CLASS__, $url);
            }
            
            $response = $q->sendMessageBatch($data);
            
            if (!empty($response->Failed))
            {
                foreach($response->Failed as $entry)
                {
                    Kohana::$log->add(Log::ALERT, 'Queue Data FAILED (:url)', array(
                        // The id of an entry in a batch request.
                        ':url' => $data->QueueUrl,
                        
                        // The id of an entry in a batch request.
                        ':id' => $entry->Id,
                        
                        // Whether the error happened due to the sender's fault.
                        ':sender_fault' => $entry->SenderFault ? 'SenderFault': 'NotSenderFault',
                        
                        // An error code representing why the operation failed on this entry.
                        ':code' => $entry->Code,
                        
                        // A message explaining why the operation failed on this entry.
                        ':message' => $entry->Message,
                    ));
                }
            }
            
            if (isset($benchmark))
            {
                // Stop the benchmark
                Profiler::stop($benchmark);
            }
        }
    }
}