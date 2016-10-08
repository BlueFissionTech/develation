<?php
namespace BlueFission\HTML;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Behavioral\Configurable;

class Table extends Configurable {
	protected $_content;

	protected $_config = array(
		'columns'=>'',
		'href'=>'',
		'query'=>'',
		'highlight'=>'#efefef',
		'headers'=>array(),
		'link_style'=>'',
		'show_image'=>false,
		'img_dir'=>'images/',
		'document_dir'=>'documents/',
		'icon'=>'',
		'truncate'=>'',
		'fields'=>array(),
	);

	public function __construct( $config = null ) {
		parent::__construct( $config );
	}

	public function content( $content = null ) {
		if (DevValue::isNotNull($content)) {
			$content = DevArray::toArray($content);
			$this->_content = $content;
		}
		return $this->_content;
	}

	//outputs standard html content box
	public function render() {
		$header = $this->config('headers');
		$trunc = $this->config('truncate');
		$file_dir = $this->config('document_dir');
		$cols = $this->config('columns');
		$link_style = $this->config('link_style');
		// $href = $this->config('href');
		// $fields = $this->config('fields');
		$query_r = $this->config('query');
		$content_r = $this->content();

		extract($this->_config);
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
					if (DevArray::isAssoc($header)) $header = array_keys($header);
					$output .= '<tr>';
					$i = 0;
					foreach ($header as $a) {
						if ($i == 0 && $link_style != 1 && $link_style !== 0) {
							$output .= '<th>';
							$output .= '';
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
					$output .= "</tr>\n";
				}
			}
			
			//manage columns
			if ($hori == 0) {
				$output .= '<tr>';
			}
			
			$i = 0;
			
			foreach ($row as $a=>$b) {
				
				if ($i > 0 || ($header === false && $link_style != 1)) {
					if (!($icon != '' && $fields == 2 && $i == 2)) $output .= '<td>';
					if (($i == 1 || ($icon != '' && $i = 2)) && $link_style == 1) $output .= '<a class="contentBox" href="' . $href . '?' . $varname . '=' . $value . ((DevArray::isAssoc($query_r)) ? '&' . http_build_query($query_r) : '') . '">';
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
						if (DevArray::isAssoc($query_r)) foreach ($query_r as $c=>$d) $output .= Form::feld('hidden', $c, '', $d);
						$output .= Form::close();
						$output .= "</td>";
					} elseif ($link_style == 3) {
						$output .= '<td>';
						
						$output .= Form::field('checkbox', $a . '[]', '', $b);
						if (DevArray::isAssoc($query_r)) foreach ($query_r as $c=>$d) $output .= Form::field('hidden', $c, '', $d);
						
						$output .= "</td>";
					} else {
						$varname = $a;
						$value = $b;
					}			
				}
				$i++;
				if ($i > $fields) break;
			}		
			
			$output .= "\n";
			if ($hori < $cols) {
				$hori++;
			} else {
				$output .= "</tr>\n";
				$hori = 0;
			}
			$count++;
		}
		if ($hori != 0) $output .= "</tr>\n";
		$output .= "</table>\n";
		
		return $output;
	}
}