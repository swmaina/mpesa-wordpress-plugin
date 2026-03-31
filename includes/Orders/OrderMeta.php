<?php

namespace Woompesa\Orders;

defined( 'ABSPATH' ) || exit;

class OrderMeta {
	const PHONE                   = '_woompesa_phone';
	const STATUS                  = '_woompesa_status';
	const ENVIRONMENT             = '_woompesa_environment';
	const THIRD_PARTY_REFERENCE   = '_woompesa_third_party_reference';
	const THIRD_PARTY_CONVERSATION_ID = '_woompesa_third_party_conversation_id';
	const TRANSACTION_REFERENCE   = '_woompesa_transaction_reference';
	const TRANSACTION_ID          = '_woompesa_transaction_id';
	const ORIGINAL_TRANSACTION_ID = '_woompesa_original_transaction_id';
	const CONVERSATION_ID         = '_woompesa_conversation_id';
	const SESSION_ID              = '_woompesa_session_id';
	const RESULT_CODE             = '_woompesa_result_code';
	const RESULT_DESCRIPTION      = '_woompesa_result_desc';
	const REVERSED                = '_woompesa_reversed';
	const RAW_INITIATE_RESPONSE   = '_woompesa_raw_initiate_response';
	const CALLBACK_RECEIVED_AT    = '_woompesa_last_callback_at';
	const LAST_RECONCILE_AT       = '_woompesa_last_reconcile_at';
}
