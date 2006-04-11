:partial
	<blockquote>
		<h3>Beh, funziona!</h3>
		<p>Se questo testo si legge, senza il blocco successivo, significa che
			il template reader per i parziali funziona.</p>
	</blockquote>

:message (this is a comment, anything after the first space is a comment. one line only)
	<div class="msg">
		Hello, I'm a <strong>message</strong>. I should appear just when called.
	</div>

:items (the Partial mode supports short tags: <$Title> is converted to <?php echo $Title ?>)
	<h2><$Title></h2>
	<p><?php echo $Content; ?> <strong>One more kiss, dear. One more time.</strong> (<?php echo $HowMuch; ?>)</p>