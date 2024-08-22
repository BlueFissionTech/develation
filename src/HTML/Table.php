<?php
namespace BlueFission\HTML;

use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\Behavioral\Configurable;

/**
 * Class Table
 *
 * This class extends the Configurable class and outputs a standard HTML content box
 *
 * @package BlueFission\HTML
 */
class Table extends Obj {
	use Configurable {
		Configurable::__construct as private __configConstruct;
	}


	/**
	 * @var array $content Stores the content of the table
	 */
	protected $content;

	/**
	 * @var array $config The default configuration options for the table
	 */
	protected $config = [
		'columns'=>'',
		'href'=>'',
		'query'=>'',
		'highlight'=>'#efefef',
		'headers'=>[],
		'link_style'=>'',
		'show_image'=>false,
		'img_dir'=>'images/',
		'document_dir'=>'documents/',
		'icon'=>'',
		'truncate'=>'',
		'fields'=>[],
	];

	/**
	 * Table constructor
	 *
	 * @param null $config Configuration options for the table
	 */
	public function __construct( $config = null ) {
		$this->__configConstruct( $config );
	}

	/**
	 * Get or set the content of the table
	 *
	 * @param null $content The new content of the table
	 *
	 * @return mixed The content of the table
	 */
	public function content( $content = null ) {
		if (Val::isNotNull($content)) {
			$content = Arr::toArray($content);
			$this->content = $content;
		}
		return $this->content;
	}

	/**
	 * Render the HTML table
	 *
	 * @return string The HTML content of the table
	 */
	public function render() {
		$header = $this->config('headers');
		$trunc = $this->config('truncate');
		$file_dir = $this->config('document_dir');
		$cols = (int)($this->config('columns') ?? Arr::size($this->config('headers'))) - 1;
		$link_style = $this->config('link_style');
		// $href = $this->config('href');
		// $fields = $this->config('fields');
		$query_r = $this->config('query');
		$content_r = $this->content();

		extract($this->config);
		$hori = 0;
		$output = '<table class="dev_table"' . (($header === false) ? '' : ' id="anyid"') . '>';
		
		$bg = false;
		$href = HTML::href($href);
		$count = 0;
		$new_row = 1;

		foreach ($content_r as $row) {
			$fields = ($fields != '' && $fields >= 0 && $fields < count( $row )) ? $fields : count( $row );

			if ($count == 0) {
				if ($header !== false) {
					$header = (is_array($header) && count($header) == count($row)) ? $header : $row;
					if (Arr::isAssoc($header)) $header = array_keys($header);
					$output .= '<tr>';
					$i = 0;
					foreach ($header as $a) {
						if ($i == 0 && $link_style !== 1 && $link_style !== 0) {
							$output .= '<th>';
							$output .= $a;
							$output .= "</th>";
						}
						if ($i > 0) {
							$output .= '<th>';
							$output .= $a;
							$output .= "</th>";
						}
						$i++;
						if ($i > $fields) break;
					}
					$output .= "</tr>";
				}
			}
			
			//manage columns
			if ($hori == 0) {
				$output .= '<tr>';
			}
			
			$i = 0;
			
			foreach ($row as $a=>$b) {
				if ($i > 0 || ($link_style !== 1 && $link_style !== 0)) {
					if (!($icon != '' && $fields == 2 && $i == 2)) $output .= '<td>';
					if (($i == 1 || ($icon != '' && $i = 2)) && $link_style == 1) $output .= '<a class="contentBox" href="' . $href . '?' . $varname . '=' . $value . ((Arr::isAssoc($query_r)) ? ('&' . http_build_query($query_r)) : '') . '">';
					if (!$show_image || $trunc != '') $data = HTML::format($b, '', $trunc);
					else $data = $b;
					if ($i != 1) $data = HTML::file($data, $file_dir);
					if ($icon != '' && $i == 1) $data = HTML::image((($data == '')?$icon:$data), $img_dir, '', '50', '', '', '', '', '', $icon) . (($fields == 1) ? "<br />".$data:'');
					elseif ($show_image) $data = HTML::image($data, $img_dir, $data, '100', '', true, '', false);
					$output .= $data;
					if ($i == 1 && $link_style == 1) $output .= "</a>";
					if (!($icon != '' && $fields == 2 && $i == 1)) $output .= "</td>";
				} elseif ($i == 0) {
					if ($link_style == 2) {
						$output .= '<td>';
						$output .= Form::open($href) . Form::field('hidden', $a, '', $b) . Form::field('submit', 'submit', '', 'Go'); 
						if (Arr::isAssoc($query_r)) foreach ($query_r as $c=>$d) $output .= Form::feld('hidden', $c, '', $d);
						$output .= Form::close();
						$output .= "</td>";
					} elseif ($link_style == 3) {
						$output .= '<td>';
						
						$output .= Form::field('checkbox', $a . '[]', '', $b);
						if (Arr::isAssoc($query_r)) foreach ($query_r as $c=>$d) $output .= Form::field('hidden', $c, '', $d);
						
						$output .= "</td>";
					} else {
						$varname = $a;
						$value = $b;
					}			
				}
				$i++;
				if ($i > $fields) {
					break;
				}
			}

			$hori = $i;

			if ($hori < $cols) {
				$hori++;
			} else {
				$output .= "</tr>";
				$hori = 0;
			}
			$count++;
		}
		if ($hori != 0) $output .= "</tr>";
		$output .= "</table>";
		
		return $output;
	}
}