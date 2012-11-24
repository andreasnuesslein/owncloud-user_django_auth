<?php

/**
 * ownCloud - user_django_auth
 *
 * @author Andreas Nüßlein
 * @copyright 2012 Andreas Nüßlein <andreas@nuessle.in>
 * @author Steffen Zieger
 * @copyright 2012 Steffen Zieger <me@saz.sh>
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
$params = array(
    'django_auth_db_host',
    'django_auth_db_user',
    'django_auth_db_password',
    'django_auth_db_name',
    'django_auth_db_driver'
);

if ($_POST) {
    foreach($params as $param){
        if(isset($_POST[$param])){
            OC_Appconfig::setValue('user_django_auth', $param, $_POST[$param]);
        }
    }
}

// fill template
$tmpl = new OC_Template( 'user_django_auth', 'settings');
foreach($params as $param){
    $default = '';
    if ($param == 'django_auth_db_driver') {
        $default = 'mysql';
    }
    $value = OC_Appconfig::getValue('user_django_auth', $param, $default);
    $tmpl->assign($param, $value);
}

return $tmpl->fetchPage();
