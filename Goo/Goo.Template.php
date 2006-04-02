<?php
/*
 * Goo Template
 * version 0.1
 * 
 * Copyright (C) 2006
 * by Davide S. Casali
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
	var $name	= '';		// template name (path)
	var $count	= 0;		// count template renders
	
	/****************************************************************************************************
	 * Constructor
	 */
	function GooTemplate($context, $template)
	{
		$this->Goo($context); // Super Constructor
		
		// ****** Init
		$this->name = $template;
		
		// ****** Buffering
		ob_start();
	}
	
	/****************************************************************************************************
	 * Render a template part.
	 * The array can be uni- or bi-dimensional.
	 * If it's uni-, its values will be converted into variables and passed to the template.
	 * If it's bi-, for each of its values will be executed the step above.
	 *
	 * @param	template part
	 * @param	array to be inserted
	 */
	function render($part, $array = null)
	{
		$this->count++;
		$filename = $this->name . '/' . $part . '.php';
		
		if (is_array($array))
		{
			if (isset($array[0]) && is_array($array[0]))
			{
				// ****** Bidimensional
				foreach ($array as $item)
				{
					extract($item);
					include $filename;
				}
			}
			else
			{
				// ****** Monodimensional
				extract($array);
				include $filename;
			}
		}
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
			$out .= 'template name: ' . $this->name . "\n";
			$out .= 'template renders: ' . $this->count . "\n";
		}
		else
		{
			// ****** HTML
			$out .= '<ul>';
			$out .= '<li><strong>Template</strong>';
			$out .= '<ul>';
			$out .= '<li>template name: ' . $this->name . '</li>';
			$out .= '<li>template renders: ' . $this->count . '</li>';
			$out .= '</ul></li>';
			$out .= '</ul>';
		}
		
		return $out;
	}
}

?>