<?php

require_once(__DIR__ . '/../src/LoggedInUserTestCase.php');

class CounterSalesTest extends LoggedInUserTestCase
{
	private function ensureLoggedIn(): void
	{
		$crawler = $this->request('GET', self::$baseUri . '/index.php');
		if (str_contains($crawler->text(), 'Please sign in') || str_contains($crawler->text(), 'Please login here')) {
			$this->submitForm('SubmitUser', [
				'CompanyNameField' => $_ENV['TEST_DB_SCHEMA'],
				'UserNameEntryField' => $_ENV['TEST_USER_ACCOUNT'],
				'Password' => $_ENV['TEST_USER_PASSWORD'],
			]);
		}
	}

	public function testCounterSalesWorkflow(): void
	{
		$this->ensureLoggedIn();
		$identifier = date('U');
		$stockID = 'FOOD-0020';

		// 1. Access CounterSales page
		$crawler = $this->request('GET', self::$baseUri . '/CounterSales.php?identifier=' . $identifier);
		$this->assertStringContainsString('Point of Sale', $crawler->text(), 'CounterSales page not accessible');

		// Extract FormID
		$formID = $crawler->filter('input[name="FormID"]')->attr('value');

		// 2. Add an item via AJAX
		$this->request('POST', self::$baseUri . '/CounterSales_Ajax.php', [
			'FormID' => $formID,
			'action' => 'add',
			'identifier' => $identifier,
			'stockid' => $stockID,
			'qty' => 1
		]);
		
		$rawResponse = $this->getResponse()->getContent();
		$response = json_decode($rawResponse, true);
		$this->assertNotNull($response, 'AJAX Add returned invalid JSON: ' . $rawResponse);
		$this->assertTrue($response['success'], 'AJAX Add failed: ' . ($response['error'] ?? 'Unknown error'));
		$this->assertEquals(1, $response['item_count']);

		// 3. Update Quantity via AJAX
		$this->request('POST', self::$baseUri . '/CounterSales_Ajax.php', [
			'FormID' => $formID,
			'action' => 'update_qty',
			'identifier' => $identifier,
			'line_id' => 0,
			'qty' => 5
		]);
		$response = json_decode($this->getResponse()->getContent(), true);
		$this->assertTrue($response['success'], 'AJAX Update Qty failed');
		$this->assertArrayHasKey('cart_total', $response);

		// 4. Remove Item via AJAX
		$this->request('POST', self::$baseUri . '/CounterSales_Ajax.php', [
			'FormID' => $formID,
			'action' => 'remove',
			'identifier' => $identifier,
			'line_id' => 0
		]);
		$response = json_decode($this->getResponse()->getContent(), true);
		$this->assertTrue($response['success'], 'AJAX Remove failed');
		$this->assertEquals(0, $response['item_count']);
	}

	public function testCounterSalesCheckout(): void
	{
		$this->ensureLoggedIn();
		$identifier = date('U') + 5000;
		$stockID = 'FOOD-0020';

		// 1. Access page and extract FormID
		$crawler = $this->request('GET', self::$baseUri . '/CounterSales.php?identifier=' . $identifier);
		$formID = $crawler->filter('input[name="FormID"]')->attr('value');

		// 2. Add item via AJAX
		$this->request('POST', self::$baseUri . '/CounterSales_Ajax.php', [
			'FormID' => $formID,
			'action' => 'add',
			'identifier' => $identifier,
			'stockid' => $stockID,
			'qty' => 1
		]);
		
		$rawResponse = $this->getResponse()->getContent();
		$response = json_decode($rawResponse, true);
		$this->assertNotNull($response, 'AJAX Add in checkout test failed');
		$cartTotal = $response['cart_total'];
		$taxTotal = $response['tax_total'] ?? 0;

		// 3. Process Sale
		$crawler = $this->request('POST', self::$baseUri . '/CounterSales.php', [
			'FormID' => $formID,
			'identifier' => (string)$identifier,
			'ProcessSale' => '1',
			'PaymentMethods' => ['1'],
			'PaymentAmounts' => [(string)$cartTotal],
			'BankAccounts' => ['1020'],
			'CashReceived' => (string)$cartTotal,
			'TaxAmount' => (string)$taxTotal,
			'InvoiceTotal' => (string)($cartTotal + $taxTotal)
		]);

		// 4. Verify Success Message
		$content = $this->getResponse()->getContent();
		$success = str_contains($content, 'processed') || 
		           str_contains($this->getResponse()->getHeader('Location') ?? '', 'CompletedInvoiceNo') ||
		           str_contains($content, 'CompletedInvoiceNo');
		
		if (!$success) {
			file_put_contents(__DIR__ . '/../../debug_response.html', $content);
			$this->fail('Sale processing failed. Full response saved to debug_response.html. Header Location: ' . ($this->getResponse()->getHeader('Location') ?? 'N/A'));
		}
		
		$this->assertTrue($success);
	}
}
