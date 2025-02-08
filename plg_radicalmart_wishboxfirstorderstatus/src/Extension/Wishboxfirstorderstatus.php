<?php
/**
 * @copyright   (c) 2013-2025 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Plugin\RadicalMart\Wishboxfirstorderstatus\Extension;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use stdClass;
use function defined;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since 1.0.0
 */
class Wishboxfirstorderstatus extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  1.0.0
	 */
	protected $app = null;

	/**
	 * Enable on RadicalMart
	 *
	 * @var  boolean
	 *
	 * @since  2.0.0
	 */
	public bool $radicalmart = true;

	/**
	 * Enable on RadicalMartExpress
	 *
	 * @var boolean
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public bool $radicalmart_express = true; // phpcs:ignore

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since 1.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onRadicalMartGetOrder'               => 'onRadicalMartGetOrder',
			'onRadicalMartAfterChangeOrderStatus' => 'onRadicalMartAfterChangeOrderStatus',
		];
	}

	/**
	 * Prepare RadicalMart method prices data.
	 *
	 * @param   string    $context    Context selector string.
	 * @param   stdClass  $orderItem  Order item
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function onRadicalMartGetOrder(string $context, stdClass $orderItem): void
	{
		if ($context === 'com_radicalmart.checkout' && $orderItem->id == 0)
		{
			$app = Factory::getApplication();

			$orderStatusId = $this->params->get('order_status_id', 0);

			if (!$orderStatusId)
			{
				return;
			}

			$fieldId = $this->params->get('field_id', 0);

			if (!$fieldId)
			{
				return;
			}

			$fieldTable = $app->bootComponent('com_radicalmart')
				->getMVCFactory()
				->createTable('Field', 'Administrator');
			$fieldTable->load($fieldId);

			$orderStatus = $this->getStatus($orderStatusId);

			$flag = true;

			foreach ($orderItem->products as $product)
			{
				if (!is_array($product->fieldsRaw)
					|| !isset($product->fieldsRaw[$fieldTable->alias])
					|| !is_array($product->fieldsRaw[$fieldTable->alias])
					|| !in_array(1, $product->fieldsRaw[$fieldTable->alias]))
				{
					$flag = false;

					break;
				}
			}

			if ($flag)
			{
				$orderItem->status = $orderStatus;
			}
		}
	}

	/**
	 * Prepare RadicalMart method prices data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param           $order
	 * @param           $oldStatus
	 * @param           $newStatus
	 * @param           $isNew
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function onRadicalMartAfterChangeOrderStatus(string $context, $order, $oldStatus, $newStatus, $isNew): bool
	{
		if ($context === 'com_radicalmart.checkout' && $isNew)
		{
			$app = Factory::getApplication();

			$orderStatusId = $this->params->get('order_status_id', 0);

			if (!$orderStatusId)
			{
				return false;
			}

			$fieldId = $this->params->get('field_id', 0);

			if (!$fieldId)
			{
				return false;
			}

			$fieldTable = $app->bootComponent('com_radicalmart')
				->getMVCFactory()
				->createTable('Field', 'Administrator');
			$fieldTable->load($fieldId);

			$flag = true;

			foreach ($order->products as $product)
			{
				if (!is_array($product->fieldsRaw)
					|| !isset($product->fieldsRaw[$fieldTable->alias])
					|| !is_array($product->fieldsRaw[$fieldTable->alias])
					|| !in_array(1, $product->fieldsRaw[$fieldTable->alias]))
				{
					$flag = false;

					break;
				}
			}

			if ($flag)
			{
				$db = Factory::getContainer()->get(DatabaseDriver::class);
				$query = $db->getQuery(true)
					->update($db->qn('#__radicalmart_orders'))
					->set('status = :status_id')
					->where($db->qn('id') . ' = :id')
					->bind(':id', $order->id, ParameterType::INTEGER)
					->bind(':status_id', $orderStatusId, ParameterType::INTEGER);
				$db->setQuery($query);
				$db->execute();

				return true;
			}
		}

		return false;
	}

	/**
	 * @param   integer  $statusId
	 *
	 * @return stdClass
	 *
	 * @since version
	 */
	protected function getStatus(int $statusId): stdClass
	{
		$db    = Factory::getContainer()->get(DatabaseDriver::class);
		$query = $db->getQuery(true)
			->select(['s.*'])
			->from($db->quoteName('#__radicalmart_statuses', 's'))
			->where('s.id = ' . $statusId);

		if ($row = $db->setQuery($query)->loadObject())
		{
			// Set title
			$row->rawtitle = $row->title;
			$row->title    = Text::_($row->title);

			// Set default
			$row->default = (int) $row->default;

			// Set params
			$row->params = new Registry($row->params);

			// Set plugins
			$row->plugins = new Registry($row->plugins);
		}

		return $row;
	}
}
