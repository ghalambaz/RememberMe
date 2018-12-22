## Remember Me - Session Persistence
**users can stay login to your website for how long as you want** 


**remember me** help you to have remember me functionality for login and users does not need to login every 20 or 30 minute after inactivity, because it will save your session to database
and you can save sessions for long as you want! for example 1 year!!!

**remember me** is secure but it's not prevent session hijacking so for more security you need to implement it for yourself

## Installation

### Step 1 : add package with composer
`composer require ghalambaz/rememberme`

### Step 2 : create database tables

```sql
-- ----------------------------
-- Table structure for tbl_acl_autologin
-- ----------------------------
CREATE TABLE `tbl_acl_autologin`  (
  `id` int(10) UNSIGNED NOT NULL,
  `token` char(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
  `data` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `used` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`, `token`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for tbl_acl_sessions
-- ----------------------------
CREATE TABLE `tbl_acl_sessions`  (
  `sid` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `expiry` int(10) UNSIGNED NOT NULL,
  `data` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`sid`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

```

> you must have a table for your users with column that include usernames
 
> in our example i have table **tbl_acl_users** that have a column **username**


## Usage

## step 1 : create a Properties Object

```php
//initialize Properties Object
$properties = new \RememberMe\Properties();
$properties->setDb("root","password","doit","localhost"); //your database access info
$properties->setTableUsers("tbl_users"); // table name that you already save your users data
$properties->setColUsername("username"); // column that save username,email or any id of your users in tbl_users
```
> you need to access this object globally in your project

## step 2 : Changing PHP Session Handler
```php
session_set_save_handler(new \RememberMe\RememberMeSessionHandler($properties));
session_start(); //starting session - check for duplication!
$_SESSION['active'] = time();
```
>please put step2 in the first line of your code

### Usage Examples        

##### how to login users?
after you logged in user with username and password you need to persist session data .
 so you should need to run **persist** function

```php
//strucure of persist function
function persist(\RememberMe\Properties $properties,$username)
{
    //after checking that 
    session_regenerate_id(true);
    $_SESSION[$properties->getSessUname()] = $username;
    $_SESSION[$properties->getSessAuth()] = true;
    // persisting login
    $r = new \RememberMe\RememberMe($properties);
    $r->start();
}
```

##### how to check that user is login now or 1 year later?

```php
‍‍‍‍//example of check that is user is logged in or not (need to login again)
function is_loggedin($properties)
{
    if (isset($_SESSION[$properties->getSessAuth()]) || isset($_SESSION[$properties->getSessPersist()])) {
        return true;
    } else {
        $autologin = new \RememberMe\RememberMe($properties);
        $autologin->login();
        if (!isset($_SESSION[$properties->getSessPersist()]))
            return false;
        else
            return true;
    }
}
```
##### how to logout?
```php
‍//example of logout function structure 
function logout($properties)
{
    $autologin = new \RememberMe\RememberMe($properties);
    $autologin->logout(true);
}
```

##### how to change session life time?
‍
```php
$days = 365;
$properties->setLifetimeDays($days);
```

‍‍‍
‍‍



