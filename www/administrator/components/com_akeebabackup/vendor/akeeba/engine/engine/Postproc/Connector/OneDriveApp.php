<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
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

/**
 * OneDrive (custom Azure AD application) API connector.
 *
 * @deprecated Legacy OneDrive connector tied to a per-user custom OAuth2 application. Superseded by the
 *             OneDriveBusiness connector using the modern Microsoft Graph API. Retained only for backwards compatibility.
 */
class OneDriveApp extends OneDrive
{
	/**
	 * The URL of the helper script which is used to get fresh API tokens
	 */
	public const helperUrl = 'https://www.akeeba.com/oauth2/onedriveapp.php';

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
	protected $rootUrl = 'https://graph.microsoft.com/v1.0/me/drive/special/approot';

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
	 * Return information about the application folder
	 *
	 * @return  array  See https://learn.microsoft.com/en-us/graph/api/drive-get-specialfolder?view=graph-rest-1.0&tabs=http
	 */
	public function getDriveInformation()
	{
		return $this->fetch('GET', '');
	}

	protected function normalizeDrivePath($relativePath, $collection = '')
	{
		$path = trim($relativePath, '/');

		if (!empty($path))
		{
			$path = ':/' . $path;
		}

		if (!empty($collection))
		{
			if ($path !== ':/')
			{
				$path .= ':/';
			}

			$path .= $collection;
		}

		return $path;
	}
}