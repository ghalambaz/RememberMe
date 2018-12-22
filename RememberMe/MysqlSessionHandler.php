<?php
/*
 * Credits
 *
 * This class was created by David Powers for the Managing PHP Persistent
 * Sessions course on lynda.com. It's based on PDOSessionHandler in the
 * Symfony HttpFoundation component (https://github.com/symfony/
 * HttpFoundation/blob/master/Session/Storage/Handler/PdoSessionHandler.php).
 * David Powers gratefully acknowledges the work of the original author, and
 * releases this version under the same MIT license.
 *
 * Copyright (c) 2004-2015 Fabien Potencier
 * Copyright (c) 2015 David Powers
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

namespace RememberMe;

/**
 * Class MysqlSessionHandler
 * @package Foundationphp\Sessions
 *
 * Custom session handler to store session data in MySQL/MariaDB
 */
abstract class MysqlSessionHandler extends ModelAbstract implements \SessionHandlerInterface
{

    /**
     * @var int Unix timestamp indicating when session should expire
     */
    protected $expiry;
    /**
     * @var \PDO MySQL database connection
     */
    protected $db;
    /**
     * @var Properties
    */
    protected $properties;
    /**
     * An array to support multiple reads before closing (manual, non-standard usage)
     *
     * @var array Array of statements to release application-level locks
     */
    protected $unlockStatements = [];

    /**
     * Constructor
     *
     * Requires a MySQL PDO database connection to the sessions table.
     * By default, the session handler uses transactions, which requires
     * the use of the InnoDB engine. If the sessions table uses the MyISAM
     * engine, set the optional second argument to false.
     *
     * @param Properties $properties
     * @param bool $useTransactions Determines whether to use transactions (default)
     */
    public function __construct(Properties $properties,$useTransactions = true)
    {
        parent::__construct($properties->getDb());
        $this->properties = $properties;
        $this->db = $this->connection;
        $this->properties->setUseTransactions($useTransactions);
        $this->expiry = time() + (int) ini_get('session.gc_maxlifetime');
    }
    /**
     * Opens the session
     *
     * @param string $save_path
     * @param string $name
     * @return bool
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Reads the session data
     *
     * @param string $session_id
     * @return string
     */
    public function read($session_id)
    {
        try {
            if ($this->expiry) {
                // MySQL's default isolation, REPEATABLE READ, causes deadlock for different sessions.
                $this->db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
                $this->db->beginTransaction();
            } else {
                $this->unlockStatements[] = $this->getLock($session_id);
            }
            $sql = "SELECT ".$this->properties->getColExpiry().",".$this->properties->getColData()."
            FROM ".$this->properties->getTableSess()." WHERE ".$this->properties->getColSid()." = :sid";
            // When using a transaction, SELECT FOR UPDATE is necessary
            // to avoid deadlock of connection that starts reading
            // before we write.
            if ($this->properties->isUseTransactions()) {
                $sql .= ' FOR UPDATE';
            }
            $selectStmt = $this->db->prepare($sql);
            $selectStmt->bindParam(':sid', $session_id);
            $selectStmt->execute();
            $results = $selectStmt->fetch(\PDO::FETCH_ASSOC);
            if ($results) {
                if ($results[$this->properties->getColExpiry()] < time()) {
                    // Return an empty string if data out of date
                    return '';
                }
                return $results[$this->properties->getColData()];
            }
            // We'll get this far only if there are no results, which means
            // the session hasn't yet been registered in the database.
            if ($this->properties->isUseTransactions()) {
                $this->initializeRecord($selectStmt);
            }
            // Return an empty string if transactions aren't being used
            // and the session hasn't yet been registered in the database.
            return '';
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Writes the session data to the database
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
            VALUES (:sid, :expiry, :data)
            ON DUPLICATE KEY UPDATE
            ".$this->properties->getColExpiry()." = :expiry,
            ".$this->properties->getColData()." = :data";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':expiry', $this->expiry, \PDO::PARAM_INT);
            $stmt->bindParam(':data', $data);
            $stmt->bindParam(':sid', $session_id);
            $stmt->execute();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            throw $e;
        }
    }

    /**
     * Closes the session and writes the session data to the database
     *
     * @return bool
     */
    public function close()
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        } elseif ($this->unlockStatements) {
            while ($unlockStmt = array_shift($this->unlockStatements)) {
                $unlockStmt->execute();
            }
        }
        if ($this->properties->isCollectGarbage()) {
            $sql = "DELETE FROM ".$this->properties->getTableSess()." WHERE ".$this->properties->getColExpiry()." < :time";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
            $stmt->execute();
            $this->properties->setCollectGarbage(false);
        }
        return true;
    }

    /**
     * Destroys the session
     *
     * @param int $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        $sql = "DELETE FROM ".$this->properties->getTableSess()." WHERE ".$this->properties->getColSid()." = :sid";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':sid', $session_id);
            $stmt->execute();
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
        return true;
    }

    /**
     * Garbage collection
     *
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        $this->properties->setCollectGarbage(true);
        return true;
    }

    /**
     * Executes an application-level lock on the database
     *
     * @param $session_id
     * @return \PDOStatement Prepared statement to release the lock
     */
    protected function getLock($session_id)
    {
        $stmt = $this->db->prepare('SELECT GET_LOCK(:key, 50)');
        $stmt->bindValue(':key', $session_id);
        $stmt->execute();

        $releaseStmt = $this->db->prepare('DO RELEASE_LOCK(:key)');
        $releaseStmt->bindValue(':key', $session_id);

        return $releaseStmt;
    }

    /**
     * Registers new session ID in database when using transactions
     *
     * Exclusive-reading of non-existent rows does not block, so we need
     * to insert a row until the transaction is committed.
     *
     * @param \PDOStatement $selectStmt
     * @return string
     */
    protected function initializeRecord(\PDOStatement $selectStmt)
    {
        try {
            $sql = "INSERT INTO ".$this->properties->getTableSess()." (".$this->properties->getColSid().", ".$this->properties->getColExpiry().", ".$this->properties->getColData().")
                VALUES (:sid, :expiry, :data)";

            $insertStmt = $this->db->prepare($sql);
            $insertStmt->bindParam(':sid', $session_id);
            $insertStmt->bindParam(':expiry',$this->expiry , \PDO::PARAM_INT);
            $insertStmt->bindValue(':data', '');
            $insertStmt->execute();
            return '';
        } catch (\PDOException $e) {
            // Catch duplicate key error if the session has already been created.
            if (0 === strpos($e->getCode(), '23')) {
                // Retrieve existing session data written by the current connection.
                $selectStmt->execute();
                $results = $selectStmt->fetch(\PDO::FETCH_ASSOC);
                if ($results) {
                    return $results[$this->properties->getColData()];
                }
                return '';
            }
            // Roll back transaction if the error was caused by something else.
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            throw $e;
        }
    }
}