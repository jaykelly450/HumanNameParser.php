<?php
/**
 * Does cutting and matching stuff with a name string.
 * Note that the string has to be UTF8-encoded.
 *
 */
class Name {
    private $str;

	 function __construct($str)
	 {
		 $this->setStr($str);
	 }

	 /**
	  * Checks encoding, normalizes whitespace/punctuation, and sets the name string.
	  *
	  * @param String $str a utf8-encoding string.
	  * @return Bool True on success
	  */
	 public function setStr($str)
	 {
		 // test to make sure PCRE will work with unicode
		  $testStr = "Björn Brembs";
		  preg_match("/\w+/u", $testStr, $m);
		  if ($m[0] != "Björn") {
			  throw new Exception (
						 "It seems that your php version does not support PCRE unicode functions.");
		  }

		 if (!mb_check_encoding($str)){
			 throw new Exception("Name is not encoded in UTF-8");
		 }
		 $this->str = $str;
		 $this->norm();
		 return true;
	 }

	 public function getStr()
	 {
		 return $this->str;
	 }


	 /**
	  * Uses a regex to chop off and return part of the namestring
	  * There are two parts: first, it returns the matched substring,
	  * and then it removes that substring from $this->str and normalizes.
	  *
	  * @param string $regex matches the part of the namestring to chop off
	  * @param integer $submatchIndex	which of the parenthesized submatches to use
	  * @param string	$regexFlags	optional regex flags
	  * @return string	the part of the namestring that got chopped off
	  */
	 public function chopWithRegex($regex, $submatchIndex = 0, $regexFlags = '')
	 {
		 $regex = $regex . "ui" . $regexFlags; // unicode + case-insensitive
		 preg_match($regex, $this->str, $m);
		 $subset = (isset($m[$submatchIndex])) ? $m[$submatchIndex] : '';
		 if ($subset){
			 $this->chopEnd($subset);
			 $this->norm();
			 return $subset;
		 }
		 else {
			 return '';
		 }
	 }
	 
	 /*
	  * Flips the front and back parts of a name with one another.
	  * Front and back are determined by a specified character somewhere in the
	  * middle of the string.
	  *
	  * @param	String $flipAroundChar	the character(s) demarcating the two halves you want to flip.
	  * @return Bool True on success.
	  */
	 public function flip($flipAroundChar)
	 {
		$substrings = preg_split("/$flipAroundChar/u", $this->str);
		if (count($substrings) == 2){
			$this->str = $substrings[1] . " " . $substrings[0];
			$this->norm();
		}
		else if (count($substrings) > 2) {
			throw new Exception("Can't flip around multiple '$flipAroundChar' characters in namestring.");
		}
		return true; // if there's 1 or 0 $flipAroundChar found
	 }

	 /**
	  * Removes a given sub-string from one of the ends of $this->str
	  *
	  * @param String	$subStr	sub-string to be removed
	  * @return Bool True on success
	  */
	 private function chopEnd($subStr)
	 {
		 mb_internal_encoding("UTF-8");

		 $wholeStrLen = mb_strlen($this->str);
		 $subStrLen = mb_strlen($subStr);
		 $pos = mb_strpos($this->str, $subStr);
		 $reversePos = $wholeStrLen - $subStrLen - $pos; //distance from the end of $subStr to end of $wholeStr
		 if ($pos == 0 ){ // at the beginning of the namestring
			 $this->str = mb_substr($this->str, $subStrLen + 1);
		 }
		 else if ($reversePos == 0){ // at the end of the namestring
			 $this->str = mb_substr($this->str, 0, $pos);
		 }
		 else {
			 throw new Exception("The substring '$subStr' is in the middle of the name '{$this->str}'.");
		 }

		 $this->norm();
		 return true;
	 }


	/**
	* Removes extra whitespace and punctuation from $this->str
	* Strips whitespace chars from ends, strips redundant whitespace, converts whitespace chars to " ".
	*
	* @return Bool True on success
	*/
	private function norm()
	{
		 $this->str = preg_replace( "#^\s*#u", "", $this->str );
		 $this->str = preg_replace( "#\s*$#u", "", $this->str );
		 $this->str = preg_replace( "#\s+#u", " ", $this->str );
		 $this->str = preg_replace( "#,$#u", " ", $this->str );
		 return true;
	}
}
?>