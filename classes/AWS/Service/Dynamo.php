<?php defined('SYSPATH') OR die('No direct script access.');

use \Aws\DynamoDb\Enum\Type;
use \Aws\DynamoDb\Enum\KeyType;
use \Aws\DynamoDb\Enum\TableStatus;
use \Aws\DynamoDb\Enum\ComparisonOperator;
use \Aws\DynamoDb\Enum\ReturnValue;
use \Aws\DynamoDb\Enum\AttributeAction;
use \Aws\DynamoDb\Iterator\ItemIterator;


class AWS_Service_Dynamo extends AWS_Service {
    public static $_resource = 'dynamodb';
    
    /**
     * Comparison Operators
     */
    const C_EQ = 'EQ';                      // ==
    const C_NE = 'NE';                      // !=
    const C_IN = 'IN';                      // IN []
    const C_LE = 'LE';                      // <=
    const C_LT = 'LT';                      // <
    const C_GE = 'GE';                      // >
    const C_GT = 'GT';                      // >=
    const C_BETWEEN = 'BETWEEN'; 
    const C_NOT_NULL = 'NOT_NULL';
    const C_NULL = 'NULL';
    const C_CONTAINS = 'CONTAINS';
    const C_NOT_CONTAINS = 'NOT_CONTAINS';
    const C_BEGINS_WITH = 'BEGINS_WITH';
    
    /**
     * The table is being created, as the result of a CreateTable operation.
     */
    const TS_CREATING = TableStatus::CREATING;
    
    /**
     * The table is being updated, as the result of an UpdateTable operation.
     */
    const TS_UPDATING = TableStatus::UPDATING;
    
    /**
     * The table is being deleted, as the result of a DeleteTable operation.
     */
    const TS_DELETING = TableStatus::DELETING;
    
    /**
     * The table is ready for use.
     */
    const TS_ACTIVE = TableStatus::ACTIVE;
    
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
    static public function factory($name, $prefix = '')
    {
        $reflection = new ReflectionClass($prefix . $name);
        $class = $reflection->newInstanceArgs(array($name));
        return $class;
    }
    
    static public function iterate($data)
    {
        return new ItemIterator($data);
    }
    
    /**
     * Creates a condition entry based of the field.
     *
     * @param  string  $name        field name.
     * @param  string  $operation   comparison operator.
     * @param  string  $value       value.
     * @return array
     */
    public function condition($name, $operation, $value)
    {
        return array(
                'AttributeValueList' => array(
                    $this->cast_field($name, $value)
                ),
                'ComparisonOperator' => $operation
            );
    }
    
    /**
     * Creates a "casted" entry of the field.
     *
     * @param  string  $name   field name.
     * @param  string  $value  value.
     * @return array
     */
    public function cast_field($name, $value)
    {
        return self::cast($this->_fields[$name], $value);
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
    
    /**
     * Creates the table
     *
     */
    public function build_table()
    {
        // send to DynamoDb
        $instance = AWS_Dynamo::instance();
        $table = false;
        
        try
        {
            $table = $instance->describeTable(array(
                'TableName' => $this->_table_name
            ));
            
            $table_status = $table->getPath('Table/TableStatus');
        }
        catch (Exception $e) {}
        
        if (!empty($table_status))
        {
            return $table_status;
        }
        
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
        $instance->createTable(array(
            'TableName' => $this->_table_name,
            
            'AttributeDefinitions' => $definition,
            
            'KeySchema' => $keyschema,
            
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits'  => self::DEFAULT_CAPACITY_READ,
                'WriteCapacityUnits' => self::DEFAULT_CAPACITY_WRITE
            )
        ));
        
        return true;
    }
    
    /**
     * Stores a record.
     *
     * @param  array  $data  Hash of fields and their data
     */
    public function add(& $data)
    {
        $fields = array();
        
        // Populates the data hash.
        $this->datafill(self::M_ADD, $data);
        
        // Runs through all the declared fields and create a proper dynamo entry.
        foreach ($this->_fields as $key => $value)
        {
            if (isset($data[$key]))
            {
                $fields[$key] = self::cast($value, $data[$key]);
            }
        }
        
        // send to DynamoDb
        $response = AWS_Dynamo::instance()->putItem(array(
            'TableName' => $this->_table_name,
            'Item' => $fields
        ));
        
        return $response;
    }
    
    public function get_keys()
    {
        $keys = array();
        
        foreach ($this->_key as $item)
        {
            list($k, $v) = each($item);
            
            $keys[] = $k;
        }
        
        return $keys;
    }
    
    /**
     * Verifies if the item is the same as the response. Comparison ignores keys.
     *
     * @param  array  $response  AWS DynamoDB response
     * @param  array  $expected  Expected result
     * @return  boolean
     */
    protected function _verify_update($response, $expected)
    {
        if (empty($response['Attributes']))
        {
            return false;
        }
        $response = $response['Attributes'];
        
        $keys = $this->get_keys();
        
        foreach ($expected as $key => $item)
        {
            if (in_array($key, $keys))
            {
                continue;
            }
            
            if (empty($response[$key]) || $response[$key][$this->_fields[$key]] != $item)
            {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Updates a record.
     *
     * @param  array  $data  Hash of fields and their data
     */
    public function update($keys, $data)
    {
        $key = array();
        
        foreach ($this->_key as $item)
        {
            list($k, $value) = each($item);
            
            if (empty($keys[$k]))
            {
                throw new Kohana_Exception('Key `:key` must be provided in performing updates.', array(':key' => $k));
            }
            
            $key[$k] = array($value => $keys[$k]);
        }
        
        $updates = array();
        
        foreach($data as $k => $value)
        {
            if (!empty($key[$k]))
            {
                continue;
            }
            
            $updates[$k] = array(
                'Value'   => array($this->_fields[$k] => $value),
                'Action'  => AttributeAction::PUT,
            );
        }
        
        // send to DynamoDb
        $response = AWS_Dynamo::instance()->updateItem(array(
            'TableName'         => $this->_table_name,
            'Key'               => $key,
            'AttributeUpdates'  => $updates,
            'ReturnValues'      => ReturnValue::UPDATED_NEW
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
    public function datafill($method, & $data)
    {
        return $data;
    }
}