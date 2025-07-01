<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'tutorapp';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function executeQuery($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            
            // Special handling for LIMIT and OFFSET
            foreach ($params as $key => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                if (is_numeric($key)) {
                    // For unnamed placeholders
                    $stmt->bindValue($key + 1, $value, $paramType);
                } else {
                    // For named placeholders
                    $stmt->bindValue($key, $value, $paramType);
                }
            }
            
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Params: " . print_r($params, true));
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    public function getVersion() {
        return $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION);
    }
}
?> 