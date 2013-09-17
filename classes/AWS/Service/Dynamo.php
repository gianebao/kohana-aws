<?php defined('SYSPATH') OR die('No direct script access.');

use \Aws\DynamoDb\Enum\Type;
use \Aws\DynamoDb\Enum\KeyType;

class AWS_Service_Dynamo extends AWS_Service {
    const ENGINE = 'dynamodb';
    
    static protected $_prefix = '';
    
    /**
     * (string) Represents the attribute data,
     * consisting of the data type and the attribute value itself.
     */
    const K_HASH = KeyType::HASH;
    const K_RANGE = KeyType::RANGE;
    
    /**
     * (string) Represents a String data type
     */
    const T_STR = Type::STRING;

    /**
     * (string) Represents a Number data type
     */
    const T_NUM = Type::NUMBER;

    /**
     * (string) Represents a Binary data type
     */
    const T_BIN = Type::BINARY;

    /**
     * [array(string)] Represents a String set data type
     */
    const T_SET_STR = Type::STRING_SET;

    /**
     * [array(string)] Represents a Number set data type
     */
    const T_SET_NUM = Type::NUMBER_SET;

    /**
     * [array(string)] Represents a Binary set data type
     */
    const T_SET_BIN = Type::BINARY_SET;
    
    /**
     * Put method
     */
    const M_ADD = 'ADD';
    
    
    const DEFAULT_CAPACITY_READ = 1;
    
    const DEFAULT_CAPACITY_WRITE = 1;
    
    /**
     * Table name. Case-sensitive
     */
    protected $_table_name = null;
    
    /**
     * Field mapping.
     * 
     * $_fields = array(
     *      'id'       => self::T_NUM,
     *      'name'     => self::T_STR,
     *      'friends'  => self::T_SET_STR
     *  )
     * 
     */
    protected $_fields = array();
    
    /**
     * Key mapping.
     *
     * $_key = array(
     *      AWS_Dynamo::K_HASH   => array('id'   => AWS_Dynamo::T_NUM),
     *      AWS_Dynamo::K_RANGE  => array('time' => AWS_Dynamo::T_NUM),
     * );
     */
    protected $_key = array();
    
    /**
     * Create instance of Message
     */
    static public function factory($name)
    {
        $engine   = self::engine(self::ENGINE);
        $reflection = new ReflectionClass(self::$_prefix . $name);
        $class = $reflection->newInstanceArgs(array($name));
        return $class;
    }
    
    /**
     * Creates a Dynamo compatible data-structure.
     *
     * @param  string  $field  field type.
     * @param  string  $value  field value.
     * @return mixed
     */
    static public function cast($field, $value)
    {
        return array($field => $value);
    }
    
    public function build_table()
    {
        // send to DynamoDb
        $engine   = self::engine(self::ENGINE);
        
        $definition = array();
        $keyschema = array();
        
        foreach ($this->_key as $key_type => $value)
        {
            foreach ($value as $name => $type)
            {
                $definition[] = array(
                    'AttributeName' => $name,
                    'AttributeType' => $type
                );
                
                $keyschema[] = array(
                    'AttributeName' => $name,
                    'KeyType'       => $key_type
                );
            }
        }
        
        // Create an "errors" table
        $engine->createTable(array(
            'TableName' => $this->_table_name,
            
            'AttributeDefinitions' => $definition,
            
            'KeySchema' => $keyschema,
            
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits'  => self::DEFAULT_CAPACITY_READ,
                'WriteCapacityUnits' => self::DEFAULT_CAPACITY_WRITE
            )
        ));
    }
    
    /**
     * Stores a record.
     *
     * @param  array  $data  Hash of fields and their data
     */
    static public function add(& $data)
    {
        $fields = array();
        
        // Populates the data hash.
        self::datafill(self::M_ADD, $data);
        
        // Runs through all the declared fields and create a proper dynamo entry.
        foreach ($this->_fields as $key => $value)
        {
            if (!isset($data[$key]))
            {
                $fields[$key] = self::cast($value, $data[$key]);
            }
        }
        
        // send to DynamoDb
        $engine   = self::engine(self::ENGINE);
        $response = $client->putItem(array(
            'TableName' => $this->_table_name,
            'Item' => $fields
        ));
        
        return $response;
    }
    
    /**
     * Creates an instance of Dynamo.
     *
     * @param  string  $name  Name of the class and the table.
     */
    public function __construct($name = '')
    {
        if (empty($this->_table_name))
        {
            $this->_table_name = $name;
        }
    }
    
     /**
     * Populates the hash before processing.
     *
     * @param  string  $method  Constant mode of what the process is.
     * @param  string  $data    Data hash.
     */
    static public function datafill($method, & $data)
    {
        return $data;
    }
}