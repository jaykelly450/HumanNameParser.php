<?php
/**
 * Works with a Name object to parse out the parts of a name.
 *
 * Example usage:
 *		$parser = new Parser("John Q. Smith");
 *		echo  $parser->getLast() . ", " . $parser->getFirst();
 *		//returns "Smith, John"
 *
 *
 */
class HumanNameParser_Parser
{
	protected $name;
	protected $leadingInit;
	protected $title;
	protected $first;
	protected $nicknames;
	protected $middle;
	protected $last;
	protected $suffix;

	protected $titles;
	protected $suffixes;
	protected $prefixes;

	/**
	 * Constructor
	 *
	 * @param	mixed $name	Either a name as a string or as a Name object.
	 */
	public function __construct($name = NULL)
	{
		$this->setName($name);
	}

	/**
	 * Sets name string and parses it.
	 * Takes Name object or a simple string (converts the string into a Name obj),
	 * parses and loads its constituant parts.
	 *
	 * @param	mixed $name	Either a name as a string or as a Name object.
	*/
	public function setName($name = NULL)
	{
		if ($name)
		{
			if ($name instanceof HumanNameParser_Name) {
				$this->name = $name;
			} else {
				$this->name = new HumanNameParser_Name($name);
			}
			
			$this->leadingInit = "";
			$this->first = "";
			$this->nicknames = "";
			$this->middle = "";
			$this->last = "";
			$this->suffix = "";
			
			$this->suffixes = array('esq','esquire','jr','sr','2','ii','iii','iv');
			$this->prefixes = array('bar','ben','bin','da','dal','de la', 'de', 'del','der','di',
			'ibn','la','le','san','st','ste','van', 'van der', 'van den', 'vel','von', 'al');
			$this->titles = array(
				// order of estimated commonality (in this case for New Zealand School communities)
				'Mrs'				=>	'Mrs',
				'Mr'				=>	'Mr',
				'Ms'				=>	'Ms',
				'Miss'			=>	'Miss',
				'Dr'				=>	'Dr',
				
				'Mister'		=>	'Mr',
				'Master'		=>	'Master',
				'Doctor'		=>	'Dr',
				'Sir'				=>	'Sir',
				'Professor'		=>	'Prof',
				'Prof'			=>	'Prof',
				'Madam'			=>	'Madam',
				'Dame'			=>	'Dame'
			);
			
			$this->parse();
		}
	}
	  
	public function getleadingInit() {
		return $this->leadingInit;
	}
	
	public function getFirst() {
		return $this->first;
	}
	
	public function getNicknames() {
		return $this->nicknames;
	}
	
	public function getMiddle() {
		return $this->middle;
	}
	
	public function getLast() {
		return $this->last;
	}
	
	public function getSuffix() {
		return $this->suffix;
	}
	public function getName(){
		return $this->name;
	}

	/**
	 * returns all the parts of the name as an array
	 *  
	 * @param String $arrType pass 'int' to get an integer-indexed array (default is associative)
	 * @return array An array of the name-parts 
	 */
	public function getArray($arrType = 'assoc')
	{
		$arr = array();
		$arr['leadingInit'] = $this->leadingInit;
		$arr['title'] = $this->title;
		$arr['first'] = $this->first;
		$arr['nicknames'] = $this->nicknames;
		$arr['middle'] = $this->middle;
		$arr['last'] = $this->last;
		$arr['suffix'] = $this->suffix;
		
		if ($arrType == 'assoc') {
			return $arr;
		} else if ($arrType == 'int'){
			return array_values($arr);
		} else {
			throw new Exception("Array must be associative ('assoc') or numeric ('num').");
		}
	}

	/**
	 * Parse the name into its constituent parts.
	 *
	 * Sequentially captures each name-part, working in from the ends and
	 * trimming the namestring as it goes.
	 * 
	 * @return boolean	true on success
	 */
	private function parse() 
	{
		$suffixes = implode("\.*|", $this->suffixes) . "\.*"; // each suffix gets a "\.*" behind it.
		$prefixes = implode(" |", $this->prefixes) . " "; // each prefix gets a " " behind it.
		$titles = implode("\.?\s?|", array_keys($this->titles)) . " "; // each title gets an optional period (.), space ( ), and surrounding parentheses (())
		
		// The regex use is a bit tricky.  *Everything* matched by the regex will be replaced,
		//	but you can select a particular parenthesized submatch to be returned.
		//	Also, note that each regex requres that the preceding ones have been run, and matches chopped out.
		$nicknamesRegex =		"/ ('|\"|\(\"*'*)(.+?)('|\"|\"*'*\)) /"; // names that starts or end w/ an apostrophe break this
		$titlesRegex =			"/\s*(\(?$titles\)?)\s*/";
		$suffixRegex =			"/,* *($suffixes)$/";
		$lastRegex =			"/(?!^)\b([^ ]+ y |$prefixes)*[^ ]+\s*$/";
		$finalComponentRegex =	"/\s*(\w+)\s*/"; // final component - one part of text with surrounding possible whitespace only
		$leadingInitRegex =		"/^(.\.*)(?= \p{L}{2})/"; // note the lookahead, which isn't returned or replaced
		$firstRegex =			"/^[^ ]+/"; //
		
		$t = $this;
		
		// flip the before-comma and after-comma parts of the name
		$this->name->flip(",");
		
		// get suffix, if there is one
		$this->suffix = trim($this->name->chopWithRegex($suffixRegex, 1), ' .');
		
		// get nickname, if there is one
		$this->nicknames = $this->name->chopWithRegex($nicknamesRegex, 2);
		
		// look for titles if there are any
		$titlespre = trim($this->name->chopWithRegex($titlesRegex, 1), ' .');
		if(!empty($titlespre)) {
			if(!array_key_exists($titlespre, $this->titles)) {
				$this->title = ucfirst(strtolower($titlespre));
			} else {
				$this->title = $this->titles[$titlespre];
			}
		}
		
		$this->name->removeNicknameComponents();
		
		// get the last name (don't throw exception - just leave it empty)
		// by default it requires a space before the last name, but...
		$this->last = trim($this->name->chopWithRegex($lastRegex, 0));
		
		// ...if we don't find one with that strategy, but we found a title before,
		// we could have only one name component left. In that case, treat it as the last name i.e.
		// Mrs Bloggs or Bloggs,Mrs
		if(empty($this->last) && !empty($this->title) && $this->name->onlyOneComponentLeft()) {
			$this->last = trim($this->name->chopWithRegex($finalComponentRegex, 1));
		}
		
		// get the first initial, if there is one
		$this->leadingInit = trim($this->name->chopWithRegex($leadingInitRegex, 1));
		
		// get the first name - don't throw exception, just leave it empty
		$this->first = trim($this->name->chopWithRegex($firstRegex, 0));
		
		// if anything's left, that's the middle name
		$this->middle = trim($this->name->getStr());
		return true;
	}
}
?>
