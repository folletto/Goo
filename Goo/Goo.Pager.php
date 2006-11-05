<?php
/*
 * Goo Pager
 * version 0.1
 * 
 * Copyright (C) 2006
 * by Davide S. Casali, Alessandro Morandi
 * www.digitalhymn.com
 *
 * This is the pages manager goo. This goo will give functions to create a .htaccess that
 * redirects the URI to the PHP routine and then handle it.
 * It makes URI rewriting easy from php, without modifying anymore the .htaccess file.
 * If the .htaccess file is uncreable due to restriction, you can create it with
 * these lines, changing the path to the folder to the one on the server:
 * 		<IfModule mod_rewrite.c>
 * 			RewriteEngine On
 * 			RewriteBase /path-to-folder/
 * 			RewriteCond %{REQUEST_FILENAME} !-f
 * 			RewriteCond %{REQUEST_FILENAME} !-d
 * 			RewriteRule . /path-to-folder/index.php [L]
 * 		</IfModule>
 * (Thanks to WordPress)
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

class GooPager extends Goo {
	var $path = null;			// pages folder
	var $parsed = null;		// parsed URI
	var $binds = array();	// declared binds array
	
	/****************************************************************************************************
	 * Constructor
	 */
	function GooPager(&$context, $path) {
		$this->Goo($context); // Super Constructor
		
		// ****** Init
		$this->path = trim($path, '/') . '/';
		
		// ****** Filters
		$this->context->setFilter('template', array($this, 'filterTemplate'));
	}
	
	/****************************************************************************************************
	 * Bind an URI 'root' relative path to a specific handler
	 * This in fact binds a function to a specific name 'directory' in the URI (mod_rewrite...)
	 * 
	 * @param		URI string, name to be matched
	 * @param		handler
	 */
	function setBind($name, $handler) {
		if ($name && $handler) {
			$this->binds[$name] = $handler;
		}
	}
	
	/****************************************************************************************************
	 * Returns the bind on an URI 'root' relative path to a specific handler
	 * 
	 * @param		URI string, name to be matched
	 * @return	handler
	 */
	function getBind($name) {
		$out = false;
		
		if ($name && isset($this->binds[$name])) {
			$out = $name;
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Parse the URI. Finds the real part of the path and splits the 'control' name from
	 * the other parameters.
	 * The query string is as always accessible via $_GET.
	 * 
	 * @return	parsed URI array
	 */
	function parsed() {
		if (!is_array($this->parsed)) {
			// ****** Prepare the variables to be matched
			$self = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';	// relative self path
			$uri = $_SERVER['REQUEST_URI'];						// user requested URI
			//$pathinfo = $_SERVER['PATH_INFO'];			// WP uses PATH_INFO, unset to me...
			
			// *** Split path from query
			$relevant = str_replace(':' . $self, '', ':' . $uri);		// get just the 'fake' part
			@list($path, $query) = explode('?', $relevant);	// split 'fake' part from query
			$extra = explode('/', $path);
			
			// ****** Prepare the array
			$this->parsed = $extra;
		}
		
		return $this->parsed;
	}
	
	/****************************************************************************************************
	 * Finds if the current passed parameter is an existing page or is handled by something
	 * and calls it.
	 * 
	 */
	function exec() {
		$purl = $this->parsed();
		
		// ****** Handling variables
		$handler = array($this, 'page');
		$matchindex = 0;
		
		// ****** Match
		$file = '';
		
		if (is_array($purl) && isset($purl[0]) && $purl[0] != '') {
			// *** Check for matches
			for ($i = sizeof($purl); $i > 0; $i--) {
				$name = join('.', array_slice($purl, 0, $i));
				$path = $this->path . $name . '.php';
				
				if (file_exists($path)) {
					// *** Use the file handler
					$matchindex = $i;
					$i = false;
				} else {
					// ****** Check for an entry in the bind array
					$name = join('/', array_slice($purl, 0, $i));
					if (isset($this->binds[$name])) {
						// *** Use the bound handler
						$handler = $this->binds[$name];
						$matchindex = $i;
						$i = false;
					}
				}
			}
			
			// *** No handler specified, at any level
			if ($matchindex == 0) {
				$purl = array_merge(array('404'), $purl);
				$matchindex = 1;
			}
		} else {
			// *** Open the index
			$purl = array('index');
			$matchindex = 1;
		}
		
		// ****** Handle!
		$this->handle($handler, $purl, $matchindex);
	}
	
	/****************************************************************************************************
	 * Handle the specified tag chunk.
	 * 
	 * @param		handler (function name string or object array($object, 'name'))
	 * @param		parsed url array
	 * @param		matching parts of the array (0 = none)
	 */
	function handle($handler, $purl, $matchindex) {
		if (is_array($handler)) {
			$handler[0]->{$handler[1]}($purl, $matchindex);
		} else {
			$handler($purl, $matchindex);
		}
	}
	
	/****************************************************************************************************
	 * Load a specific page from the specified path.
	 * 
	 * @param		page name
	 * @return	boolean, true on success
	 */
	function page($purl, $matchindex) {
		$name = join('.', array_slice($purl, 0, $matchindex));
		$root = dirname($_SERVER['PHP_SELF']) . '/';
		$path = $this->path . $name . '.php';
		
		// *** Preparing some variables in order to be usable easily in the page
		$context = $this->context;
		
		if ($path && file_exists($path)) {
			include $path;
			
			// *** File found and running
			return true;
		} else {
			// *** File not found.
			// Note: this function uses two exit points to avoid variables overwriting
			//       from the included page.
			return false;
		}
	}
	
	
	/****************************************************************************************************
	 * Includes a specific page.
	 * 
	 * @param		page name
	 * @return	boolean, true on success
	 */
	function render($name) {
		$purl = explode('.', $name);
		$matchindex = sizeof($purl);
		
		return $this->page($purl, $matchindex);
	}
	
	/****************************************************************************************************
	 * Creates the .htaccess file in the path of the running document.
	 * 
	 * @return	boolean true on success
	 */
	function makeHTAccess() {
		$out = false;
		$self = dirname($_SERVER['PHP_SELF']) . '/';	// relative self path
		$path = dirname($_SERVER['SCRIPT_FILENAME']) . '/'; // also PATH_TRANSLATED?
		$htaccess = $path . '.htaccess';
		
		if (file_exists($htaccess) && is_writable($htaccess)
			|| is_writable($path)) {
			// ****** Prepare text
			$content = '';
			$content .= "\n\n";
			$content .= '<IfModule mod_rewrite.c>' . "\n";
			$content .= '    RewriteEngine On' . "\n";
			$content .= '    RewriteBase ' . $self . "\n";
			$content .= '    RewriteCond %{REQUEST_FILENAME} !-f' . "\n";
			$content .= '    RewriteCond %{REQUEST_FILENAME} !-d' . "\n";
			$content .= '    RewriteRule . ' . $self . 'index.php [L]' . "\n";
			$content .= '</IfModule>' . "\n";
			$content .= "\n";
			
			// ****** Existence check
			$old = file_get_contents($htaccess);
			
			if (substr($old, $content) === false) {
				// ****** Write the mod_rewrite rules
				$hfile = @fopen($htaccess, 'a');
				if ($hfile) {
					// *** Write
					if (@fwrite($hfile, $content) !== false)
					{
						// Rewrites written!
						$out = true;
					}
					
					@fclose($hfile);
				}
			}
			else
			{
				// The Rewrites are already there!
				$out = true;
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Template filter to allow path addressing working
	 * This function converts 
	 * 
	 * @param	input text
	 * @return	output relativized text
	 */
	function filterTemplate($text) {
		$out = $text;
		
		// ****** Prepare
		$path = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';
		
		// ****** Relativize
		$out = preg_replace('/<a(.*)href="((?!http).*)"/i', '<a$1href="' . $path . '$2"', $out);
		
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
			$out .= 'Pager: ' . "\n";
			$out .= 'pages path: ' . $this->path . "\n";
			$out .= 'parsed uri chunks: ' . join('/', $this->parsed()) . "\n";
		} else {
			// ****** HTML
			$out .= '<ul>';
			$out .= '<li><strong>Pager</strong>';
			$out .= '<ul>';
			$out .= '<li>pages path: ' . $this->path . '</li>';
			$out .= '<li>parsed uri chunks: ' . join('/', $this->parsed()) . '</li>';
			$out .= '</ul></li>';
			$out .= '</ul>';
		}
		
		return $out;
	}
}