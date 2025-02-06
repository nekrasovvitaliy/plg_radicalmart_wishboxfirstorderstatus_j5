<?php
/**
 * @copyright   (c) 2013-2025 Nekrasov Vitaliy <nekrsov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\RadicalMart\Wishboxfirstorderstatus\Extension\Wishboxfirstorderstatus;

defined('_JEXEC') or die;

return new class implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 */
	public function register(Container $container): void
	{
		$container->set(PluginInterface::class,
			function (Container $container)
			{
				$plugin  = PluginHelper::getPlugin('radicalmart', 'wishboxfirstorderstatus');
				$subject = $container->get(DispatcherInterface::class);

				$plugin = new Wishboxfirstorderstatus($subject, (array) $plugin);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
