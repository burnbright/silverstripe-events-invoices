<?php

class EventAdmin_EventRegistration_RecordControllerInvoiceDecorator extends Extension{

	static $allowed_actions = array(
		"createregistrationinvoice" => "ADMIN"
	);

	/**
	 * Calls the createInvoice function for a specified EventRegistration.
	 */
	function createregistrationinvoice(){
		$registration =  $this->owner->getCurrentRecord();
		if($registration && !$registration->Invoice()->exists()){
			if($invoice = $registration->createInvoice()){
				return "success";
			}else{
				return "No invoice was created.";
			}
		}
		//else return 'no registration found'
		Debug::show($registration->Invoice());
		return "something went wrong";
	}

}