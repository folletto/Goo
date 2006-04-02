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

class GooUsers extends Goo
{
	var $dbUsers = null;	// users table object
	
	/****************************************************************************************************
	 * Constructor
	 */
	function GooUsers($context, $table)
	{
		$this->Goo($context); // Super Constructor
		
		// ****** Init
		$this->dbUsers = $context->gooDB->table($table);
	}
	
	
	
	/****************************************************************************************************
	 * To String method
	 *
	 * @param	optional: sets the output mode (def: 'html') [text, html]
	 * @return	this object to string
	 */
	function toString($mode = '')
	{
		$out = '';
		
		if ($mode == 'text')
		{
			// ****** Text
			$out .= 'Users: ' . "\n";
			$out .= 'database table: ' . $this->dbUsers->name . "\n";
		}
		else
		{
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

?>