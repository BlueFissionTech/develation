<?php
namespace BlueFission\Data\Storage;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\IObj;
use BlueFission\Net\HTTP;
use BlueFission\Data\IData;
use BlueFission\Data\FileSystem;
use BlueFission\Behavioral\Behaviors\Event;
use BlueFission\Behavioral\Behaviors\Action;
use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\DevElation as Dev;

class Disk extends Storage implements IData
{
	/**
	 * Holds the configuration data for the disk storage object.
	 * 
	 * @var array 
	 */
	protected $_config = [ 
		'location'=>null, 
		'name'=>null 
	];

	/** Whether the cached contents came from a JSON object read. */
	protected bool $_contentsFromRead = false;

	/** A FileSystem save event, not the fluent write return, confirms a write. */
	private ?object $_observedWriteSource = null;
	private bool $_sourceWriteConfirmed = false;
	private ?object $_observedReadSource = null;
	private bool $_sourceReadConfirmed = false;
		
	/**
	 * Constructor method for the disk storage object.
	 * 
	 * @param array|null $config 
	 */
	public function __construct( $config = null ) {
		parent::__construct( $config );
	}

	public function contents($data = null): mixed
	{
		if (!Val::isNull($data)) {
			$this->_contentsFromRead = false;
		}

		return parent::contents($data);
	}
	
	/**
	 * Activates the disk storage object.
	 * 
	 * @return IObj
	 */
	public function activate( ): IObj
	{
		$path = Str::make((string)($this->config('location') ?? sys_get_temp_dir()));
		$name = Str::make((string)($this->config('name') ?? ''));

		if ($name->isEmpty())	{
			$name->val(basename(tempnam($path->val(), 'store_')));
		}

		$filesystem = new FileSystem( [
			'mode'=>'c+',
			'filter'=>'file',
			'root'=>$path->val()
		] );

		$filesystem->filename = $name->val();
		$filesystem
		->when(Event::CONNECTED, (function ($b, $m) use ($filesystem) {
			$this->_source = $filesystem;

			$this->status( self::STATUS_SUCCESSFUL_INIT );
		})->bindTo($this, $this))
		->when(Event::FAILURE, (function ($b, $m) {
			if ( $m->when == Action::CONNECT ) {
				$this->status( self::STATUS_FAILED_INIT );
				return;
			}
			error_log('Failed: ' . $m->info);
		})->bindTo($this, $this))
		->when(Event::ERROR, (function ($b, $m) {
			if ( $m->when == Action::CONNECT ) {
				$this->status( self::STATUS_FAILED_INIT );
				return;
			}
			error_log('Error: ' . $m->info);
		})->bindTo($this, $this))
		->open();

		return parent::activate();
	}
	
	/**
	 * Writes data to disk storage object.
	 * 
	 * @return void
	 */
	protected function _write(): void
	{
		$source = $this->_source;
		$status = self::STATUS_FAILED;
		if (!$source) {
			$this->status( $status );
			return;
		}

		$unsupportedExplicitContents = !$this->_contentsFromRead
			&& (is_array($this->_contents)
				|| (is_object($this->_contents) && !($this->_contents instanceof \Stringable)));
		if ($this->_contentsFromRead) {
			$data = HTTP::jsonEncode((object)$this->_data->val());
		} elseif ($this->_contents === '' || !Val::isEmpty($this->_contents)) {
			// An explicitly supplied empty string replaces the file with empty bytes.
			$data = $this->_contents;
		} else {
			$data = HTTP::jsonEncode($this->_data->val());
		}

		$data = Dev::apply(null, $data);
		if ($unsupportedExplicitContents || is_array($data)
			|| (is_object($data) && !($data instanceof \Stringable))) {
			$this->status(self::STATUS_FAILED);
			$this->perform(
				[Event::ACTION_FAILED, Event::FAILURE],
				new Meta(when: Action::SAVE, info: 'Disk cannot write array or non-stringable object contents; use fields for JSON objects.')
			);
			return;
		}

		if ($this->_observedWriteSource !== $source) {
			$source->when(Event::SAVED, function (): void {
				$this->_sourceWriteConfirmed = true;
			});
			$this->_observedWriteSource = $source;
		}

		$this->_sourceWriteConfirmed = false;
		$source->flush()->contents($data)->write();
		if (!$this->_sourceWriteConfirmed) {
			$this->status(self::STATUS_FAILED);
			$this->perform(
				[Event::ACTION_FAILED, Event::FAILURE],
				new Meta(when: Action::SAVE, info: 'Disk source did not confirm the write.')
			);
			return;
		}

		$this->status(self::STATUS_SUCCESS);
	}
	
	/**
	 * Reads data from disk storage object.
	 * 
	 * @return void 
	 */
	protected function _read(): void
	{	
		$source = $this->_source;
		if (!$source) {
			$status = self::STATUS_FAILED;
			$this->status( $status );

			return;
		}
		if ($this->_observedReadSource !== $source) {
			$source->when(Event::READ, function (): void {
				$this->_sourceReadConfirmed = true;
			});
			$this->_observedReadSource = $source;
		}

		$this->_sourceReadConfirmed = false;
		$source->read();
		if (!$this->_sourceReadConfirmed) {
			$this->status(self::STATUS_FAILED);
			$this->perform(
				[Event::ACTION_FAILED, Event::FAILURE],
				new Meta(when: Action::READ, info: 'Disk source did not confirm the read.')
			);
			return;
		}

		$value = $source->contents();

		$value = Dev::apply(null, $value);

		$invalidJson = new \stdClass();
		$decoded = HTTP::jsonDecode((string)$value, true, $invalidJson);

		if (is_array($decoded) && str_starts_with(ltrim((string)$value), '{')) {
			$this->contents($decoded);
			if ($decoded) {
				$this->assign(Arr::make($decoded)->val());
			}
			$this->_contentsFromRead = true;
		} else {
			$this->contents($value);
		}
		$this->status(self::STATUS_SUCCESS);
	}
	
	/**
	 * Delete the stored data in the underlying source
	 *
	 * @return void
	 */
	protected function _delete(): void
	{
		$source = $this->_source;
		if (!$source) {
			$status = self::STATUS_FAILED;
			$this->status( $status );
			return;
		}

		$source->delete();
	}

	private function _disconnect() {
        if (Val::is($this->_source)) {
            $this->_source->close();
        }
    }

	/**
	 * Close the connection to the underlying source and call parent destructor
	 *
	 * @return void
	 */
	public function __destruct() {
		if (Val::is($this->_source)) {
			$this->_source->close();
		}
		parent::__destruct();
	}

}
