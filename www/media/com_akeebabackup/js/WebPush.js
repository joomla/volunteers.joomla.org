/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Akeeba Web Push integration.
 *
 * Window events being dispatched:
 *
 * * onAkeebaBackupWebPushNotSubscribed when the user is not subscribed to push notifications or just unsubscribed.
 * * onAkeebaBackupWebPushSubscribed when the user is already subscribed to push notifications or just subscribed.
 */
((document, window, navigator) => {
	class AkeebaBackupWebPushIntegration
	{
		options = {};

		/**
		 * Public constructor
		 *
		 * @param {Object} options The configuration options
		 */
		constructor(options)
		{
			this.options = options;
		}

		/**
		 * Convert a Base64Url-encoded string to a byte array
		 *
		 * @param   {string} base64String The string to convert
		 * @returns {Uint8Array} The resulting array
		 * @since   9.3.1
		 * @internal
		 */
		#urlBase64ToUint8Array(base64String)
		{
			var padding = '='.repeat((4 - base64String.length % 4) % 4);
			var base64  = (base64String + padding)
				.replace(/-/g, '+')
				.replace(/_/g, '/');

			var rawData     = window.atob(base64);
			var outputArray = new Uint8Array(rawData.length);

			for (var i = 0; i < rawData.length; ++i)
			{
				outputArray[i] = rawData.charCodeAt(i);
			}
			return outputArray;
		}

		/**
		 * Disable the Web Push subscription interface.
		 *
		 * Used when the browser does not support Service Workers or the Web Push Manager object.
		 *
		 * @since  9.3.1
		 */
		disableInterface()
		{
			document.querySelector(this.options.subscribeButton).classList.add('d-none', 'disabled');
			document.querySelector(this.options.unsubscribeButton).classList.add('d-none', 'disabled');
			document.querySelector(this.options.unavailableInfo).classList.remove('d-none');
			document.getElementById('webPushDetails').classList.add('d-none');
		}

		/**
		 * Registers a Service Worker to listen to our Web Push messages.
		 *
		 * @returns {null|Promise<ServiceWorkerRegistration>}
		 * @since   9.3.1
		 */
		registerServiceWorker()
		{
			console.debug('[Web Push] Preparing to register a Service Worker.');

			const workerUri = this.options.workerUri;

			if (!workerUri)
			{
				console.error('[Web Push] The backend provided no Service Worker URI. Cannot proceed.');

				return null;
			}

			return navigator.serviceWorker
				.register(workerUri)
				.then((registration) => {
					console.log('[Web Push] Service worker successfully registered.');

					return registration;
				})
				.catch((err) => {
					console.error('[Web Push] Unable to register Service Worker.', err);

					return null;
				})
		}

		/**
		 * Ask for permission to send Web Push notifications
		 *
		 * @returns {Promise<unknown>}
		 * @since   9.3.1
		 */
		askPermission()
		{
			return new Promise((resolve, reject) => {
				console.debug('[Web Push] Requesting permission.');

				const permissionResult = Notification
					.requestPermission((result) => {
						resolve(result);
					});

				if (permissionResult)
				{
					permissionResult.then(resolve, reject);
				}
			}).then((permissionResult) => {
				if (permissionResult !== 'granted')
				{
					console.error('[Web Push] Permission for push notifications was NOT granted.');

					throw new Error("We were not granted permission.");
				}

				console.log('[Web Push] Permission for push notifications was granted.');

				return true;
			});
		}

		/**
		 * Gets the user Web Push registration and returns the subscription record.
		 *
		 * @returns {Promise<PushSubscription>|null}
		 * @since   9.3.1
		 */
		subscribeUserToPush()
		{
			console.debug('[Web Push] Preparing to subscribe the user.');

			const workerUri = this.options.workerUri;
			const publicKey = this.options.vapidKeys?.publicKey;

			if (!workerUri)
			{
				console.error('[Web Push] The backend provided no Service Worker URI. Cannot proceed.');

				return null;
			}

			if (!publicKey)
			{
				console.error('[Web Push] The backend provided no VAPID public Key. Cannot proceed.')
			}

			return navigator.serviceWorker
				.register(workerUri)
				.then((registration) => {
					const subscriberOptions = {
						userVisibleOnly:      true,
						applicationServerKey: this.#urlBase64ToUint8Array(publicKey)
					};

					return registration.pushManager.subscribe(subscriberOptions);
				})
				.then((pushSubscription) => {
					console.log('[Web Push] Received PushSubscription');
					console.debug(JSON.stringify(pushSubscription));

					return pushSubscription;
				});
		}

		/**
		 * Sends the user's Web Push subscription record to the server
		 *
		 * @param   pushSubscription
		 * @returns {null|Promise<any>}
		 * @since   9.3.1
		 */
		saveUserSubscription(pushSubscription)
		{
			console.log('[Web Push] About to send the user subscription information to the backend.');

			const subscribeUri = this.options.subscribeUri;

			if (!subscribeUri)
			{
				console.error('[Web Push] The backend provided no subscription registration URL. Cannot proceed.');

				return null;
			}

			const body = new FormData();
			body.append('subscription', JSON.stringify(pushSubscription));

			return fetch(subscribeUri, {
				method:  'POST', headers: {
					'X-CSRF-Token': Joomla.getOptions('csrf.token')
				}, body: body
			})
				.then((response) => {
					if (!response.ok)
					{
						console.error(`[Web Push] Server returned HTTP error ${response.status}. Cannot proceed.`);

						throw new Error(`Server returned HTTP error ${response.status}.`);
					}

					return response.json();
				})
				.then((responseData) => {
					if (!responseData || !responseData.success)
					{
						console.error(`[Web Push] Server returned invalid data: ${responseData}. Cannot proceed.`);

						const additionalMessage = responseData?.error ?? '';

						throw new Error('Bad response from server. ' + additionalMessage);
					}
				});
		}

		isUserSubscribed(serviceWorkerRegistration)
		{
			serviceWorkerRegistration.pushManager.getSubscription()
				.then((subscription) => {
					let myEvent;

					if (!subscription)
					{
						myEvent = new CustomEvent('onAkeebaBackupWebPushNotSubscribed');
					}
					else
					{
						myEvent = new CustomEvent('onAkeebaBackupWebPushSubscribed', {
							detail: {
								subscription
							}
						});
					}

					window.dispatchEvent(myEvent);
				})
				.catch((err) => {
					if (navigator.appVersion.includes('Safari'))
					{
						console.error(
							'[Web Push] Safari does not yet support Web Push (even if it is enabled as an experimental feature)');

						this.disableInterface();

						return;
					}

					console.error('[Web Push] Cannot get push subscription status', err);
				});
		}

		onSubscribeClick(e)
		{
			e.target.classList.add('disabled');
			var theSubscription;

			this.askPermission()
				.then((junk) => {
					return this.subscribeUserToPush();
				})
				.then((pushSubscription) => {
					if (pushSubscription === null)
					{
						return null;
					}

					theSubscription = pushSubscription;

					return this.saveUserSubscription(pushSubscription);
				})
				.then(() => {
					e.target.classList.remove('disabled');

					const myEvent = new CustomEvent('onAkeebaBackupWebPushSubscribed', {
						detail: {
							subscription: theSubscription
						}
					});

					window.dispatchEvent(myEvent);

				})
				.catch((err) => {
					e.target.classList.remove('disabled');

					Joomla.renderMessages({error: [err.message]});
				});
		}

		onUnsubscribeClick(e)
		{
			const that     = this;
			const elButton = e.target;

			function removeFromServer(subscription)
			{
				console.log('[Web Push] About to send the user unsubscription information to the backend.');

				const unsubscribeUri = that.options.unsubscribeUri;

				if (!unsubscribeUri)
				{
					console.log(that.options);
					console.error(
						'[Web Push] The backend provided no unsubscription registration URL. Cannot proceed.');

					return null;
				}

				const json = JSON.stringify(subscription);
				const body = new FormData();
				body.append('subscription', json);

				return fetch(unsubscribeUri, {
					method:  'POST', headers: {
						'X-CSRF-Token': Joomla.getOptions('csrf.token')
					}, body: body
				})
					.then((response) => {
						if (!response.ok)
						{
							console.error(`[Web Push] Server returned HTTP error ${response.status}. Cannot proceed.`);

							throw new Error(`Server returned HTTP error ${response.status}.`);
						}

						return response.json();
					})
					.then((responseData) => {
						if (!responseData || !responseData.success)
						{
							console.error(`[Web Push] Server returned invalid data: ${responseData}. Cannot proceed.`);

							throw new Error('Bad response from server.');
						}

						elButton.classList.remove('disabled');

						// Send custom event
						const myEvent = new CustomEvent('onAkeebaBackupWebPushNotSubscribed', {
							detail: {
								subscription: subscription
							}
						});

						window.dispatchEvent(myEvent);
					})
					.catch((err) => {
						elButton.classList.remove('disabled');

						Joomla.renderMessages({error: [err.message]});
					});
			}

			this.registerServiceWorker().then((reg) => {
				elButton.classList.add('disabled');

				reg.pushManager.getSubscription().then((subscription) => {
					subscription.unsubscribe().then((successful) => {
						if (!successful)
						{
							elButton.classList.remove('disabled');

							return subscription;
						}

						removeFromServer(subscription);
					})
				}).catch((err) => {
					elButton.classList.remove('disabled');

					Joomla.renderMessages({error: [err.message]});
				})
			})
		}

		/**
		 * Initialise the Web Push integration
		 *
		 * @since  9.3.1
		 */
		init()
		{
			console.debug('[Web Push] Initialising');

			if (!this.options.subscribeButton)
			{
				console.warn(
					'[Web Push] The backend provided no element to trigger the user subscription process. Abort.');
			}

			const elSubscribeButton = document.querySelector(this.options.subscribeButton);

			if (!elSubscribeButton)
			{
				console.info('[Web Push] The element to trigger the user subscription process does not exist.')

				return;
			}

			if (!('serviceWorker' in navigator) || !('PushManager' in window))
			{
				console.warn('[Web Push] The browser is incompatible with the Web Push standard.');

				this.disableInterface();

				return;
			}

			// What to do if the user is already subscribed
			window.addEventListener('onAkeebaBackupWebPushSubscribed', (e) => {
				console.debug('[Web Push] The user is already subscribed.');

				const elUnsubscribeButton = document.querySelector(this.options.unsubscribeButton);

				if (elSubscribeButton)
				{
					elSubscribeButton.classList.add('d-none', 'disabled');
				}

				if (elUnsubscribeButton)
				{
					elUnsubscribeButton.classList.remove('d-none', 'disabled');

					elUnsubscribeButton.addEventListener('click', (e) => {
						this.onUnsubscribeClick(e);
					});
				}
			});

			window.addEventListener('onAkeebaBackupWebPushNotSubscribed', (e) => {
				console.debug('[Web Push] The user is not yet subscribed.');

				const elUnsubscribeButton = document.querySelector(this.options.unsubscribeButton);

				if (elUnsubscribeButton)
				{
					elUnsubscribeButton.classList.add('d-none', 'disabled');
				}

				if (elSubscribeButton)
				{
					elSubscribeButton.classList.remove('d-none', 'disabled');

					elSubscribeButton.addEventListener('click', (e) => {
						this.onSubscribeClick(e);
					});
				}
			});


			// Register the service worker, then check the user's subscription
			this.registerServiceWorker()
				.then((serviceWorkerRegistration) => {
					this.isUserSubscribed(serviceWorkerRegistration);
				});

			return this;
		}
	}

	if (Joomla.getOptions('akeeba.webPush') ?? {})
	{
		const akeebaWebPush = new AkeebaBackupWebPushIntegration(Joomla.getOptions('com_akeebabackup.webPush') ?? {});
		akeebaWebPush.init();
	}
})(document, window, navigator);