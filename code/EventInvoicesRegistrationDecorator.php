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
		$event = $this->owner->Event();

		//set email
		$invoice->Email = $this->owner->getEmail();
		$invoice->Name = $event->Title;//set name
		$invoice->InvoiceTypeID = $event->InvoiceTypeID; //TODO: make this default to something
		$invoice->Address =	$this->owner->Address;

		if($duedate){
			$invoice->DueDate = $duedate;
		}elseif($invoice->InvoiceTypeID && $event->InvoiceType()->PaymentDays){
			$invoice->DueDate = date('Y-m-d', strtotime("+".$event->InvoiceType()->PaymentDays." days"));
		}

		$invoice->setParent($this->owner);
		$invoice->EventRegistrationID = $this->owner->ID;
		$invoice->write();

		$lines = array();

		//create invoice lines
		foreach($this->owner->Attendees() as $attendee){
			if($attendee->TicketID && $ticket = $attendee->Ticket()){
				$description = sprintf(_t("EventRegistration.INVOICELINE"));
				if($ticket->InvoiceItems()->exists()){
					foreach($ticket->InvoiceItems($filter = "",$sort = "\"Sort\" ASC,\"ID\" ASC") as $origitem){
						$newitem = $origitem->duplicate(true); //true = write to db
						$newitem->EventTicketID = null;
						$newitem->parseDescription($attendee); //set name(s)
						$newitem->write();
						$invoice->InvoiceItems()->add($newitem);
					}
				}else{
					$description = sprintf(_t("EventInvoices.DESCRIPTION","%s for %s"),$ticket->Type,$attendee->getFirstName()." ".$attendee->getSurname());
					$invoice->addItem($description,$ticket->Price,1);
				}
			}
		}

		//TODO: group all invoice items that match in price and description
			//find all unique, add 1 to quantity for each duplicate (match on price and description)

		$this->owner->extend("onCreateInvoice",$invoice);
		$invoice->write();
		if($pdfversion)
			$invoice->generatePDFInvoice();

		$this->owner->InvoiceID = $invoice->ID;
		$this->owner->write();

		if($event->ReceiptContent) $invoice->EmailContent = $event->ReceiptContent; //what is this for?
		return $invoice;
	}

}