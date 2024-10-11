<?php

use PHPUnit\Framework\TestCase;
use Sessioneer\Sessioneer;
use Sessioneer\Exceptions\SessionExpiredException;
use Sessioneer\Exceptions\KeyNotFoundException;

class SessioneerTest extends TestCase
{
    /**
     * Prepara l'ambiente prima di ogni test.
     * Distrugge eventuali sessioni attive e resetta l'array $_SESSION.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    /**
     * Pulizia dell'ambiente dopo ogni test.
     * Distrugge eventuali sessioni attive e resetta l'array $_SESSION.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    /**
     * Verifica che il metodo start() imposti correttamente il tempo di scadenza della sessione.
     *
     * @return void
     */
    public function testStartSetsExpirationTime()
    {
        $expiration = 1800;
        Sessioneer::start($expiration);

        $this->assertEquals($expiration, Sessioneer::$expirationTime);
    }

    /**
     * Verifica che il metodo start() avvii la sessione se non esiste già una sessione attiva.
     *
     * @return void
     */
    public function testStartInitiatesSessionIfNoneExists()
    {
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        Sessioneer::start();
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    /**
     * Verifica che il metodo start() distrugga la sessione esistente se è scaduta
     * e avvii una nuova sessione.
     *
     * @return void
     */
    public function testStartDestroysAndRestartsSessionIfExpired()
    {
        session_start();
        $_SESSION['LAST_ACTIVITY'] = time() - 4000;
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());

        Sessioneer::start(3600);

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    /**
     * Verifica che il metodo start() aggiorni il timestamp di LAST_ACTIVITY.
     *
     * @return void
     */
    public function testStartUpdatesLastActivity()
    {
        Sessioneer::start();

        $this->assertArrayHasKey('LAST_ACTIVITY', $_SESSION);
    }

    /**
     * Verifica che il metodo destroy() svuoti la sessione attiva.
     *
     * @return void
     */
    public function testDestroyUnsetsSession()
    {
        session_start();
        $_SESSION['username'] = 'Andrea';

        $this->assertNotEmpty($_SESSION);

        Sessioneer::destroy();

        $this->assertEmpty($_SESSION);
    }

    /**
     * Verifica che il metodo destroy() distrugga la sessione attiva.
     *
     * @return void
     */
    public function testDestroyDestroysSession()
    {
        session_start();
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());

        Sessioneer::destroy();

        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    /**
     * Verifica che il metodo set() inserisca correttamente una coppia chiave-valore nella sessione.
     *
     * @return void
     */
    public function testSetStoresValueInSession()
    {
        session_start();

        $key = 'username';
        $value = 'Andrea';

        Sessioneer::set($key, $value);

        $this->assertEquals($value, $_SESSION[$key]);
    }

    /**
     * Verifica che il metodo set() lanci un'eccezione SessionExpiredException se la sessione è scaduta.
     *
     * @return void
     */
    public function testSetThrowsExceptionIfSessionExpired()
    {
        session_start();
        $_SESSION['LAST_ACTIVITY'] = time() - 4000;

        $this->expectException(SessionExpiredException::class);

        Sessioneer::set('username', 'Andrea');
    }

    /**
     * Verifica che il metodo set() aggiorni il valore di LAST_ACTIVITY quando viene chiamato.
     *
     * @return void
     */
    public function testSetUpdatesLastActivity()
    {
        session_start();

        $key = 'username';
        $value = 'Andrea';

        Sessioneer::set($key, $value);

        $this->assertArrayHasKey('LAST_ACTIVITY', $_SESSION);
        $this->assertEquals(time(), $_SESSION['LAST_ACTIVITY'], '', 1);
    }

    /**
     * Verifica che il metodo get() ritorni correttamente il valore associato alla chiave richiesta.
     *
     * @return void
     */
    public function testGetReturnsValueFromSession()
    {
        session_start();

        $key = 'username';
        $value = 'Andrea';
        $_SESSION[$key] = $value;

        $this->assertEquals($value, Sessioneer::get($key));
    }

    /**
     * Verifica che il metodo get() lanci un'eccezione SessionExpiredException se la sessione è scaduta.
     *
     * @return void
     */
    public function testGetThrowsExceptionIfSessionExpired()
    {
        session_start();
        $_SESSION['LAST_ACTIVITY'] = time() - 4000;

        $this->expectException(SessionExpiredException::class);

        Sessioneer::get('username');
    }

    /**
     * Verifica che il metodo get() aggiorni il valore di LAST_ACTIVITY quando viene chiamato.
     *
     * @return void
     */
    public function testGetUpdatesLastActivity()
    {
        session_start();

        $_SESSION['username'] = 'Andrea';

        Sessioneer::get('username');

        $this->assertArrayHasKey('LAST_ACTIVITY', $_SESSION);
        $this->assertEquals(time(), $_SESSION['LAST_ACTIVITY'], '', 1);
    }

    /**
     * Verifica che il metodo get() lanci un'eccezione KeyNotFoundException se la chiave non esiste.
     *
     * @return void
     */
    public function testGetThrowsExceptionIfKeyNotFound()
    {
        session_start();

        $this->expectException(KeyNotFoundException::class);
        $this->expectExceptionMessage("The key 'non_existent_key' was not found.");

        Sessioneer::get('non_existent_key');
    }

    /**
     * Test per il metodo remove: rimuove una chiave esistente.
     */
    public function testRemoveKeyExists()
    {
        Sessioneer::start();
        $_SESSION['username'] = 'Andrea';

        Sessioneer::remove('username');

        $this->assertArrayNotHasKey('username', $_SESSION);
    }

    /**
     * Test per il metodo remove: lancia KeyNotFoundException se la chiave non esiste.
     */
    public function testRemoveKeyNotFound()
    {
        Sessioneer::start();

        $this->expectException(KeyNotFoundException::class);
        Sessioneer::remove('non_existent_key');
    }

    /**
     * Test per il metodo remove: lancia SessionExpiredException se la sessione è scaduta.
     */
    public function testRemoveSessionExpired()
    {
        Sessioneer::start(3600);
        $_SESSION['LAST_ACTIVITY'] = time() - 4000;

        $this->expectException(SessionExpiredException::class);
        Sessioneer::remove('username');
    }

    /**
     * Test per il metodo getSessionStatus: restituisce lo stato della sessione.
     */
    public function testGetSessionStatus()
    {
        $this->assertEquals(PHP_SESSION_NONE, Sessioneer::getSessionStatus());

        Sessioneer::start();
        $this->assertEquals(PHP_SESSION_ACTIVE, Sessioneer::getSessionStatus());

        Sessioneer::destroy();
        $this->assertEquals(PHP_SESSION_NONE, Sessioneer::getSessionStatus());
    }
}
