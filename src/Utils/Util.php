<?php
namespace BlueFission\Utils;

use BlueFission\Val;
use BlueFission\Net\Email;
use BlueFission\Net\HTTP;
use BlueFission\Data\Storage\Disk;

class Util {
    /**
     * sends an email to the admin with a specified message, subject, from and recipient
     *
     * @param string $message
     * @param string $subject
     * @param string $from
     * @param string $rcpt
     * @return bool
     */
    static function emailAdmin($message = '', $subject = '', $from = '', $rcpt = '') {
        $message = (Val::isNotNull($message)) ? $message : "If you have recieved this email, then the admnistrative alert system on your website has been activated with no status message. Please check your log files.\n";
        $subject = (Val::isNotNull($subject)) ? $subject : "Automated Email Alert From Your Site!";
        $from = (Val::isNotNull($from)) ? $from : "admin@" . HTTP::domain();
        $rcpt = (Val::isNotNull($rcpt)) ? $rcpt : "admin@" . HTTP::domain();

        $status = Email::sendMail($rcpt, $from, $subject, $message);
        return $status;
    }

    /**
     * check if count is greater than max and then either redirect, email or exit
     *
     * @param int $count
     * @param int $max
     * @param string $redirect
     * @param bool $log
     * @param bool $alert
     */
    static function parachute(&$count, $max = '', $redirect = '', $log = false, $alert = false) {
        $max = (Val::isNotNull($max)) ? $max : 400;
        if ($count >= $max) {
            $status = "Loop exceeded max count! Killing Process.\n";
            if ($alert) Util::emailAdmin($status);
            if ($log) {
                $logger = Log::instance(['storage'=>'log']);
                $logger->push($status);
                $logger->write();
            }
            if (Val::isNotNull($redirect)) HTTP::redirect($redirect, array('msg'=>$status));
            else exit("A script on this page began to loop out of control. Process has been killed. If you are viewing this message, please alert the administrator.\n");
        }
        $count++;
    }

    static function globals($var, $value = null)
    {
        if (Val::isNull($value) )
            return isset( $GLOBALS[$var] ) ? $GLOBALS[$var] : null;
            
        $GLOBALS[$var] = $value;
            
        $status = ($GLOBALS[$var] = $value) ? true : false;
        
        return $status;
    }

    // Function to get the storage path
    static function getStoragePath() {
        // Use an environment variable or fallback to a default path
        $storagePath = getenv('STORAGE_PATH') ?: __DIR__ . '/storage/data';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }
        return $storagePath;
    }

    // Function to get or generate a unique CLI session ID
    static function getCliSessionId() {
        try {
            $userHome = self::getUserHomeDir();
            $sessionIdFile = $userHome . DIRECTORY_SEPARATOR . '.cli_session_id';
            if (file_exists($sessionIdFile)) {
                // Retrieve existing session ID
                $sessionId = file_get_contents($sessionIdFile);
            } else {
                // Generate a new session ID
                $sessionId = bin2hex(random_bytes(16)); // Generate a random session ID
                if (file_put_contents($sessionIdFile, $sessionId) === false) {
                    throw new \Exception("Unable to write session ID to file.");
                }
                chmod($sessionIdFile, 0600); // Set file permissions to be read/write for the owner only
            }
        } catch (\Exception $e) {
            // Use an environment variable or generate a unique ID
            $sessionId = getenv('CLI_SESSION_ID');
            if (!$sessionId) {
                $sessionId = bin2hex(random_bytes(16)); // Generate a random session ID
                putenv("CLI_SESSION_ID=$sessionId"); // Store the session ID in the environment
            }
        }
        return $sessionId;
    }


    // Function to get the storage file name
    static function getStorageFileName($sessionId) {
        // Use an environment variable or fallback to a default file name
        return getenv('STORAGE_FILE_NAME') ?: "cli_storage_{$sessionId}.json";
    }

    // Function to get the user's home directory in a cross-platform way
    static function getUserHomeDir() {
        $homeDir = getenv('HOME'); // Unix-like systems
        if (!$homeDir) {
            $homeDrive = getenv('HOMEDRIVE');
            $homePath = getenv('HOMEPATH');
            if ($homeDrive && $homePath) { // Windows
                $homeDir = $homeDrive . $homePath;
            } else {
                $homeDir = getenv('USERPROFILE'); // Alternative for Windows
            }
        }
        if (!$homeDir) {
            throw new \Exception("Unable to determine the user's home directory.");
        }
        return $homeDir;
    }

    static function store($name, $value = null)
    {
        if (php_sapi_name() === 'cli') {
            // CLI environment: use DiskStorage
            $sessionId = self::getCliSessionId();
            $storagePath = self::getStoragePath();
            $storageFileName = self::getStorageFileName($sessionId);


            $diskStorage = new Disk([
                'location' => $storagePath, // Set your desired storage path
                'name' => $storageFileName   // Set your desired storage file name
            ]);
            $diskStorage->activate();
            $storedData = $diskStorage->read()->contents() ?? [];

            if ($value === null) {
                // Return the value if $value is null
                return isset($storedData[$name]) ? $storedData[$name] : null;
            }

            $storedData[$name] = $value;
            $diskStorage->assign($storedData);
            $diskStorage->contents(json_encode($storedData));
            $diskStorage->write();
            unset($diskStorage);
        } else {
            // HTTP environment: use sessions
            return HTTP::session($name, $value);
        }
    }

    /**
     * generates a csrf token
     *
     * @return string
     */
    static function csrfToken()
    {
        $token = bin2hex(random_bytes(32));

        return $token;
    }

    /**
     * get a value from a cookie, post or get
     *
     * @param string $var
     * @param int $filter
     * @return mixed
     */
    static function value($var, $filter = FILTER_DEFAULT ) {

        $cookie = filter_input(INPUT_COOKIE, $var);
		$get = filter_input(INPUT_GET, $var);
		$post = filter_input(INPUT_POST, $var);
		return ( Val::isNotNull($cookie) ) ? $cookie : ( ( Val::isNotNull($post) ) ? $post : $get);
	}
}