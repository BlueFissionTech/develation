<?php 

namespace BlueFission\HTML;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Data\File;
use BlueFission\Behavioral\Configurable;

class XML extends Configurable
{
	private $_filename;
	private $_parser;
	protected $_data;
	protected $_status;

	public function __construct($file = null) 
	{
		parent::__construct();
		$this->_parser = \xml_parser_create();
		\xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, true);
		\xml_set_object($this->_parser, $this);
		\xml_set_element_handler($this->_parser, array($this, 'startHandler'), array($this, 'endHandler'));
		\xml_set_character_data_handler($this->_parser, array($this, 'dataHandler'));
		if (DevValue::isNotNull($file)) {
			$this->file($file);
			$this->parseXML($file);
		}
	}

	public function file($file = null) 
	{
		if (DevValue::isNull($file))
			return $this->_filename;		
		
		$this->_filename = $file;
	}

	public function parseXML($file = null) 
	{
		if (DevValue::isNull($file)) {
			$file = $this->file();
		}

		$status = 'Failed to open xml path';
		// if ($stream = dev_stream_file($file, $status)) {
		if ( $stream = @fopen($file, 'r' )) {
			while ($data = fread($stream, 4096)) {
				if (!xml_parse($this->_parser, $data, feof($stream))) {
					$this->status(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($this->_parser)), xml_get_current_line_number($this->_parser)));
					return false;
				}
			}
		} else {
			$this->status($status);
			return false;
		}
		return true;
	}

	public function startHandler($parser, $name = null, $attributes = null) {
		$data['name'] = $name;
		if ($attributes) $data['attributes'] = $attributes;
		$this->_data[] = $data;
	}

	public function dataHandler($parser, $data = null) {
		if ($data = trim($data)) {
			$index = count($this->_data)-1;
			if (!isset($this->_data[$index]['content'])) $this->_data[$index]['content'] = "";
			$this->_data[$index]['content'] .= $data;
		}
	}
	 
	public function endHandler($parser, $name = null) {
		if (count($this->_data) > 1) {
			$data = array_pop($this->_data);
			$index = count($this->_data)-1;
			$this->_data[$index]['child'][] = $data;
		}
	}

	public function buildXML($data = null, $indent = 0) {
		$xml = '';
		$tabs = "";
		for ($i=0; $i<$indent; $i++) $tabs .= "\t";
		//if (!is_array($data)) $data = DevArray::toArray($data);
		if (is_array($data)) {
			foreach($data as $b=>$a) {
				if (!DevArray::isAssoc($a)) {
					$xml .= $this->buildXML($a, $indent);
				} else {
					$attribs = '';
					if (DevArray::isAssoc($a['attributes'])) foreach($a['attributes'] as $c=>$d) $attribs .= " $c=\"$d\"";
					$xml .= "$tabs<" . $a['name'] . "" . $attribs . ">" . ((count($a['child']) > 0) ? "\n" . $this->buildXML($a['child'], ++$indent) . "\n$tabs" : $a['content']) . "</" . $a['name'] . ">\n";
				}
			}
		}
		return $xml;
	}

	public function status($status = null) 
	{
		if (DevValue::isNull($status))
			return $this->_status;
		$this->_status = $status;
	}

	public function data() 
	{
		return $this->_data;
	}

	public function outputXML($data = null) 
	{
		header("Content-Type: XML");
		$xml = 'No XML';
		if (DevValue::isNull($data == '')) $data = $this->_data;
		$xml = $this->buildXML($data);
		echo $xml;
	}

} //End class DevXML
