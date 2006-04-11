<?php

error_reporting(E_ALL);

require_once 'Goo/Goo.php';

$g = new GooContext(array(
	'lang'		=> 'lang/eng',
	'DB'		=> 'mysql://folletto:@localhost/goo',
	'Users'		=> 'users',
	'Template'	=> 'template/default',
	'lol'		=> 'this will stay as env, goo doesn\'t exist'
	));

$g->gooTemplate->render('header');

// ****** Database and Template Example
$single = array('Title' => 'Vangelis Docet', 'Content' => 'This is some text.');
$double = array(
	array('Title' => 'Porcois', 'Content' => 'Bunch of text'),
	array('Title' => 'Porquette', 'Content' => 'Bleeding edge text')
	);

$dbTest = $g->gooDB->table('goo_test');
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

$g->gooTemplate->render('items', $items);

// ****** Filters example
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


// ****** Closing
$g->_dbg($g->toString());
$g->_dbg('generated in ' . $g->lifeTime(3) . ' sec with Goo');

$g->gooTemplate->render('footer');
?>