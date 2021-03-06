<?php
/*
 * Goo
 * Golem Objects
 * version 0.1
 * 
 * Copyright (C) 2006
 * by Davide S. Casali, Alessandro Morandi
 * www.digitalhymn.com
 *
 * This is the core Goo component. This file contains the GooContext class,
 * that is the ambient where all the goos live.
 * The GooContext initializes with an array of environment variables
 * (name => value): if a goo named like a variable exists, it will be
 * instantiated using the variable values as configuration.
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

/****************************************************************************************************
 * Context class
 * Defines the execution environment for GoO
 *
 */
class GooContext {
	var $version = 0001;	// incremental version id (for compatibility checks) [syntax: Mmmm]
	
	var $env = array();		// environment variables array
	var $goos = array();	// goos array
	var $filters = array();	// filters array
	
	var $instanceBornAt;	// instance start time
	
	/****************************************************************************************************
	 * Golem Objects Context constructor
	 *
	 * @param	environment array
	 */
	function GooContext($env = null) {
		// ****** Timer
		$mtime = explode(' ', microtime());
		$this->instanceBornAt = $mtime[1] + $mtime[0];
		
		// ****** Init Goos
		$this->init($env);
	}
	
	/****************************************************************************************************
	 * Goo initialization.
	 * This function assigns the env array to an internal env array, then pushes out just
	 * the ones that match an existing Goo, loading it.
	 *
	 * @param	environment array
	 */
	function init($env = null) {
		// ****** Prepare
		if (!is_array($env)) {
			$env = array(
				);
		}
		
		$this->env = $env;
		
		// ****** Loop through
		foreach ($env as $name => $params) {
			if ($this->setGoo($name, $params)) {
				// *** Remove the goo name from the environment
				unset($this->env[$name]);
			}
		}
	}
	
	/****************************************************************************************************
	 * Goo plugging.
	 * Goo names MUST have the first letter uppercase.
	 *
	 * @param	goo file name (adds file: goo.name.php)
	 */
	function setGoo($name, $params = '') {
		$out = false;
		
		// Checks if the first letter is not a lowercase letter
		if (!preg_match('/^[a-z]/', $name)) {
			$filename = dirname(__FILE__) . '/Goo.' . $name . '.php';
			$classname = 'Goo' . $name;
		
			// ****** Creation
			if ($name && file_exists($filename)) {
				include_once $filename;
			
				// ****** Bind
				$goo = new $classname($this, $params);
				if (is_subclass_of($goo, 'Goo')) {
					// ...to Array
					$this->goos[$name] = &$goo;
					// ...to gooObj
					$this->{$name} = &$this->goos[$name];
				}
			
				$out = true;
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Get all the goos in an array
	 *
	 * @return	array containing all the enabled goos
	 */
	function getGoos() {
		return $this->goos;
	}
	
	/****************************************************************************************************
	 * Get a specific goo, instead of using direct goo ($this->gooName)
	 *
	 * @param	goo name
	 * @return	goo
	 */
	function getGoo($name) {
		return $this->goos[$name];
	}
	
	/****************************************************************************************************
	 * Check if the specified name is a loaded goo name.
	 *
	 * @param	goo name
	 * @return	boolean true if name is a loaded goo name
	 */
	function isGoo($name) {
		return array_key_exists($name, $this->goos);
	}
	
	/****************************************************************************************************
	 * Get the value of an environemt variable.
	 *
	 * @param	environment variable
	 * @return	variable value
	 */
	function getEnv($name) {
		if (isset($this->env[$name]))
			return $this->env[$name];
		else
			return null;
	}
	
	/****************************************************************************************************
	 * Set a new filter pipeline.
	 *
	 * @param	name of the filter
	 * @param	function reference
	 * @param	optional position, must be int
	 * @return	variable value
	 */
	function setFilter($filter, $fx, $position = false) {
		// ****** Prepare
		if (!isset($this->filters[$filter]))
			$this->filters[$filter] = array();
		
		// ****** Append or reposition/rewrite
		if ($position === false)
			$this->filters[$filter][] = $fx;
		elseif (is_int($position))
			$this->filters[$filter][$position] = $fx;
		
		// ****** Reorder
		ksort($this->filters[$filter]);
	}
	
	/****************************************************************************************************
	 * Filter a stream of data.
	 *
	 * @param	name of the filter
	 * @param	data stream
	 * @return	variable value
	 */
	function filter($filter, $stream) {
		$out = $stream;
		
		if (isset($this->filters[$filter]) && is_array($this->filters[$filter])) {
			foreach ($this->filters[$filter] as $fx) {
				if (is_array($fx))
					$out = $fx[0]->{$fx[1]}($out);
				else
					$out = $fx($out);
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Displays the elapsed time since the creation of this instance of the GooContext class.
	 *
	 * @param	optional: sets the decimal places to be returned (default: all)
	 * @return	this object to string
	 */
	function lifeTime($limit = false) {
		$mtime = explode(' ', microtime());
		$out = ($mtime[1] + $mtime[0]) - $this->instanceBornAt;
		
		if ($limit)
			$out = substr($out, 0, strpos($out, '.') + $limit + 1);
		
		return $out;
	}
	
	/****************************************************************************************************
	 * To String method
	 *
	 * @param	optional: sets the output mode (def: 'html') [text, html]
	 * @return	this object to string
	 */
	function toString($mode = '') {
		$out = '';
		
		if ($mode == 'text') {
			// ****** Text
			$out .= 'Env: ' . "\n";
			foreach ($this->env as $name => $value) {
				$out .= ' * ' . $name . ' = ' . $value . '' . "\n";
			}
			$out .= 'Goos: ';
			foreach ($this->goos as $name => $obj) {
				$out .= ' * ' . $name . "\n";
			}
			$out .= "\n";
		} else {
			// ****** HTML
			$out .= '<ul>';
			$out .= '<li>Env<ul>';
			foreach ($this->env as $name => $value) {
				$out .= '<li><strong>' . $name . '</strong> = ' . $value . '</li>';
			}
			$out .= '</ul></li>';
			$out .= '<li>Goos<ul>';
			foreach ($this->goos as $name => $obj) {
				$out .= '<li><strong>' . $name . '</strong></li>';
			}
			$out .= '</ul></li>';
			$out .= '</ul>';
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Debug Box
	 *
	 * @param	text to be displayed
	 */
	function _dbg($text, $return = false) {
		$out = print_r($text, true);
		
		$out = str_replace("\n", '<br/>', $out);
		$out = str_replace(' ', '&nbsp;', $out);
		
		$out = '<div class="GooDebugBox" style="
			font-family: Courier New;
			font-size: 12px;
			margin: 2em;
			padding: 1em;
			background: #fcfafa;
			border: 2px dotted #ffe0e0;
			">' . $out . '</div>';
		
		if (!$return)
			echo $out;
		
		return $out;
	}
}

/****************************************************************************************************
 * Proto class
 * Every Goo must extend this class. Do NOT use this directly.
 *
 */
class Goo
{
	var $context = null;
	var $lang = array();
	
	/****************************************************************************************************
	 * Golem Objects Constructor Method
	 * 
	 */
	function Goo(&$context) {
		// ****** Init Context
		$this->context = &$context;
		
		// ****** Init Language
		if ($context->getEnv('lang')) {
			$this->lang = $context->getEnv('lang');
		}
	}
	
	
}
  
?>