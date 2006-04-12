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
	var $isRunning = null;	// name of the page currently running
	
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
	 * Bind an URI relative path to a specific handler
	 * 
	 * @param	URI match string
	 * @param	handler
	 */
	function bind($uri, $handler)
	{
		
	}
	
	/****************************************************************************************************
	 * Finds if the current passed parameter is an existing page or is handled by something
	 * and calls it.
	 * 
	 */
	function match()
	{
		// ****** Prepare the variables to be matched
		$self = dirname($_SERVER['PHP_SELF']) . '/';	// relative self path
		$uri = $_SERVER['REQUEST_URI'];					// user requested URI
		//$pathinfo = $_SERVER['PATH_INFO'];			// WP uses PATH_INFO, unset to me...
		
		// *** Split path from query
		$relevant = str_replace($self, '', $uri);		// get just the 'fake' part
		@list($path, $query) = explode('?', $relevant);	// split 'fake' part from query
		$chunks = explode('/', $path);
		
		// ****** Match
		$handler = '';
		
		if (is_array($chunks) && isset($chunks[0]) && $chunks[0] != '')
		{
			// *** Open a page
			$handler = $this->path . $chunks[0] . '.php';
		}
		else
		{
			// *** Open the index
			$handler = $this->path . 'index.php';
			
		}
		
		// ****** Include!
		$this->page($handler);
	}
	
	/****************************************************************************************************
	 * Load a specific page from the specified path.
	 * 
	 * @param	page name
	 */
	function page($handler)
	{
		// *** Preparing some variables in order to be usable easily in the page
		$context = $this->context;
		
		if ($handler && file_exists($handler))
		{
			include $handler;
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
						$out = true;
					}
				}
			
				@fclose($hfile);
			}
		}
		
		return $out;
	}
}