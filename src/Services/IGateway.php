<?php 
namespace BlueFission\Services;

interface IGateway {

	public function process( Request $request, &$arguments );
}