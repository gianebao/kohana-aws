<?php defined('SYSPATH') OR die('No direct script access.');

use \Aws\CloudWatch\Enum\Unit;

class AWS_Service_Watch extends AWS_Service {
    public static $_resource = 'cloudwatch';
    
    static protected $_data = array();
    
    static protected $_initialized = false;
    static protected $_received = array();
    
    const UNIT_SECONDS = 'Seconds';
    const UNIT_MICROSECONDS = 'Microseconds';
    const UNIT_MILLISECONDS = 'Milliseconds';
    const UNIT_BYTES = 'Bytes';
    const UNIT_KILOBYTES = 'Kilobytes';
    const UNIT_MEGABYTES = 'Megabytes';
    const UNIT_GIGABYTES = 'Gigabytes';
    const UNIT_TERABYTES = 'Terabytes';
    const UNIT_BITS = 'Bits';
    const UNIT_KILOBITS = 'Kilobits';
    const UNIT_MEGABITS = 'Megabits';
    const UNIT_GIGABITS = 'Gigabits';
    const UNIT_TERABITS = 'Terabits';
    const UNIT_PERCENT = 'Percent';
    const UNIT_COUNT = 'Count';
    const UNIT_BYTES_PER_SECOND = 'Bytes/Second';
    const UNIT_KILOBYTES_PER_SECOND = 'Kilobytes/Second';
    const UNIT_MEGABYTES_PER_SECOND = 'Megabytes/Second';
    const UNIT_GIGABYTES_PER_SECOND = 'Gigabytes/Second';
    const UNIT_TERABYTES_PER_SECOND = 'Terabytes/Second';
    const UNIT_BITS_PER_SECOND = 'Bits/Second';
    const UNIT_KILOBITS_PER_SECOND = 'Kilobits/Second';
    const UNIT_MEGABITS_PER_SECOND = 'Megabits/Second';
    const UNIT_GIGABITS_PER_SECOND = 'Gigabits/Second';
    const UNIT_TERABITS_PER_SECOND = 'Terabits/Second';
    const UNIT_COUNT_PER_SECOND = 'Count/Second';
    const UNIT_NONE = 'None';
    
    /**
     * Declare the cleanup
     */
    static public function initialize()
    {
        if (true !== self::$_initialized)
        {
            register_shutdown_function(array('AWS_Watch', 'shutdown_handler'));
            self::$_initialized = true;
        }
    }
    
    /**
     * Push a queue request.
     *
     * @param string  $queue_url  The URL of the SQS queue to take action on.
     * @param mixed   $data       Data message of the queue.
     * @param object  $encrypt    Instance of an encryption class that has an `encode` & `decode` method.
     */
    static public function push($namespace, $name, $unit, $value = 1, $timestamp = null, $dimensions = array())
    {
        if (empty($timestamp))
        {
            $timestamp = time();
        }
        
        $data = array(
            'Namespace'  => $namespace,
            'MetricData' => array(
                array(
                    'MetricName' => $name,
                    'Timestamp'  => $timestamp,
                    'Value'      => $value,
                    'Unit'       => $unit,
                ),
            ),
            
            'namespace' => $namespace,
            'name' => $name,
            'value' => $value,
            'unit' => $unit,
            'timestamp' => $timestamp,
        );
        
        if (!empty($dimensions))
        {
            list($d_name, $d_value) = each($dimensions);
            $data['MetricData']['Dimensions'] = array('Name' => $d_name, 'Value' => $d_value);
        }
        
        self::$_data[] = $data;
        
        self::initialize();
    }
    
    /**
     * Pushes the data to CloudWatch.
     * 
     */
    static public function send_data()
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
        
        $client = AWS_Watch::instance();
        
        if (isset($benchmark))
        {
            // Stop the benchmark
            Profiler::stop($benchmark);
        }
        
        if (empty($client) && is_object($client) && !is_callable($client, 'putMetricData'))
        {
            Kohana::$log->add(Log::ALERT, 'Invalid `CloudWatch` object.');
            
            return true;
        }
        
        // Profile sending.
        if (TRUE === Kohana::$profiling)
        {
            // Start a new benchmark
            $benchmark = Profiler::start(__CLASS__, $url);
        }
        
        foreach (self::$_data as $data)
        {
            // Profile sending.
            if (TRUE === Kohana::$profiling)
            {
                // Start a new benchmark
                $benchmark = Profiler::start(__CLASS__, 'putMetricData');
            }
            
            $response = $client->putMetricData($data);
            
            if (isset($benchmark))
            {
                // Stop the benchmark
                Profiler::stop($benchmark);
            }
        }
        
        self::$_data = array();
    }
    
    /**
     * Termination processes.
     */
    static public function shutdown_handler()
    {
        self::send_data();
    }
}