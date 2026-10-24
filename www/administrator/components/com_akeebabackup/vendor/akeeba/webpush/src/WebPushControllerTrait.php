<?php
/**
 * Akeeba WebPush
 *
 * An abstraction layer for easier implementation of WebPush in Joomla components.
 *
 * @copyright Copyright (c) 2022-2026 Akeeba Ltd
 * @license   GNU GPL v3 or later; see LICENSE.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\WebPush;

use Joomla\CMS\Language\Text;

/**
 * Trait for controllers implementing the Web Push user registration flow
 *
 * @since  1.0.0
 */
trait WebPushControllerTrait
{
	/**
	 * Record the Web Push user subscription object to the database.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function webpushsubscribe(): void
	{
		$ret = [
			'success' => true,
			'error'   => null,
		];

		try
		{
			if (!$this->checkToken('post', false))
			{
				throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'));
			}

			$json  = $this->input->post->getRaw('subscription', '{}');
			$model = $this->getModel();

			$model->webPushSaveSubscription($json);

			if (method_exists($this, 'onAfterWebPushSaveSubscription'))
			{
				$this->onAfterWebPushSaveSubscription(json_decode($json));
			}
		}
		catch (\Throwable $e)
		{
			$ret['success'] = false;
			$ret['error'] = $e->getMessage();
		}

		@ob_end_clean();

		header('Content-Type: application/json');
		echo json_encode($ret);

		$this->app->close();
	}

	/**
	 * Remove the Web Push user subscription object from the database.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function webpushunsubscribe(): void
	{
		$ret = [
			'success' => true,
			'error'   => null,
		];

		try
		{
			if (!$this->checkToken('post', false))
			{
				throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'));
			}

			$json  = $this->input->post->getRaw('subscription', '{}');
			$model = $this->getModel();

			$model->webPushRemoveSubscription($json);
		}
		catch (\Throwable $e)
		{
			$ret['success'] = false;
			$ret['error'] = $e->getMessage();
		}

		@ob_end_clean();

		header('Content-Type: application/json');
		echo json_encode($ret);

		$this->app->close();
	}
}