<?php

namespace RememberMe;

/*
 * Credits
 *
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

 * Copyright (c) 2018 Ali Ghalambaz
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


class RememberMe extends ModelAbstract
{

    /**
     * @var Properties
     */
    protected $properties;

    /**
     * @var int Position at which user key is inserted in single-use token
     */
    protected $token_index;

    /**
     * @var int Unix timestamp for when the cookie expires
     */
    protected $expiry;

    /**
     * @var string Path to be set in the autologin cookie
     */
    protected $cookiePath = '/';

    /**
     * @var string Domain for autologin cookie
     */
    protected $domain = '';

    /**
     * @var null Whether cookie should be sent only over a secure connection
     */
    protected $secure = null;

    /**
     * @var bool Whether cookie should be accessible only through HTTP protocol
     */
    protected $httponly = true;


    /**
     * Constructor
     *
     * Requires a PDO connection to the database where the user's credentials,
     * session data, and autologin details are stored.
     *
     * @param Properties $properties
     * @param int $token_index Position at which user key is to be inserted in token
     */
    public function __construct(Properties $properties, $token_index = 0)
    {
        parent::__construct($properties->getDb());
        $this->properties = $properties;
        if ($this->connection->getAttribute(\PDO::ATTR_ERRMODE) !== \PDO::ERRMODE_EXCEPTION) {
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        $this->token_index = ($token_index <= 31) ? $token_index : 31;
        $this->expiry = time() + ($this->properties->getLifetimeDays() * 60 * 60 * 24);

    }

    /**
     * Creates a persistent login for the user
     *
     * The login process gets the user's unique key (an 8-digit
     * hexadecimal string), and stores it with a random 32-digit
     * string. Both values are merged and appended to the
     * username to create a single-use token that's stored as a
     * cookie in the user's browser.
     */
    public function start()
    {
        // Get the user's ID
        if ($_SESSION[$this->properties->getSessUkey()] = $this->getUserKey()) {
            $this->getExistingData();
            // Generate a random 32-digit hexadecimal token
            $token = $this->generateToken();
            // Store the token and user's ID in the database
            $this->storeToken($token);
            // Store the single-use token as a cookie in the user's browser
            $this->setCookie($token);
            $_SESSION[$this->properties->getSessPersist()] = true;
            unset($_SESSION[$this->properties->getCookie()]);
        }
    }

    /**
     * Check if a valid persistent cookie has been presented
     *
     * If the cookie exists, the user's unique key is retrieved
     * from the database and removed from the single-use token.
     * Before checking for a matching pair, expired tokens are
     * deleted from the database. If the token is valid, data from
     * the stored session is retrieved and added to the current
     * session.
     */
    public function login()
    {
        // Do nothing if the cookie doesn't exist
        if (isset($_COOKIE[$this->properties->getCookie()])) {

            if ($storedToken = $this->parseCookie()) {
                // Delete expired tokens before checking the current one
                $this->clearOld();
                // Log in the user if the token hasn't been used

                $res = $this->checkCookieToken($storedToken);
                if (is_null($res)) {
                    $_SESSION = [];
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 86400, $params['path'], $params['domain'],
                        $params['secure'], $params['httponly']);
                    session_destroy();
                    // Invalidate the autologin cookie
                    setcookie($this->properties->getCookie(), '', time() - 86400, $this->cookiePath,
                        $this->domain, $this->secure, $this->httponly);
                    return false;
                } else if ($res['used'] == 0) {
                    // Log in the user
                    $this->cookieLogin($storedToken);
                    // Generate and store a fresh single-use token
                    $newToken = $this->generateToken();
                    $this->storeToken($newToken);
                    $this->setCookie($newToken);
                    return true;
                } else {
                    // If the token has already been used, suspect an attack,
                    // delete all tokens associated with the user key,
                    // and invalidate the current session.
                    $this->deleteAll();
                    $_SESSION = [];
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 86400, $params['path'], $params['domain'],
                        $params['secure'], $params['httponly']);
                    session_destroy();
                    // Invalidate the autologin cookie
                    setcookie($this->properties->getCookie(), '', time() - 86400, $this->cookiePath,
                        $this->domain, $this->secure, $this->httponly);
                    return false;
                }
            }
        }
        return false;
    }


    public function sessionLogout()
    {
        $_SESSION = [];
        setcookie($this->properties->getCookie(), '', time() - 86400, $this->cookiePath,
            $this->domain, $this->secure, $this->httponly);
        session_destroy();
    }
    /**
     * Logs out the user from all sessions or just the current one
     *
     * @param bool $all True if all sessions are to be deleted
     */
    public function logout($all = true)
    {
        if ($all) {
            $this->deleteAll();

        } else {
            $token = $this->parseCookie();
            $sql = "UPDATE " . $this->properties->getTableAutologin() . " SET " . $this->properties->getColUsed() . " = 1
                    WHERE " . $this->properties->getColToken() . " = :token";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

        }
        $this->sessionLogout();

    }

    /**
     * Retrieves the user's ID from the users table
     *
     * @return string User's ID
     */
    protected function getUserKey()
    {
        $sql = "SELECT " . $this->properties->getColUkey() . " FROM " . $this->properties->getTableUsers() . "
                WHERE " . $this->properties->getColUsername() . " = :username";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':username', $_SESSION[$this->properties->getSessUname()], \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Retrieve the user's data from the most recent session
     */
    protected function getExistingData()
    {
        $sql = "SELECT " . $this->properties->getColData() . " FROM " . $this->properties->getTableAutologin() . "
                WHERE " . $this->properties->getColUkey() . " = :key
                ORDER BY " . $this->properties->getColCreated() . " DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':key', $_SESSION[$this->properties->getSessUkey()]);
        $stmt->execute();
        // Get the most recent result
        if ($data = $stmt->fetchColumn()) {
            // Populate the $_SESSION superglobal array
            session_decode($data);
        }
        // Release the database connection for other queries
        $stmt->closeCursor();
    }

    /**
     * Generates a random 32-character string for the single-use token
     *
     * @return string 32-character hexadecimal string
     */
    protected function generateToken()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * Stores the user's ID and single-use token in the database
     *
     * @param string $token 32-character hexadecimal string
     */
    protected function storeToken($token)
    {
        try {
            $sql = "INSERT INTO " . $this->properties->getTableAutologin() . "
                    (" . $this->properties->getColUkey() . ", " . $this->properties->getColToken() . ", " . $this->properties->getColData() . ")
                    VALUES (:key, :token , '')";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':key', $_SESSION[$this->properties->getSessUkey()]);
            $stmt->bindValue(
                ':token', trim($token));
            $stmt->execute();
        } catch (\PDOException $e) {
            echo $e->getMessage();
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Creates and stores autologin cookie in user's browser
     *
     * @param string $token 32-character single-use token
     */
    protected function setCookie($token)
    {
        $merged = str_split($token);
        array_splice($merged, hexdec($merged[$this->token_index]), 0, $_SESSION[$this->properties->getSessUkey()]);
        $merged = implode('', $merged);

        $token = $_SESSION[$this->properties->getSessUname()] . '|' . $merged;
        setcookie($this->properties->getCookie(), $token, $this->expiry,
            $this->cookiePath, $this->domain, $this->secure,
            $this->httponly);
    }

    /**
     * Removes the user_key from the cookie token
     *
     * @return array|bool Array containing the username and token, or false
     */
    protected function parseCookie()
    {
        // Separate the username and submitted token
        if(empty($_COOKIE[$this->properties->getCookie()])) return false;
        $parts = explode('|', $_COOKIE[$this->properties->getCookie()]);

        if (count($parts) > 1) {
            $_SESSION[$this->properties->getSessUname()] = $parts[0];
            $token = $parts[1];
        } else
            return false;

        // Proceed only if the username is valid
        if ($_SESSION[$this->properties->getSessUkey()] = $this->getUserKey()) {
            // Remove the user's ID from the submitted cookie token
            return str_replace($_SESSION[$this->properties->getSessUkey()], '', $token);
        } else {
            return false;
        }
    }

    /**
     * Deletes records older than the value set in the $lifetimeDays property
     */
    protected function clearOld()
    {
        $sql = "DELETE FROM " . $this->properties->getTableAutologin() . "
                WHERE DATE_ADD(" . $this->properties->getColCreated() . ", INTERVAL :expiry DAY) < NOW()";
        $stmt = $this->connection->prepare($sql);
        $life = $this->properties->getLifetimeDays();
        $stmt->bindParam(':expiry', $life);
        $stmt->execute();
    }

    /**
     * Checks whether the single-use token has already been used
     *
     * @param string $token 32-digit single-use token
     * @return bool Depends on value of $used
     */
    protected function checkCookieToken($token)
    {
        $sql = "SELECT " . $this->properties->getColUsed() . " FROM " . $this->properties->getTableAutologin() . "
                WHERE " . $this->properties->getColUkey() . " = :key AND " . $this->properties->getColToken() . " = '$token' limit 1";
        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(':key', $_SESSION[$this->properties->getSessUkey()], \PDO::PARAM_INT);
        //$stmt->bindParam(':token', $token,\PDO::PARAM_STR);
        //$stmt->bindParam(':used', $used, \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetchAll();

        if (isset($res[0][$this->properties->getColUsed()])) {
            return $res[0];
        } else
            return null;
    }

    /**
     * Delete all entries in autologin table related with the user's ID
     */
    protected function deleteAll()
    {
        $sql = "DELETE FROM " . $this->properties->getTableAutologin() . " WHERE " . $this->properties->getColUkey() . " = :key";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':key', $_SESSION[$this->properties->getSessUkey()]);
        $stmt->execute();
    }

    /**
     * Logs in the user if the single-use cookie is valid
     *
     * @param string $token 32-character single-use token
     */
    protected function cookieLogin($token)
    {
        try {
            $this->getExistingData();

            $sql = "UPDATE " . $this->properties->getTableAutologin() . " SET " . $this->properties->getColUsed() . " = 1
                    WHERE " . $this->properties->getColUkey() . " = :key AND " . $this->properties->getColToken() . " = :token";

            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':key', $_SESSION[$this->properties->getSessUkey()]);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            session_regenerate_id(true);

            unset($_SESSION[$this->properties->getCookie()]);
            unset($_SESSION[$this->properties->getSessRevalid()]);
            $_SESSION[$this->properties->getSessAuth()] = 1;
            $_SESSION[$this->properties->getSessPersist()] = 1;
        } catch (\PDOException $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $e;
        }

    }

}