<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_formbuilder
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Formbuilder\Administrator\Extension\FormbuilderComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The formbuilder service provider.
 *
 * @since  __DEPLOY_VERSION__
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\Formbuilder'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\Formbuilder'));
        $container->registerServiceProvider(new RouterFactory('\\Joomla\\Component\\Formbuilder'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new FormbuilderComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
