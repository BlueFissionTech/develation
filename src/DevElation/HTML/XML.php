<?php namespace BlueFission\Develation;

use BlueFission;
@include_once('Loader.php');
$loader = BlueFission\Loader::instance();
$loader->load('com.bluefission.develation.functions.common');

class XML 
{
var $_filename;
var $_parser;
var $_data;
var $_status;

function __construct($file = null) 
{
	$this->_parser = xml_parser_create();
	xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, true);
	xml_set_object($this->_parser, $this);
	xml_set_element_handler($this->_parser, 'startHandler', 'endHandler');
	xml_set_character_data_handler($this->_parser, 'dataHandler');
	if (dev_not_null($file)) {
		$this->file($file);
		$this->parseXML($file);
	}
}

function file($file = null) 
{
	if (dev_is_null($file))
		return $this->_filename;		
	
	$this->_filename = $file;
}

function parseXML($file = null) 
{
	if (dev_is_null($file)) {
		$file = $this->file();
	}
	if ($stream = dev_stream_file($file, $status)) {
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

function startHandler($parser, $name = null, $attributes = null) {
	$data['name'] = $name;
	if ($attributes) $data['attributes'] = $attributes;
	$this->_data[] = $data;
}

function dataHandler($parser, $data = null) {
	if ($data = trim($data)) {
		$index = count($this->_data)-1;
		$this->_data[$index]['content'] .= $data;
	}
}
 
function endHandler($parser, $name = null) {
	if (count($this->_data) > 1) {
		$data = array_pop($this->_data);
		$index = count($this->_data)-1;
		$this->_data[$index]['child'][] = $data;
	}
}

function buildXML($data = null, $indent = 0) {
	$xml = '';
	$tabs = "";
	for ($i=0; $i<$indent; $i++) $tabs .= "\t";
	//if (!is_array($data)) $data = dev_value_to_array($data);
	if (is_array($data)) {
		foreach($data as $b=>$a) {
			if (!dev_is_assoc($a)) {
				$xml .= $this->buildXML($a, $indent);
			} else {
				$attribs = '';
				if (dev_is_assoc($a['attributes'])) foreach($a['attributes'] as $c=>$d) $attribs .= " $c=\"$d\"";
				$xml .= "$tabs<" . $a['name'] . "" . $attribs . ">" . ((count($a['child']) > 0) ? "\n" . $this->buildXML($a['child'], ++$indent) . "\n$tabs" : $a['content']) . "</" . $a['name'] . ">\n";
			}
		}
	}
	return $xml;
}

function status($status = null) 
{
	if (dev_is_null($status))
		return $this->_status;
	$this->_status = $status;
}

function data() 
{
	return $this->_data;
}

function outputXML($data = null) 
{
	header("Content-Type: XML");
	$xml = 'No XML';
	if (dev_is_null($data == '')) $data = $this->_data;
	$xml = $this->buildXML($data);
	echo $xml;
}

} //End class DevXML
?>
