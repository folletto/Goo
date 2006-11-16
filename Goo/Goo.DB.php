<?php
/*
 * Goo DataBase
 * version 0.1
 * 
 * Copyright (C) 2006
 * by Davide S. Casali
 * www.digitalhymn.com
 *
 * Database interface wrapper for easy interaction. This goo act as a
 * connection manager and raw functions handler, while the method table()
 * is a factory to get a GooDBTable object bound to a specific table.
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

class GooDB extends Goo {
	var $connection = null;
	var $uri = '';
	var $scheme = '';
	var $host = '';
	var $user = '';
	var $pass = '';
	var $path = '';
	
	var $count = 0;
	var $verbose = false;
	
	var $lastQuery = '';
	var $lastRows = '';
	
	// Caches
	var $cache_tables = array();
	
	/****************************************************************************************************
	 * Constructor
	 */
	function GooDB(&$context, $uri) {
		$this->Goo($context); // Super Constructor
		
		// ****** Connect
		$this->setConnection($uri);
	}
	
	/****************************************************************************************************
	 * Set connection to the database
	 *
	 * @param	uri of the database (mysql://user:pass@host/database)
	 * @param	new link (default true)
	 */
	function setConnection($uri, $new_link = true) {
		$err = '';
		
		// ****** Prepare
		$this->uri = $uri; //preg_replace('/(\w+:\/\/\w*)(:\w*)?(.*)/', '$1:***$3', $uri);
		$parsed = parse_url($uri);
		
		$this->scheme = @$parsed['scheme'];
		$this->host = @$parsed['host'];
		$this->user = @$parsed['user'];
		$this->pass = @$parsed['pass'];
		$this->name = @substr(@$parsed['path'], 1);
		
		// ****** Check
		if (trim($this->name) == "") $err .= 'Missing database name. ';
		if (trim($this->host) == "") $err .= 'Missing hostname. ';
		
		// ****** Err
		if ($err) {
			$this->_log($err);
		} else {
			// ****** Connect
			$this->connection = mysql_connect($this->host, $this->user, $this->pass, $new_link);
			
			if ($this->connection) {
				mysql_select_db($this->name) OR $this->_log('Maybe the database "' . $this->name . '@' . $this->host . '" is missing or misspelled.'); 
			}
		}
	}
	
	/****************************************************************************************************
	 * Retrieve the previously set connection
	 *
	 * @return	connection id
	 */
	function getConnection() {
		return $this->connection;
	}
	
	/****************************************************************************************************
	 * SQL QUERY IMPROVED
	 * Executes a specific SQL query, with different return values
	 *
	 * @param	sql query string
	 * @return	on success the fields array OR the query id. On failure boolean false.
	 */
	function query($query) {
		$out = false;
		
		if ($this->connection) {
			// ****** Various
			$this->count++;
			$this->lastQuery = $query;
		
			// ****** Verbose
			if ($this->verbose) echo '<div style="font-family: Courier New, serif; font-size: 12px; color: #ffffff; background: #aa0000" size="-1"><strong>@</strong> ' . $query . '</div>';
		
			// ****** Query
			if ($result = mysql_query($query, $this->connection)) {
				if (gettype($result) == 'resource') {
					// *** SELECT/SHOW/DESCRIBE/EXPLAIN
					$this->lastRows = @mysql_num_rows($result);
					
					$out = array();
					while ($row = mysql_fetch_array($result)) {
						$out[] = $row;
					}
				} else {
					// *** INSERT/UPDATE/DELETE/REPLACE
					$this->lastRows = @mysql_affected_rows($this->connection);
					if ($result === true)
						$out = $this->lastRows;
					else
						$out = $result;
				}
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * DB COUNT
	 * Count all the rows that are matching a criteria.
	 *
	 * @param	table name
	 * @param	optional where condition string
	 * @return	number of rows matching
	 */
	function count($table, $where = true) {
		$out = false;
		
		$query = '
			SELECT count(*)
			FROM ' . $table . '
			WHERE ' . $where . '
			;';
		
		$result = $this->query($query);
		
		if (isset($result[0][0]))
			$out = $result[0][0];
		
		return $out;
	}
	
	/****************************************************************************************************
	 * DB GET TABLES
	 * Gets all the tables from the database.
	 *
	 * @return	database tables array
	 */
	function getTables() {
		$out = array();
		
		// *** Retrieve tables listing and cache
		if (!$this->cache_tables) {
			if ($this->connection) {
				$rows = $this->query('SHOW TABLES;');
			
				foreach ($rows as $row) {
					$this->cache_tables[] = $row[0];
				}
			}
		}
		
		// *** Return cached values
		$out = $this->cache_tables;
		
		return $out;
	}
	
	/****************************************************************************************************
	 * DB GET TABLE FIELDS
	 * Gets all the fields from a specific table of the database.
	 *
	 * @param	table name
	 * @return	database tables array
	 */
	function getFields($table) {
		$out = array();
		
		if ($this->connection) {
			$rows = $this->query('DESCRIBE ' . $table . ';');
			
			foreach ($rows as $row) {
				$out[] = $row['Field'];
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * BIND A TABLE 
	 * Binds a table to a PHP object
	 *
	 * @param	table name
	 * @return	object
	 */
	function table($table) {
		$table = new GooDBTable($table, $this);
		
		return $table;
	}
	
	/****************************************************************************************************
	 * To String method
	 *
	 * @param	optional: sets the output mode (def: 'text') [text, html]
	 * @return	this object to string
	 */
	function toString($mode = '') {
		$out = '';
		
		if ($mode == 'text') {
			// ****** Text
			$out .= '' . $this->scheme . '://' . $this->user . ':*@' . $this->host . '/' . $this->table . "\n";
			$out .= 'queries done: ' . $this->count . "\n";
			$out .= 'last query: ' . $this->lastQuery . "\n";
		} else {
			// ****** HTML
			$out .= '<ul>';
			$out .= '<li><strong>DB</strong>';
			$out .= '<ul>';
			$out .= '<li>' . $this->scheme . '://' . $this->user . ':*@' . $this->host . '/' . $this->table . '</li>';
			$out .= '<li>queries done: ' . $this->count . '</li>';
			$out .= '<li>last query: ' . $this->lastQuery . '</li>';
			$out .= '</ul></li>';
			$out .= '</ul>';
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Log to output
	 *
	 * @param	text to be formatted and written
	 */
	function _log($text) {
		echo '
			<div style="font-family: Arial, Helvetica, Verdana, sans serif; margin: 20px">
				<h2>Goo.DB: error connecting to the database </h2>
				<p>' . $text . '<br />
					<br/>
					<small>Try refreshing this page, or coming back in a few minutes.<br />
					If the problem persists, contact the web administrator.</small></p>
			</div>
		';
	}
}

/****************************************************************************************************
 * CLASS: single Database Table
 */
class GooDBTable {
	var $name	= '';			// table name
	var $db		= null;		// GooDB database object
	
	/****************************************************************************************************
	 * Constructor
	 *
	 * @param		text to be formatted and written
	 */
	function GooDBTable($table, &$gooDB) {
		$this->name	= $table;
		$this->db	= &$gooDB;
	}
	
	/****************************************************************************************************
	 * Set an array of fields into the database table.
	 * The syntax is:
	 * array(
	 *	'field1'	=> 'value',
	 *	'field2'	=> 'value'	
	 *	);
	 *
	 * @param		where condition (false means INSERT)
	 * @param		array of pairs (field => values)
	 * @param		optional limit field (i.e. '1', '20', ...)
	 * @return	boolean true on success
	 */
	function set($where, $array, $limit = '') {
		$out = false;
		$quote = "'";
		
		if ($limit) $limit = 'LIMIT ' . $limit;
		
		if ($where) {
			// ****** UPDATE
			foreach ($array as $field => $value) {
				$set[] = $field . ' = ' . $quote . $this->norm($value) . $quote;
			}
			
			// *** Query
			$query = '
				UPDATE ' . $this->name . '
				SET ' . join(", ", $set) . '
				WHERE ' . $where . '
				' . $limit . '
				;';
			
			$out = $this->db->query($query);
		} else {
			// ****** INSERT
			foreach ($array as $field => $value) {
				$fields[] = $field;
				$values[] = $this->norm($value);
			}
			
			// *** Query
			$query = '
				INSERT INTO ' . $this->name . '
					(' . join(', ', $fields) . ') 
				VALUES
					(' . $quote . join($quote . ', ' . $quote, $values) . $quote . ')
				' . $limit . '
				;';
			
			$out = $this->db->query($query);
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Get an array of rows from the database table.
	 *
	 * @param		where condition
	 * @param		string of order bys
	 * @param		optional limit condition (i.e. '0,25', '25,50', ...)
	 * @return	array of rows (boolean false on failure)
	 */
	function get($where, $order = '', $limit = '') {
		$out = false;
		
		if ($limit) $limit = 'LIMIT ' . $limit;
		if ($order) $order = 'ORDER BY ' . $order;
		
		$query = '
			SELECT *
			FROM ' . $this->name . '
			WHERE ' . $where . '
			' . $order . '
			' . $limit . '
			;';
		
		$out = $this->db->query($query);
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Deletes the specified rows from the table
	 *
	 * @param		where condition
	 * @param		optional limit condition (i.e. '1', '20', ...)
	 * @param		number of the rows deleted, boolean false on failure
	 */
	function destroy($where, $limit = '') {
		$out = false;
		
		if ($limit) $limit = 'LIMIT ' . $limit;
		
		$query = '
			DELETE
			FROM ' . $this->name . '
			WHERE ' . $where . '
			' . $limit . '
			;';
		
		$out = $this->db->query($query);
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Count the number of rows matching the where condition. If no where condition is specified,
	 * it returns the table length.
	 *
	 * @param		optional where condition
	 * @return	count index
	 */
	function count($where = true) {
		return $this->db->count($this->name, $where);
	}
	
	/****************************************************************************************************
	 * Creates a new table.
	 * This function is a simple way to create standard tables. To create more complex tables use
	 * a direct query using the DB goo.
	 *
	 * @param		fields sql array
	 * @param		primary key field name
	 * @return	boolean true on success
	 */
	function create($array) {
		$out = false;
		
		if (!in_array($this->name, $this->db->getTables())) {
			// ****** Prepare
			$fields = array();
			foreach ($array as $name => $value) {
				if ($value == 'key' || $value == 'KEY') {
					$fields[] = '`' . $name . '` INT UNSIGNED NOT NULL AUTO_INCREMENT';
					$primarykey = $name;
				} else {
					$fields[] = '`' . $name . '` ' . $value . ' NOT NULL';
				}
			}
			
			// ****** Create
			$query = '
				CREATE 
				TABLE `' . $this->name . '` (
					' . join(', ', $fields) . ',
				PRIMARY KEY (`' . $primarykey . '`)
				);';
		
			$out = $this->db->query($query);
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Drops the current table.
	 *
	 * @return	boolean true on success
	 */
	function drop() {
		$out = false;
		
		$query = '
			DROP 
			TABLE ' . $this->name . '
			;';
		
		$out = $this->db->query($query);
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Normalize text to be passed into db
	 * Should avoid SQL injection attacks
	 *
	 * @param		text to be normalized
	 * @return	normalized text
	 */
	function norm(&$text) {
		$out = $text;
		
		// Stripslashes
		if (get_magic_quotes_gpc()) {
			$out = stripslashes($out);
		}

		// Quote if not integer
		if (!is_numeric($out)) {
			$out = @mysql_real_escape_string($out);
		}

		return $out;
	}
}

?>