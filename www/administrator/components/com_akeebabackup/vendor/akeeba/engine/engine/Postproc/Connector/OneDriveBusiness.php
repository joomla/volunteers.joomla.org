<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Postproc\Connector;

defined('AKEEBAENGINE') || die();

class OneDriveBusiness extends OneDrive
{
	/**
	 * The URL of the helper script which is used to get fresh API tokens
	 */
	public const helperUrl = 'https://www.akeeba.com/oauth2/onedrivebusiness.php';

	/**
	 * Size limit for single part uploads.
	 *
	 * This is 4MB per https://docs.microsoft.com/en-us/graph/api/driveitem-put-content?view=graph-rest-1.0&tabs=http
	 */
	public const simpleUploadSizeLimit = 4194304;

	/**
	 * Item property to set the name conflict behavior
	 *
	 * @see https://docs.microsoft.com/en-us/onedrive/developer/rest-api/concepts/direct-endpoint-differences?view=odsp-graph-online#instance-annotations
	 */
	public const nameConflictBehavior = '@microsoft.graph.conflictBehavior';

	/**
	 * The root URL for the MS Graph API
	 *
	 * @see  https://docs.microsoft.com/en-us/graph/api/resources/onedrive?view=graph-rest-1.0
	 */
	protected $rootUrl = 'https://graph.microsoft.com/v1.0/me/';

	/**
	 * The Drive ID we are connecting to.
	 *
	 * @var   string|null
	 * @since 9.2.2
	 */
	private $driveId;

	private $refreshUrl = '';

	public function __construct(?string $accessToken, ?string $refreshToken, ?string $dlid = null, ?string $driveId = null, $refreshUrl = '')
	{
		parent::__construct($accessToken, $refreshToken, $dlid);

		$this->setDriveId($driveId);
		$this->refreshUrl   = $refreshUrl ?: self::helperUrl;
	}

	/**
	 * Get the raw listing of a folder
	 *
	 * @param   string  $path          The relative path of the folder to list its contents
	 * @param   string  $searchString  If set returns only items matching the search criteria
	 *
	 * @return  array  See http://onedrive.github.io/items/list.htm
	 *
	 * @see https://docs.microsoft.com/en-us/graph/api/driveitem-list-children?view=graph-rest-1.0&tabs=http
	 * @see https://docs.microsoft.com/en-us/graph/api/driveitem-search?view=graph-rest-1.0&tabs=http
	 */
	public function getRawContents($path, $searchString = null)
	{
		$collection  = empty($searchString) ? 'children' : 'search';
		$relativeUrl = $this->normalizeDrivePath($path, $collection);

		/**
		 * Search for items?
		 *
		 * @see https://docs.microsoft.com/en-us/graph/api/driveitem-search?view=graph-rest-1.0&tabs=http
		 */
		if ($searchString)
		{
			$relativeUrl .= sprintf('(q=\'%s\')', urlencode($searchString));
		}

		$result = $this->fetch('GET', $relativeUrl);

		return $result;
	}

	/**
	 * Creates a new multipart upload session and returns its upload URL
	 *
	 * @param   string  $path  Relative path in the Drive
	 *
	 * @return  string  The upload URL for the session
	 */
	public function createUploadSession($path)
	{
		$relativeUrl = $this->normalizeDrivePath($path, 'createUploadSession');

		$explicitPost = (object) [
			'item' => [
				static::nameConflictBehavior => 'replace',
				'name'                       => basename($path),
			],
		];

		$explicitPost = json_encode($explicitPost);

		$info = $this->fetch('POST', $relativeUrl, [
			'headers' => [
				'Content-Type: application/json',
			],
		], $explicitPost);

		return $info['uploadUrl'];
	}

	/**
	 * Get a signed (pre-authenticated) download URL for the remote file.
	 *
	 * Unlike the legacy consumer OneDrive API, the Microsoft Graph API does not expose a usable download URL through the
	 * redirect Location of the `/content` endpoint with an appended `access_token`: that produces a short-lived,
	 * download.aspx URL that breaks when query parameters are tacked on, returning an HTML error page instead of the
	 * file. Graph instead surfaces a fully pre-authenticated, time-limited URL as the `@microsoft.graph.downloadUrl`
	 * property of the item's metadata. We use that directly.
	 *
	 * @param   string  $path   Relative path to the Drive's root
	 * @param   bool    $retry  Should I refresh the token and retry once if the URL cannot be retrieved?
	 *
	 * @return  string  Pre-authenticated URL to download the file's contents
	 *
	 * @see https://docs.microsoft.com/en-us/graph/api/driveitem-get?view=graph-rest-1.0&tabs=http#download-a-file
	 */
	public function getSignedUrl($path, $retry = true)
	{
		$relativeUrl = $this->normalizeDrivePath($path);

		$metadata = $this->fetch('GET', $relativeUrl);

		$downloadUrl = $metadata['@microsoft.graph.downloadUrl'] ?? $metadata['@content.downloadUrl'] ?? null;

		if (!empty($downloadUrl))
		{
			return $downloadUrl;
		}

		// We failed to get a download URL. This usually means the access token has expired. Refresh it and retry once.
		if ($retry)
		{
			$this->refreshToken();

			return $this->getSignedUrl($path, false);
		}

		throw new \RuntimeException('Could not get the download URL', 500);
	}

	/**
	 * Get a list of Drives accessible to this user's account.
	 *
	 * @return  array  String indexed array of Drive ID => User readable name.
	 *
	 * @since   9.2.2
	 */
	public function getDrives()
	{
		$relativeUrl = 'drives';

		$result = $this->fetch('GET', $relativeUrl);

		$translate = function ($string)
		{
			$translation = $string;

			if (class_exists(\Joomla\CMS\Language\Text::class))
			{
				$translation = \Joomla\CMS\Language\Text::_($string);
			}

			if (class_exists(\Awf\Text\Text::class))
			{
				$translation = \Awf\Text\Text::_($string);
			}

			if ($translation === $string && substr($string, 0, 11) === 'COM_AKEEBA_')
			{
				$string = 'COM_AKEEBABACKUP_' . substr($string, 11);
			}

			if (class_exists(\Joomla\CMS\Language\Text::class))
			{
				$translation = \Joomla\CMS\Language\Text::_($string);
			}

			if (class_exists(\Awf\Text\Text::class))
			{
				$translation = \Awf\Text\Text::_($string);
			}

			return $translation;
		};

		if (empty($result) || !is_array($result) || !isset($result['value']) || !is_array($result['value']) || empty($result['value']))
		{
			return [
				'' => 'Drive (OneDrive Personal)',
			];
		}

		$keys   = array_map(function ($driveArray) {
			return $driveArray['id'] ?? '';
		}, $result['value']);
		$values = array_map(function ($driveArray) {
			$description = $driveArray['description'] ?? 'Drive';

			switch ($driveArray['driveType'] ?? 'personal')
			{
				case 'personal':
					$type = 'OneDrive Personal';
					break;

				case 'business':
					$type = 'OneDrive for Business';
					break;

				case 'documentLibrary':
					$type = 'SharePoint';
					break;
			}

			return sprintf('%s (%s)', $description, $type);
		}, $result['value']);

		return array_combine($keys, $values);
	}

	/**
	 * Set the effective Drive ID
	 *
	 * @param   string|null  $driveId
	 *
	 * @return  void
	 */
	public function setDriveId(?string $driveId)
	{
		$this->driveId = $driveId;

		$this->rootUrl = empty($this->driveId) ? 'https://graph.microsoft.com/v1.0/me/' : 'https://graph.microsoft.com/v1.0/';
	}

	protected function normalizeDrivePath($relativePath, $collection = '')
	{
		if (empty($this->driveId))
		{
			return parent::normalizeDrivePath($relativePath, $collection);
		}

		$relativePath = trim($relativePath, '/');

		if (empty($relativePath))
		{
			$path = '/drives/' . $this->driveId . '/items/root';

			if ($collection)
			{
				$path .= '/' . $collection;
			}

			return $path;
		}

		$path = '/drives/' . $this->driveId . '/items/root:/' . $relativePath;

		if ($collection)
		{
			$path = sprintf("/drives/%s/root:/%s:/%s", $this->driveId, $relativePath, $collection);
		}

		$path = str_replace(' ', '%20', $path);

		return $path;
	}

	protected function getRefreshUrl()
	{
		if (empty($this->refreshUrl))
		{
			throw new \RuntimeException('The OAuth2 Refresh URL is empty. Please check your backup profile\'s configuration.');
		}

		$refreshUrl = $this->refreshUrl .
		              (strpos($this->refreshUrl, '?') === false ? '?' : '&')
		              . 'refresh_token=' . urlencode($this->refreshToken);

		if (strpos($refreshUrl, self::helperUrl) === 0)
		{
			$refreshUrl .= '&dlid=' . urlencode($this->dlid);
		}

		return $refreshUrl;
	}
}
