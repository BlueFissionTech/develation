<?php 

interface IGateway {

	public function process( Request $request, &$arguments );
}