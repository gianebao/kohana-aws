<?php defined('SYSPATH') OR die('No direct script access.');

class Dynamo_Token extends AWS_Dynamo {
    
    const LANG_CN_MAN  = 'cn-man'; // Mandarin
    const LANG_EN      = 'en'; // English
    const LANG_ES      = 'es'; // Spanish
    const LANG_ID      = 'id'; // Bahasa
    const LANG_IN_HIN  = 'in-hin'; // Hindi
    const LANG_MY      = 'my'; // Malay
    const LANG_VN      = 'vn'; // Vietnamese
    
    protected $_fields = array(
        'id'        => AWS_Dynamo::T_STR,
        'lang'      => AWS_Dynamo::T_STR,
        'value'     => AWS_Dynamo::T_STR,
    );
    
     protected $_key = array(
        AWS_Dynamo::K_HASH   => array('id'       => AWS_Dynamo::T_STR),
        AWS_Dynamo::K_RANGE  => array('lang'     => AWS_Dynamo::T_STR),
     );
    
    protected $_table_name = 'dynamo_token';
    
    public function datafill($method, & $data)
    {
        if (self::M_ADD != $method)
        {
            return false;
        }
    }
    
    /**
     * Find the translation for the token
     *
     * @param  string  $token  token
     * @param  string  $lang   lang ISO 639-1 with first 3 letter for dialect
     * @return string
     */
    public function find($token, $lang = Dynamo_Token::LANG_EN)
    {
        $filter = array();
        
        $filter['id'] = $this->condition('id', self::C_EQ, $token);
        $filter['lang'] = $this->condition('lang', self::C_EQ, $lang);
        
        $query = array(
            'TableName'        => $this->_table_name,
            'KeyConditions'    => $filter,
            'Limit'            => $limit,
            'ScanIndexForward' => false
        );
        
        return AWS_Dynamo::iterate($this->getIterator('Query', $query));
    }
}