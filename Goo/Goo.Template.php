<?php
/*
 * Goo Template
 * version 0.2
 * 
 * Copyright (C) 2006
 * by Davide S. Casali, Alessandro Morandi
 * www.digitalhymn.com
 *
 * This is the template manager goo.
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

class GooTemplate extends Goo
{
	var $path	= '';			// template name (path)
	var $count	= 0;			// count template renders
	
	var $partials = array();	// partials array
	
	/****************************************************************************************************
	 * Constructor
	 */
	function GooTemplate($context, $path)
	{
		$this->Goo($context); // Super Constructor
		
		// ****** Init
		$path = trim($path, '/') . '/';
		
		$this->path = $path;
		$this->partials = $this->read($path);
		
		// ****** Buffering
		ob_start();
	}
	
	/****************************************************************************************************
	 * Read all the template files.
	 * This function imports into an array all the files and their partials.
	 * Reads JUST the files in the template folder that match this syntax: tpl.*.php
	 *
	 * @param	template folder
	 * @return	array-ized partials ('partial' => 'template block')
	 */
	function read($template)
	{
		$out = array();
		
		if (is_dir($template))
		{
			// ****** Read template directory
			if ($hdir = @opendir($template))
			{
				while (($file = readdir($hdir)) !== false)
				{
					if (strpos($file, 'tpl.') === 0)
					{
						$content = file_get_contents($template . $file); // PHP4.3+
						
						//preg_match_all('/:(\w+).*\n+(.|\n)*\n:end\n/i', $content, $matches, PREG_SET_ORDER);
						$matches = $this->readPartials($content);
						$out = array_merge($out, $matches);
					}
				}
				
				@closedir($hdir);
			}
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Read the string and convert its content into a partials template array
	 *
	 * @param	text string
	 * @return	array-ized partials ('partial' => 'template block')
	 */
	function readPartials($string)
	{
		$out = array();
		
		$partial_name = null;
		$partial_content = '';
		
		$lines = preg_split('/\n/', $string);
		
		foreach ($lines as $line)
		{
			if (preg_match('/^:(\w+)\s*.*$/i', $line, $matches) > 0)
			{
				// ****** Partial
				// *** Close old partial
				if ($partial_name !== null)
				{
					$out[$partial_name] = $partial_content;
					$partial_content = "";
				}
				
				// *** Start new partial
				$partial_name = $matches[1];
			}
			else
			{
				// ****** Fill content
				if ($partial_name !== null && $line !== '')
					$partial_content .= $line . "\n";
			}
		}
		
		// *** Close last partial
		if ($partial_name !== null)
		{
			$out[$partial_name] = $partial_content;
		}
		
		return $out;
	}
	
	/****************************************************************************************************
	 * Render a template part.
	 * The array is optional and it can be uni- or bi-dimensional.
	 * If it's uni-, its values will be converted into variables and passed to the template.
	 * If it's bi-, for each of its values will be executed the step above.
	 *
	 * @param	template part name identifier
	 * @param	optional array to be inserted (uni/bi-dimensional)
	 * @param	optional render function name callback (def: Partial) [Partial, File]
	 */
	function render($partial, $array = null, $fx = null)
	{
		$this->count++;
		
		// ****** Select renderer
		if ($fx)
			$renderer = array($this, 'renderHelper' . $fx . '');
		else if (is_array($fx))
			$renderer = $fx;
		else
			$renderer = array($this, 'renderHelperPartial');
		
		// ****** Render loops
		if (is_array($array))
		{
			if (isset($array[0]) && is_array($array[0]))
			{
				// ****** Bidimensional
				foreach ($array as $item)
				{
					$renderer[0]->{$renderer[1]}($partial, $item);
				}
			}
			else
			{
				// ****** Monodimensional
				$renderer[0]->{$renderer[1]}($partial, $array);
			}
		}
		else if ($array == null)
		{
			$renderer[0]->{$renderer[1]}($partial, array());
		}
	}
	
	/****************************************************************************************************
	 * Partials Array Renderer
	 * Renderer for the render() loop.
	 * 
	 * @param	partial name
	 * @param	item array
	 */
	function renderHelperPartial($partial, $item)
	{
		extract($item);
		
		// *** Evaluates the string
		// adding a intermediate 'smart variables' parser
		$code = preg_replace('/\<\$(\w+)\>/', '<?php echo \$$1; ?>', $this->partials[$partial]);
		
		// partials are HTML mainly, so we close the php tags before evaluating.
		eval(' ?' . '>' . $code . '<' . '?php ');
	}
	
	/****************************************************************************************************
	 * File Include Renderer
	 * Renderer for the render() loop.
	 * 
	 * @param	partial name
	 * @param	item array
	 */
	function renderHelperFile($partial, $item)
	{
		extract($item);
		
		// *** Include a file
		$filename = $this->path . 'tpl.' . $partial . '.php';
		include $filename;
	}
	
	/****************************************************************************************************
	 * Check for cache file existence and in case use it.
	 * 
	 */
	function cache()
	{
		/// TODO
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
			$out .= 'Template: ' . "\n";
			$out .= 'template name: ' . $this->path . "\n";
			$out .= 'template renders: ' . $this->count . "\n";
		}
		else
		{
			// ****** HTML
			$out .= '<ul>';
			$out .= '<li><strong>Template</strong>';
			$out .= '<ul>';
			$out .= '<li>template name: ' . $this->path . '</li>';
			$out .= '<li>template renders: ' . $this->count . '</li>';
			$out .= '</ul></li>';
			$out .= '</ul>';
		}
		
		return $out;
	}
}

?>