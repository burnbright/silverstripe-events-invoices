<?php

class EventInvoicesRegistrationDecorator extends DataObjectDecorator{

	function extraStatics(){
		return array(
			'has_one' => array(
				'Invoice' => 'Invoice'
			)
		);
	}

	function updateCMSFields(FieldSet &$fields){
		if($this->owner->ID){
			if($invoice = $this->owner->Invoice()->exists()){

				$tablefields = array(
					'InvoiceNumber' => 'Invoice Number',
					'Name' => 'Name',
					'Created' => 'Date', //TODO: find out how to style better
					'Total' => 'Total',
					'TotalOutstanding' => 'Outstanding',
					'IsSent' => 'Sent?',
					'Status' => 'Status'
				);

				$ctf = new HasOneComplexTableField($this->owner,"Invoice","Invoice",$tablefields,null,"\"ID\" = ".$this->owner->InvoiceID);
				$fields->addFieldToTab('Root.Invoice',$ctf);
				$ctf->setShowPagination(false);
				$ctf->setPermissions(array('show','edit','delete'));
			}elseif($this->owner->getCost()){
				$generatelink = Controller::curr()->Link("createregistrationinvoice");
				$fields->addFieldToTab('Root.Invoice',new LiteralField("generateinvoicelink","<a href=\"$generatelink\">Generate Invoice</a>"));
			}
		}
	}

	/**
	 * Creates an invoice for this registration.
	 * @todo: move this into a events_invoice module?
	 * @param date $duedate
	 * @return Invoice
	 */
	function createInvoice($duedate = null,$pdfversion = true){
		$invoice = new Invoice();
		$registration = $this->owner;
		$event = $registration->Event();

		//set email
		$invoice->Email = $this->owner->getEmail();

		$invoice->Name = $event->Title;//set name
		$invoice->InvoiceTypeID = $event->InvoiceTypeID; //TODO: make this default to something

		if($duedate){
			$invoice->DueDate = $duedate;
		}elseif($invoice->InvoiceTypeID && $event->InvoiceType()->PaymentDays){
			$invoice->DueDate = date('Y-m-d', strtotime("+".$event->InvoiceType()->PaymentDays." days"));
		}

		$invoice->setParent($registration);
		$invoice->EventRegistrationID = $registration->ID;

		$tickets = array();
		foreach($registration->Attendees() as $attendee){
			if($attendee->TicketID){
				if(!isset($tickets[$attendee->TicketID])){
					$tickets[$attendee->TicketID] = new DataObjectSet();
				}
				$tickets[$attendee->TicketID]->push($attendee);
			}
		}

		if(!count($tickets))
			return null;

		$invoice->write();
		foreach($tickets as $ticketid => $attendees){
			$ticket = DataObject::get_by_id('EventTicket',$ticketid);
			$invoice->addItem($ticket->Type,$ticket->Price,$attendees->Count());
		}

		$this->owner->extend("onCreateInvoice",$invoice);
		if($pdfversion)
			$invoice->generatePDFInvoice();

		$this->owner->InvoiceID = $invoice->ID;
		$this->owner->write();

		if($event->ReceiptContent) $invoice->EmailContent = $event->ReceiptContent; //what is this for?
		return $invoice;
	}

}