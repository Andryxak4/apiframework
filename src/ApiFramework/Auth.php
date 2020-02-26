<?php namespace ApiFramework;

/**
 * Auth class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Auth extends Core
{

    /**
     * @var Array Authenticated user
     */
    private $user = null;

    /**
     * @var Array Current token
     */
    private $token = null;

    /**
     * Attempts to login a user
     *
     * @param string $username Username to login
     * @param string $password Password
     * @param boolean $remember Store cookie or not
     * @return string Token
     */
    public function attempt ($username, $password, $remember = false) {

        // Check credentials
        if (!$this->validate($username, $password)) {
            throw new \Exception('Incorrect username or password', 401);
        }

        // Logins user
        $token = $this->login($username);

        // Set cookie
        if ($remember) {
            $cookieSet = $this->setCookie($token);
        }

        // Return token
        return $token;
    }

    /**
     * Login a user
     *
     * @param string $username Username to login
     * @return mixed
     */
    public function login ($username) {

        // Store session file
        $token = $this->createTokenHash($username);

        // Get user data
        $user = $this->findUser($username);

        // Abort if the user does not exist
        if (!$user) {
            throw new \Exception('Username not found', 404);
        }

        // Manage session data
        unset($user[$this->app->config('auth.password')]);
        $this->user = $user;
        $this->token = $token;

        // Save session
        $sessionSaved = $this->storeSession($token, $user);

        // Return token
        return $token;
    }

    /**
     * Tells if an user is logged in
     *
     * @param string $token Token
     * @return mixed User session data or false
     */
    public function check ($token) {

        // Check session
        $sessionData = $this->getSession($token);
        if (!$sessionData) {
            return false;
        }

        // Return user info
        return $sessionData;
    }

    /**
     * Signs out a user
     *
     * @param string $token Token
     * @return boolean Success or fail of sign out
     */
    public function signout ($token) {

        // Get session
        $sessionData = $this->getSession($token);

        // Delete session cookies
        $cookieDeleted = $this->deleteCookie();
        $sessionDeleted = $this->deleteSession($token);

        // Return success or fail
        return $cookieDeleted && $sessionDeleted;
    }

    /**
     * Validates a user - password pair
     *
     * @param string $username Username to validate
     * @param string $password Raw password
     * @return boolean Success or fail of validation
     */
    public function validate ($username, $password) {

        // Get the user's data
        $user = $this->findUser($username);

        // Abort if the user does not exist
        if (!$user) {
            return false;
        }

        // Verify the password against the stored hash
        return password_verify($password, $user[$this->app->config('auth.password')]);
    }

    /**
     * Creates a reminder and sends it to the user
     *
     * @param string $username Username to validate
     * @return boolean|string Password reminder or false
     */
    public function remindPassword ($username) {

        // Get the user's data
        $user = $this->findUser($username);

        // Abort if the user does not exist
        if (!$user) {
            throw new \Exception('Username not found', 404);
        }

        // Creates the reminder
        $reminder = $this->createReminderHash($username);

        // Saves the reminder
        $reminderSaved = $this->storeReminder($reminder, [$this->app->config('auth.username') => $username]);

        // Return
        return $reminder;
    }

    /**
     * Changes the password of a user
     *
     * @param string $username Username
     * @param string $token Token or password reminder
     * @param string $newPassword New password
     * @return boolean Success or fail of password change
     */
    public function changePassword ($username, $token, $newPassword) {

        // Get the user's data
        $user = $this->findUser($username);

        // Abort if the user does not exist
        if (!$user) {
            throw new \Exception('Username not found', 404);
        }

        // Get data from reminder or session
        $isReminder = $this->isReminder($token);
        $sessionData = $isReminder? $this->getReminder($token) : $this->getSession($token);

        // Abort if the session is invalid
        if (!$sessionData) {
            $type = $isReminder? 'reminder' : 'token';
            throw new \InvalidArgumentException('Invalid ' . $type, 400);
        }

        // Abort if the received username does not match the session data username
        if ($username !== $sessionData['user'][$this->app->config('auth.username')]) {
            throw new \InvalidArgumentException('Invalid username for this ' . $type, 400);
        }

        // Abort if the reminder is expired
        $maxage = time() + $this->app->config('reminders.ttl');
        if ($isReminder && (!isset($sessionData['expires']) || $sessionData['expires'] > $maxage)) {
            throw new \InvalidArgumentException('Expired reminder', 400);
        }

        // Hash password
        $data['password'] = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update user
        $userUpdated = $this->app->db->reset()->table($this->app->config('auth.table'))->where($this->app->config('auth.username'), $username)->update($data);

        // Check for errors
        if (!$userUpdated) {
            throw new \InvalidArgumentException('Could not change password', 500);
        }

        // Delete reminder
        if ($userUpdated && $isReminder) {
            $reminderDeleted = $this->deleteReminder($token);
        }

        // Return status
        return $userUpdated;
    }

    /**
     * Retrieves data from the session file
     *
     * @param string $token Token
     * @return mixed Session data, or false if the session is invalid
     */
    public function getSession ($token = null) {
        if (!$token) {
            $token = $this->app->request->token();
        }
        if (!$token) {
            $token = $this->token;
        }
        $path = $this->sessionPath($token);
        return (file_exists($path)) ? json_decode(file_get_contents($path), true) : false;
    }

    /**
     * Gets a user from the database
     *
     * @param string $username Username
     * @return mixed User array, of false if the user does not exist
     */
    private function findUser ($username) {
        $user = $this->app->db->reset()->table($this->app->config('auth.table'))->where($this->app->config('auth.username'), $username)->getOne();
        return $user;
    }

    /**
     * Creates a random token hash based on the username and time
     *
     * @param string $username Username to hash
     * @return string Token
     */
    private function createTokenHash ($username) {
        return md5($username . time() . rand(0, 100));
    }

    /**
     * Returns the session path for a given token
     *
     * @param string $token Token
     * @return string Session path
     */
    private function sessionPath ($token) {
        return $this->app->config('sessions.folder') . $token . '.json';
    }

    /**
     * Stores the session data on a file
     *
     * @param string $token Token
     * @param string $data Data to store
     * @return boolean Success or fail of file writing
     */
    private function storeSession ($token, $data) {
        $path = $this->sessionPath($token);
        return file_put_contents($path, json_encode([
            'user' => $data,
            'token' => $token,
            'ctime' => time()
        ]));
    }

    /**
     * Deletes the session file
     *
     * @param string $token Token
     * @return boolean Success or fail of file delete
     */
    private function deleteSession ($token) {
        $path = $this->sessionPath($token);
        if (file_exists($path) && is_writable($path)) {
            unlink($path);
        }
        return true;
    }

    /**
     * Creates a random reminder hash on the username and time
     *
     * @param string $username Username to hash
     * @return string Reminder
     */
    private function createReminderHash ($username) {
        return $this->app->config('reminders.suffix') . md5($username . time() . rand(0, 100));
    }

    /**
     * Returns the reminder path for a given reminder
     *
     * @param string $reminder Reminder
     * @return string Reminder path
     */
    private function reminderPath ($reminder) {
        return $this->app->config('reminders.folder') . $reminder . '.json';
    }

    /**
     * Stores the reminder data on a file
     *
     * @param string $reminder Reminder
     * @param string $data Data to store
     * @return boolean Success or fail of file writing
     */
    private function storeReminder ($reminder, $data) {
        $path = $this->reminderPath($reminder);
        return file_put_contents($path, json_encode([
            'user' => $data,
            'reminder' => $reminder,
            'expires' => time() + $this->app->config('reminders.ttl')
        ]));
    }

    /**
     * Retrieves the data from the reminder file
     *
     * @param string $reminder Reminder
     * @return mixed Reminder data, or false if the reminder is invalid
     */
    private function getReminder ($reminder) {
        $path = $this->reminderPath($reminder);
        return (file_exists($path))? json_decode(file_get_contents($path), true) : false;
    }

    /**
     * Deletes the reminder file
     *
     * @param string $reminder Reminder
     * @return boolean Success or fail of file delete
     */
    private function deleteReminder ($reminder) {
        $path = $this->reminderPath($reminder);
        return unlink($path);
    }

    /**
     * Check if a hash is a reminder
     *
     * @param string $string String to evaluate
     * @return boolean True if the string is a reminder
     */
    private function isReminder ($string) {
        return strpos($string, $this->app->config('reminders.suffix')) === 0;
    }

    /**
     * Sets the token cookie
     *
     * @param string $token Token
     * @return boolean Success or fail of cookie writing
     */
    private function setCookie ($token) {
        $ttl = $this->app->config('sessions.ttl');
        $cookie = $this->app->config('sessions.cookie');
        return setcookie($cookie, $token, time() + $ttl, '/');
    }

    /**
     * Deletes the token cookie
     *
     * @return boolean Success or fail of cookie writing
     */
    private function deleteCookie () {
        $cookie = $this->app->config('sessions.cookie');
        return setcookie($cookie, '', time() - 3600, '/');
    }

}