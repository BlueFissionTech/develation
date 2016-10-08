<?php

class Interpreter {

	// [message type] [priority] [subject] [modality] [behavior] [conditions] [object] [relationship] [indirect object] [position]
	// [statement|command|query] [priority] [subject property|entity|class] [modality] [behavior] [conditions] [object property|entity|class] [preposition] [indirect object] [active pos] [passive pos]

	// QUERY 100 {Delta} LIKE {spectacles, testicles, wallet, watch} here now

	// ? .Delta LIKE {spectacles,testicles,wallet,watch}
	// & .Milkshakes WILL .Lambda to BEHAVIOR #Happy @[{.HOME},{"2012-12-14"}]

	// When I found out that I was pregnant with Mayths, I wrapped the test in toilet paper and drove it to my husband's floating office to show him

	// & .self HANDLES .self{"pregnant"} WITH .mathys 
	// & .self WILL {"pregnancy test"} IN {"toilet paper"} 
	// & .self DOES .self.car TO .self.husband.office{"floating"} 
	// & .self COMMIT .{"pregnancy test"} TO .self.husband @(EVENT)

	protected static $_terminals = array(
        "/^(\W\D\S+)/" => "T_TYPE_INDICATOR",
        "/^(\d+)/" => "T_PRIORITY",
        "/^(\s+)/" => "T_WHITESPACE",
        "/^(\{)/" => "T_CLASS_OPEN_BRACKET",
        "/^(\\)/" => "T_ESCAPE",
        "/^(\')/" => "T_SINGLE_QUOTE",
        "/^(\")/" => "T_DOUBLE_QUOTE",
        "/^(,)/" => "T_COMMA_SEPARATOR",
        "/^(\w+)/" => "T_PROPERTY_NAME",
        "/^(\w+)/" => "T_PROPERTY",
        "/^(\})/" => "T_CLASS_CLOSE_BRACKET",
        "/^(.)/" => "T_ENTITY",
        "/^(#)/" => "T_HASH",
        "/^(\/[A-Za-z0-9\/:]+[^\s])/" => "T_NAME",
        "/^(->)/" => "T_VERBS",
        "/^(\w+)/" => "T_IDENTIFIER",
        "/^(@)/" => "T_COORDINATE",

    );

	protected static $_grammar = array(
		'type',
		'context',
		'priority',
		'subject',
		'modality',
		'behavior',
		'object'
		'conditions',
		'relation',
		'object',
		'position'
	);

	protected static $_tokens = array(
		// OPERATORS
		''=>'&',
		''=>'?',
		''=>'!',
		''=>'{',
		''=>'}',

		// LOGIC GATES
		''=>'AND',
		''=>'OR',
		''=>'NOT',

		// PREPOSITIONAL RELATION
		''=>'ON',
		''=>'IN',
		''=>'TO',
		''=>'FROM',
		''=>'WITH',

		// STATE INDICATORS
		''=>'BEHAVIOR',
		''=>'ACTION',
		''=>'EVENT',
		''=>'STATE',

		// PROTO-VERBS
		''=>'LIKE',
		''=>'DOES',
		''=>'WILL',
		''=>'HANDLES',
		''=>'COMMITS',
		''=>'QUERIES',
		''=>'INTENDS',
	);
	
	public static function run($source) {
	    $tokens = array();

	    foreach($source as $number => $line) {            
	        $offset = 0;
	        while($offset < strlen($line)) {
	            $result = static::_match($line, $number, $offset);
	            if($result === false) {
	                throw new Exception("Unable to parse line " . ($line+1) . ".");
	            }
	            $tokens[] = $result;
	            $offset += strlen($result['match']);
	        }
	    }

	    return $tokens;
	}

	protected static function _match($line, $number, $offset) {
	    $string = substr($line, $offset);

	    foreach(static::$_terminals as $pattern => $name) {
	        if(preg_match($pattern, $string, $matches)) {
	            return array(
	                'match' => $matches[1],
	                'token' => $name,
	                'line' => $number+1
	            );
	        }
	    }

	    return false;
	}
}