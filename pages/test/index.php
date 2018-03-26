<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/includes/config.php';

$pageShell = new StandardPageShell("Test");
HttpPageShell::requireSessionLogin();

?>

<form style="background-color:lemonchiffon;">
	<label>
		<input type="checkbox"> Only search users newer than <input type="text" size="3"> days
	</label>
	<input type="submit" value="Search">
</form>



<div style="white-space:pre-wrap; font-size:11px;">
* Buttons: Border is 3px in Firefox, 2px in Chrome, and if you change either, it loses its default look. The only way to achieve cross-browser consistency is to define the button's entire style from scratch, using inset/outset border and gradients that respond to :hover:active
* Forms vs Text: With 13px font-size, 21px line-height might be appropriate for blocks of text, but a 21px input, button, and select are too narrow. Therefore the concept of a universal line-height is dubious. It might be best inherited. There are at least 3 contexts that I can see: text, form (contains inputs which affect line-height), structured information (has no input elements; often tabular)
* Start by designing the inputs.
</div>

