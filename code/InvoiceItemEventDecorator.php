<?php

class InvoiceItemEventDecorator extends DataObjectDecorator{

	function extraStatics(){
		return array(
			'has_one' => array(
				'EventTicket' => 'EventTicket'
			)
		);
	}

	function parseDescription(DataObject $obj){
		$description = $this->owner->Description;
		if(preg_match_all("/{([^}]*)}/",$description,$matches)){
			if($matches)
				foreach($matches[1] as $match){
					$description = str_replace("{".$match."}",$this->getObjField($obj,$match),$description);
				}
		}
		$this->owner->Description = $description;
	}


	/**
	 * Dot notation to get any related field.
	 */
	protected function getObjField($obj,$match){
		$targetobj = $obj;
		$field = $match;

		//find relationobj
		if(strpos($match,'.') > 0){
			$parts = explode('.',$match);
			$field = array_pop($parts);
			foreach($parts as $part){
				if($component = $targetobj->getComponent($part)){
					$targetobj = $component;
				}else{
					return false; //faield traversing relations
				}
			}
		}
		//get field
		return $targetobj->getField($field);
	}

}