<?php

namespace Sescandell\Payment\BitpayBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use BitPay\Request\Curl;
use BitPay\Encrypter\Hash;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Stéphane Escandell <stephane.escandell@gmail.com>
 */
class SescandellPaymentBitpayExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $options = array();
        $options['transactionSpeed']  = $config['transaction_speed'];
        $options['fullNotifications'] = $config['notifications']['full'];
        $options['notificationUrl']   = $config['notifications']['url'];
        if (array_key_exists('email', $config['notifications'])) {
            $options['notificationEmail'] = $config['notifications']['email'];
        }

        $bitpayClientDefinition = $container->getDefinition('payment.bitpay.client');
        $bitpayClientDefinition->addArgument(new Curl()); // Make it dynamic
        $bitpayClientDefinition->addArgument(new Hash()); // Make it dynamic
        $bitpayClientDefinition->addArgument($config['api_key']);
        $bitpayClientDefinition->addArgument($options);
    }
}
