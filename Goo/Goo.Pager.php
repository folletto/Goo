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

class GooPager extends Goo
{
	var $path = null;		// pages folder
	var $parsed = null;		// parsed URI
	var $binds = array();	// declared binds array
	
	/****************************************************************************************************
	 * Constructor
	 */
	function GooPager($context, $path)
	{
		$this->Goo($context); // Super Constructor
		
		// ****** Init
		$path = trim($path, '/') . '/';
		
		$this->path = $path;
	}
	
	/****************************************************************************************************
	 * Bind an URI 'root' relative path to a specific handler
	 * This in fact binds a function to a specific name 'directory' in the URI (mod_rewrite...)
	 * 
	 * @param	URI string, name to be matched
	 * @param	handler
	 */
	function setBind($name, $handler)
	{
		if ($name && $handler)
		{
			$this->binds[$name] = $handler;
		}
	}
	
	/****************************************************************************************************
	 * Returns the bind on an URI 'root' relative path to a specific handler
	 * 
	 * @param	URI string, name to be matched
	 * @return	handler
	 */
	function getBind($name)
	{
		$out = false;
		
		if ($name && isset($this->binds[$name]))
		{
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
	function parsed()
	{
		if (!is_array($this->parsed))
		{
			// ****** Prepare the variables to be matched
			$self = dirname($_SERVER['PHP_SELF']) . '/';	// relative self path
			$uri = $_SERVER['REQUEST_URI'];					// user requested URI
			//$pathinfo = $_SERVER['PATH_INFO'];			// WP uses PATH_INFO, unset to me...
		
			// *** Split path from query
			$relevant = str_replace($self, '', $uri);		// get just the 'fake' part
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
	function exec()
	{
		$purl = $this->parsed();
		
		// ****** Handling variables
		$handler = array($this, 'page');
		$params = $purl;
		
		// ****** Match
		$file = '';
		
		if (is_array($purl) && isset($purl[0]) && $purl[0] != '')
		{
			// *** Open a page
			for ($i = sizeof($purl); $i >= 0; $i--)
			{
				$name = join('.', array_slice($purl, 0, $i));
				$path = $this->path . $name . '.php';
				
				if (file_exists($path))
				{
					$params = array($name);
					$i = -1;
				}
			}
		}
		else
		{
			// *** Open the index
			$params = array('index');
		}
		
		// ****** Handle!
		$this->handle($handler, $params);
	}
	
	/****************************************************************************************************
	 * Handle the specified tag chunk.
	 * 
	 * @param	handler (function name string or object array($object, 'name'))
	 * @param	chunks
	 */
	function handle($handler, $controller)
	{
		if (is_array($handler))
		{
			$handler[0]->{$handler[1]}($controller);
		}
		else
		{
			$handler($controller);
		}
	}
	
	/****************************************************************************************************
	 * Load a specific page from the specified path.
	 * 
	 * @param	page name
	 */
	function page($purl)
	{
		$file = $this->path . $purl[0] . '.php';
		
		// *** Preparing some variables in order to be usable easily in the page
		$context = $this->context;
		
		if ($file && file_exists($file))
		{
			include $file;
		}
		else
		{
			if (file_exists($this->path . '404.php'))
			{
				include $this->path . '404.php';
			}
		}
	}
	
	/****************************************************************************************************
	 * Creates the .htaccess file in the path of the running document.
	 * 
	 * @return	boolean true on success
	 */
	function makeHTAccess()
	{
		$out = false;
		$self = dirname($_SERVER['PHP_SELF']) . '/';	// relative self path
		$path = dirname($_SERVER['SCRIPT_FILENAME']) . '/'; // also PATH_TRANSLATED?
		$htaccess = $path . '.htaccess';
		
		if (file_exists($htaccess) && is_writable($htaccess)
			|| is_writable($path))
		{
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
			
			if (substr($old, $content) === false)
			{
				// ****** Write the mod_rewrite rules
				$hfile = @fopen($htaccess, 'a');
				{	
					// *** Write
					if (@fwrite($hfile, $content) !== false)
					{
						// Rewrites written!
						$out = true;
					}
				}
			
				@fclose($hfile);
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
			$out .= 'Pager: ' . "\n";
			$out .= 'pages path: ' . $this->path . "\n";
		}
		else
		{
			// ****** HTML
			$out .= '<ul>';
			$out .= '<li><strong>Pager</strong>';
			$out .= '<ul>';
			$out .= '<li>pages path: ' . $this->path . '</li>';
			$out .= '</ul></li>';
			$out .= '</ul>';
		}
		
		return $out;
	}
}