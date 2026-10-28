<?php
class Event {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAllEvents() {
        $sql = "SELECT * FROM events ORDER BY date ASC";
        return mysqli_query($this->conn, $sql);
    }
}
?>
