<?php

namespace WPO\IPS\EN16931\Handlers\Common;

use WPO\IPS\UBL\Handlers\UblHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class AddressHandler extends UblHandler {

	public function handle( $data, $options = array() ) {
		$root = isset( $options['root'] ) ? $options['root'] : 'cac:AccountingSupplierParty';

		// cac:AccountingSupplierParty or cac:AccountingCustomerParty
		if ( 'cac:AccountingSupplierParty' === $root ) {
			return $this->return_supplier_party( $data, $options );
		}

		return $this->return_customer_party( $data, $options );
	}

	public function return_supplier_party( $data, $options = array() ) {

		$supplierParty = array(
			array(
				'name'  => 'cac:Party',
				'value' => $this->return_supplier_party_details(),
			),
		);

		$data[] = apply_filters( 'wpo_ips_en16931_handle_AccountingSupplierParty', $supplierParty, $data, $options, $this );

		return $data;
	}

	public function return_supplier_party_details() {
		$company      = ! empty( $this->document->order_document ) ? $this->document->order_document->get_shop_name()         : '';
		$address      = ! empty( $this->document->order_document ) ? $this->document->order_document->get_shop_address()      : get_option( 'woocommerce_store_address' );
		$vat_number   = ! empty( $this->document->order_document ) ? $this->document->order_document->get_shop_vat_number()   : '';
		$coc_number   = ! empty( $this->document->order_document ) ? $this->document->order_document->get_shop_coc_number()   : '';
		$phone_number = ! empty( $this->document->order_document ) ? $this->document->order_document->get_shop_phone_number() : '';

		$supplierPartyDetails = array(
			array(
				'name'  => 'cbc:EndpointID',
				'value' => get_option( 'woocommerce_email_from_address' ),
				'attributes' => array(
					'schemeID' => 'EM',
				),
			),
			array(
				'name'  => 'cac:PostalAddress',
				'value' => array(
					array(
						'name'  => 'cbc:StreetName',
						'value' => wpo_ips_ubl_sanitize_string( get_option( 'woocommerce_store_address' ) ),
					),
					array(
						'name'  => 'cbc:CityName',
						'value' => wpo_ips_ubl_sanitize_string( get_option( 'woocommerce_store_city' ) ),
					),
					array(
						'name'  => 'cbc:PostalZone',
						'value' => get_option( 'woocommerce_store_postcode' ),
					),
					array(
						'name'  => 'cac:AddressLine',
						'value' => array(
							'name'  => 'cbc:Line',
							'value' => wpo_ips_ubl_sanitize_string( $address ),
						),
					),
					array(
						'name'  => 'cac:Country',
						'value' => array(
							'name'  => 'cbc:IdentificationCode',
							'value' => wc_format_country_state_string( get_option( 'woocommerce_default_country', '' ) )['country'],
						),
					),
				),
			),
		);

		if ( ! empty( $vat_number ) ) {
			$supplierPartyDetails[] = array(
				'name'  => 'cac:PartyTaxScheme',
				'value' => array(
					array(
						'name'  => 'cbc:CompanyID',
						'value' => $vat_number,
					),
					array(
						'name'  => 'cac:TaxScheme',
						'value' => array(
							array(
								'name'  => 'cbc:ID',
								'value' => 'VAT',
							),
						),
					),
				),
			);
		}
		
		$partyLegalEntity = array();
		if ( ! empty( $company ) ) {
			$partyLegalEntity = array(
				'name'  => 'cac:PartyLegalEntity',
				'value' => array(
					array(
						'name'  => 'cbc:RegistrationName',
						'value' => wpo_ips_ubl_sanitize_string( $company ),
					),
				),
			);
		}
		
		if ( ! empty( $partyLegalEntity ) && ! empty( $coc_number ) ) {
			$partyLegalEntity['value'][] = array(
				'name'       => 'cbc:CompanyID',
				'value'      => $coc_number,
				'attributes' => array(
					'schemeID' => '0106',
				),
			);
		}
		
		if ( ! empty( $partyLegalEntity ) ) {
			$supplierPartyDetails[] = $partyLegalEntity;
		}

		$contact = array(
			'name'  => 'cac:Contact',
			'value' => array(),
		);
			
		if ( ! empty( $company ) ) {
			$contact['value'][] = array(
				'name'  => 'cbc:Name',
				'value' => wpo_ips_ubl_sanitize_string( $company ),
			);
		}
		
		if ( ! empty( $phone_number ) ) {
			$contact['value'][] = array(
				'name'  => 'cbc:Telephone',
				'value' => $phone_number,
			);
		}
		
		$email = get_option( 'woocommerce_email_from_address' );
		
		if ( ! empty( $email ) ) {
			$contact['value'][] = array(
				'name'  => 'cbc:ElectronicMail',
				'value' => $email,
			);
		}
		
		$supplierPartyDetails[] = $contact;		
		
		return $supplierPartyDetails;
	}

	public function return_customer_party( $data, $options = array() ) {
		$vat_number        = apply_filters( 'wpo_ips_en16931_vat_number', wpo_wcpdf_get_order_customer_vat_number( $this->document->order ), $this->document->order );
		$customerPartyName = $customerPartyContactName = $this->document->order->get_formatted_billing_full_name();
		$billing_company   = $this->document->order->get_billing_company();

		if ( ! empty( $billing_company ) ) {
			// $customerPartyName = "{$billing_company} ({$customerPartyName})";
			// we register customer name separately as Contact too,
			// so we use the company name as the primary name
			$customerPartyName = $billing_company;
		}

		$customerParty = array(
			array(
				'name'  => 'cac:Party',
				'value' => array(
					array(
						'name'  => 'cbc:EndpointID',
						'value' => $this->document->order->get_billing_email(),
						'attributes' => array(
							'schemeID' => 'EM',
						),
					),
					array(
						'name'  => 'cac:PostalAddress',
						'value' => array(
							array(
								'name'  => 'cbc:StreetName',
								'value' => wpo_ips_ubl_sanitize_string( $this->document->order->get_billing_address_1() ),
							),
							array(
								'name'  => 'cbc:CityName',
								'value' => wpo_ips_ubl_sanitize_string( $this->document->order->get_billing_city() ),
							),
							array(
								'name'  => 'cbc:PostalZone',
								'value' => $this->document->order->get_billing_postcode(),
							),
							array(
								'name'  => 'cac:AddressLine',
								'value' => array(
									'name'  => 'cbc:Line',
									'value' => wpo_ips_ubl_sanitize_string( $this->document->order->get_billing_address_1() . '<br/>' . $this->document->order->get_billing_address_2() ),
								),
							),
							array(
								'name'  => 'cac:Country',
								'value' => array(
									'name'  => 'cbc:IdentificationCode',
									'value' => $this->document->order->get_billing_country(),
								),
							),
						),
					),
				),
			),
		);
		
		if ( ! empty( $vat_number ) ) {
			$partyTaxScheme = array(
				'name'  => 'cac:PartyTaxScheme',
				'value' => array(
					array(
						'name'  => 'cbc:CompanyID',
						'value' => $vat_number,
					),
					array(
						'name'  => 'cac:TaxScheme',
						'value' => array(
							array(
								'name'  => 'cbc:ID',
								'value' => 'VAT',
							),
						),
					),
				),
			);
			
			$customerParty[0]['value'][] = $partyTaxScheme;
		}
		
		if ( ! empty( $customerPartyName ) ) {
			$partyLegalEntity = array(
				'name'  => 'cac:PartyLegalEntity',
				'value' => array(
					array(
						'name'  => 'cbc:RegistrationName',
						'value' => wpo_ips_ubl_sanitize_string( $customerPartyName ),
					),
				),
			);
			
			$customerParty[0]['value'][] = $partyLegalEntity;
		}

		$data[] = apply_filters( 'wpo_ips_en16931_handle_AccountingCustomerParty', $customerParty, $data, $options, $this );

		return $data;
	}
}
