<?php declare(strict_types=1);

use VRPayment\Sdk\Model\TransactionState;
use GXModules\VRPayment\VRPaymentPayment\Shop\Classes\Model\VRPaymentTransactionModel;
use GXModules\VRPayment\VRPaymentPayment\Shop\Classes\Model\VRPaymentRefundModel;

class VRPayment_OrderExtender extends VRPayment_OrderExtender_parent
{
	public function proceed()
	{
		require(DIR_FS_CATALOG . DIR_WS_CLASSES . 'xtcPrice.php');
		$xtPrice = new xtcPrice(DEFAULT_CURRENCY, $_SESSION['customers_status']['customers_status_id']);

		$contentView = MainFactory::create('ContentView');
		$contentView->set_template_dir(DIR_FS_DOCUMENT_ROOT);
		$contentView->set_content_template('GXModules/VRPayment/VRPaymentPayment/Admin/Templates/vrpayment_transaction_panel.html');
		$contentView->set_flat_assigns(true);
		$contentView->set_caching_enabled(false);

		$transactionModel = new VRPaymentTransactionModel();
		$orderId = (int)$_GET['oID'];

		$transaction = $transactionModel->getByOrderId($orderId);
		if (empty($transaction)) {
			return parent::proceed();
		}

		$transactionData = $transaction->getData();
		$transactionInfo = $transactionData ? \json_decode($transactionData, true) : [];
		$transactionState = $transaction->getState();
		$contentView->set_content_data('orderId', $orderId);

		$refunds = VRPaymentRefundModel::getRefunds($orderId);
		$totalRefundsAmount = VRPaymentRefundModel::getTotalRefundsAmount($refunds);

		$contentView->set_content_data('refunds', $refunds);
		$contentView->set_content_data('xtPrice', $xtPrice);
		$contentView->set_content_data('totalSumOfRefunds', $totalRefundsAmount);
		$contentView->set_content_data('totalOrderAmount', round($transactionInfo['info']['total'], 2));
		$amountToBeRefunded = round(floatval($transactionInfo['info']['total']) - $totalRefundsAmount, 2);
		$contentView->set_content_data('amountToBeRefunded', number_format($amountToBeRefunded, 2));
		$contentView->set_content_data('transactionState', $transactionState);
		$contentView->set_content_data('authorizedState', TransactionState::AUTHORIZED);
		$contentView->set_content_data('fulfillState', TransactionState::FULFILL);

		$showRefundsForm = $transactionState !== VRPaymentTransactionModel::TRANSACTION_STATE_REFUNDED && $amountToBeRefunded > 0;
		$contentView->set_content_data('showRefundsForm', $showRefundsForm);

		$showButtonsAfterFullfill = $transactionState !== TransactionState::FULFILL
			&& $transactionState !== VRPaymentTransactionModel::TRANSACTION_STATE_REFUNDED
			&& $transactionState !== VRPaymentTransactionModel::TRANSACTION_STATE_PARTIALLY_REFUNDED
			&& $transactionState !== VRPaymentTransactionModel::TRANSACTION_STATE_PAID;
		$contentView->set_content_data('showButtonsAfterFullfill', $showButtonsAfterFullfill);

		$showRefundNowButton = $transactionState !== TransactionState::FULFILL
			&& $transactionState !== VRPaymentTransactionModel::TRANSACTION_STATE_REFUNDED
			&& $transactionState !== VRPaymentTransactionModel::TRANSACTION_STATE_PARTIALLY_REFUNDED ;
		$contentView->set_content_data('showRefundNowButton', $showRefundNowButton);

		$languageTextManager = MainFactory::create_object(LanguageTextManager::class, array(), true);
		$this->v_output_buffer['below_product_data_heading'] = 'VRPayment ' . $languageTextManager->get_text('transaction_panel', 'vrpayment');
		$this->v_output_buffer['below_product_data'] = $contentView->get_html();

		$this->addContent();
		parent::proceed();
	}
}
