<?php

error_reporting(E_ALL);

require_once 'Goo/Goo.php';

$g = new GooContext(array(
	'lang'		=> 'lang/eng',
	'DB'		=> 'mysql://folletto:@localhost/goo',
	'Template'	=> 'template/default',
	'Pager'		=> 'pages',
	'Users'		=> 'users',
	'lol'		=> 'this will stay as env, goo doesn\'t exist'
	));

$g->Template->render('header');

// ****** Pager
$g->Pager->makeHTAccess();
$g->Pager->match();

// ****** Database and Template Example
{
	$single = array('Title' => 'Vangelis Docet', 'Content' => 'This is some text.');
	$double = array(
		array('Title' => 'Porcois', 'Content' => 'Bunch of text'),
		array('Title' => 'Porquette', 'Content' => 'Bleeding edge text')
		);

	$dbTest = $g->DB->table('goo_test');
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

	$g->Template->render('items', $items);
}


// ****** Closing
$g->_dbg($g->toString());
$g->_dbg('generated in ' . $g->lifeTime(3) . ' sec with Goo');

$g->Template->render('footer');
?>