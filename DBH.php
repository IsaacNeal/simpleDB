<?php
/**
 * Description of DBH
 * @var (array) $PS         PDOStatement object storage array
 * @var (array) $db_info    Server variable constants array
 *
 * @author Isaac Price | www.ipriceproductions.com
 */
if(file_exists(dirname(__FILE__).'/../config/settings.inc.php')){
    include_once(dirname(__FILE__).'/../config/settings.inc.php');
}
class DBH {
    protected $db_info = array("host"=>_DB_SERVER_, "user"=>_DB_USER_, "password"=>_DB_PASSWD_, 'database' => _DB_NAME_);
    private static $PS = array();
    private $dbh = NULL;
    
    public static $instance = NULL;
    
    private function __construct(array $db_info = null) {
        if(isset($db_info)){
            foreach($db_info as $key_name => $key_value){
                if(!in_array($key_value, array("host", "db_name", "username", "password")) || empty($key_value)){
                    throw new Exception("Invalid key value pairs");
                }
                $this->db_info = $db_info;
            }
        }
        try{
        $this->dbh = new PDO("mysql:host={$this->db_info['host']};dbname={$this->db_info['database']}", $this->db_info['user'],
                $this->db_info['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        catch(PDOException $e){
            echo $e->getMessage();
            exit;
        }
    }
    
    public static function getInstance() {
        if(!isset(self::$instance)){
            self::$instance = new DBH();
        }
        return self::$instance;
    }
    
    public function disconnect() {
        $this->dbh = NULL;
    }
    
    public function insertId() {
        return $this->dbh->lastInsertId();
    }
    
    public function numRows($result) {
        return $result->rowCount();
    }
    
    public function fetchRows($sql) {
        $stmt = $this->dbh->query($sql);
        return $stmt->fetchAll();
    }
    
    public function fetchRow($sql) {
        $stmt = $this->dbh->query($sql);
        return $stmt->fetch();
    }
    
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    public function endTransaction() {
        return $this->dbh->commit();
    }
    
    public function cancelTransaction() {
        return $this->dbh->rollBack();
    }
    
    public function autoInsert($table, $values, $limit = false) {
        $stmt = 'INSERT INTO `'.$table.'` (';
        foreach($values as $key => $val)
            $stmt .= '`'.$key.'`,';
           $stmt = rtrim($stmt, ',').') VALUES (';
           foreach ($values AS $key => $value)
		$stmt .= '\''.(is_bool($value) ? (int)$value : $value).'\',';
		$stmt = rtrim($stmt, ',').')';
        
          if ($limit)
            $stmt .= ' LIMIT '.(int)($limit);
          
        try {
            $ret = $this->dbh->query($stmt);
        }
        catch(PDOException $e){
            $ermsg = 'The following server error has occured: '.$e->getMessage();
            throw new Exception($ermsg, 1);
           
        }
          return $ret;
    }
    
    public function autoUpdate($table, $values, $where = false, $limit = false) {
        $stmt = 'UPDATE `'.$table.'` SET ';
        foreach ($values AS $key => $value)
            $stmt .= '`'.$key.'` = \''.(is_bool($value) ? (int)$value : $value).'\',';
           $stmt = rtrim($stmt, ',');
        
         if ($where)
            $stmt .= ' WHERE '.$where;
         if($limit)
             $stmt .= ' LIMIT '.(int)($limit);
         try {
            $ret = $this->dbh->query($stmt);
        }
        catch(PDOException $e){
            $ermsg = 'The following server error has occured: '.$e->getMessage();
            throw new Exception($ermsg, 1);
           
        }
          return $ret;
    }
    
    public function prepare($index, $sql) {
        if(isset(self::$PS[$index])){
            $ermsg = "Index [$index] is already in use.";
            throw new Exception($ermsg, 1);
        }
        try{
            self::$PS[$index] = $this->dbh->prepare($sql);
        }
        catch(PDOException $e){
            return false;
        }
        return true;
    }
    
    public function execute($index, Array $param = array()) {
        if(!isset(self::$PS[$index])){
            $ermsg = "Index [$index] is unavailable.";
            throw new Exception($ermsg, 1);
        }
        
        foreach($param as $key => $val){
            if(is_int($key)) ++$key;
            
            $type = $this->getValueType($val);
            
            $bnd = self::$PS[$index]->bindValue($key, $val, $type);
            
            if(!$bnd){
                $ermsg = "Paramater '$key' in [$index] failed to bind";
                throw new Exception($ermsg, 2);
            }
            
        }
        
        try{
            $bnd = self::$PS[$index]->execute();
        }
        catch(PDOException $e){
            $ermsg = "PDO-Error while executing prepared statement [$index]<br />".$e->getMessage();
            throw new Exception($ermsg, 3);
        }
        
        if($bnd === false){
            $ermsg = "Result error in prepared statement [$index]";
            throw new Exception($ermsg, 3);
        }
        
        return self::$PS[$index];
    }
    
    private function getValueType($value) {
        if(is_int($value))
            return PDO::PARAM_INT;
        if(is_string($value))
            return PDO::PARAM_STR;
        if(is_bool($value))
            return PDO::PARAM_BOOL;
        if(is_null($value))
            return PDO::PARAM_NULL;
       // if(is_binary($value)) return PDO::PARAM_LOB; // PHP 6
            
    }
}
