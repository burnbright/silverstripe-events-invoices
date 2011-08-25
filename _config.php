<?php

EventAdmin::$allowed_actions['createregistrationinvoice'] = 'ADMIN';
Object::add_extension("EventAdmin_EventRegistration_RecordController", "EventAdmin_EventRegistration_RecordControllerInvoiceDecorator");
DataObject::add_extension("EventRegistration","EventInvoicesRegistrationDecorator");