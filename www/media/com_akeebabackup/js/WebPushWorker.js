/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

self.addEventListener('push', (event) => {
	const payload   = event.data ? event.data.json() : {};
	payload.options = payload?.options ?? {};

	// Keep the service worker alive until the notification is created.
	event.waitUntil(
		self.registration.showNotification(payload.title, payload.options)
	);
});