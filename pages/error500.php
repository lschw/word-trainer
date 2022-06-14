<?php
$_title = "Error 500";
?>
<h2>Error 500</h2>
<?php if (DEBUG): ?>
<pre>
<?php var_dump($_error); ?>
</pre>
<?php endif; ?>
