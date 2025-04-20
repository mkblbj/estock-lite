<?php

/**
 * 数据库连接类
 * 使用单例模式确保整个应用只有一个数据库连接实例
 */
class DB
{
    // 单例实例
    private static $instance = null;
    
    // PDO实例
    private $pdo;
    
    // 数据库配置信息
    private $config;
    
    /**
     * 私有构造函数，防止直接实例化
     */
    private function __construct($config = [])
    {
        // 如果没有传入配置，加载默认配置
        if (empty($config)) {
            $this->config = require_once __DIR__ . '/../data/mysql_config.php';
        } else {
            $this->config = $config;
        }
        
        $this->connect();
    }
    
    /**
     * 私有克隆方法，防止克隆实例
     */
    private function __clone() {}
    
    /**
     * 获取DB实例
     * 
     * @param array $config 数据库配置
     * @return DB
     */
    public static function getInstance($config = [])
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * 连接数据库
     */
    private function connect()
    {
        $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
        
        try {
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 获取PDO实例
     * 
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }
    
    /**
     * 执行SQL查询
     * 
     * @param string $sql SQL语句
     * @param array $params 绑定参数
     * @return PDOStatement
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * 获取单条记录
     * 
     * @param string $sql SQL语句
     * @param array $params 绑定参数
     * @return array|false
     */
    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * 获取多条记录
     * 
     * @param string $sql SQL语句
     * @param array $params 绑定参数
     * @return array
     */
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * 执行SQL语句
     * 
     * @param string $sql SQL语句
     * @return int 受影响的行数
     */
    public function exec($sql)
    {
        return $this->pdo->exec($sql);
    }
    
    /**
     * 获取最后插入的ID
     * 
     * @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * 开始事务
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * 提交事务
     */
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    /**
     * 回滚事务
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}