<?php
/*
 * Goo Users
 * version 0.1
 * 
 * Copyright (C) 2006
 * by Davide S. Casali
 * www.digitalhymn.com
 *
 * This is the user manager goo.
 * 
 ***************************************************************************************************
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  US
 *
 ****************************************************************************************************
 */

class GooUsers extends Goo {
	var $dbUsers = null;	// users table object
	
	// This array maps the fields used in the User class to the field names in the database table
	var $mapping = array(
		'uid'					=> 'uid', 
		'User'				=> 'User',
		'Pass'				=> 'Pass',
		'Nick'				=> 'Nick',
		'EMail'				=> 'EMail',
		'SessionHash'	=> 'SessionHash',
		'LastLogin'		=> 'LastLogin'
		);
	
	/****************************************************************************************************
	 * Constructor
	 */
	function GooUsers(&$context, $table) {
		$this->Goo($context); // Super Constructor
		
		// ****** Init
		$this->dbUsers = $context->DB->table($table);
		$this->selfInstall();
	}
	
	/****************************************************************************************************
	 * DataBase initialization
	 * 
	 */
	function selfInstall() {
		$this->dbUsers->create(array(
			'uid'					=> 'key', 
			'User'				=> 'varchar(32)',
			'Pass'				=> 'varchar(32)',
			'Nick'				=> 'varchar(50)',
			'EMail'				=> 'varchar(60)',
			'SessionHash'	=> 'varchar(32)',
			'LastLogin'		=> 'datetime'
			));
	}
	
	function extend() { /* Stub */ }
	
	/****************************************************************************************************
	 * Creates the PHP object to interact with a specific user.
	 *
	 */
	function user($identifier) {
		$user = new GooUsersUser($identifier, $this);
		
		return $user;
	}
	
	/****************************************************************************************************
	 * To String method
	 *
	 * @param		optional: sets the output mode (def: 'html') [text, html]
	 * @return	this object to string
	 */
	function toString($mode = '') {
		$out = '';
		
		if ($mode == 'text') {
			// ****** Text
			$out .= 'Users: ' . "\n";
			$out .= 'database table: ' . $this->dbUsers->name . "\n";
		} else {
			// ****** HTML
			$out .= '<ul>';
			$out .= '<li><strong>Users</strong>';
			$out .= '<ul>';
			$out .= '<li>database table: ' . $this->dbUsers->name . '</li>';
			$out .= '</ul></li>';
			$out .= '</ul>';
		}
		
		return $out;
	}
}

/****************************************************************************************************
 * CLASS: single User
 */
class GooUsersUser {
	var $id				= '';			// user identifier
	var $users		= null;		// GooUsers object
	var $context	= null;		// Goo context
	
	/****************************************************************************************************
	 * Constructor
	 *
	 * @param		user name
	 */
	function GooUsersUser($identifier, &$gooUsers) {
		$this->id = $identifier;
		$this->users = $gooUsers;
		$this->context = $gooUsers->context;
	}
	
	function save() {}
	function destroy() {}
	function getName() {}
	function setName() {}
}

?>