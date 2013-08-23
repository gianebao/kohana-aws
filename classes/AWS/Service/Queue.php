<?php defined('SYSPATH') OR die('No direct script access.');

class AWS_Service_Queue {
    
    const ENGINE = 'sqs';
    
    static protected $_data = array();
    static protected $_engine = null;
    
    static protected $_initialized = false;
    static protected $_received = array();
    
    /**
     * Declare the cleanup
     */
    static public function initialize()
    {
        if (true !== self::$_initialized)
        {
            register_shutdown_function(array('AWS_Queue', 'shutdown_handler'));
            self::$_initialized = true;
        }
    }
    
    /**
     * Declare and queue engine
     */
    static public function engine()
    {
        if (empty(self::$_engine))
        {
            self::$_engine = AWS::factory()->get(self::ENGINE);
        }
        
        return self::$_engine;
    }
    
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
        
        self::initialize();
        
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
     * Removes a request from the top of AWS queue
     *
     * @return array
     */
    static public function shift($queue_url, $encrypt = null)
    {
        self::initialize();
        
        $q = self::engine();
        
        $response = $q->receiveMessage(array(
            'QueueUrl' => $queue_url
        ));
        
        if (!isset(self::$_received[$queue_url]))
        {
            self::$_received[$queue_url] = array();
        }
        
        $contents = array();
        
        if (!$response->hasKey('Messages'))
        {
            return false;
        }
        
        $response = $response->get('Messages');
        
        foreach ($response as $data)
        {
            self::$_received[$queue_url][] = $data['ReceiptHandle'];
            
            // encrypt data when instance is provided
            if (!empty($encrypt))
            {
                if (!method_exists($encrypt, 'decode'))
                {
                    // Invalid encrytion class
                    throw new Kohana_Exception('Not a valid Encryption Class');
                }
                
                $content = $encrypt->decode($data['Body']);
                
                if (false === $content)
                {
                    Kohana::$log->add(Log::ALERT, 'Queue Data DECRYPT FAILED (url:`:url`) -- :error -- :message', array(
                        // The id of an entry in a batch request.
                        ':url' => $queue_url,
                        
                        // A message explaining why the operation failed on this entry.
                        ':error' => $encrypt::$last_error,
                        
                        // Message body
                        ':message' => $data['Body'],
                    ));
                }
                else
                {
                    $contents[] = $content;
                }
            }
            
            
        }
        
        self::$_received[$queue_url] = array_merge(array_unique(self::$_received[$queue_url]));
        
        return $contents;
    }
    
    /**
     * Send log when response contains failed result
     */
    static protected function _check_failed(& $response)
    {
        if (empty($response->Failed))
        {
            return false;
        }
        
        foreach($response->Failed as $entry)
        {
            Kohana::$log->add(Log::ALERT, 'Queue Data FAILED (url:`:url` id:`:id` sender_fault:`:sender_fault` code:`:code` message:`:message`)', array(
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
    
    /**
     * Delete all the messages that were used by se.
     *
     */
    static public function remove_shifted()
    {
        $q = self::engine();
        
        foreach (self::$_received as $url => $data)
        {
            for ($i = 0,  $entries = array(), $count = count($data); $count > $i; $i ++)
            {
                $entries[] = array(
                    'Id'            => $i,
                    'ReceiptHandle' => $data[$i]
                );
            }
            
            if (empty($entries))
            {
                continue;
            }
            
            $response = $q->deleteMessageBatch(array(
                'QueueUrl' => $url,
                'Entries' => $entries
            ));
            
            self::_check_failed($response);
        }
        
        self::$_received = array();
    }
    
    static public function send_queue()
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
        
        $q = self::engine();
        
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
            
            self::_check_failed($response);
            
            if (isset($benchmark))
            {
                // Stop the benchmark
                Profiler::stop($benchmark);
            }
        }
        
        self::$_data = array();
    }
    
    /**
     * Push a queue request.
     *
     * @param string $data   The URL of the SQS queue to take action on.
     */
    static public function shutdown_handler()
    {
        self::send_queue();
        //self::remove_shifted();
    }
}