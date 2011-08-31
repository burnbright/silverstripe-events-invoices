<?php

class InvoiceItemEventDecorator extends DataObjectDecorator{

	function extraStatics(){
		return array(
			'has_one' => array(
				'EventTicket' => 'EventTicket'
			)
		);
	}

}