<?php declare(strict_types=1);
	
	use GXModules\VRPaymentPayment\Library\Core\Settings\Struct\Settings;
	
	use VRPayment\Sdk\{
		Model\RefundCreate,
		Model\RefundType,
		Model\TransactionState
	};
	
	use GXModules\VRPayment\VRPaymentPayment\Shop\Classes\Model\VRPaymentTransactionModel;
	
	class VRPaymentOrderActionController extends AdminHttpViewController
	{
		protected const ACTION_COMPLETE = 'complete';
		protected const ACTION_CANCEL = 'cancel';
		protected const ACTION_INVOICE = 'invoice';
		protected const ACTION_PACKAGE_SLIP = 'package-slip';
		
		/**
		 * @var Settings $settings
		 */
		protected $settings;
		
		/**
		 * @var VRPaymentTransactionModel $transactionModel
		 */
		protected $transactionModel;
		
		/**
		 * @param HttpContextReaderInterface $httpContextReader
		 * @param HttpResponseProcessorInterface $httpResponseProcessor
		 * @param ContentViewInterface $defaultContentView
		 */
		public function __construct(HttpContextReaderInterface $httpContextReader, HttpResponseProcessorInterface $httpResponseProcessor, ContentViewInterface $defaultContentView)
		{
			$this->settings = new Settings();
			$this->transactionModel = new VRPaymentTransactionModel();
			
			parent::__construct($httpContextReader, $httpResponseProcessor, $defaultContentView);
		}
		
		/**
		 * @return HttpControllerResponse
		 * @throws \VRPayment\Sdk\ApiException
		 * @throws \VRPayment\Sdk\Http\ConnectionException
		 * @throws \VRPayment\Sdk\VersioningException
		 */
		public function actionChangeTransactionStatus(): HttpControllerResponse
		{
			$orderId = (int)$this->_getPostData('orderId');
			$action = $this->_getPostData('action');
			
			$transaction = $this->transactionModel->getByOrderId($orderId);
			$transactionStateAuthorized = TransactionState::AUTHORIZED;
			
			try {
				if (strtolower($transaction->getState()) === strtolower($transactionStateAuthorized)) {
					$transactionID = $transaction->getTransactionId();
					switch ($action) {
						case self::ACTION_COMPLETE:
							$this->settings->getApiClient()->getTransactionCompletionService()->completeOnline($this->settings->getSpaceId(), $transactionID);
							return new HttpControllerResponse('');
						
						case self::ACTION_CANCEL:
							$this->settings->getApiClient()->getTransactionVoidService()->voidOnline($this->settings->getSpaceId(), $transactionID);
							return new HttpControllerResponse('');
						
						default:
							return new HttpControllerResponse('Unknown action called to updated transaction status.');
					}
				}
				
				return new HttpControllerResponse(
					sprintf('Transaction should be in state %s', $transactionStateAuthorized)
				);
			} catch (\Exception $e) {
				return new HttpControllerResponse('An error appear during this action.');
			}
		}
		
		/**
		 * @return HttpControllerResponse
		 * @throws \VRPayment\Sdk\ApiException
		 * @throws \VRPayment\Sdk\Http\ConnectionException
		 * @throws \VRPayment\Sdk\VersioningException
		 */
		public function actionDownloadFile()
		{
			$orderId = (int)$_GET['orderId'];
			$action = $_GET['action'];
			$transaction = $this->transactionModel->getByOrderId($orderId);
			
			$transactionStateFulfill = VRPaymentTransactionModel::TRANSACTION_STATE_FULFILL;
			
			$allowedStates = [
				$transactionStateFulfill,
				VRPaymentTransactionModel::TRANSACTION_STATE_REFUNDED,
				VRPaymentTransactionModel::TRANSACTION_STATE_PARTIALLY_REFUNDED,
				VRPaymentTransactionModel::TRANSACTION_STATE_PAID
			];
			
			if (\in_array(strtoupper($transaction->getState()), $allowedStates)) {
				$transactionID = $transaction->getTransactionId();
				
				try {
					switch ($action) {
						case self::ACTION_INVOICE:
							$document = $this->settings->getApiClient()->getTransactionService()->getInvoiceDocument($this->settings->getSpaceId(), $transactionID);
							break;
						
						case self::ACTION_PACKAGE_SLIP:
							$document = $this->settings->getApiClient()->getTransactionService()->getPackingSlip($this->settings->getSpaceId(), $transactionID);
							break;
						
						default:
							return new HttpControllerResponse('Unknown action called to updated transaction status.');
					}
					
					if ($document) {
						$filename = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $document->getTitle()) . '.pdf';
						$filedata = base64_decode($document->getData());
						header('Content-Description: File Transfer');
						header('Content-Type: ' . $document->getMimeType());
						header('Content-Disposition: attachment; filename=' . $filename);
						header('Content-Transfer-Encoding: binary');
						header('Expires: 0');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');
						header('Content-Length: ' . strlen($filedata));
						ob_clean();
						flush();
						echo $filedata;
					}
				} catch (\Exception $e) {
					return new HttpControllerResponse('This document can\'t be downloaded.');
				}
			}
			
			return new HttpControllerResponse(
				sprintf('Transaction should be in state %s', $transactionStateFulfill)
			);
		}
		
		/**
		 * @return HttpControllerResponse
		 * @throws \VRPayment\Sdk\ApiException
		 * @throws \VRPayment\Sdk\Http\ConnectionException
		 * @throws \VRPayment\Sdk\VersioningException
		 */
		public function actionRefund()
		{
			$orderId = (int)$this->_getPostData('orderId');
			$amount = floatval(str_replace(',', '', $this->_getPostData('amount')));
			
			if ($amount <= 0) {
				return new HttpControllerResponse('Amount should be greater than 0');
			}
			
			$transaction = $this->transactionModel->getByOrderId($orderId);
			$transactionInfo = json_decode($transaction->getData(), true);
			$transactionAmount = floatval($transactionInfo['info']['total']);
			
			if ($amount > $transactionAmount) {
				return new HttpControllerResponse('Please make sure you are trying to refund correct amount of money');
			}
			
			$transactionStateFulfill = TransactionState::FULFILL;
			$transactionStatePaid = VRPaymentTransactionModel::TRANSACTION_STATE_PAID;
			if (
				strtolower($transaction->getState()) === strtolower($transactionStateFulfill) ||
				strtolower($transaction->getState()) === strtolower($transactionStatePaid)
			) {
				try {
					$transactionID = $transaction->getTransactionId();
					$refundPayload = (new RefundCreate())
						->setAmount(\round($amount, 2))
						->setTransaction($transactionID)
						->setMerchantReference((string)$orderId)
						->setExternalId(uniqid('refund_', true))
						->setType(RefundType::MERCHANT_INITIATED_ONLINE);
					
					if (!$refundPayload->valid()) {
						return new HttpControllerResponse('Refund payload invalid:' . json_encode($refundPayload->listInvalidProperties()));
					}
					
					$this->settings->getApiClient()->getRefundService()->refund($this->settings->getSpaceId(), $refundPayload);
					
					return new HttpControllerResponse('');
				} catch (\Exception $e) {
					$detectJsonPattern = '/
					\{              # { character
						(?:         # non-capturing group
							[^{}]   # anything that is not a { or }
							|       # OR
							(?R)    # recurses the entire pattern
						)*          # previous group zero or more times
					\}              # } character
					/x';
					preg_match_all($detectJsonPattern, $e->getMessage(), $matches);
					$jsonErrorMessage = $matches[0][0];
					$errorData = \json_decode($jsonErrorMessage);
					
					return new HttpControllerResponse($errorData->message);
				}
			}
			
			return new HttpControllerResponse(
				sprintf('Transaction should be in state %s', $transactionStateFulfill)
			);
		}
	}
