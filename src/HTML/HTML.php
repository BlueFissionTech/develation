<?php
namespace BlueFission\HTML;

use BlueFission\DevValue;
use BlueFission\DevString;
use BlueFission\Utils\Util;

class HTML {

	const PAGE_EXTENSION = '.php';

	static function href($href = null, $secure = false, $doc = true) 
	{
		if (DevValue::isNotNull($href)) {

			$href = '';
			if ($doc === false) {
				$href .= $_SERVER['DOCUMENT_ROOT'];
			} else {
				$protocol = $secure ? 'https' : 'http';
				$href = $protocol.'://' . $_SERVER['SERVER_NAME'];
				$href .= $_SERVER['REQUEST_URI'];
				if (DevString::strrpos($href, self::PAGE_EXTENSION)) $href = substr($href, 0, DevString::strrpos($href, self::PAGE_EXTENSION) + strlen(PAGE_EXTENSION));
				elseif (DevString::strrpos($href, '/')) $href = substr($href, 0, DevString::strrpos($href, '/') + strlen('/'));
			}
		}
		
		return $href;
	}

	static function format($content = null, $rich = false) {
		$search = array('/^==========/mx',
			'/^\b([\W\w\D\d\s]|[^\n]+)\n----------/mx',
			'/^\b([\W\w\D\d\s]|[^\n]+)\n-------/mx',
			'/^\~ ([\W\w\D\d^\n][^\n]+)\n/mx',
			'/(^<li>.*<\/li>)+/mx',
			'/<\/ol>([\s]*)<ol>/mx',

			'/^\~ ([\W\w\D\d^\n][^\n]+)\n(<ol>[.*?]<\/ol>){0,1}/mx',
			'/(^<li>.*<\/li>)+/mx',
			'/<\/ul>([\s]*)<ul>/mx',

			'/^\* ([\W\w\D\d^\n][^\n]+)\n(<ul>[.*?]<\/ul>){0,1}/mx',
			'/(^<li>.*<\/li>)+/mx',
			'/<\/ul>([\s]*)<ul>/mx',
			
			'/^\- ([\W\w\D\d^\n]|[^\n]+)\n/mx',
			'/\[b\](.*?)\[\/b\]/mxi',
			'/\[i\](.*?)\[\/i\]/mxi',
			'/\[u\](.*?)\[\/u\]/mxi',
			"/^([\s])$/m"
		);
		$replace = array('<hr class="hr">' . "\n",
			'<h2 class="h2">$1</h2>',
			'<h3 class="h3">$1</h3>',
			'<li>$1</li>' . "\n",
			'<ol>' . "\n" . '$1' . "\n" . '</ol>' . "\n",
			'',

			'<li>$1</li>' . "\n",
			'<ul>' . "\n" . '$1' . "\n" . '</ul>' . "\n",
			'',

			'<li>$1</li>' . "\n",
			'<ul>' . "\n" . '$1' . "\n" . '</ul>' . "\n",
			'',
			'<li>$1</li>' . "\n",
			'<b>$1</b>',
			'<i>$1</i>',
			'<u>$1</u>',
			'<br />'
		);
		
		$output = preg_replace($search, $replace, $content);

		if ( $rich ) {
			$pattern = array( '/\'/', '/^([\w\W\d\D\s]+)$/', '/(\d{4})\-(\d+)\-(\d+)/', '/(\d)/', '/\$/', '/(http\:\/\/[^\s]+)/', '/([\w\W\d\D\S]+@[\w\W\d\D\S]+.[\w\S]+)/');
			$replacement = array( '&#39;', '$1', '$2/$3/$1', '$1', '&#36;', '<a href="$1" target="_blank">$1</a>', '<a href="MAILTO:$1">$1</a>');
			$output = preg_replace($pattern, $replacement, stripslashes($output));
		}
		
		return $output;
	}
	
	//prints an image
	static function image($image, $dir = '', $alt = '', $width = '100', $height = '', $thumb = false, $align = '', $link = true, $defaults = 0, $blank = false) {
		$imgdir = Util::value('imgdir');
		$output = '';
		$pattern = "/\.gif$|\.jpeg$|\.tif$|\.jpg$|\.tiff$|\.png$|\.bmp$/i";
		if (preg_match($pattern, $image) || $blank !== false) {
			$height_line = ($height == '') ? '' : " height=" . $height;
			if ($defaults) {
				$width = '100'; 
			}
			if ($dir == '' && $imgdir != '') $dir = $imgdir;
			$image = $dir . $image;
			if ($align != '') $align =  'align="' . $align . '"';
			if ($image != '' && file_exists(getcwd() . '/' . $image)) {
				if ($link) $output .= '<a href="' . $image . '" target="_blank">';
				if ($thumb) $output .= '<img src="thumb.php?f=' . $image . '&d=' . $dir . '" border="0" alt="' . $alt . '" title="' . $alt . '" width="' . $width . $height_line . '" ' . $align . ' />';
				else $output .= '<img src="' . $image . '" border="0" alt="' . $alt . '" title="' . $alt . '" width="' . $width . $height_line . '" ' . $align . ' />';
				if ($link) $output .= '</a>';
			} elseif ($blank != '' && file_exists(getcwd() . '/' . $blank)) {
				$output .= '<img src="' . $blank . '" border="0" alt="' . $alt . '" title="' . $alt . '" width="' . $width . $height_line . '" ' . $align . ' />';
			} elseif ($image != '' ){
				$output .= '<img src="' . $image . '" border="0" alt="' . $alt . '" title="' . $alt . '" width="' . $width . $height_line . '" ' . $align . ' />';
			}
			else
				$output .= '<img src="noimage.png" border="0" alt="' . $alt . '" title="' . $alt . '" width="' . $width . $height_line . '" align="left" />';
			return $output;
		} else {
			return $image;
		}
	}

	//prints an image
	static function file($file, $dir, $virtual = false) {
		$href = HTML::href();
		$output = '';
		$pattern = "/\.pdf$|\.doc$|\.zip$|\.mp3$|\.mpeg$|\.mov$|\.rar$|\.txt$/i";
		if (preg_match($pattern, $file)) {
			$file = $dir . $file;
			if ($file != '' && file_exists($file))
				if ($virtual) $output .= '<a href="file.php?f=' . ((strpos($file, getcwd())) ? str_replace(getcwd(), '', $file) : $file). '" target="_blank">' . basename($file) . '</a>';
				else $output .= '<a href="' . ((strpos($file, getcwd())) ? str_replace(getcwd(), $href, $file) : $file). '" target="_blank">' . basename($file) . '</a>';
			else
				$output .= 'file "' . basename($file) . '" not found';
			return $output;
		} else {
			return $file;
		}
	}

	// static function formatAscii($text = '') {
	// 	$search = array('/^==========/mx',
	// 		'/^\b([\W\w\D\d\s]|[^\n]+)\n----------/mx',
	// 		'/^\b([\W\w\D\d\s]|[^\n]+)\n-------/mx',
	// 		'/^\~ ([\W\w\D\d^\n][^\n]+)\n/mx',
	// 		'/(^<li>.*<\/li>)+/mx',
	// 		'/<\/ol>([\s]*)<ol>/mx',

	// 		'/^\~ ([\W\w\D\d^\n][^\n]+)\n(<ol>[.*?]<\/ol>){0,1}/mx',
	// 		'/(^<li>.*<\/li>)+/mx',
	// 		'/<\/ul>([\s]*)<ul>/mx',

	// 		'/^\* ([\W\w\D\d^\n][^\n]+)\n(<ul>[.*?]<\/ul>){0,1}/mx',
	// 		'/(^<li>.*<\/li>)+/mx',
	// 		'/<\/ul>([\s]*)<ul>/mx',
			
	// 		'/^\- ([\W\w\D\d^\n]|[^\n]+)\n/mx',
	// 		'/\[b\](.*?)\[\/b\]/mxi',
	// 		'/\[i\](.*?)\[\/i\]/mxi',
	// 		'/\[u\](.*?)\[\/u\]/mxi',
	// 		"/^([\s])$/m"
	// 	);
	// 	$replace = array('<hr class="hr">' . "\n",
	// 		'<h2 class="h2">$1</h2>',
	// 		'<h3 class="h3">$1</h3>',
	// 		'<li>$1</li>' . "\n",
	// 		'<ol>' . "\n" . '$1' . "\n" . '</ol>' . "\n",
	// 		'',

	// 		'<li>$1</li>' . "\n",
	// 		'<ul>' . "\n" . '$1' . "\n" . '</ul>' . "\n",
	// 		'',

	// 		'<li>$1</li>' . "\n",
	// 		'<ul>' . "\n" . '$1' . "\n" . '</ul>' . "\n",
	// 		'',
	// 		'<li>$1</li>' . "\n",
	// 		'<b>$1</b>',
	// 		'<i>$1</i>',
	// 		'<u>$1</u>',
	// 		'<br />'
	// 	);
		
	// 	$output = preg_replace($search, $replace, $text);
		
	// 	return $output;
	// }

	// static function format($data = '', $convert = false, $trunc = '') {
	// 	$output = '';
		
	// 	$pattern = array( '/\'/', '/^([\w\W\d\D\s]+)$/', '/(\d{4})\-(\d+)\-(\d+)/', '/(\d)/', '/\$/', '/(http\:\/\/[\w\W\d\D\S]+)/', '/([\w\W\d\D\S]+@[\w\W\d\D\S]+.[\w\S]+)/');
	// 	$replacement = array( '&#39;', '$1', '$2/$3/$1', '$1', '&#36;', '<a href="$1" target="_blank">$1</a>', '<a href="MAILTO:$1">$1</a>');
	// 	$string = preg_replace($pattern, $replacement, stripslashes($data));
		
	// 	if ($convert) $string = dev_ascii_to_html($string);
	// 	//else $string = htmlentities($string);
	// 	if ($trunc != '' && $trunc > 0) $string = dev_trunc_str(strip_tags($string), (int)$trunc);
	// 	elseif (!$convert) $string = nl2br($string);
		
	// 	return $string;
	// }

	static function paginate($list_r, $begin = 'start', $end = 'lim', $href = '', $limit = 20) {
		$output = '';
		$chapter_r = array();
		$count = is_numeric($list_r) ? $list_r : count($list_r);
		$list_r = (is_array($list_r) && ($count) <= 0) ? array() : $list_r;
		$href = HTML::href($href);
			
		$start = (isset($_GET[$begin]) && is_numeric($_GET[$begin])) ? $_GET[$begin] : 0;
		$lim = (isset($_GET[$end]) && is_numeric($_GET[$end])) ? $_GET[$end] : $limit;
		$query_r = array_merge($_POST, $_GET);
		unset($query_r[$begin]);
		unset($query_r[$end]);
		$get_query = http_build_query($query_r);

		if ( $start > 0 )
		{
			$chapter_r[] = '&lt; <a href="' . $href . '?' . $begin . '=' . ((($start) >= $lim) ? ($start - $lim) : 0) . '&amp;' . $get_query . '">Previous</a> ';
		}
		if ( ($count/$lim) > 1 )
		{
			for ($i=0; $i<(($count/$lim)); $i++) $chapter_r[] = '<a href="' . $href . '?' . $begin . '=' . ($i * $lim) . '&amp;' . $get_query . '">' . ($i + 1) . '</a>';
		}
		if ( $start < round($count/$lim) )
		{
			$chapter_r[] = '<a href="' . $href . '?' . $begin . '=' . (($start + $lim) >= ($count) ? $start : ($start + $lim)) . '&amp;' . $get_query . '">Next</a> &gt;';
		}
		
		$output .= (($count > 0) ? 'Showing ' . ($start + 1) . '-' . (($count < ($start+$lim)) ? $count : ($start+$lim)) . ' of ' . $count . ' results.' : 'No matching results') . '<br />
		' . implode(' | ', $chapter_r);
		
		$output .= "<br />\n";
		return $output;
	}

	static function results($list_r, $begin = 'start', $end = 'lim', $href = '', $chapters = true, $link_style = 1, $query_r = '', $highlight = '#c0c0ff', $img_dir = 'images/', $file_dir = 'assets/', $headers = '', $trunc = '', $limit = 20) {
		$start = (isset($_GET[$begin]) && is_numeric($_GET[$begin])) ? $_GET[$begin] : 0;
		$end = (isset($_GET[$end]) && is_numeric($_GET[$end])) ? $_GET[$end] : $limit;
		$list_r = (count($list_r) <= 0) ? array() : $list_r;
		$href = HTML::href($href);
		
		if ($chapters) {
			$chapter_list = dev_list_chapter($list_r, $begin, $end, $href);
		}
		
		$output .= $chapter_list;
		
		$list_r = array_splice($list_r, $start, $end);
		
		//for ($i = $start; $i < (((count($list_r) - $start) > $end) ? ($start + $end) : count($list_r)); $i++) 
		// $output .= dev_content_box($list_r, '', $href, $query_r, $highlight, $headers, $link_style, true, $img_dir, $file_dir, '', $trunc);
		$table = new Table();
		$table->content($list_r);
		$output .= $table->render();
		
		$output .= $chapter_list;
		
		return $output;
	}

	static function baseHref($href = '') {
		$href = HTML::href($href, false);
		$output = '<base href="' . $href . '">';
		$output .= "\n";
		return $output;
	}

	static function barGraph($data, $height = '', $width = '', $max = '', $is_percent = '') {
		$output = '';
		if (DevArray::isAssoc($data)) {
			if ($max == '') $max = DevArray::max($data);
			$output .= '<table width = "' . (($width > 0) ? $width : 200) . '" height = "' . (($height > 0) ? $height : 100) . '">';
			$output .= "\n";
			foreach ($data as $a=>$b) {
				$output .= '<tr>';
				$output .= "<td>$a</td>" . (($hori) ? '<tr>' : '<td>');
				$output .= '<td width="80%" class="dev_bar"><table width="100%" cellpadding="0" cellspacing="0" border="1" bgcolor="#ffffff"><tr><td width="' . DevNumber::ratio($b, $max, false) . '%" height="5" bgcolor="#c0c0c0"></td><td></td></tr></table></td>';
				$output .= "<td>" . (($is_percent) ? DevNumber::ratio($b, $max, $is_percent) : $b) . "</td>";
				$output .= "</tr>";
			}	
			$output .= "\n";
			$output .= '</table>';
		}
		
		return $output;
	}
	static function nl2li($str) {
		$output = '';
		$str_r = explode("\n", $str);
		foreach ($str_r as $a) if ($a != '' && $a != ' ') $output .= "<li>$a</li>\n";
		return $output;
	}

	static function br2nl($str){ 
		if (version_compare(PHP_VERSION, '5.0.0', '<')) 
		{
			$str = strtolower($str);
			$str = str_replace('<br>', "\n", $str);  
			$str = str_replace('<br />', "\n", $str); 
			$str = str_replace('<br/>',"\n", $str);
		}
		else
		{
			$str = str_ireplace('<br>', "\n", $str);  
			$str = str_ireplace('<br />', "\n", $str); 
			$str = str_ireplace('<br/>',"\n", $str);
		} 
		return $str;
	} 

	static function darkerColor($hex)
	{
		$color = preg_replace("/[^A-Za-z0-9 ]/", '', $hex);
		$color2 = '';
		foreach (str_split($color) as $a)
		{
			$num = hexdec($a);
			if ( $num > 1 )
			{
				$num = $num-2;
			}
			$color2 .= dechex($num); 
		}
		
		return $color2;
	}	
}