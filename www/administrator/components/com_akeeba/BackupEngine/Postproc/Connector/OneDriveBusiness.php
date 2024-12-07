<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
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

	public function __construct(?string $accessToken, ?string $refreshToken, ?string $dlid = null, ?string $driveId = null)
	{
		parent::__construct($accessToken, $refreshToken, $dlid);

		$this->setDriveId($driveId);
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


}
