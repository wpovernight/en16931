<?php

namespace WPO\IPS\EN16931\Handlers\Common;

use WPO\IPS\UBL\Handlers\UblHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LegalMonetaryTotalHandler extends UblHandler {

	public function handle( $data, $options = array() ) {
		$total_exc_tax = $this->get_order_items_total();
		$total_tax     = round( $this->document->order->get_total_tax(), 2 );
		$total_inc_tax = round( $total_exc_tax + $total_tax, 2 );
		$currency      = $this->document->order->get_currency();

		$legalMonetaryTotal = array(
			'name'  => 'cac:LegalMonetaryTotal',
			'value' => array(
				array(
					'name'       => 'cbc:LineExtensionAmount',
					'value'      => number_format( $total_exc_tax, 2, '.', '' ),
					'attributes' => array( 'currencyID' => $currency ),
				),
				array(
					'name'       => 'cbc:TaxExclusiveAmount',
					'value'      => number_format( $total_exc_tax, 2, '.', '' ),
					'attributes' => array( 'currencyID' => $currency ),
				),
				array(
					'name'       => 'cbc:TaxInclusiveAmount',
					'value'      => number_format( $total_inc_tax, 2, '.', '' ),
					'attributes' => array( 'currencyID' => $currency ),
				),
				array(
					'name'       => 'cbc:PayableAmount',
					'value'      => number_format( $total_inc_tax, 2, '.', '' ),
					'attributes' => array( 'currencyID' => $currency ),
				),
			),
		);

		$data[] = apply_filters( 'wpo_ips_en16931_handle_LegalMonetaryTotal', $legalMonetaryTotal, $data, $options, $this );

		return $data;
	}
	
	private function get_order_items_total() {
		$items_total = 0;
		$items       = $this->document->order->get_items( array( 'line_item', 'fee', 'shipping' ) );
	
		foreach ( $items as $item ) {
			$items_total += round( $item->get_total(), 2 );
		}
	
		return round( $items_total, 2 );
	}

}
