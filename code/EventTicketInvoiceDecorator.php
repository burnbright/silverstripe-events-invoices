<?php

class EventTicketInvoiceDecorator extends DataObjectDecorator{

	function extraStatics(){
		return array(
			'has_many' => array(
				'InvoiceItems' => 'InvoiceItem'
			)
		);
	}

	function updateCMSFields(FieldSet &$fields){
		$filter = "\"EventTicketID\" = ".$this->owner->ID;
		$fieldlist = array(
			'Description' => 'Description',
			'Quantity' => 'Quantity',
			'Cost' => 'Cost'
		);

		$fieldtypes = array(
			'Description' => 'TextField',
			'Quantity' => 'TextField', //FIXME: should be NumericField, but causes bug
			'Cost' => 'TextField' //FIXME: should be CurrencyField, but causes bug
		);

		//TODO: add note about how to specify attendee name in description

		$itemstable = new CustomTableField("InvoiceItems", "InvoiceItem", $fieldlist,$fieldtypes,null,$filter);
		$fields->addFieldsToTab('Root.InvoiceLines',array(
			$itemstable,
		new LiteralField("invoicelinesnote", "<p class=\"message warning\">Make sure the sum total of these items matches what you have specified as the ticket price.</p>"),
		));
	}

}