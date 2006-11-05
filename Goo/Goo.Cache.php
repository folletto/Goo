<?php
/*
 * Goo Cache
 * version 0.1
 * 
 * Copyright (C) 2006
 * by Davide S. Casali, Alessandro Morandi
 * www.digitalhymn.com
 *
 * This is the cache manager goo.
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

class GooCache extends Goo {
	var $path			= '';		// cache name (path)
	var $selfpath	= '';		// cache path, relative to site root
	var $hit			= 0;		// count cache hits
	var $miss			= 0;		// count cache misses

	var $cache		= array();	// cache

	/****************************************************************************************************
	 * Constructor
	 */
	function GooCache(&$context, $path) {
		$this->Goo($context); // Super Constructor

		// ****** Init
		$this->path = rtrim($path, '/') . '/';
		$this->selfpath = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/' . $this->path;
		
		// ****** Filters
		$this->context->setFilter('serialize', array($this, 'filterCacheSerializer'));
		$this->context->setFilter('unserialize', array($this, 'filterCacheUnSerializer'));
	}
	
	/****************************************************************************************************
	 * Loads the cache.
	 *
	 * @param		cache identifier
	 * @param		optional, force the loading from file (avoids runtime caching, default false)
	 * @return	the content of the cache
	 */
	function get($name, $force = false) {
		$cache = $this->context->filter('unserialize', $this->getCache($name, $force));
		$content = $cache['content'];
		return $content;
	}
	
	/****************************************************************************************************
	 * Writes the cache.
	 *
	 * @param		cache identifier
	 * @return	positive values or true on success
	 */
	function set($name, $content) {
		if ($content === null) {
			$cache_serialized = null;
		} else {
			$cache = array(
				'time' => mktime(),
				'content' => $content
			);
			$cache_serialized = $this->context->filter('serialize', $cache);
		}
		return $this->setCache($name, $cache_serialized);
	}
	
	/****************************************************************************************************
	 * Loads the cache file.
	 *
	 * @param		cache identifier
	 * @param		optional, force the loading from file (avoids runtime caching, default false)
	 * @return	the content of the cache
	 */
	function getCache($name, $force = false) {
		$out = '';
		
		$this->hit++;
		if ($force || !isset($this->cache[$name])) {
			// ****** (Re)Load in memory cache
			$this->cache[$name] = $this->readFile($this->path . $name . '.cache');
			$this->hit--;
			$this->miss++;
		}
		
		$out = $this->cache[$name];
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Writes the cache file.
	 *
	 * @param		cache identifier
	 * @return	positive values or true on success
	 */
	function setCache($name, $content) {
		$out = false;
		
		// ****** Check directory existence
		if ($this->mkpath($this->path)) {
			// ****** Write
			if ($content == '') $content = null; // Delete (check writeFile doc)
			
			if ($out = $this->writeFile($this->path . $name . '.cache', $content)) {
				$this->cache[$name] = $content; // Update runtime cache
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Read a file.
	 * 
	 * @param		full file path
	 * @param		string open flags, in addition to 'r', optional (default to '')
	 * @return	file content, boolean false on failure
	 */	
	function readFile($fullpath, $openflags = '') {
		$out = false;
		
		if (is_readable($fullpath)) {
			$fd = @fopen($fullpath, 'r' . $openflags);
			if ($fd) {
				$out = fread($fd, filesize($fullpath));
				fclose($fd);
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Writes a file.
	 * Deletes it if it's unrequired, passing null keyword as content (===).
	 * 
	 * @param		full file path
	 * @param		content to be written (null keyword to delete, ===)
	 * @param		string open flags, optional (default to 'w')
	 * @return	file content, boolean false on failure
	 */	
	function writeFile($fullpath, $content, $openflags = 'w') {
		$out = false;
		
		if ($content === null) {
			// ****** Delete the file
			if (@unlink($fullpath)) $out = true;
		} else {
			// ****** Write the content
			$fd = @fopen($fullpath, $openflags);
			if ($fd) {
				$out = fwrite($fd, $content);
				fclose($fd);
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Makes a full path of directories (recursive mkdir).
	 *
	 * @param		path string
	 * @param		optional parameter mode (default 0777)
	 * @return	boolean true on success 
	 */
	function mkpath($path, $mode = 0777) {
		if (!is_dir(dirname($path))) {
			$this->mkpath(dirname($path));
		}
		return @mkdir($path, $mode) || is_dir($path);
	}
	
	/****************************************************************************************************
	 * Serializer for cache transformations.
	 * 
	 * @param		input plain text
	 * @return	output serialized text
	 */
	function filterCacheSerializer($text) {
		return @serialize($text);
	}
	
	/****************************************************************************************************
	 * UnSerializer for cache transformations.
	 * 
	 * @param		input text serialized
	 * @return	output plain text
	 */
	function filterCacheUnSerializer($text) {
		return @unserialize($text);
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
			$out .= 'Cache: ' . "\n";
			$out .= 'template name: ' . $this->path . "\n";
			$out .= 'template renders: ' . $this->count . "\n";
		} else {
			// ****** HTML
			$out .= '<ul>';
			$out .= '<li><strong>Cache</strong>';
			$out .= '<ul>';
			$out .= '<li>cache path: ' . $this->path . '</li>';
			$out .= '<li>mem cache hits: ' . $this->hit . '</li>';
			$out .= '<li>mem cache misses: ' . $this->miss . '</li>';
			$out .= '</ul></li>';
			$out .= '</ul>';
		}
		
		return $out;
	}
}

?>