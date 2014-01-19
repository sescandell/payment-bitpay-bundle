<?php
namespace Sescandell\Payment\BitpayBundle\Plugin;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use BitPay\BitPay;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\ErrorBuilder;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\PaymentPendingException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;

/**
 *
 * @author Stéphane Escandell <stephane.escandell@gmail.com>
 */
class BitpayInvoicePlugin extends AbstractPlugin
{
    /**
     * @var Bitpay
     */
    private $bitpay = null;

    /**
     * Default constructor
     *
     * @param BitPay $bitPay
     */
    public function __construct(BitPay $bitPay)
    {
        parent::__construct();

        $this->bitpay = $bitpay;
    }
    /**
     * (non-PHPdoc)
     * @see \JMS\Payment\CoreBundle\Plugin\PluginInterface::processes()
     */
    public function processes($paymentSystemName)
    {
        return 'bitpay_invoice' === $paymentSystemName;
    }

    /**
     * (non-PHPdoc)
     * @see \JMS\Payment\CoreBundle\Plugin\AbstractPlugin::checkPaymentInstruction()
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $paymentInstruction)
    {
        // TODO: use validators
        $errorBuilder = new ErrorBuilder();

        $extendedData = $paymentInstruction->getExtendedData();

//         if (!$extendedData->has('price')) {
//             $errorBuilder->addDataError('price', 'form.error.required');
//         }

//         if ($extendedData->get('price')<=0) {
//             $errorBuilder->addDataError('price', 'form.error.invalid');
//         }

//         if (!$extendedData->has('currency')) {
//             $errorBuilder->addDataError('currency', 'form.error.required');
//         }

        if (!$extendedData->has('orderId')) {
            $errorBuilder->addDataError('orderId', 'form.error.required');
        }

        if (!$extendedData->has('posData')) {
            $errorBuilder->addDataError('posData', 'form.error.required');
        }

        if (!$extendedData->has('options')) {
            $errorBuilder->addDataError('options', 'form.error.required');
        }

        if ($errorBuilder->hasErrors()) {
            throw $errorBuilder->getException();
        }
    }

    /**
     * (non-PHPdoc)
     * @see \JMS\Payment\CoreBundle\Plugin\AbstractPlugin::approveAndDeposit()
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->createInvoiceBillingAgreement($transaction);
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException
     * @throws PaymentPendingException
     */
    protected function createInvoiceBillingAgreement(FinancialTransactionInterface $transaction)
    {
        $invoiceId = $this->obtainInvoiceId($transaction);

        $details = $this->bitpay->getInvoice($invoiceId);
        $this->throwUnlessSuccessResponse($details, $transaction);

        // Check status
        switch($details->status) {
            case 'expired':
            case 'invalid':
                $ex = new FinancialException('Transaction failed');
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode('Invalid status: ' . $details->status);
                $ex->setFinancialTransaction($transaction);

                throw $ex;

            case 'new':
                $actionRequest = new ActionRequiredException('User must confirm transaction');
                $actionRequest->setFinancialTransaction($transaction);
                $actionRequest->setAction(new VisitUrl($details->url));

                throw $actionRequest;

            case 'paid':
                $transaction->setReferenceNumber($details->id);

                throw new PaymentPendingException('Payment waiting for confirmation');

            case 'confirmed':
            case 'complete':
                break;
        }

        $transaction->setReferenceNumber($details->id);
        $transaction->setProcessedAmount($details->price);
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException
     *
     * @return string
     */
    protected function obtainInvoiceId(FinancialTransactionInterface $transaction)
    {
        $data = $transaction->getExtendedData();
        if ($data->has('invoice_id')) {
            return $data->get('invoice_id');
        }

        $options = $data->get('options');
        $invoice = $this->bitpay->createInvoice($data->get('orderId'), $transaction->getRequestedAmount(), $data->get('posData'), $data->get('options'));

        $this->throwUnlessSuccessResponse($invoice, $transaction);

        $data->set('invoice_id', $invoice->id);
        $data->set('invoice_time', $invoice->invoiceTime);
        $data->set('url', $invoice->url);
        $data->set('btc_price', $invoice->btcPrice);

        $transaction->setReferenceNumber($invoice->id);

        $actionRequest = new ActionRequiredException('User must confirm transaction');
        $actionRequest->setFinancialTransaction($transaction);
        $actionRequest->setAction(new VisitUrl($invoice->url));

        throw $actionRequest;
    }

    /**
     * @param stdClass $response
     * @param FinancialTransactionInterface $transaction
     * @throws \JMS\Payment\CoreBundle\Plugin\Exception\FinancialException
     * @return null
     */
    protected function throwUnlessSuccessResponse($response, FinancialTransactionInterface $transaction)
    {
        if (!empty($response->error)) {
            $transaction->setResponseCode('Failed');
            $transaction->setReasonCode($response->error);

            $ex = new FinancialException('Bitpay request was not successful: ' . $response->error);
            $ex->setFinancialTransaction($transaction);

            throw $ex;
        }
    }

}
