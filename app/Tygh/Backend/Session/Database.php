<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

namespace Tygh\Backend\Session;

use Tygh\Session;

class Database extends ABackend
{
    /**
     * Read session data
     *
     * @param string $sess_id session ID
     *
     * @return mixed session data if exist, false otherwise
     */
    public function read($sess_id)
    {
        $session = db_get_row('SELECT * FROM ?:sessions WHERE session_id = ?s', $sess_id);

        if (!empty($session)) {

            if ($session['expiry'] > TIME) {
                return $session['data'];
            } else {
                // the session did not have time to get in "stored_sessions" and got out of date, it is necessary to return only settings
                db_query('DELETE FROM ?:sessions WHERE session_id = ?s', $sess_id);
                $session = \Tygh::$app['session']->decode($session['data']);

                return \Tygh::$app['session']->encode(array ('settings' => !empty($session['settings']) ? $session['settings'] : array()));
            }
        }

        return false;
    }

    /**
     * Write session data
     *
     * @param string $sess_id session ID
     * @param array  $data    session data
     *
     * @return boolean always true
     */
    public function write($sess_id, $data)
    {
        $data['session_id'] = $sess_id;

        db_query('REPLACE INTO ?:sessions ?e', $data);

        return true;
    }

    /**
     * Update session ID
     *
     * @param string $old_id old session ID
     * @param array  $new_id new session ID
     *
     * @return boolean always true
     */
    public function regenerate($old_id, $new_id)
    {
        db_query('UPDATE ?:sessions SET session_id = ?s WHERE session_id = ?s', $new_id, $old_id);

        return true;
    }

    /**
     * Delete session data
     *
     * @param string $sess_id session ID
     *
     * @return boolean always true
     */
    public function delete($sess_id)
    {
        db_query('DELETE FROM ?:sessions WHERE session_id = ?s', $sess_id);

        return true;
    }

    /**
     * Garbage collector - move expired sessions to session archive
     *
     * @param int $max_lifetime session lifetime
     *
     * @return boolean always true
     */
    public function gc($max_lifetime)
    {
        $sessions = db_get_array('SELECT * FROM ?:sessions WHERE expiry < ?i', TIME);

        if ($sessions) {
            foreach ($sessions as $session) {
                Session::expire($session['session_id'], $session);
            }

            // delete old sessions
            db_query('DELETE FROM ?:sessions WHERE expiry < ?i', TIME);
        }

        return true;
    }

    /**
     * Gets sessions that were used less than number of seconds, defined in ttl_online property of Session class
     * @param  string $area session area
     * @return array  list of session IDs
     */
    public function getOnline($area)
    {
        return db_get_fields("SELECT session_id FROM ?:sessions WHERE expiry > ?i AND SUBSTR(session_id, -1) = ?s", TIME + $this->config['ttl'] - $this->config['ttl_online'], $area);
    }
}
