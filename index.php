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
if (!$g->Pager->makeHTAccess()) $g->_dbg('Unable to create the .htaccess file. Check dir rights.');

function feed($purl, $matchindex)
{
	echo "<h3>don't feed the doc. use plutonium. back to the future.</h3>";
	echo "<p>Parsed URL chunk 0: <strong>$purl[0]</strong>, Chunks matching: <strong>$matchindex</strong></p>";
}
$g->Pager->setBind('feed', 'feed');

$g->Pager->exec();

$g->_dbg($g->Pager->toString());

// ****** Closing
$g->_dbg($g->toString());
$g->_dbg('generated in ' . $g->lifeTime(3) . ' sec with Goo');

$g->Template->render('footer');
?>