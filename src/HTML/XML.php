<?php 

namespace BlueFission\HTML;

use BlueFission\DevValue;
use BlueFission\DevArray;
use BlueFission\Data\File;
use BlueFission\Behavioral\Configurable;

/**
 * Class XML
 * 
 * The XML class is used for parsing XML data and building XML structures.
 * It extends the Configurable class to provide configurable behavior to the class.
 * 
 * @package BlueFission\HTML
 * 
 * @property string $_filename The filename of the XML file being parsed.
 * @property resource $_parser The XML parser being used.
 * @property array $_data The data obtained from parsing the XML file.
 * @property string $_status The status of the XML parsing operation.
 * 
 * @method void file($file = null) Gets or sets the filename of the XML file being parsed.
 * @method bool parseXML($file = null) Parses the XML file and returns the result.
 * @method void startHandler($parser, $name = null, $attributes = null) The handler function that is called when an XML start tag is encountered.
 * @method void dataHandler($parser, $data = null) The handler function that is called when XML data is encountered.
 * @method void endHandler($parser, $name = null) The handler function that is called when an XML end tag is encountered.
 * @method string buildXML($data = null, $indent = 0) Builds an XML structure from the data obtained from parsing the XML file.
 */
class XML extends Configurable
{
	private $_filename;
	private $_parser;
	protected $_data;
	protected $_status;

	/**
	 * The XML class constructor.
	 * 
	 * @param string|null $file The XML file to parse.
	 * 
	 * @return void
	 */
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

	/**
	 * Gets or sets the filename of the XML file being parsed.
	 * 
	 * @param string|null $file The filename of the XML file being parsed.
	 * 
	 * @return string|void
	 */
	public function file($file = null) 
	{
		if (DevValue::isNull($file))
			return $this->_filename;		
		
		$this->_filename = $file;
	}
	
	/**
	 * parseXML
	 *
	 * Parse an XML file and saves it to a data array.
	 *
	 * @param string|null $file Path to the XML file
	 *
	 * @return bool Returns true on success, false otherwise
	 */
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

	/**
	 * startHandler
	 *
	 * Handle the start of an XML element.
	 *
	 * @param resource $parser The XML parser resource
	 * @param string $name The name of the element
	 * @param array $attributes An array of element attributes
	 *
	 * @return void
	 */
	public function startHandler($parser, $name = null, $attributes = null) {
		$data['name'] = $name;
		if ($attributes) $data['attributes'] = $attributes;
		$this->_data[] = $data;
	}

	/**
	 * dataHandler
	 *
	 * Handle data within an XML element.
	 *
	 * @param resource $parser The XML parser resource
	 * @param string $data The data within the element
	 *
	 * @return void
	 */
	public function dataHandler($parser, $data = null) {
		if ($data = trim($data)) {
			$index = count($this->_data)-1;
			if (!isset($this->_data[$index]['content'])) $this->_data[$index]['content'] = "";
			$this->_data[$index]['content'] .= $data;
		}
	}

	/**
	 * endHandler
	 *
	 * Handle the end of an XML element.
	 *
	 * @param resource $parser The XML parser resource
	 * @param string $name The name of the element
	 *
	 * @return void
	 */
	public function endHandler($parser, $name = null) {
		if (count($this->_data) > 1) {
			$data = array_pop($this->_data);
			$index = count($this->_data)-1;
			$this->_data[$index]['child'][] = $data;
		}
	}

	/**
	 * Builds an XML string from the given data array
	 *
	 * @param array $data
	 * @param integer $indent
	 * @return string
	 */
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

	/**
	 * Gets or sets the current status
	 *
	 * @param mixed $status
	 * @return mixed
	 */
	public function status($status = null) 
	{
		if (DevValue::isNull($status))
			return $this->_status;
		$this->_status = $status;
	}

	/**
	 * Gets the data
	 *
	 * @return mixed
	 */
	public function data() 
	{
		return $this->_data;
	}

	/**
	 * Outputs the XML
	 *
	 * @param array $data
	 */
	public function outputXML($data = null) 
	{
		header("Content-Type: XML");
		$xml = 'No XML';
		if (DevValue::isNull($data == '')) $data = $this->_data;
		$xml = $this->buildXML($data);
		echo $xml;
	}


} //End class DevXML
