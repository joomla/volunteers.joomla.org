<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @var \Akeeba\Component\AkeebaBackup\Site\View\Oauth2\RawView $this
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;

$doc = \Joomla\CMS\Factory::getApplication();
$doc->setHeader('Pragma', 'public');
$doc->setHeader('Expires', '0');
$doc->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
$doc->setHeader('Cache-Control', 'public');
$this->getDocument()->setMimeEncoding('text/html');

$title = Text::sprintf('COM_AKEEBABACKUP_OAUTH2_TITLE', $this->provider->getEngineNameForHumans());


?>
<html lang="<?= $this->getLanguage()->getLanguage() ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= Text::sprintf('COM_AKEEBABACKUP_OAUTH2_TITLE', $this->provider->getEngineNameForHumans()) ?></title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
	      rel="stylesheet"
	      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
	      crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
	        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
	        defer
	        crossorigin="anonymous"></script>
</head>
<body>

<div class="card m-2">
	<div class="card-body">
		<h1>
			<?= Text::sprintf('COM_AKEEBABACKUP_OAUTH2_AUTH_ALMOST_COMPLETE', $this->provider->getEngineNameForHumans()) ?>
		</h1>
		<p>
			<?= Text::_('COM_AKEEBABACKUP_OAUTH2_AUTH_COPY') ?>
		</p>
		<p>
			<strong><?= Text::_('COM_AKEEBABACKUP_OAUTH2_ACCESS') ?></strong><br/>
			<code><?= $this->escape($this->tokens['accessToken']) ?></code><br/>
			<strong><?= Text::_('COM_AKEEBABACKUP_OAUTH2_REFRESH') ?></strong><br/>
			<code><?= $this->escape($this->tokens['refreshToken']) ?></code><br/><br/>
		</p>
	</div>
</div>

</body>
</html>
