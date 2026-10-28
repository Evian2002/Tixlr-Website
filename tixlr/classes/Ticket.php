<?php
class Ticket {
    private $conn;
    private $ticket_id;

    public function __construct($conn, $ticket_id) {
        $this->conn = $conn;
        $this->ticket_id = $ticket_id;
    }

    public function getDetails() {
        $sql = "SELECT * FROM tickets WHERE ticket_id = " . intval($this->ticket_id);
        return mysqli_fetch_assoc(mysqli_query($this->conn, $sql));
    }
}
?>
