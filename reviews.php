<?php
class Reviews { 

	public $ReviewId;
  public $ScriptName;
	public $Review;
	public $Score;


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