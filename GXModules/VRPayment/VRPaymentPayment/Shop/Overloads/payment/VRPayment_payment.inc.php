<?php

class VRPayment_payment extends VRPayment_payment_parent
{
	public function __construct($module = '')
	{
		$payment = $_SESSION['payment'] ?? '';
		parent::__construct($module);
		if (strpos(strtolower($payment), 'vrpayment') !== false) {
			$_SESSION['payment'] = $payment;
		}
	}
}
