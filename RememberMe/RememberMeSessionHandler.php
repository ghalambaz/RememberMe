<?php
namespace RememberMe;

class RememberMeSessionHandler extends MysqlSessionHandler
{

    public function __construct(Properties $properties, $useTransactions = true)
    {
        parent::__construct($properties, $useTransactions);
    }

    /**
     *
     * @param string $session_id
     * @param string $data
     * @return bool
     */
    public function write($session_id, $data)
    {
        try {
            $sql = "INSERT INTO ".$this->properties->getTableSess()." (".$this->properties->getColSid().",
            ".$this->properties->getColExpiry().", ".$this->properties->getColData().")
            VALUES (:sid, :expiry, :dt)
            ON DUPLICATE KEY UPDATE
            ".$this->properties->getColExpiry()." = :expiry2,
            ".$this->properties->getColData()." = :dt2";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':expiry',$this->expiry , \PDO::PARAM_INT);
            $stmt->bindParam(':dt', $data);
            $stmt->bindParam(':sid', $session_id);
            $stmt->bindParam(':expiry2', $this->expiry, \PDO::PARAM_INT);
            $stmt->bindParam(':dt2', $data);
            $stmt->execute();
            if (isset($_SESSION[$this->properties->getSessPersist()]) || isset($_SESSION[$this->properties->getCookie()])) {
                $this->storeRememberMeData($data);
            }
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            throw $e;
        }
    }

    /**
     * Copies the user's session data to the autologin table
     *
     * @param string $data Session data
     */
    protected function storeRememberMeData($data)
    {
        // Get the user key if it's not already stored as a session variable
        if (!isset($_SESSION[$this->properties->getSessUkey()])) {
            $sql = "SELECT ".$this->properties->getColUkey()." FROM ".$this->properties->getTableUsers()."
                WHERE ".$this->properties->getTableSess()." = :username";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $_SESSION[$this->properties->getSessUname()]);
            $stmt->execute();
            $_SESSION[$this->properties->getSessUkey()] = $stmt->fetchColumn();
        }
        // Copy the session data to the autologin table
        $sql = "UPDATE ".$this->properties->getTableAutologin()."
            SET ".$this->properties->getColData()." = :data WHERE ".$this->properties->getColUkey()." = :key";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':data', $data);
        $stmt->bindValue(':key', $_SESSION[$this->properties->getSessUkey()]);
        $stmt->execute();
    }
}