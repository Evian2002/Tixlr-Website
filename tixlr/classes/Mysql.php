<?php
class Mysql {
    private $host;
    private $user;
    private $pass;
    private $conn;

    public function __construct($host, $user, $pass) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function connect() {
        $this->conn = mysqli_connect($this->host, $this->user, $this->pass);
        if (!$this->conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        return $this->conn;
    }

    public function selectDB($db) {
        return mysqli_select_db($this->conn, $db);
    }

    public function query($sql) {
        return mysqli_query($this->conn, $sql);
    }

    public function fetchArray($result) {
        return mysqli_fetch_array($result, MYSQLI_ASSOC);
    }
}
?>
