<?php

namespace Sescandell\Payment\BitpayBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author Stéphane Escandell <stephane.escandell@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder
            ->root('sescandell_payment_bitpay', 'array')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('notifications')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('full')->isRequired()->defaultTrue()->end()
                            ->scalarNode('url')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('email')->end()
                        ->end()
                    ->end()
                    ->scalarNode('api_key')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('transaction_speed')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->defaultValue('low')
                        ->validate()
                        ->ifNotInArray(array('high', 'medium', 'low'))
                            ->thenInvalid('Invalid Bitpay transaction speed "%s"')
                        ->end()
                    ->end()
                    ->scalarNode('encrypter_key')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->arrayNode('services')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('request')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->defaultValue('payment.bitpay.request.curl')
                            ->end()
                            ->scalarNode('encrypter')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->defaultValue('payment.bitpay.encrypter.hash')
                            ->end()
                        ->end()
                    ->end()
            ->end();

        return $treeBuilder;
    }
}
