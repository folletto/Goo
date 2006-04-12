<?php

/*
 * Some tests
 *
 */

echo $handler;
echo '<h2>I know, this page doesn\'t work. It\'s an issue of variable scopes ($g/$this).</h2>';

// ****** Filters example
{
	function filter($text)
	{
		return '.: ' . $text . ' :.';
	}

	function refilter($text)
	{
		return '<h3> ' . $text . ' </h3>';
	}

	$g->setFilter('test', 'filter');
	$g->setFilter('test', 'refilter');
	echo $g->filter('test', 'oh long john');
}

?>