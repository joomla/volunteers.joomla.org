<?php
/**
 * @Copyright
 * @package     Field - Donation Code Check
 * @author      Viktor Vogel <admin@kubik-rubik.de>
 * @version     Joomla! 3 - 3.1.2 - 2016-06-03
 * @link        https://joomla-extensions.kubik-rubik.de/
 *
 * @license     GNU/GPL
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('JPATH_PLATFORM') or die;

use Joomla\Registry\Registry;

/**
 * Form Field class for Kubik-Rubik Joomla! Extensions.
 * Provides a donation code check.
 */
class JFormFieldKRDonationCodeCheck extends JFormField
{
	protected $type = 'krdonationcodecheck';

	protected function getInput()
	{
		$field_set = $this->form->getFieldset();
		$donation_code = '';

		if(empty($this->group))
		{
			$donation_code = $field_set['jform_donation_code']->value;
		}
		elseif($this->group == 'params')
		{
			$donation_code = $field_set['jform_params_donation_code']->value;
		}

		$session = JFactory::getSession();
		$field_value_session = $session->get('field_value', '', 'krdonationcodecheck');
		$field_value_head_session = $session->get('field_value_head', '', 'krdonationcodecheck');
		$donation_code_session = $session->get('donation_code', '', 'krdonationcodecheck');

		if($field_value_session === 1 AND ($donation_code === $donation_code_session))
		{
			$field_value = '';

			if($this->id == 'jform_params_donation' OR $this->id == 'jform_donation')
			{
				$field_value .= '<div class="'.$this->randomClassName($session, 'success').'">'.JText::_('KR_DONATION_CODE_CHECK_SUCCESS').'</div>';
				$this->setHeadDataSession($session);
			}

			return $field_value;
		}
		elseif(!empty($field_value_session) AND !empty($field_value_head_session) AND ($donation_code == $donation_code_session))
		{
			$this->addHeadData($field_value_head_session);

			return $field_value_session;
		}

		$session->clear('field_value', 'krdonationcodecheck');
		$session->clear('field_value_head', 'krdonationcodecheck');
		$session->clear('donation_code', 'krdonationcodecheck');

		$host = JUri::getInstance()->getHost();
		$session->set('donation_code', $donation_code, 'krdonationcodecheck');

		if($host == 'localhost')
		{
			$field_value = '<div class="'.$this->randomClassName($session).'">'.JText::_('KR_DONATION_CODE_CHECK_DEFAULT_LOCALHOST').'</div>';

			if(!empty($donation_code))
			{
				$field_value .= '<div class="'.$this->randomClassName($session, 'warning').'">'.JText::_('KR_DONATION_CODE_CHECK_ERROR_LOCALHOST').'</div>';
			}

			$session->set('field_value', $field_value, 'krdonationcodecheck');
			$this->setHeadDataSession($session);

			return $field_value;
		}

		$donation_code_check = $this->getDonationCodeStatus($host, $donation_code);

		if($donation_code_check !== 1)
		{
			$field_value = '<div class="'.$this->randomClassName($session).'">'.JText::sprintf('KR_DONATION_CODE_CHECK_DEFAULT', $host).'</div>';

			if($donation_code_check === -1)
			{
				$field_value .= '<div class="'.$this->randomClassName($session, 'warning').'">'.JText::_('KR_DONATION_CODE_CHECK_ERROR_SERVER').'</div>';
			}

			if($donation_code_check === -2)
			{
				$field_value .= '<div class="'.$this->randomClassName($session, 'warning').'">'.JText::_('KR_DONATION_CODE_CHECK_ERROR').'</div>';
			}

			$session->set('field_value', $field_value, 'krdonationcodecheck');
			$this->setHeadDataSession($session);

			return $field_value;
		}

		$field_value = '';

		if($this->id == 'jform_params_donation' OR $this->id == 'jform_donation')
		{
			$field_value .= '<div class="'.$this->randomClassName($session, 'success').'">'.JText::_('KR_DONATION_CODE_CHECK_SUCCESS').'</div>';
		}

		$session->set('field_value', 1, 'krdonationcodecheck');
		$this->setHeadDataSession($session);

		return $field_value;
	}

	/**
	 * Creates random classes for the div containers
	 *
	 * @param        $session
	 * @param string $type
	 *
	 * @return string
	 */
	private function randomClassName($session, $type = 'error')
	{
		$field_value_head_session = $session->get('field_value_head', '', 'krdonationcodecheck');

		$characters = range('a', 'z');
		$class_name = $characters[mt_rand(0, count($characters) - 1)];
		$class_name_length = mt_rand(6, 12);
		$class_name .= @JUserHelper::genRandomPassword($class_name_length);

		$head_data = '<style type="text/css">div.'.$class_name.'{border-radius: 2px; padding: 5px; font-size: 120%; margin: 4px 0 4px;';

		if($type == 'error')
		{
			$head_data .= ' border: 1px solid #DD87A2; background-color: #F9CAD9;';
		}
		elseif($type == 'success')
		{
			$head_data .= ' border: 1px solid #73F26F; background-color: #CBF7CA;';
		}
		elseif($type == 'warning')
		{
			$head_data .= ' border: 1px solid #F2DB82; background-color: #F7EECA;';
		}

		$head_data .= '} @media(min-width:482px){div.'.$class_name.'{margin: 4px 0 4px -180px;}}</style>';

		$session->set('field_value_head', $field_value_head_session.$head_data, 'krdonationcodecheck');

		return $class_name;
	}

	/**
	 * Sets the CSS instructions (stored in the session) to the head
	 *
	 * @param $session
	 */
	private function setHeadDataSession($session)
	{
		// Set the style data to the head of the page
		$field_value_head_session = $session->get('field_value_head', '', 'krdonationcodecheck');

		if(!empty($field_value_head_session))
		{
			$this->addHeadData($field_value_head_session);
		}
	}

	/**
	 * Add the style data to the head
	 *
	 * @param $data
	 */
	private function addHeadData($data)
	{
		static $data_loaded = false;

		if(empty($data_loaded))
		{
			$document = JFactory::getDocument();
			$document->addCustomTag($data);

			$data_loaded = true;
		}
	}

	/**
	 * Check the entered donation code with the validation script that is located on a main and a fall back server
	 *
	 * @param $host
	 * @param $donation_code
	 *
	 * @return int|string
	 */
	private function getDonationCodeStatus($host, $donation_code)
	{
		$donation_code_check = 0;

		if(JHttpFactory::getAvailableDriver(new Registry) == false)
		{
			return -2;
		}

		if(!empty($host) AND !empty($donation_code))
		{
			// First try it with the main validation server and with HTTPS
			$url_check = 'https://check.kubik-rubik.de/donationcode/validation.php?key='.rawurlencode($donation_code).'&host='.rawurlencode($host);

			try
			{
				$donation_code_request = JHttpFactory::getHttp()->get($url_check);
			}
			catch(Exception $e)
			{
				// Try it with the fall back server and without HTTPS
				$url_check_fallback = 'http://check.kubik-rubik.eu/donationcode/validation.php?key='.rawurlencode($donation_code).'&host='.rawurlencode($host);

				try
				{
					$donation_code_request = JHttpFactory::getHttp()->get($url_check_fallback);
				}
				catch(Exception $e)
				{
					return -1;
				}
			}

			if(!empty($donation_code_request->body))
			{
				if(preg_match('@(error|access denied)@i', $donation_code_request->body))
				{
					return -1;
				}

				$donation_code_check = (int)$donation_code_request->body;
			}
		}

		return $donation_code_check;
	}

	protected function getLabel()
	{
		return;
	}
}
