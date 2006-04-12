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




// ****** Closing
$g->_dbg($g->toString());
$g->_dbg('generated in ' . $g->lifeTime(3) . ' sec with Goo');

$g->Template->render('footer');
?>