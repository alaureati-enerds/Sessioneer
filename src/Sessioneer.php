<?php

namespace Sessioneer;

use Sessioneer\Exceptions\SessionExpiredException;
use Sessioneer\Exceptions\KeyNotFoundException;

/**
 * Class Sessioneer
 *
 * Provides a simplified interface for managing PHP sessions, including session
 * initialization, termination, and key-value storage with expiration control.
 *
 * @author Laureati Andrea, eNerds srl
 * @package Sessioneer
 */
class Sessioneer
{

    public static $expirationTime;

    /**
     * Starts the session and sets the expiration time.
     *
     * If a session is already active, it checks if the session has expired.
     * If expired, it destroys the current session and starts a new one.
     *
     * @param int $expiration The session expiration time in seconds. Default is 3600 seconds.
     * @return void
     */
    public static function start($expiration = 3600)
    {
        self::$expirationTime = $expiration;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (self::isSessionExpired()) {
            self::destroy();
            session_start();
        }

        self::updateLastActivity();
    }

    /**
     * Destroys the current session and clears all session variables.
     *
     * @return void
     */
    public static function destroy()
    {
        session_unset();
        session_destroy();
    }

    /**
     * Sets a session variable.
     *
     * @param string $key The key for the session variable.
     * @param mixed $value The value to set for the session variable.
     * @throws SessionExpiredException If the session has expired.
     * @return void
     */
    public static function set($key, $value)
    {
        if (self::isSessionExpired()) {
            throw new SessionExpiredException();
        }

        $_SESSION[$key] = $value;
        self::updateLastActivity();
    }

    /**
     * Retrieves a session variable.
     *
     * @param string $key The key of the session variable to retrieve.
     * @throws SessionExpiredException If the session has expired.
     * @throws KeyNotFoundException If the specified key does not exist in the session.
     * @return mixed The value of the session variable, or null if it doesn't exist.
     */
    public static function get($key)
    {
        if (self::isSessionExpired()) {
            throw new SessionExpiredException();
        }

        self::updateLastActivity();

        if (!isset($_SESSION[$key])) {
            throw new KeyNotFoundException("The key '{$key}' was not found.");
        }

        return $_SESSION[$key];
    }

    /**
     * Rimuove una chiave dall'array di sessione.
     * 
     * @param string $key La chiave da rimuovere.
     * 
     * @throws SessionExpiredException Se la sessione è scaduta.
     * @throws KeyNotFoundException Se la chiave non esiste.
     */
    public static function remove($key)
    {
        if (self::isSessionExpired()) {
            throw new SessionExpiredException();
        }

        if (!isset($_SESSION[$key])) {
            throw new KeyNotFoundException("The key '{$key}' was not found.");
        }

        unset($_SESSION[$key]);
        self::updateLastActivity();
    }

    /**
     * Restituisce lo stato della sessione corrente.
     * 
     * @return string Lo stato della sessione.
     */
    public static function getSessionStatus()
    {
        return session_status();
    }

    /**
     * Checks if the session has expired.
     *
     * @return bool True if the session has expired, false otherwise.
     */
    private static function isSessionExpired()
    {
        if (empty($_SESSION['LAST_ACTIVITY'])) {
            return false;
        }

        return (time() - $_SESSION['LAST_ACTIVITY']) > self::$expirationTime;
    }

    /**
     * Updates the last activity timestamp for the session.
     *
     * @return void
     */
    private static function updateLastActivity()
    {
        $_SESSION['LAST_ACTIVITY'] = time();
    }
}
