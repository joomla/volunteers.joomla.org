<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector;

use Exception;
use RuntimeException;

class OneDriveBusiness extends OneDrive
{
	/**
	 * The URL of the helper script which is used to get fresh API tokens
	 */
	const helperUrl = 'https://www.akeebabackup.com/oauth2/onedrivebusiness.php';

	/**
	 * The root URL for the MS Graph API
	 *
	 * @see  https://docs.microsoft.com/en-us/graph/api/resources/onedrive?view=graph-rest-1.0
	 */
	protected $rootUrl = 'https://graph.microsoft.com/v1.0/me/';

	/**
	 * Size limit for single part uploads.
	 *
	 * This is 4MB per https://docs.microsoft.com/en-us/graph/api/driveitem-put-content?view=graph-rest-1.0&tabs=http
	 */
	const simpleUploadSizeLimit = 4194304;

	/**
	 * Item property to set the name conflict behavior
	 *
	 * @see https://docs.microsoft.com/en-us/onedrive/developer/rest-api/concepts/direct-endpoint-differences?view=odsp-graph-online#instance-annotations
	 */
	const nameConflictBehavior = '@microsoft.graph.conflictBehavior';

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

		/**
		 * Order the results by name, ascending
		 *
		 * @see https://docs.microsoft.com/en-us/graph/query-parameters#orderby-parameter
		 */
		$queryParams = [
			'$orderby' => 'displayName asc',
		];

		$result = $this->fetch('GET', $relativeUrl . '?' . http_build_query($queryParams));

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

}
