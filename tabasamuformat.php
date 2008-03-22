<?php

class TabasamuFormat extends Format
{
	public function tabasamu( $content )
	{
		return Plugins::filter( 'tabasamu_smilies', $content );
	}
}

?>