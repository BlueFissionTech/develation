<?php 

namespace BlueFission\HTML;

use BlueFission\Val;
use BlueFission\Arr;
use BlueFission\Obj;
use BlueFission\IObj;
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
class XML extends Obj {
	use Configurable;

	private $_filename;
	private $_parser;
	protected $_data;
	protected $_status;
	protected $_config = [];

	const STATUS_SUCCESS = 'Success';
	const STATUS_FAILED = 'Failed';
	const STATUS_ERROR = 'Error';
	const STATUS_OPEN_FAILED = 'Failed to open xml path';

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
		$this->bootstrapConfig();
		$this->_parser = \xml_parser_create();
		\xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, true);
		\xml_set_object($this->_parser, $this);
		\xml_set_element_handler($this->_parser, array($this, 'startHandler'), array($this, 'endHandler'));
		\xml_set_character_data_handler($this->_parser, array($this, 'dataHandler'));
		if (Val::isNotNull($file)) {
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
		if (Val::isNull($file))
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
	 * @return IObj
	 */
	public function parseXML($file = null): IObj
	{
		if ( Val::isNull($file) ) {
			$file = $this->file();
		}

		$status = self::STATUS_OPEN_FAILED;

		if ( $stream = @fopen($file, 'r') ) {
			while ( $data = fread($stream, 4096) ) {
				if ( !xml_parse($this->_parser, $data, feof($stream)) ) {
					$this->status(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($this->_parser)), xml_get_current_line_number($this->_parser)));
					
					return $this;
				}
			}

			fclose($stream);
		} else {
			$this->status($status);
			
			return $this;
		}

		$this->status(self::STATUS_SUCCESS);

		return $this;
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
		$data = ['name' => $name];
		if ($attributes) {
			$data['attributes'] = $attributes;
		}
		$this->_data->push($data);
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
			$index = $this->_data->count() - 1;
			$item = $this->_data[$index] ?? [];
			$item['content'] = ($item['content'] ?? '') . $data;
			$this->_data[$index] = $item;
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
		if ($this->_data->count() > 1) {
			$data = $this->_data->pop();
			$index = $this->_data->count() - 1;
			$item = $this->_data[$index] ?? [];
			$children = $item['child'] ?? [];
			$children[] = $data;
			$item['child'] = $children;
			$this->_data[$index] = $item;
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
		//if (!is_array($data)) $data = Arr::toArray($data);
		if (is_array($data)) {
			foreach($data as $b=>$a) {
				if (!Arr::isAssoc($a)) {
					$xml .= $this->buildXML($a, $indent);
				} else {
					$attribs = '';
					if (Arr::isAssoc($a['attributes'])) foreach($a['attributes'] as $c=>$d) $attribs .= " $c=\"$d\"";
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
		if (Val::isNull($status))
			return $this->_status;
		$this->_status = $status;
	}

	/**
	 * Gets the data
	 *
	 * @return mixed
	 */
	public function data(): mixed
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
		if (Val::isNull($data == '')) $data = $this->_data;
		$xml = $this->buildXML($data);
		echo $xml;
	}


} //End class DevXML
