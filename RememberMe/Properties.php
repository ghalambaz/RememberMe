<?php
namespace RememberMe;

class Properties
{
    /**
     * @return string
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * @param string $cookie
     * @return Properties
     */
    public function setCookie($cookie)
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableSess()
    {
        return $this->table_sess;
    }

    /**
     * @param string $table_sess
     * @return Properties
     */
    public function setTableSess($table_sess)
    {
        $this->table_sess = $table_sess;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableUsers()
    {
        return $this->table_users;
    }

    /**
     * @param string $table_users
     * @return Properties
     */
    public function setTableUsers($table_users)
    {
        $this->table_users = $table_users;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableAutologin()
    {
        return $this->table_autologin;
    }

    /**
     * @param string $table_autologin
     * @return Properties
     */
    public function setTableAutologin($table_autologin)
    {
        $this->table_autologin = $table_autologin;
        return $this;
    }

    /**
     * @return string
     */
    public function getColSid()
    {
        return $this->col_sid;
    }

    /**
     * @param string $col_sid
     * @return Properties
     */
    public function setColSid($col_sid)
    {
        $this->col_sid = $col_sid;
        return $this;
    }

    /**
     * @return string
     */
    public function getColExpiry()
    {
        return $this->col_expiry;
    }

    /**
     * @param string $col_expiry
     * @return Properties
     */
    public function setColExpiry($col_expiry)
    {
        $this->col_expiry = $col_expiry;
        return $this;
    }

    /**
     * @return string
     */
    public function getColUkey()
    {
        return $this->col_ukey;
    }

    /**
     * @param string $col_ukey
     * @return Properties
     */
    public function setColUkey($col_ukey)
    {
        $this->col_ukey = $col_ukey;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessUkey()
    {
        return $this->sess_ukey;
    }

    /**
     * @param string $sess_ukey
     * @return Properties
     */
    public function setSessUkey($sess_ukey)
    {
        $this->sess_ukey = $sess_ukey;
        return $this;
    }

    /**
     * @return string
     */
    public function getColUsername()
    {
        return $this->col_name;
    }

    /**
     * @param string $col_name
     * @return Properties
     */
    public function setColUsername($col_name)
    {
        $this->col_name = $col_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getColData()
    {
        return $this->col_data;
    }

    /**
     * @param string $col_data
     * @return Properties
     */
    public function setColData($col_data)
    {
        $this->col_data = $col_data;
        return $this;
    }

    /**
     * @return string
     */
    public function getColToken()
    {
        return $this->col_token;
    }

    /**
     * @param string $col_token
     * @return Properties
     */
    public function setColToken($col_token)
    {
        $this->col_token = $col_token;
        return $this;
    }

    /**
     * @return string
     */
    public function getColCreated()
    {
        return $this->col_created;
    }

    /**
     * @param string $col_created
     * @return Properties
     */
    public function setColCreated($col_created)
    {
        $this->col_created = $col_created;
        return $this;
    }

    /**
     * @return string
     */
    public function getColUsed()
    {
        return $this->col_used;
    }

    /**
     * @param string $col_used
     * @return Properties
     */
    public function setColUsed($col_used)
    {
        $this->col_used = $col_used;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessPersist()
    {
        return $this->sess_persist;
    }

    /**
     * @param string $sess_persist
     * @return Properties
     */
    public function setSessPersist($sess_persist)
    {
        $this->sess_persist = $sess_persist;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessUname()
    {
        return $this->sess_uname;
    }

    /**
     * @param string $sess_uname
     * @return Properties
     */
    public function setSessUname($sess_uname)
    {
        $this->sess_uname = $sess_uname;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessAuth()
    {
        return $this->sess_auth;
    }

    /**
     * @param string $sess_auth
     * @return Properties
     */
    public function setSessAuth($sess_auth)
    {
        $this->sess_auth = $sess_auth;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessRevalid()
    {
        return $this->sess_revalid;
    }

    /**
     * @param string $sess_revalid
     * @return Properties
     */
    public function setSessRevalid($sess_revalid)
    {
        $this->sess_revalid = $sess_revalid;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param $db_user
     * @param $db_pass
     * @param $db_name
     * @param string $db_host
     * @return Properties
     */
    public function setDb($db_user,$db_pass,$db_name,$db_host = 'localhost')
    {
        $this->db = array('user'=>$db_user,'pass'=>$db_pass,'name'=>$db_name,'host'=>$db_host);
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseTransactions()
    {
        return $this->useTransactions;
    }

    /**
     * @param bool $useTransactions
     * @return Properties
     */
    public function setUseTransactions($useTransactions)
    {
        $this->useTransactions = $useTransactions;
        return $this;
    }




    /**
     * @return bool
     */
    public function isCollectGarbage()
    {
        return $this->collectGarbage;
    }

    /**
     * @param bool $collectGarbage
     * @return Properties
     */
    public function setCollectGarbage($collectGarbage)
    {
        $this->collectGarbage = $collectGarbage;
        return $this;
    }
    /**
     * @var string Name of the autologin cookie
     */
    protected $cookie = 'remember_me_auth';

    /**
     * @var string Default table where session data is stored
     */
    protected $table_sess = 'tbl_acl_sessions';

    /**
     * @var string Name of database table that stores user credentials
     */
    protected $table_users = 'tbl_acl_users';

    /**
     * @var string Name of database table that stores autologin details
     */
    protected $table_autologin = 'tbl_acl_autologin';

    /**
     * @var string Default column for session ID
     */
    protected $col_sid = 'sid';

    /**
     * @var string Default column for expiry timestamp
     */
    protected $col_expiry = 'expiry';

    /**
     * @var string Name of table column that stores user's ID - a unique 8-character alphanumeric string
     */
    protected $col_ukey = 'id';


    protected $sess_ukey ='userkey';

    /**
     * @var string Name of table column that stores the user's username
     */
    protected $col_name = 'username';

    /**
     * @var string Default column for session data
     */
    protected $col_data = 'data';

    /**
     * @var string Name of table column that stores 32-character single-use tokens
     */
    protected $col_token = 'token';

    /**
     * @var string Name of table column that stores when the record was created as a MySQL timestamp
     */
    protected $col_created = 'created';

    /**
     * @var string Name of table column that stores a Boolean recording whether the token has been used
     */
    protected $col_used = 'used';

    /**
     * @var string Session variable that persists data
     */
    protected $sess_persist = 'remember_me';

    /**
     * @var string Session variable that stores the username
     */
    protected $sess_uname = 'username';

    /**
     * @var string Session name that indicates user has been authenticated
     */
    protected $sess_auth = 'authenticated';

    /**
     * @var string Session name that indicates user has been revalidated
     */
    protected $sess_revalid = 'revalidated';


    protected $db ;
    /**
     * @var bool Determines whether to use transactions
     */
    protected $useTransactions;




    /**
     * @var bool True when PHP has initiated garbage collection
     */
    protected $collectGarbage = false;

    /**
     * @var int Number of days the autologin cookie remains valid
     */
    protected $lifetimeDays = 1000;

    /**
     * @return int
     */
    public function getLifetimeDays()
    {
        return $this->lifetimeDays;
    }

    /**
     * @param int $lifetimeDays
     * @return Properties
     */
    public function setLifetimeDays($lifetimeDays)
    {
        $this->lifetimeDays = $lifetimeDays;
        return $this;
    }

}