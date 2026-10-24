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

declare(strict_types=1);

namespace Akeeba\WebPush\WebPush;

use Akeeba\WebPush\Base64Url\Base64Url;
use Joomla\CMS\Uri\Uri;
use Joomla\Http\Http as HttpClient;
use Joomla\Http\HttpFactory;
use Joomla\Http\Response;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\StreamFactory;
use function count;

/**
 * This class is a derivative work based on the WebPush library by Louis Lagrange. It has been modified to only use
 * dependencies shipped with Joomla itself and must not be confused with the original work.
 *
 * You can find the original code at https://github.com/web-push-libs
 *
 * The original code came with the following copyright notice:
 *
 * =====================================================================================================================
 *
 * This file is part of the WebPush library.
 *
 * (c) Louis Lagrange <lagrange.louis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE-LAGRANGE.txt
 * file that was distributed with this source code.
 *
 * =====================================================================================================================
 */
class WebPush
{
	/**
	 * @var array
	 */
	protected $auth;

	/**
	 * @var int Automatic padding of payloads, if disabled, trade security for bandwidth
	 */
	protected $automaticPadding = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;

	/**
	 * @var HttpClient
	 */
	protected $client;

	/**
	 * @var array Default options : TTL, urgency, topic, batchSize
	 */
	protected $defaultOptions;

	/**
	 * @var null|array Array of array of Notifications
	 */
	protected $notifications;

	/**
	 * @var bool Reuse VAPID headers in the same flush session to improve performance
	 */
	protected $reuseVAPIDHeaders = false;

	/**
	 * @var array Dictionary for VAPID headers cache
	 */
	protected $vapidHeaders = [];

	/**
	 * WebPush constructor.
	 *
	 * @param   array     $auth            Some servers needs authentication
	 * @param   array     $defaultOptions  TTL, urgency, topic, batchSize
	 * @param   int|null  $timeout         Timeout of POST request
	 *
	 * @throws \ErrorException
	 */
	public function __construct(
		array $auth = [], array $defaultOptions = [], ?int $timeout = 30, array $clientOptions = []
	)
	{
		$extensions = [
			'curl'     => '[WebPush] curl extension is not loaded but is required. You can fix this in your php.ini.',
			'mbstring' => '[WebPush] mbstring extension is not loaded but is required for sending push notifications with payload or for VAPID authentication. You can fix this in your php.ini.',
			'openssl'  => '[WebPush] openssl extension is not loaded but is required for sending push notifications with payload or for VAPID authentication. You can fix this in your php.ini.',
		];
		$phpVersion = phpversion();
		if ($phpVersion && version_compare($phpVersion, '7.3.0', '<'))
		{
			$extensions['gmp'] = '[WebPush] gmp extension is not loaded but is required for sending push notifications with payload or for VAPID authentication. You can fix this in your php.ini.';
		}
		foreach ($extensions as $extension => $message)
		{
			if (!extension_loaded($extension))
			{
				trigger_error($message, E_USER_WARNING);
			}
		}

		if (ini_get('mbstring.func_overload') >= 2)
		{
			trigger_error(
				"[WebPush] mbstring.func_overload is enabled for str* functions. You must disable it if you want to send push notifications with payload or use VAPID. You can fix this in your php.ini.",
				E_USER_NOTICE
			);
		}

		if (isset($auth['VAPID']))
		{
			$auth['VAPID'] = VAPID::validate($auth['VAPID']);
		}

		$this->auth = $auth;

		$this->setDefaultOptions($defaultOptions);

		if (!array_key_exists('timeout', $clientOptions) && isset($timeout))
		{
			$clientOptions['timeout'] = $timeout;
		}

		$this->client = (new HttpFactory())->getHttp($clientOptions);
	}

	public function countPendingNotifications(): int
	{
		return null !== $this->notifications ? count($this->notifications) : 0;
	}

	/**
	 * Flush notifications. Triggers the requests.
	 *
	 * @param   null|int  $batchSize  Defaults the value defined in defaultOptions during instantiation (which defaults
	 *                                to 1000).
	 *
	 * @return \Generator|array<MessageSentReport|null>
	 * @throws \ErrorException
	 */
	public function flush(?int $batchSize = null): \Generator
	{
		if (empty($this->notifications))
		{
			yield from [];

			return;
		}

		if (null === $batchSize)
		{
			$batchSize = $this->defaultOptions['batchSize'];
		}

		$batches = array_chunk($this->notifications, $batchSize);

		// reset queue
		$this->notifications = [];

		foreach ($batches as $batch)
		{
			// for each endpoint server type
			$requests = $this->prepare($batch);

			foreach ($requests as $request)
			{
				try
				{
					// So, this SHOULD work, but it doesn't because of a Joomla Framework bug. HARD MODE ENGAGED.
					//$response = $this->client->sendRequest($request);

					$httpMethod = strtolower($request->getMethod());

					$headers = array_map(
						function ($values) {
							if (!is_array($values))
							{
								return $values;
							}

							return implode(' ', $values);
						},
						$request->getHeaders()
					);

					$timeout = $this->client->getOption('timeout', 10);

					switch ($httpMethod)
					{
						case 'options':
						case 'head':
						case 'get':
						case 'trace':
						default:
							/** @var Response $response */
							$response = $this->client->{$httpMethod}(new Uri($request->getUri()), $headers, $timeout);
							break;

						case 'post':
						case 'put':
						case 'delete':
						case 'patch':
							/** @var Response $response */
							$response = $this->client->{$httpMethod}(
								new Uri($request->getUri()), $request->getBody()->getContents(), $headers, $timeout
							);
							break;
					}

					$success = $response->getStatusCode() >= 200 && $response->getStatusCode() < 400;

					if ($success)
					{
						$reason = 'OK';
					}
					else
					{
						$body   = $response->getBody();
						$body   = $body ? (string) $body : null;
						$reason = strip_tags($body ?? '') ?: $response->getReasonPhrase();
					}

					yield new MessageSentReport($request, $response, $success, $reason);
				}
				catch (\Exception $e)
				{
					yield null;
				}
			}
		}

		if ($this->reuseVAPIDHeaders)
		{
			$this->vapidHeaders = [];
		}
	}

	/**
	 * @return int
	 */
	public function getAutomaticPadding()
	{
		return $this->automaticPadding;
	}

	/**
	 * @param   int|bool  $automaticPadding  Max padding length
	 *
	 * @throws \Exception
	 */
	public function setAutomaticPadding($automaticPadding): WebPush
	{
		if ($automaticPadding > Encryption::MAX_PAYLOAD_LENGTH)
		{
			throw new \Exception(
				'Automatic padding is too large. Max is ' . Encryption::MAX_PAYLOAD_LENGTH . '. Recommended max is '
				. Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH . ' for compatibility reasons (see README).'
			);
		}
		elseif ($automaticPadding < 0)
		{
			throw new \Exception('Padding length should be positive or zero.');
		}
		elseif ($automaticPadding === true)
		{
			$this->automaticPadding = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;
		}
		elseif ($automaticPadding === false)
		{
			$this->automaticPadding = 0;
		}
		else
		{
			$this->automaticPadding = $automaticPadding;
		}

		return $this;
	}

	public function getDefaultOptions(): array
	{
		return $this->defaultOptions;
	}

	/**
	 * @param   array  $defaultOptions  Keys 'TTL' (Time To Live, defaults 4 weeks), 'urgency', 'topic', 'batchSize'
	 *
	 * @return WebPush
	 */
	public function setDefaultOptions(array $defaultOptions)
	{
		$this->defaultOptions['TTL']       = $defaultOptions['TTL'] ?? 2419200;
		$this->defaultOptions['urgency']   = $defaultOptions['urgency'] ?? null;
		$this->defaultOptions['topic']     = $defaultOptions['topic'] ?? null;
		$this->defaultOptions['batchSize'] = $defaultOptions['batchSize'] ?? 1000;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getReuseVAPIDHeaders()
	{
		return $this->reuseVAPIDHeaders;
	}

	/**
	 * Reuse VAPID headers in the same flush session to improve performance
	 *
	 * @return WebPush
	 */
	public function setReuseVAPIDHeaders(bool $enabled)
	{
		$this->reuseVAPIDHeaders = $enabled;

		return $this;
	}

	public function isAutomaticPadding(): bool
	{
		return $this->automaticPadding !== 0;
	}

	/**
	 * Queue a notification. Will be sent when flush() is called.
	 *
	 * @param   string|null  $payload  If you want to send an array or object, json_encode it
	 * @param   array        $options  Array with several options tied to this notification. If not set, will use the
	 *                                 default options that you can set in the WebPush object
	 * @param   array        $auth     Use this auth details instead of what you provided when creating WebPush
	 *
	 * @throws \ErrorException
	 */
	public function queueNotification(
		SubscriptionInterface $subscription, ?string $payload = null, array $options = [], array $auth = []
	): void
	{
		if (isset($payload))
		{
			if (Utils::safeStrlen($payload) > Encryption::MAX_PAYLOAD_LENGTH)
			{
				throw new \ErrorException(
					'Size of payload must not be greater than ' . Encryption::MAX_PAYLOAD_LENGTH . ' octets.'
				);
			}

			$contentEncoding = $subscription->getContentEncoding();
			if (!$contentEncoding)
			{
				throw new \ErrorException('Subscription should have a content encoding');
			}

			$payload = Encryption::padPayload($payload, $this->automaticPadding, $contentEncoding);
		}

		if (array_key_exists('VAPID', $auth))
		{
			$auth['VAPID'] = VAPID::validate($auth['VAPID']);
		}

		$this->notifications[] = new Notification($subscription, $payload, $options, $auth);
	}

	/**
	 * @param   string|null  $payload  If you want to send an array or object, json_encode it
	 * @param   array        $options  Array with several options tied to this notification. If not set, will use the
	 *                                 default options that you can set in the WebPush object
	 * @param   array        $auth     Use this auth details instead of what you provided when creating WebPush
	 *
	 * @throws \ErrorException
	 */
	public function sendOneNotification(
		SubscriptionInterface $subscription, ?string $payload = null, array $options = [], array $auth = []
	): ?MessageSentReport
	{
		$this->queueNotification($subscription, $payload, $options, $auth);

		return $this->flush()->current();
	}

	/**
	 * @return array
	 * @throws \ErrorException
	 */
	protected function getVAPIDHeaders(string $audience, string $contentEncoding, array $vapid)
	{
		$vapidHeaders = null;

		$cache_key = null;
		if ($this->reuseVAPIDHeaders)
		{
			$cache_key = implode('#', [$audience, $contentEncoding, crc32(serialize($vapid))]);
			if (array_key_exists($cache_key, $this->vapidHeaders))
			{
				$vapidHeaders = $this->vapidHeaders[$cache_key];
			}
		}

		if (!$vapidHeaders)
		{
			$vapidHeaders = VAPID::getVapidHeaders(
				$audience, $vapid['subject'], $vapid['publicKey'], $vapid['privateKey'], $contentEncoding
			);
		}

		if ($this->reuseVAPIDHeaders)
		{
			$this->vapidHeaders[$cache_key] = $vapidHeaders;
		}

		return $vapidHeaders;
	}

	/**
	 * @return Request[]
	 * @throws \ErrorException
	 *
	 */
	protected function prepare(array $notifications): array
	{
		$requests = [];
		foreach ($notifications as $notification)
		{
			\assert($notification instanceof Notification);
			$subscription    = $notification->getSubscription();
			$endpoint        = $subscription->getEndpoint();
			$userPublicKey   = $subscription->getPublicKey();
			$userAuthToken   = $subscription->getAuthToken();
			$contentEncoding = $subscription->getContentEncoding();
			$payload         = $notification->getPayload();
			$options         = $notification->getOptions($this->getDefaultOptions());
			$auth            = $notification->getAuth($this->auth);

			if (!empty($payload) && !empty($userPublicKey) && !empty($userAuthToken))
			{
				if (!$contentEncoding)
				{
					throw new \ErrorException('Subscription should have a content encoding');
				}

				$encrypted      = Encryption::encrypt($payload, $userPublicKey, $userAuthToken, $contentEncoding);
				$cipherText     = $encrypted['cipherText'];
				$salt           = $encrypted['salt'];
				$localPublicKey = $encrypted['localPublicKey'];

				$headers = [
					'Content-Type'     => 'application/octet-stream',
					'Content-Encoding' => $contentEncoding,
				];

				if ($contentEncoding === "aesgcm")
				{
					$headers['Encryption'] = 'salt=' . Base64Url::encode($salt);
					$headers['Crypto-Key'] = 'dh=' . Base64Url::encode($localPublicKey);
				}

				$encryptionContentCodingHeader = Encryption::getContentCodingHeader(
					$salt, $localPublicKey, $contentEncoding
				);
				$content                       = $encryptionContentCodingHeader . $cipherText;

				$headers['Content-Length'] = (string) Utils::safeStrlen($content);
			}
			else
			{
				$headers = [
					'Content-Length' => '0',
				];

				$content = '';
			}

			$headers['TTL'] = $options['TTL'];

			if (isset($options['urgency']))
			{
				$headers['Urgency'] = $options['urgency'];
			}

			if (isset($options['topic']))
			{
				$headers['Topic'] = $options['topic'];
			}

			if (array_key_exists('VAPID', $auth) && $contentEncoding)
			{
				$audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
				if (!parse_url($audience))
				{
					throw new \ErrorException('Audience "' . $audience . '"" could not be generated.');
				}

				$vapidHeaders = $this->getVAPIDHeaders($audience, $contentEncoding, $auth['VAPID']);

				$headers['Authorization'] = $vapidHeaders['Authorization'];

				if ($contentEncoding === 'aesgcm')
				{
					if (array_key_exists('Crypto-Key', $headers))
					{
						$headers['Crypto-Key'] .= ';' . $vapidHeaders['Crypto-Key'];
					}
					else
					{
						$headers['Crypto-Key'] = $vapidHeaders['Crypto-Key'];
					}
				}
			}

			$streamFactory = new StreamFactory();

			$requests[] = new Request($endpoint, 'POST', $streamFactory->createStream($content), $headers);
		}

		return $requests;
	}
}
