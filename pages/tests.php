<?php
/*
 * Some tests
 * 
 * This page demonstrates some testing cases for the main goos.
 * Note that inside a page you are in fact inside the Pager goo: even if this
 * will give you native access to all its functions, it's important
 * to use the $context variable instead and nothing more.
 * If you need an external variable (you should avoid this) just use $GLOBALS or global.
 * 
 * Inside a page you can use:
 *  - $context, the GooContext object that is running the Pager goo.
 *  - $handler, the string containing the relative path to this file.
 *
 */



// ****** Filters example
{
	echo '<h2>Filters Example</h2>';
	
	function filter($text)
	{
		return '.: ' . $text . ' :.';
	}

	function refilter($text)
	{
		return '<h3> ' . $text . ' </h3>';
	}

	$context->setFilter('test', 'filter');
	$context->setFilter('test', 'refilter');
	echo $context->filter('test', 'oh long john');
}

// ****** Database and Template Example
{
	echo '<h2>Database Interaction Example</h2>';
	
	$single = array('Title' => 'Vangelis Docet', 'Content' => 'This is some text.');
	$double = array(
		array('Title' => 'Porcois', 'Content' => 'Bunch of text'),
		array('Title' => 'Porquette', 'Content' => 'Bleeding edge text')
		);

	$dbTest = $context->DB->table('goo_test');
	//$dbTest->drop();
	$dbTest->create(array(
		'test3id'	=> 'key', 
		'Title'		=> 'varchar(200)',
		'Content'	=> 'text',
		'HowMuch'	=> 'int'
		));

	if ($dbTest->count() < 2)
	{
		$dbTest->set(false, array('Title' => 'Vangelis Docet', 'Content' => 'Indeed he does.', 'HowMuch' => '10'));
		$dbTest->set(false, array('Title' => 'Jarre Docet', 'Content' => 'He does too.', 'HowMuch' => '9'));
	}

	$items = $dbTest->get(true, 'HowMuch DESC');

	$context->Template->render('items', $items);
}

?>