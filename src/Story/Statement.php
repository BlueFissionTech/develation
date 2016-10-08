<?php

namespace BlueFission;

class Statement extends DevObject {
	protected $_data = array(
		'type'=>'statement',
		'context'=>'',
		'priority'=>0,
		'subject'=>'',
		'modality'=>'',
		'behavior'=>'',
		'condition'=>'',
		'object'=>'',
		'relationship'=>'',
		'indirect_object'=>'',
		'position'=>''
	);

	protected $_modalities = array(
	// MODALITIES OF SPEECH
		'NEEDS',
		'MIGHT',
		'COULD',
		'WOULD',
		'SHOULD',
		'MUST',
	);

	protected $_verbs = array(
	// PROTO-VERBS
		''=>'LIKE',
		''=>'DOES',
		''=>'WILL',
		''=>'HANDLES',
		''=>'COMMITS',
		''=>'QUERIES',
		''=>'INTENDS',
	);

	protected $_relationships = array(
	// PREPOSITIONAL RELATION
		''=>'ON',
		''=>'IN',
		''=>'TO',
		''=>'FROM',
		''=>'WITH',
	);

	public function __construct() {
		$this->subject = DevArray();
		$this->object = DevArray();
		$this->indirect_object = DevArray();
	}
}