<?php

class Drive {
	const MAX_ATTENTION = 1024*1024;
	const MAX_SENSITIVITY = 10;

	public $attention = 1024;
	public $sensitivity = 0;
	public $quality = .4;
	public $tolerance = 100;
	public $dimensions = [320,200];
	public $filters = ['bf6209'];
	public $chunksize = 6;


	public function invoke( $input ) {
		$size = count($input)*$this->quality;

		$increment = floor( 1 / $this->quality );

		$i = $j = 0;
		$k = 1;
		while ( $i <= $this->attention && $j <= $this->sensitivity && $i < $size ) {
			if ( $i > $size ) {
				$i = 0;
				$j++;
			}
			echo '<div style="background:#'.$input[$i].'"></div>';
			if ( $k == $this->dimensions[0]*$this->quality) {
				echo '<br/>';
				$k = 0;
			}
			if ( !$parent->has(translate($input[$i]))) {
				$this->attention+=$dimensions[0]*$this->quality;
			}

			if ( $input[$i] == $this->filters[$j] ) {
				//perform(filter[j], input[i]); 
				// echo "found<br/>";
			}
			$k++;
			$i+=$increment;
		}
	}

	public function callback( $obj ) {
		// $this->quality += 5;
	}
}

/**
 * Creates function imagecreatefrombmp, since PHP doesn't have one
 * @return resource An image identifier, similar to imagecreatefrompng
 * @param string $filename Path to the BMP image
 * @see imagecreatefrompng
 * @author Glen Solsberry <glens@networldalliance.com>
 */
if (!function_exists("imagecreatefrombmp")) {
	function imagecreatefrombmp( $filename ) {
		$file = fopen( $filename, "rb" );
		$read = fread( $file, 10 );
		while( !feof( $file ) && $read != "" )
		{
			$read .= fread( $file, 1024 );
		}
		$temp = unpack( "H*", $read );
		$hex = $temp[1];
		$header = substr( $hex, 0, 104 );
		$body = str_split( substr( $hex, 108 ), 6 );
		if( substr( $header, 0, 4 ) == "424d" )
		{
			$header = substr( $header, 4 );
			// Remove some stuff?
			$header = substr( $header, 32 );
			// Get the width
			$width = hexdec( substr( $header, 0, 2 ) );
			// Remove some stuff?
			$header = substr( $header, 8 );
			// Get the height
			$height = hexdec( substr( $header, 0, 2 ) );
			unset( $header );
		}
		$x = 0;
		$y = 1;
		$image_r = array();
		// $image = imagecreatetruecolor( $width, $height );
		foreach( $body as $rgb )
		{
			$r = hexdec( substr( $rgb, 4, 2 ) );
			$g = hexdec( substr( $rgb, 2, 2 ) );
			$b = hexdec( substr( $rgb, 0, 2 ) );
			// $color = imagecolorallocate( $image, $r, $g, $b );
			// imagesetpixel( $image, $x, $height-$y, $color );
			$image_r[] = $rgb;
			$x++;
			if( $x >= $width )
			{
				$x = 0;
				$y++;
			}
		}
		// return $image;
		return $image_r;
	}
}

$drive = new Drive();
$drive->invoke(imagecreatefrombmp('tiger.bmp'));
// echo file_get_contents ( 'tiger.bmp' );
// print_r ( imagecreatefrombmp('tiger.bmp'));
echo '<style> div { height: 2px; width: 2px; display:inline-block;}</style>';