public function createPopUp( $header, $text, $duration, $style )
{
	$(html).append( '<div class = "popupMessage" id = "popupMessage"><div class = "panel panel-default"><div class = "panel-heading panel-heading-popup">'+$header+'</div><div class = "panel-body">'+ $text +'</div></div></div>' );
}
