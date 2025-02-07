<?php

namespace WPO\IPS\EN16931\Handlers\Invoice;

use WPO\IPS\UBL\Handlers\UblHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class InvoiceTypeCodeHandler extends UblHandler {

	public function handle( $data, $options = array() ) {
		$invoiceTypeCode = array(
			'name'       => 'cbc:InvoiceTypeCode',
			'value'      => '380',
		);

		$data[] = apply_filters( 'wpo_ips_en16931_handle_InvoiceTypeCode', $invoiceTypeCode, $data, $options, $this );

		return $data;
	}

}
