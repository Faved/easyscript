<?php
class Versions { 

	public $ScriptName;
	public $ScriptCode;
	public $VersionNumber;
  public $VersionInfo;

	public function __get($property) {
    	if (property_exists($this, $property)) {
      		return $this->$property;
    	}
  	}

  	public function __set($property, $value) {
    	if (property_exists($this, $property)) {
      		$this->$property = $value;
    	}
    }


}

?>