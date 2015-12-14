<?php
namespace Hypercharge;
require_once dirname(__DIR__).'/test_helper.php';

if(getenv('DEBUG') == '1') Config::setLogger(new StdoutLogger());

class TransactionIntegrationTest extends HyperchargeTestCase {

	function setUp() {
		$this->credentials();

		Config::setIdSeparator('---');
		$this->channelToken = $this->credentials->channelTokens->USD;
	}

	function fixture($file) {
		$data = self::schemaRequest($file);
		$trx = $data['payment_transaction'];

		$this->injectRedirectUrls($trx, 'transaction');

		// move credit card expiration year into the future
		if(isset($trx['expiration_year'])) {
			$trx['expiration_year'] = (string) (date('Y') + 2);
		}

		// move recurring schedule into the future. otherwise it would be invalid
		if(isset($trx['recurring_schedule'])) {
			$start = new \DateTime('now', new \DateTimeZone('UTC'));
			$start->add(new \DateInterval('P1M'));
			$trx['recurring_schedule']['start_date'] = $start->format('Y-m-d');
			if(!empty($trx['recurring_schedule']['end_date'])) {
				$end = $start->add(new \DateInterval('P2Y6M'));
				$trx['recurring_schedule']['end_date'] = $end->format('Y-m-d');
			}
		}

		return $trx;
	}

	function testWrongCurrencyShouldReturnTrxWithError() {
		$data = $this->fixture('sale.json');
		$data['currency'] = 'EUR';
		$trx = Transaction::sale($this->channelToken, $data); // USD channel -> baaam!
		$this->assertIsA($trx->error, 'Hypercharge\Errors\InputDataInvalidError', "%s, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertTrue($trx->isPersistentInHypercharge(), 'isPersistentInHypercharge: %s');
		$this->assertFalse($trx->isApproved(), 'isApproved() %s');
		$this->assertTrue($trx->isError(), 'isError() %s');
		$this->assertEqual($trx->transaction_type, 'sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
	}

	function testInvalidChannelTokenShouldThrowException() {
		$data = $this->fixture('sale.json');
		$this->expectException(new Errors\AccountError('Transaction failed, please contact support!'));
		$trx = Transaction::sale('wrong_channel_token', $data);
		$this->fail('should throw AccountError! trx: '. $trx->__toString());
	}

	function testSale() {
		$data = $this->fixture('sale.json');
		$trx = Transaction::sale($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
		return $trx;
	}

	function testSaleWithRequestObject() {
		$data = $this->fixture('sale.json');
		$req = new TransactionRequest($data);
		$trx = Transaction::sale($this->channelToken, $req);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
		return $trx;
	}

	function testSaleWithExpiredCreditCard() {
		$data = $this->fixture('sale.json');
		$data['expiration_year'] = '2012';
		$trx = Transaction::sale($this->channelToken, $data);
		$this->assertIsA($trx->error, 'Hypercharge\Errors\InputDataInvalidError');
		$error = $trx->error;
		$this->assertEqual($error->status_code, 340);
		$this->assertEqual($error->technical_message, "'expiration_year' is invalid");
		$this->assertEqual($error->message, "Please check input data for errors!");
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isError(), 'isError() %s');
		$this->assertFalse($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	}

	function testSaleWithInvalidChannel() {
		$this->expectException(new Errors\AccountError("Transaction failed, please contact support!", "Invalid Channel"));
		$data = $this->fixture('sale.json');
		Transaction::sale('wrong_channel_token', $data);
	}

	function testSaleWithInvalidAmount() {
		$this->expectException(new Errors\ValidationError(array(array(
			'property'=>'payment_transaction.amount'
			,'message'=>'must have a minimum value of 1'
		))));
		$data = $this->fixture('sale.json');
		$data['amount'] = -10;
		Transaction::sale($this->channelToken, $data);
	}

	function testAutorize() {
		$data = $this->fixture('authorize.json');
		$trx = Transaction::authorize($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'authorize');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		return $trx;
	}

	function testCapture() {
		$authorize = $this->testAutorize();
		$data = $this->fixture('capture.json');
		$data['reference_id'] = $authorize->unique_id;
		$trx = Transaction::capture($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'capture');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	}

	function testCaptureWithInvalidReferenceId() {
		$data = $this->fixture('capture.json');
		$trx = Transaction::capture($this->channelToken, $data);
		$this->assertIsA($trx->error, 'Hypercharge\Errors\ConfigurationError');
		$error = $trx->error;
		$this->assertEqual($error->status_code, 120);
		$this->assertEqual($error->technical_message, 'gateway is missing!');
		$this->assertEqual($error->message, 'Transaction failed, please contact support!');
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isError(), 'isError() %s');
		$this->assertEqual($trx->transaction_type, 'capture');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	}

	function testRefund() {
		$sale = $this->testSale();
		$data = $this->fixture('refund.json');
		$data['reference_id'] = $sale->unique_id;
		$trx = Transaction::refund($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'refund');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	}

	function testRefundWithInvalidReferenceId() {
		$data = $this->fixture('refund.json');
		$trx = Transaction::refund($this->channelToken, $data);
		$this->assertIsA($trx->error, 'Hypercharge\Errors\ConfigurationError');
		$error = $trx->error;
		$this->assertEqual($error->status_code, 120);
		// see merchant app for better error info
		// in fact its a
		// Workflow ReferenceNotFoundError: no approved reference transaction found
		$this->assertEqual($error->technical_message, 'gateway is missing!');
		$this->assertEqual($error->message, 'Transaction failed, please contact support!');
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isError(), 'isError() %s');
		$this->assertEqual($trx->transaction_type, 'refund');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	}

	function testVoid() {
		$sale = $this->testSale();
		$data = $this->fixture('void.json');
		$data['reference_id'] = $sale->unique_id;
		$trx = Transaction::void($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'void');
	}

	function testReferencedFundTransfer() {
		$sale = $this->testSale();
		$data = $this->fixture('referenced_fund_transfer.json');
		$data['reference_id'] = $sale->unique_id;
		$trx = Transaction::referenced_fund_transfer($this->channelToken, $data);
		if($trx->error) {
			$this->fail('TODO maik! does not work on testgate! #1604 and #1632');
			return;
		}
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'referenced_fund_transfer');
	}

	function testAutorize3dAsync() {
	 	$data = $this->fixture('authorize3d_async.json');
	 	$trx = Transaction::authorize3d($this->channelToken, $data);
	 	$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
	 	$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
	 	$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
	 	$this->assertEqual($trx->transaction_type, 'authorize3d');
	 	$this->assertEqual($trx->amount  , $data['amount']);
	 	$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertPattern('/\/redirect\/to_acquirer\//', $trx->redirect_url);
	 	$this->assertTrue($trx->shouldRedirect());
	 	return $trx;
	}

	function testAutorize3dSync() {
	 	$data = $this->fixture('authorize3d_sync.json');
	 	$trx = Transaction::authorize3d($this->channelToken, $data);
	 	$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
	 	$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		if($trx->isPendingAsync()) {
			$this->fail('TODO maik! created trx should be sync but is async! #1605');
			return;
		}
	 	$this->assertTrue($trx->isApproved(), 'isApproved() %s');
	 	$this->assertEqual($trx->transaction_type, 'authorize3d');
	 	$this->assertEqual($trx->amount  , $data['amount']);
	 	$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertNull($trx->redirect_url);
	 	$this->assertFalse($trx->shouldRedirect());
	 	return $trx;
	}

	function testSale3dAsync() {
	 	$data = $this->fixture('sale3d_async.json');
	 	$trx = Transaction::sale3d($this->channelToken, $data);
	 	$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
	 	$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
	 	$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
	 	$this->assertEqual($trx->transaction_type, 'sale3d');
	 	$this->assertEqual($trx->amount  , $data['amount']);
	 	$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertPattern('/\/redirect\/to_acquirer\//', $trx->redirect_url);
	 	$this->assertTrue($trx->shouldRedirect());
	 	return $trx;
	}

	function testSale3dSync() {
	 	$data = $this->fixture('sale3d_sync.json');
	 	$trx = Transaction::sale3d($this->channelToken, $data);
	 	$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
	 	$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
	 	if($trx->isPendingAsync()) {
	 		$this->fail('TODO maik! created trx should be sync but is async! #1606 and #1648 ');
	 		return;
	 	}
	 	$this->assertTrue($trx->isApproved(), 'isApproved() %s');
	 	$this->assertEqual($trx->transaction_type, 'sale3d');
	 	$this->assertEqual($trx->amount  , $data['amount']);
	 	$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertNull($trx->redirect_url);
	 	$this->assertFalse($trx->shouldRedirect());
	 	return $trx;
	}

	function testInitRecurringSale() {
		$data = $this->fixture('init_recurring_sale.json');
		$trx = Transaction::init_recurring_sale($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'init_recurring_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
		return $trx;
	}

	function testInitRecurringAuthorize() {
		$data = $this->fixture('init_recurring_authorize.json');
		$trx = Transaction::init_recurring_authorize($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'init_recurring_authorize');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
		return $trx;
	}

	function testRecurringSale() {
		$sale = $this->testInitRecurringSale();
		$data = $this->fixture('recurring_sale.json');
		$data['reference_id'] = $sale->unique_id;
		$trx = Transaction::recurring_sale($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isApproved(), 'isApproved() %s');
		$this->assertEqual($trx->transaction_type, 'recurring_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
		return $trx;
	}

	function testPayPal() {
		$data = $this->fixture('pay_pal.json');
		$data['currency'] = 'USD';
		$trx = Transaction::pay_pal($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
		$this->assertEqual($trx->transaction_type, 'pay_pal');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
	 	$this->assertPattern('/\/redirect\/to_acquirer\//', $trx->redirect_url);
	 	$this->assertTrue($trx->shouldRedirect());
	}

	function testDebitSale() {
		$data = $this->fixture('debit_sale.json');
		$data['currency'] = 'USD';
		$trx = Transaction::debit_sale($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
		$this->assertEqual($trx->transaction_type, 'debit_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertNull(@$trx->redirect_url);
	 	$this->assertFalse($trx->shouldRedirect());
	}

	function testInitRecurringDebitSale() {
		$data = $this->fixture('init_recurring_debit_sale.json');
		$data['currency'] = 'USD';
		$trx = Transaction::init_recurring_debit_sale($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
		$this->assertEqual($trx->transaction_type, 'init_recurring_debit_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertNull(@$trx->redirect_url);
	 	$this->assertFalse($trx->shouldRedirect());
	 	return $trx;
	}

	function testSepaDebitSale() {
		$data = $this->fixture('sepa_debit_sale.json');
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::sepa_debit_sale($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
			$this->assertEqual($trx->transaction_type, 'sepa_debit_sale');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
		 	$this->assertNull(@$trx->redirect_url);
		 	$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
	}

	function testGtdSepaDebitSale() {
		$data = $this->fixture('gtd_sepa_debit_sale.json');
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::gtd_sepa_debit_sale($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
			$this->assertEqual($trx->transaction_type, 'gtd_sepa_debit_sale');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
		 	$this->assertNull(@$trx->redirect_url);
		 	$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
	}

	function testInitRecurringSepaDebitSale() {
		$data = $this->fixture('init_recurring_sepa_debit_sale.json');
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::init_recurring_sepa_debit_sale($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
			$this->assertEqual($trx->transaction_type, 'init_recurring_sepa_debit_sale');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
		 	$this->assertNull(@$trx->redirect_url);
		 	$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
		return $trx;
	}

	function testInitRecurringGtdSepaDebitSale() {
		$data = $this->fixture('init_recurring_gtd_sepa_debit_sale.json');
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::init_recurring_gtd_sepa_debit_sale($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
			$this->assertEqual($trx->transaction_type, 'init_recurring_gtd_sepa_debit_sale');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
		 	$this->assertNull(@$trx->redirect_url);
		 	$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
		return $trx;
	}

	function testRecurringSepaDebitSale() {
		$init = $this->testInitRecurringSepaDebitSale();

		$charged = Sandbox::charge_depit_sale($this->channelToken, $init->unique_id);

		$this->assertEqual($charged->unique_id, $init->unique_id);
		$this->assertEqual($charged->status, 'approved');
		$this->assertTrue($charged->isApproved());

		$data = $this->fixture('recurring_sepa_debit_sale.json');
		$data['reference_id'] = $init->unique_id;
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::recurring_sepa_debit_sale($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertTrue($trx->isApproved(), 'isApproved() %s');
			$this->assertEqual($trx->transaction_type, 'recurring_sepa_debit_sale');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
			$this->assertNull(@$trx->redirect_url);
			$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
	}

	function testRecurringGtdSepaDebitSale() {
		$init = $this->testInitRecurringGtdSepaDebitSale();

		$charged = Sandbox::charge_depit_sale($this->channelToken, $init->unique_id);

		$this->assertEqual($charged->unique_id, $init->unique_id);
		$this->assertEqual($charged->status, 'approved');
		$this->assertTrue($charged->isApproved());

		$data = $this->fixture('recurring_gtd_sepa_debit_sale.json');
		$data['reference_id'] = $init->unique_id;
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::recurring_gtd_sepa_debit_sale($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertTrue($trx->isApproved(), 'isApproved() %s');
			$this->assertEqual($trx->transaction_type, 'recurring_gtd_sepa_debit_sale');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
			$this->assertNull(@$trx->redirect_url);
			$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
	}

	function testInitRecurringSepaDebitAuthorize() {
		$data = $this->fixture('init_recurring_sepa_debit_authorize.json');
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::init_recurring_sepa_debit_authorize($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
			$this->assertEqual($trx->transaction_type, 'init_recurring_sepa_debit_authorize');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
		 	$this->assertNull(@$trx->redirect_url);
		 	$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
	}

	function testInitRecurringGtdSepaDebitAuthorize() {
		$data = $this->fixture('init_recurring_gtd_sepa_debit_authorize.json');
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::init_recurring_gtd_sepa_debit_authorize($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
			$this->assertEqual($trx->transaction_type, 'init_recurring_gtd_sepa_debit_authorize');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
		 	$this->assertNull(@$trx->redirect_url);
		 	$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
	}

	function testGiroPaySale() {
		$data = $this->fixture('giro_pay_sale.json');
		$data['currency'] = 'EUR';
		$trx = Transaction::giro_pay_sale($this->credentials->channelTokens->EUR, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
		$this->assertEqual($trx->transaction_type, 'giro_pay_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertPattern('/\/redirect\/to_acquirer\//', $trx->redirect_url);
	 	$this->assertTrue($trx->shouldRedirect());
	}

	function testDirectPay24Sale() {
		$data = $this->fixture('direct_pay24_sale.json');
		$data['currency'] = 'EUR';
		$trx = Transaction::direct_pay24_sale($this->credentials->channelTokens->EUR, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s, status: '.$trx->status);
		$this->assertEqual($trx->transaction_type, 'direct_pay24_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertPattern('/\/redirect\/to_acquirer\//', $trx->redirect_url);
	 	$this->assertTrue($trx->shouldRedirect());
	}

	function testIdealSale() {
		$data = $this->fixture('ideal_sale.json');
		$data['currency'] = 'USD';
		$trx = Transaction::ideal_sale($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s, status: '.$trx->status);
		$this->assertEqual($trx->transaction_type, 'ideal_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
	 	$this->assertPattern('/\/redirect\/to_acquirer\//', $trx->redirect_url);
	 	$this->assertTrue($trx->shouldRedirect());
	}

	function testPurchaseOnAccount() {
		$data = $this->fixture('purchase_on_account.json');
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::purchase_on_account($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertIsA($trx, 'Hypercharge\Transaction');
			$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s, status: '.$trx->status);
			$this->assertEqual($trx->transaction_type, 'purchase_on_account');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
			$o = Helper::extractRandomId($trx->transaction_id);
			$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
			$this->assertEqual($o->transaction_id, $data['transaction_id']);
			$this->assertNull(@$trx->redirect_url);
			$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
	}

	function testGtdPurchaseOnAccount() {
		$data = $this->fixture('gtd_purchase_on_account.json');
		$data['currency'] = 'USD';
		try {
			$trx = Transaction::gtd_purchase_on_account($this->channelToken, $data);
			$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
			$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
			$this->assertIsA($trx, 'Hypercharge\Transaction');
			$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s, status: '.$trx->status);
			$this->assertEqual($trx->transaction_type, 'gtd_purchase_on_account');
			$this->assertEqual($trx->amount  , $data['amount']);
			$this->assertEqual($trx->currency, $data['currency']);
			$o = Helper::extractRandomId($trx->transaction_id);
			$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
			$this->assertEqual($o->transaction_id, $data['transaction_id']);
			$this->assertNull(@$trx->redirect_url);
			$this->assertFalse($trx->shouldRedirect());
		} catch(\Exception $e) {
			print_r($e->errors);
			throw $e;
		}
	}

	function testPayInAdvance() {
		$data = $this->fixture('pay_in_advance.json');
		$data['currency'] = 'USD';
		$trx = Transaction::pay_in_advance($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s, status: '.$trx->status);
		$this->assertEqual($trx->transaction_type, 'pay_in_advance');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
	 	$this->assertNull(@$trx->redirect_url);
	 	$this->assertFalse($trx->shouldRedirect());
	}

	function testPaymentOnDelivery() {
		$data = $this->fixture('payment_on_delivery.json');
		$data['currency'] = 'USD';
		$trx = Transaction::payment_on_delivery($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertTrue($trx->isApproved(), 'isApproved() %s, status: '.$trx->status);
		$this->assertEqual($trx->transaction_type, 'payment_on_delivery');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
	}

	function testRecurringDebitSale() {
		$sale = $this->testInitRecurringDebitSale();
		$data = $this->fixture('recurring_debit_sale.json');
		$data['currency']     = $sale->currency;
		$data['reference_id'] = $sale->unique_id;
		$trx = Transaction::recurring_debit_sale($this->channelToken, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s, status: '.$trx->status);
		$this->assertEqual($trx->transaction_type, 'recurring_debit_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
		$o = Helper::extractRandomId($trx->transaction_id);
		$this->assertEqual($trx->transaction_id, $data['transaction_id'].'---'.$o->random_id);
		$this->assertEqual($o->transaction_id, $data['transaction_id']);
	 	$this->assertNull(@$trx->redirect_url);
	 	$this->assertFalse($trx->shouldRedirect());
	}

	function testPaySafeCardSale() {
		$data = $this->fixture('pay_safe_card_sale.json');
		$data['currency'] = 'EUR';
		$trx = Transaction::pay_safe_card_sale($this->credentials->channelTokens->EUR, $data);
		$this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
		$this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
		$this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s, status: '.$trx->status);
		$this->assertEqual($trx->transaction_type, 'pay_safe_card_sale');
		$this->assertEqual($trx->amount  , $data['amount']);
		$this->assertEqual($trx->currency, $data['currency']);
	 	$this->assertPattern('/\/redirect\/to_acquirer\//', $trx->redirect_url);
	 	$this->assertTrue($trx->shouldRedirect());
	}

    function testEpsSale() {
        $data = $this->fixture('eps_sale.json');
        $data['currency'] = 'EUR';
        $trx = Transaction::giro_pay_sale($this->credentials->channelTokens->EUR, $data);
        $this->assertNull($trx->error, "error %s , uid: $trx->unique_id, error:".$trx->error);
        $this->assertPattern('/^[0-9a-f]{32}$/', $trx->unique_id);
        $this->assertTrue($trx->isPendingAsync(), 'isPendingAsync() %s');
        $this->assertEqual($trx->transaction_type, 'eps_sale');
        $this->assertEqual($trx->amount  , $data['amount']);
        $this->assertEqual($trx->currency, $data['currency']);
        $this->assertPattern('/\/redirect\/to_acquirer\//', $trx->redirect_url);
        $this->assertTrue($trx->shouldRedirect());
    }

	function testFind() {
		$sale = $this->testSale();
		$trx = Transaction::find($this->channelToken, $sale->unique_id);
		$this->assertIsA($trx, 'Hypercharge\Transaction');
		$this->assertEqual($trx, $sale);
	}

	function testPageDefaultFirstPage() {
		$all = Transaction::page($this->channelToken);
		$this->assertIsA($all, 'Hypercharge\PaginatedCollection');
		$this->assertIsA($all, 'Iterator');

		$this->assertEqual($all->getCount(), 100);
		$this->assertEqual($all->getPerPage(), 100);
		$this->assertEqual($all->getPage(), 1);
		$this->assertEqual($all->getNextPage(), 2);
		$this->assertTrue($all->hasNextPage());
		$this->assertIsA($all->getTotalCount(), 'int');
		$this->assertIsA($all->getPagesCount(), 'int');

		$k = 0;
		foreach($all as $i => $trx) {
			$this->assertIsA($trx, 'Hypercharge\Transaction', "element $i: %s");
			$this->assertPattern('/^[a-f0-9]{32}$/', $trx->unique_id);
			$this->assertIsA($trx->transaction_type, 'string');
			$this->assertIsA($trx->status, 'string');
			$k++;
		}
		$this->assertEqual($all->getCount(), $k);
	}

	function testPageEmpty() {
		$all = Transaction::page(
			$this->channelToken
			,array('start_date'=>'2013-05-24', 'end_date' => '2013-05-24')
		);
		$this->assertIsA($all, 'Hypercharge\PaginatedCollection');
		$this->assertEqual($all->getEntries(), array());
		$this->assertEqual($all->getCount(), 0);
		$this->assertEqual($all->getPerPage(), 100);
		$this->assertEqual($all->getPage(), 1);
		$this->assertEqual($all->getNextPage(), false);
		$this->assertFalse($all->hasNextPage());
		$this->assertEqual($all->getTotalCount(), 0);
		$this->assertEqual($all->getPagesCount(), 1);
	}

	function testEachEmpty() {
		$n = 0;
		$me = $this;
		$all = Transaction::each(
			$this->channelToken
			,array('start_date'=>'2013-05-24', 'end_date' => '2013-05-24')
			,function($trx) use ($me, &$n) {
				$n++;
				$me->fail('in an empty Transaction::each() result the callback should never be called!');
			}
		);
		$this->assertEqual($n, 0);
	}

	function testEachForOneDay() {
		$uids = array();
		$n = 0;
		$me = $this;
		$all = Transaction::each(
			$this->channelToken
			,array('start_date'=>'2013-05-24', 'period' => 'P1D')
			,function($trx) use ($me, &$n, &$uids) {
				$n++;
				$me->assertIsA($trx, 'Hypercharge\Transaction');
				$me->assertPattern('/^2013-05-24/', $trx->timestamp);
				$me->assertIsA($trx->transaction_type, 'string');
				$me->assertIsA($trx->status, 'string');
				$me->assertPattern('/^[a-f0-9]{32}$/', $trx->unique_id);
				if(isset($uids[$trx->unique_id])) $me->fail("dublicate transaction! $n ". print_r($trx, true));
				$uids[$trx->unique_id] = true;
			}
		);
		$this->assertEqual($n, 247);
	}

	function testEachForOneWeek() {
		$uids = array();
		$n = 0;
		$me = $this;
		$request = array('start_date'=>'2013-05-20', 'period'=>'P1W');

		$testPage = Transaction::page($this->channelToken, $request);

		Transaction::each($this->channelToken, $request, function($trx) use ($me, &$n, &$uids) {
			$n++;
			$me->assertIsA($trx, 'Hypercharge\Transaction', "element $n: %s");
			$me->assertIsA($trx->transaction_type, 'string');
			$me->assertIsA($trx->status, 'string');
			$me->assertPattern('/^[a-f0-9]{32}$/', $trx->unique_id);
			if(isset($uids[$trx->unique_id])) $me->fail('dublicate transaction!'. print_r($trx, true));
			$uids[$trx->unique_id] = true;
		});
		$this->assertTrue($n>800, 'should be more than 800 total transactions but was: '. $n);
		$this->assertEqual($testPage->getTotalCount(), $n, 'total_count %s');
	}

}

