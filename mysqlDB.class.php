<?php
class MysqlDB extends mysqli{
    var $user;
    var $db_name;
    var $host;
    var $pass;
    var $port;
    var $codepage = 'UTF8';
    var $DB;
    function __construct(&$DB){
        $this -> host = $DB['host'];
        $this -> user = $DB['user'];
        $this -> password = $DB['password'];
        $this -> port = $DB['port'];
        $this -> db_name = $DB['database'];
        $this -> codepage = $DB['codepage'];
        $this -> db = &$DB;
    }

    function connect(){
        parent::__construct($this -> host,
                            $this -> user,
                            $this -> password,
                            $this -> db_name,
                            $this -> port);
        if ($this->connect_error) {
            die('Connect Error (' . $this->connect_errno . ') '
                    . $this->connect_error);
        }
        $this -> query('SET NAMES \''.$this -> codepage.'\'');
        $this -> DB['link'] = $this;
    }
    function query($s){
        $qid = parent::query($s);
        if(!$qid){
    	    errorLog('SQL: '.$this -> error.' '.$s);
    	    throw new Exception($this -> error);
            $this -> error_msg($this -> error, $s);
        }
        return $qid;
    }
    function error_msg($er, $q = ''){
        die('SQL: '.$er.'<br />'.$q);
    }
    function item($s, $field_name = ''){
        $qid = $this -> query($s);
        $t = $qid -> fetch_assoc();
        return $field_name == ''?$t:$t[$field_name];
    }
    function table($s, $key_field_name = '', $value_field_name = ''){
        $qid = $this -> query($s);
        $result = array();
        if($key_field_name != '' && $value_field_name != ''){
            while($t = $qid -> fetch_assoc()){$result[$t[$key_field_name]] = $t[$value_field_name];}
        }else if($key_field_name != '' && $value_field_name == ''){
            while($t = $qid -> fetch_assoc()){$result[$t[$key_field_name]] = $t;}
        }else if($key_field_name == '' && $value_field_name != ''){
            while($t = $qid -> fetch_assoc()){$result[] = $t[$value_field_name];}
        }else{
            while($t = $qid -> fetch_assoc()){$result[] = $t;}
        }
        return $result;
    }
    function tree($s){
        $result = array();
        $qid = $this -> query($s);
        while($t = $qid -> fetch_assoc()){
            $link_key = (isset($t['root']) && $t['root'] == 1)?0:$t['id'];
            if(!isset($result[$link_key])){
                $result[$link_key] = $t;
            }else{
                foreach($t as $key => $value){
                    $result[$link_key][$key] = $value;
                }
            }
            if(isset($t['root']) && $t['root'] == 1){
                if(!isset($result[$t['id']])){
                    $result[$t['id']] = $t;
                }else{
                    foreach($t as $key => $value){
                        $result[$t['id']][$key] = $value;
                    }
                }
            }
            if($t['parent_id'] === null){
                continue;
            }
            if(isset($t['root']) && $t['root'] == 1){
                continue;
            }
            if(!isset($result[$t['parent_id']])){
                $result[$t['parent_id']] = array('childs' => array($t['id'] => &$result[$t['id']]));
            }else{
                $result[$t['parent_id']]['childs'][$t['id']] = &$result[$t['id']];
            }
        }
        return $result;
    }
    function escape($s){
        return $this -> real_escape_string($s);
    }
}
?>
