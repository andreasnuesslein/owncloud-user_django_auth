<?php

/**
 * ownCloud
 *
 * @author Andreas Nüßlein
 * @copyright 2012 Andreas Nüßlein <andreas@nuessle.in>
 * @author steffen zieger
 * @copyright 2012 steffen zieger <me@saz.sh>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class OC_User_Django_Auth extends OC_User_Backend {
    protected $django_auth_db_host;
    protected $django_auth_db_name;
    protected $django_auth_db_user;
    protected $django_auth_db_password;
    protected $db;
    protected $db_conn;

    function __construct() {
        $this->db_conn = false;
        $db_host = OC_Appconfig::getValue('user_django_auth', 'django_auth_db_host','');
        $db_name = OC_Appconfig::getValue('user_django_auth', 'django_auth_db_name','');
        $db_driver = OC_Appconfig::getValue('user_django_auth', 'django_auth_db_driver', 'mysql');
        $db_user = OC_Appconfig::getValue('user_django_auth', 'django_auth_db_user','');
        $db_password = OC_Appconfig::getValue('user_django_auth', 'django_auth_db_password','');
        $dsn = "${db_driver}:host=${db_host};dbname=${db_name}";

        try {
            $this->db = new PDO($dsn, $db_user, $db_password);
            $this->db_conn = true;
        } catch (PDOException $e) {
            OC_Log::write('OC_User_Django_Auth',
                'OC_User_Django_Auth, Failed to connect to django auth database: ' . $e->getMessage(),
                OC_Log::ERROR);
        }
        return false;
    }

    /**
     * @brief Set email address
     * @param $uid The username
     */
    private function setEmail($uid) {
        if (!$this->db_conn) {
            return false;
        }

        $sql = 'SELECT email FROM auth_user WHERE username = :uid';
        $sth = $this->db->prepare($sql);
        if ($sth->execute(array(':uid' => $uid))) {
            $row = $sth->fetch();

            if ($row) {
                if (OC_Preferences::setValue($uid, 'settings', 'email', $row['email'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @brief Check if the password is correct
     * @param $uid The username
     * @param $password The password
     * @returns true/false
     */
    public function checkPassword($uid, $password){
        if (!$this->db_conn) {
            return false;
        }

        $sql = 'SELECT username FROM auth_user WHERE username = :uid';
        $sql .= ' AND is_active';
        $sql .= ' AND SUBSTRING_INDEX(password,"$",-1) = SHA1(CONCAT(SUBSTRING_INDEX(SUBSTRING(password,6),"$",1),:password))';
        $sth = $this->db->prepare($sql);
        if ($sth->execute(array(':uid' => $uid, ':password' => $password))) {
            $row = $sth->fetch();

            if ($row) {
                $this->setEmail($uid);
                return $row['username'];
            }
        }
        return false;
    }

    /**
     * @brief Get a list of all users
     * @returns array with all uids
     *
     * Get a list of all users
     */
    public function getUsers($search = '', $limit = null, $offset = null) {
        if (!$this->db_conn) {
            return array();
        }

        $users = array();
        $offset = (int)$offset;
        $limit = (int)$limit;

        $sql = "SELECT username FROM auth_user WHERE password != ''";
        $sql .= " AND is_active";
        if (!empty($search)) {
            $sql .= " AND username LIKE :search";
        }
        $sql .= ' ORDER BY username';
        if ($limit) {
            $sql .= ' LIMIT :offset,:limit';
        }

        $sth = $this->db->prepare($sql);
        if (!empty($search)) {
            $sth->bindParam(':search', '%'.$search.'%', PDO::PARAM_STR);
        }
        if ($limit) {
            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
            $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
        }

        if ($sth->execute()) {
            while ($row = $sth->fetch()) {
                $users[] = $row['username'];
            }
        }
        return $users;
    }

    /**
     * @brief check if a user exists
     * @param string $uid the username
     * @return boolean
     */
    public function userExists($uid) {
        if (!$this->db_conn) {
            return false;
        }

        $sql = 'SELECT username FROM auth_user WHERE username = :uid';
        $sth = $this->db->prepare($sql);
        if ($sth->execute(array(':uid' => $uid))) {
            $row = $sth->fetch();

            return !empty($row);
        }
        return false;
    }
}
