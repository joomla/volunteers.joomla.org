<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\View\Oauth2;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Site\Model\OAuth2\ProviderInterface;
use Akeeba\Component\AkeebaBackup\Site\Model\OAuth2\TokenResponse;
use Exception;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class RawView extends BaseHtmlView
{
	public ?ProviderInterface $provider = null;

	public ?TokenResponse $tokens = null;

	public ?Exception $exception = null;

	public ?string $step1url = null;
}