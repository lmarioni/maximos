<?php
//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
$hostname = "127.0.0.1";
$database = "maximos";
$username = "root";
$password = "";
//global $cxn;
$cxn = mysqli_connect($hostname, $username, $password, $database);
// Establece el conjunto de caracteres para mysql => acentos y ñ's
mysqli_set_charset($cxn, "latin1");

class Sql {

    private $type;
    private $table;
    private $name;
    private $join;
    private $select;
    private $update;
    private $insert;
    private $set;
    private $order;
    private $group;
    private $where;
    private $limit;
    private $offset;
    private $sql;
    private $result;

    public function __construct($table, $name = '') {
        $this->type = '';
        $this->table = $table;
        $this->name = $name;
        $this->join = array();
        $this->select = array();
        $this->update = array();
        $this->insert = array();
        $this->set = array();
        $this->order = array();
        $this->group = '';
        $this->where = array();
        $this->limit = 0;
        $this->offset = 0;
        $this->sql = '';
        $this->result = null;
    }

    public function get($param=null) {
        if($this->type==''){
            $this->select();
        }
        $regs = $this->fetch($param);
        if(is_object($regs)){
            $smart = new Registry($this->table, $regs);
        } elseif(is_array($regs)) {
            $smart = array();
            foreach($regs as $reg){
                $smart[] = new Registry($this->table, $reg);
            }
        } else {
            $smart = null;
        }
        return $smart;
    }

    static public function getSelect($table, $column = null, $content = null, $criteria = '=', $limit = null, $param = null) {
        $c = new Sql($table);
        $c->select();
        if ($column != null) {
            $c->where($column, $content, $criteria);
        }
        if ($limit != null) {
            $c->limit($limit);
        }
        return $c->get($param);
    }

    static public function doSelect($table, $column = null, $content = null, $criteria = '=', $limit = null, $param = null) {
        $c = new Sql($table);
        $c->select();
        if ($column != null) {
            $c->where($column, $content, $criteria);
        }
        if ($limit != null) {
            $c->limit($limit);
        }
        $c->execute();
        return $c->fetch($param);
    }

    static public function doUpdate($table, $regs, $column, $content, $criteria = '=', $param = null) {
        $c = new Sql($table);
        $c->update($regs);
        $c->where($column, $content, $criteria);
        return $c->execute($param);
    }

    static public function doDelete($table, $column, $content, $criteria = '=', $param = null) {
        $c = new Sql($table);
        $c->delete();
        $c->where($column, $content, $criteria);
        return $c->execute($param);
    }

    static public function doInsert($table, $columns = null, $contents = null, $param = null) {
        $c = new Sql($table);
        $c->insert($columns, $contents);
        return $c->execute($param);
    }

    static public function doCount($table, $column = null, $content = null, $criteria = '=') {
        $c = new Sql($table);
        $c->select();
        if ($column != null) {
            $c->where($column, $content, $criteria);
        }
        $res = $c->fetch('count');
        return $res;
    }

    static public function doSql($sql) {
        $class = new Sql('');
        $class->sql($sql);
        return $class->fetch();
    }

    public function select($columns = null) {
        $this->type = 'select';
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $this->select[] = $column;
            }
        } elseif ($columns != null) {
            $this->select[] = $columns;
        }
    }

    public function update($regs = array(), $cont = false) {
        $this->type = 'update';
        if (!is_array($regs) && $cont !== false) {
            $this->update[$regs] = $cont;
        } else {
            foreach ($regs as $column => $content) {
                $this->update[$column] = $content;
            }
        }
    }

    public function delete() {
        $this->type = 'delete';
    }

    public function insert($columns = array(), $contents = array()) {
        $this->type = 'insert';
        if (count($columns) > 0) {
            $this->insert = $columns;
        }
        if (count($contents) > 0) {
            $this->set[] = $contents;
        }
    }

    public function set($contents) {
        $this->set[] = $contents;
    }

    public function values($contents) {
        self::set($contents);
    }

    public function where($column, $content, $criteria = '=') {
        if (isset($column)) {
            $this->where[] = array(
                'column' => $column,
                'content' => $content,
                'criteria' => $criteria,
                'union' => 'and'
            );
        }
    }

    public function orWhere($column, $content, $criteria = '=') {
        if(isset($column)) {
            $this->where[] = array(
                'column' => $column,
                'content' => $content,
                'criteria' => $criteria,
                'union' => 'or'
            );
        }
    }

    public function limit($limit) {
        $this->limit = $limit;
    }

    public function group($column) {
        $this->group = $column;
    }

    public function order($column, $type = null) {
        $this->order[] = array(
            'column' => $column,
            'type' => $type
        );
    }

    public function offset($offset) {
        $this->offset = $offset;
    }

    public function page($page,$limit=null) {
        if($limit!=null&&$this->limit==0){
            $this->limit($limit);
        }
        if($page!=0){
            $this->offset = $this->limit * ($page-1);
        }
    }

    public function sql($sql) {
        $this->sql = $sql;
    }

    public function execute($param = null) {
        //Poner las funciones dentro de cada tipo de execute
        //Si es select poner las de select, si es update las que necesite update, etc
        self::prepare();
        switch ($param) {
            case 'sql':
                return $this->sql;
            case 'object':
                return $this;
        }
        switch ($this->type) {
            case 'select':
                break;
            case 'update':
                self::do_update();
                return $this->result;
            case 'delete':
                self::do_delete();
                return $this->result;
            case 'insert':
                self::do_insert();
                return $this->result;
        }
    }

    public function fetch($fetch = null) {
        $object = ($fetch == null) || ($fetch == 'object');
        $sql = ($fetch == 'sql');
        $limit = is_int($fetch);
        $arr_assoc = ($fetch == 'array') || ($fetch == 'assoc');
        $arr_num = ($fetch == 'num');
        $count = ($fetch == 'count');
        $id = ($fetch == 'id');
        $field = (strpos($fetch,'field:')!==false);
        if ($this->type == '') {
            $this->select();
        }
        if (!($this->sql != '' && $this->table == '')) {
            self::prepare();
        }
        switch (true) {
            case $object:
                self::fetch_object();
                return $this->result;
            case $sql:
                return $this->sql;
            case $limit:
                self::fetch_limit($fetch);
                return $this->result;
            case $arr_assoc:
                self::fetch_array();
                return $this->result;
            case $arr_num:
                self::fetch_array('num');
                return $this->result;
            case $count:
                $cant = self::fetch_count();
                return $cant;
            case $id:
                self::fetch_field('field:id');
                return $this->result;
            case $field:
                self::fetch_field($fetch);
                return $this->result;
            default:
                return 'error';
        }
    }

    static public function index_by(&$objects_array,$field){
        $a_array = $objects_array;
        $objects_array = array();
        foreach ($a_array as $reg){
            $objects_array[$reg->$field] = $reg;
        }
    }

    //PRIVATE FUNCTIONS
    private function prepare() {
        $sql = '';
        switch ($this->type) {
            case 'select':
                $sql .= self::prepare_select();
                $sql .= self::prepare_from();
                $sql .= self::prepare_where();
                $sql .= self::prepare_group();
                $sql .= self::prepare_order();
                $sql .= self::prepare_limit();
                $sql .= self::prepare_offset();
                $this->sql = $sql;
                break;
            case 'update':
                $sql .= self::prepare_update();
                $sql .= self::prepare_where();
                $sql .= self::prepare_order();
                $this->sql = $sql;
                break;
            case 'delete':
                $sql .= self::prepare_delete();
                $sql .= self::prepare_from();
                $sql .= self::prepare_where();
                $this->sql = $sql;
                break;
            case 'insert':
                $sql .= self::prepare_insert();
                $sql .= self::prepare_set();
                $this->sql = $sql;
                break;
        }
    }

    private function prepare_select() {
        $select = "SELECT ";
        if (count($this->select) > 0) {
            $i = 0;
            foreach ($this->select as $column) {
                if ($i > 0) {
                    $select .= ',';
                }
                $select .= "$column ";
                $i++;
            }
        } else {
            $select .= "* ";
        }
        return $select;
    }

    private function prepare_update() {
        global $cxn;
        $update = "UPDATE `$this->table` $this->name SET ";
        $i = 0;
        foreach ($this->update as $column => $content) {
            if (is_string($content)) {
                $content = mysqli_real_escape_string($cxn,$content);
                $content = "'$content'";
            }
            if (is_null($content)) {
                $content = "NULL";
            }
            if ($i > 0) {
                $update .= ',';
            }
            $update .= "$column = $content ";
            $i++;
        }
        return $update;
    }

    private function prepare_delete() {
        $this->sql = "DELETE ";
        return $this->sql;
    }

    private function prepare_insert() {
        $sql = "INSERT INTO `$this->table` ";
        if (count($this->insert) > 0) {
            $columns = "";
            $i = 0;
            foreach ($this->insert as $column) {
                if ($i > 0) {
                    $columns .= ",";
                }
                $columns .= "$column";
                $i++;
            }
            $sql .= "($columns) ";
        } else {
            $sql .= '() ';
        }
        return $sql;
    }

    private function prepare_set() {
        global $cxn;
        $i = 0;
        if (count($this->set) > 0) {
            $sql = "SET ";
            foreach ($this->set as $set) {
                if ($i > 0) {
                    $sql .= ",";
                }
                $content = "";
                $j = 0;
                foreach ($set as $value) {
                    if ($j > 0) {
                        $content .= ",";
                    }
                    if (is_string($value)) {
                        $value = mysqli_real_escape_string($cxn,$value);
                        $value = "'$value'";
                    }
                    if (is_null($value)) {
                        $value = "NULL";
                    }
                    $content .= "$value";
                    $j++;
                }
                $sql .= "($content)";
                $i++;
            }
        }
        if ($this->type == 'insert') {
            //ESCAPAR
            $sql = "VALUES ($content) ";
        }
        return $sql;
    }

    private function prepare_from() {
        return "FROM `$this->table` $this->name ";
    }

    private function prepare_where() {
        global $cxn;
        $where = '';
        if (count($this->where) > 0) {
            $where .= "WHERE ";
            $i = 0;
            foreach ($this->where as $condition) {
                $column = $condition['column'];
                $content = $condition['content'];
                $criteria = $condition['criteria'];
                $union = (isset($condition['union']))? $condition['union']:'';
                if (is_string($content)) {
                    $content = mysqli_real_escape_string($cxn,$content);
                    $content = "'$content'";
                } elseif (is_array($content)) {
                    if($criteria=='='){
                        $criteria = 'IN';
                    } elseif ($criteria=='!='){
                        $criteria = 'NOT IN';
                    }
//                    foreach($content as $key => $value){
//                        if(is_string($value)){
//                            $value = mysqli_real_escape_string($cxn,$value);
//                            $content[$key] = "'$value'";
//                        }
//                    }
//                    $content = "(".  implode(',', $content) .")";
                    //PARCHE
                    if(in_array('',$content)){
                        $content = "(".  implode(',', $content) .")";
                    } else {
                        $content = "('".  implode("','", $content) ."')";
                    }
                    //
                }
                if ($i > 0) {
                    if($union!=''){
                        $where .= "$union ";
                    } else {
                        $where .= "AND ";
                    }
                }
                if ($content === null) {
                    if ($criteria == '!=') {
                        $where .= "$column IS NOT NULL ";
                    } else {
                        $where .= "$column IS NULL ";
                    }
                } else {
                    $where .= "$column $criteria $content ";
                }
                $i++;
            }
        }
        return $where;
    }

    private function prepare_group() {
        if ($this->group != '') {
            return "GROUP BY $this->group ";
        }
    }

    private function prepare_order() {
        $orderby = '';
        if (count($this->order) > 0) {
            $orderby .= "ORDER BY ";
            $i = 0;
            foreach ($this->order as $order) {
                $column = $order['column'];
                if ($order['type'] != null) {
                    $type = strtoupper($order['type']);
                    $column .= " $type";
                }
                if ($i > 0) {
                    $orderby .= ", ";
                }
                $orderby .= "$column ";
                $i++;
            }
        }
        return $orderby;
    }

    private function prepare_limit() {
        if ($this->limit > 0) {
            return "LIMIT $this->limit ";
        }
    }

    private function prepare_offset() {
        if ($this->offset > 0) {
            return "OFFSET $this->offset ";
        }
    }

    private function fetch_object($sql = null) {
        global $cxn;
        if ($sql == null) {
            $res = mysqli_query($cxn, $this->sql);
        } else {
            $res = mysqli_query($cxn, $sql);
        }
        $results = null;
        while ($result = mysqli_fetch_object($res)) {
            $results[] = $result;
        }
        if (count($results) == 1 && $this->limit == 1) {
            $this->result = $results[0];
        } else {
            if($results!=null){
                $this->result = $results;
            } else {
                $this->result = array();
            }
        }
    }

    private function fetch_field($cfield) {
        if(count($this->select)==0) {
            $field = trim(substr($cfield,strpos($cfield,":")+1)); //$select
//            if($pos = strpos($select,'as ')){
//                $field = trim(substr($select,$pos+3));
//            } else {
//                $field = $select;
//            }
            $this->select($field);
            self::fetch_object();
            $this->select = array();
            if(is_object($this->result) && $this->limit == 1){
                $this->result = $this->result->$field;
            } elseif(count($this->result)>0){
                foreach($this->result as $result){
                    $results[] = $result->$field;
                }
                $this->result = $results;
            } elseif(count($this->result)==0 && $this->limit == 1){
                $this->result = false;
            }
        } else {
            self::fetch_object();
        }
    }

    private function fetch_limit($limit) {
        if ($this->limit == 0) {
            $this->limit = $limit;
            $sql = $this->sql;
            $sql .= self::prepare_limit();
            self::fetch_object($sql);
            $this->limit = 0;
        }
    }

    private function fetch_array($type = null) {
        global $cxn;
        $res = mysqli_query($cxn, $this->sql);
        $results = null;
        if ($type == 'num') {
            while ($result = mysqli_fetch_row($res)) {
                $results[] = $result;
            }
        } else {
            while ($result = mysqli_fetch_assoc($res)) {
                $results[] = $result;
            }
        }
        if (count($results) == 1 && $this->limit == 1) {
            $this->result = $results[0];
        } else {
            $this->result = $results;
        }
    }

    private function fetch_count() {
        global $cxn;
        $res = mysqli_query($cxn, $this->sql);
        $cant = mysqli_num_rows($res);
        return $cant;
    }

    private function do_update() {
        global $cxn;
        if (mysqli_query($cxn, $this->sql)) {
            $this->result = true;
        } else {
            $this->result = false;
        }
    }

    private function do_delete() {
        global $cxn;
        if (mysqli_query($cxn, $this->sql)) {
            $this->result = true;
        } else {
            $this->result = false;
        }
    }

    private function do_insert() {
        global $cxn;
        if (mysqli_query($cxn, $this->sql)) {
            if($key = mysqli_insert_id($cxn)){
                $this->result = $key;
            } else {
                $this->result = true;
            }
        } else {
            $this->result = false;
        }
    }

}

class Registry {

    private $table;
    private $key;
    private $val;
    private $fields;
    private $vars;

    public function __construct($table,$object=null) {
        if(is_object($object)){
            $this->table = $table;
            $key = $this->table_key();
            foreach (get_object_vars($object) as $field => $value)
            {
                if($field == $key){
                    $this->key = $field;
                    $this->val = $value;
                } else {
                    $this->fields[$field] = $value;
                }
            }
        } elseif($object==null) {
            $this->table = $table;
            $fields = $this->get_fields();
            foreach($fields as $field){
                if($field->Key == 'PRI'){
                    $this->key = $field->Field;
                } else {
                    if(($field->Default==null) && $field->Null == 'NO'){
                        //seteo defaults
                        if(strpos($field->Type,'varchar')!==false){
                            $this->fields[$field->Field] = '';
                        } elseif(strpos($field->Type,'text')!==false){
                            $this->fields[$field->Field] = '';
                        } elseif(strpos($field->Type,'int')!==false){
                            $this->fields[$field->Field] = 0;
                        } elseif(strpos($field->Type,'datetime')!==false){
                            $this->fields[$field->Field] = '0000-00-00 00:00:00';
                        } elseif(strpos($field->Type,'date')!==false){
                            $this->fields[$field->Field] = '0000-00-00';
                        } elseif(strpos($field->Type,'float')!==false){
                            $this->fields[$field->Field] = 0;
                        } elseif(strpos($field->Type,'real')!==false){
                            $this->fields[$field->Field] = 0;
                        } elseif(strpos($field->Type,'double')!==false){
                            $this->fields[$field->Field] = 0;
                        }
                    } else {
                        $this->fields[$field->Field] = $field->Default;
                    }
                }
            }
        }
        if(!isset($this->key)){
            unset($this->table);
        }
    }

    public function __get($field) {
        if ($field == $this->key) {
            return $this->val;
        } elseif(is_array($this->fields) && array_key_exists($field, $this->fields)) {
            return $this->fields[$field];
        } elseif(is_array($this->vars) && array_key_exists($field, $this->vars)) {
            return $this->vars[$field];
        }
    }

    public function __set($field, $value) {
        if ($field == $this->key) {

        } elseif (is_array($this->fields) && array_key_exists($field, $this->fields)) {
            $this->fields[$field] = $value;
        } else {
            $this->vars[$field] = $value;
        }
    }

    public function save($param=null) {
        if(isset($this->key)&&isset($this->val)){
            $c = new Sql($this->table);
            $c->update($this->fields);
            $c->where($this->key, $this->val);
            $resp = $c->execute($param);
            return $resp;
        } elseif(isset($this->key)&&!isset($this->val)) {
            foreach($this->fields as $column => $content){
                $columns[] = $column;
                $contents[] = $content;
            }
            $c = new Sql($this->table);
            $c->insert($columns,$contents);
            $key_val = $c->execute($param);
            $this->val = $key_val;
            return $key_val;
        } else {
            return false;
        }
    }

    public function delete($param=null) {
        if(isset($this->key)&&isset($this->val)){
            $c = new Sql($this->table);
            $c->delete();
            $c->where($this->key, $this->val);
            $resp = $c->execute($param);
            if($resp){
                unset($this->fields);
                unset($this->table);
                unset($this->key);
                unset($this->val);
            }
            return $resp;
        } else {
            return false;
        }
    }

    private function table_key() {
        global $cxn;
        $sql = "SHOW KEYS FROM `$this->table` WHERE Key_name = 'PRIMARY'";
        $res = mysqli_query($cxn, $sql);
        if ($res) {
            $key_info = mysqli_fetch_object($res);
            return $key_info->Column_name;
        } else {
            return false;
        }
    }

    public function get_fields() {
        global $cxn;
        $sql = "SHOW COLUMNS FROM $this->table";
        $res = mysqli_query($cxn,$sql);
        if($res){
            $fields = array();
            while($field = mysqli_fetch_object($res)):
                $fields[] = $field;
            endwhile;
        }
        return $fields;
    }
}
