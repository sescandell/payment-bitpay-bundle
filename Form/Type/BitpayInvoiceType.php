<?php
namespace Sescandell\Payment\BitpayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Type for Bitpay Invoice checkout
 *
 * @author Stéphane Escandell <stephane.escandell@gmail.com>
 */
class BitpayInvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // TODO
    }

    public function getName()
    {
        return 'bitpay_invoice_checkout';
    }
}
