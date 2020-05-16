<?php
/**
 * Template form for giving consent.
 *
 * Parameters:
 * - 'srcMetadata': Metadata/configuration for the source.
 * - 'dstMetadata': Metadata/configuration for the destination.
 * - 'yesTarget': Target URL for the yes-button. This URL will receive a POST request.
 * - 'yesData': Parameters which should be included in the yes-request.
 * - 'noTarget': Target URL for the no-button. This URL will receive a GET request.
 * - 'noData': Parameters which should be included in the no-request.
 * - 'attributes': The attributes which are about to be released.
 * - 'sppp': URL to the privacy policy of the destination, or FALSE.
 *
 * @package SimpleSAMLphp
 */

use Joomla\CMS\Language\Text;

assert(is_array($this->data["consents"]));
assert(is_string($this->data["site"]));
assert(is_string($this->data["yesTarget"]));
assert(is_array($this->data["yesData"]));
assert(is_string($this->data["noTarget"]));
assert(is_array($this->data["noData"]));
?>
<!DOCTYPE html>
<html lang="en" dir="<?php if ($this->isLanguageRTL()): ?>rtl<?php else: ?>ltr<?php endif; ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="initial-scale=1.0"/>

    <title>Login</title>
    <link href="/templates/joomla/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.joomla.org/template/css/template_3.0.0.min.css"/>
	<?php if ($this->isLanguageRTL()): ?>
        <link rel="stylesheet" type="text/css" href="/templates/joomla/css/template-rtl.min.css"/>
	<?php endif; ?>

    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans"/>
    <link href="/templates/joomla/css/custom.css" rel="stylesheet"/>
    <style>
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Open Sans', sans-serif;
        }
    </style>

    <meta name="robots" content="noindex, nofollow"/>

    <script src="/media/jui/js/jquery.min.js"></script>
    <script src="/media/jui/js/jquery-noconflict.js"></script>
    <script src="/media/jui/js/jquery-migrate.min.js"></script>
    <script src="/media/system/js/caption.js"></script>
    <script src="/media/jui/js/bootstrap.min.js"></script>
    <script src="/templates/joomla/js/template.js"></script>
    <script src="/templates/joomla/js/blockadblock.js"></script>
    <script src="/templates/joomla/js/js.cookie.js"></script>
</head>

<body class="site">
<nav class="navigation" role="navigation">
    <div id="mega-menu" class="navbar navbar-inverse navbar-fixed-top">
        <div class="navbar-inner">
            <div class="container">
                <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </a>
				<?php echo file_get_contents('https://cdn.joomla.org/template/renderer.php?section=menu&language=en-GB'); ?>
            </div>
        </div>
    </div>
</nav>

<!-- Header -->
<header class="header">
    <div class="container">
        <div class="row-fluid">
            <div class="span7">
                <h1 class="page-title">
                    <a href="/">Joomla! Identity Portal</a>
                </h1>
            </div>
            <div class="span5">
                <div class="btn-toolbar pull-right">
                    <div class="btn-group">
                        <a href="https://downloads.joomla.org/" class="btn btn-large btn-warning">Download</a>
                    </div>
                    <div class="btn-group">
                        <a href="https://launch.joomla.org" class="btn btn-large btn-primary">Launch</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<nav class="subnav-wrapper">
    <div class="subnav">
        <div class="container">
            <ul class="nav menu nav-pills">
                <li class="current active"><a href="/">Login</a></li>
                <li><a href="/register">Register</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Body -->
<div class="body">
    <div class="container">
        <div class="row-fluid">
            <main id="content" class="span12">
                <div class="page-header">
                    <h1>Consent Agreement</h1>
                </div>
                <!-- Begin Content -->
				<?php if ($this->data['message']): ?>
                    <h2><?php echo $this->data['message']; ?></h2>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($this->data['yesTarget']); ?>">

					<?php foreach ($this->data['consents'] as $consent) : ?>
						<?php $fields = json_decode($consent->allowedfields); ?>

                        <div class="control-group">
                            <div class="controls">
                                <label class="checkbox">
                                    <input type="checkbox" name="consent[<?php echo $consent->id; ?>]" value="1" required="required"> <h3><?php echo $consent->title; ?></h3>
                                </label>
                            </div>
                        </div>

                        <ul>
                            <?php foreach ($fields as $field): ?>
                                <li>
                                    <?php echo Text::_('COM_IDENTITY_DATA_' . $field->name); ?>
                                    <p class="form-text muted">
                                        <?php echo $field->description; ?>
                                    </p>
                                </li>
                            <?php endforeach; ?>
                        </ul>

					<?php endforeach; ?>

					<?php foreach ($this->data['yesData'] as $name => $value) : ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>"/>
					<?php endforeach; ?>

                    <button type="submit" name="yes" class="btn btn-large btn-block btn-primary" id="yesbutton">
						<?php echo htmlspecialchars($this->t('{consent:consent:yes}')) ?>
                    </button>
                </form>

                <form action="<?php echo htmlspecialchars($this->data['noTarget']); ?>" method="get">
					<?php foreach ($this->data['noData'] as $name => $value)    : ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>"/>
					<?php endforeach; ?>

                    <button type="submit" class="btn btn-large btn-block" name="no" id="nobutton">
						<?php echo htmlspecialchars($this->t('{consent:consent:no}')) ?>
                    </button>
                </form>
                <!-- End Content -->
            </main>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer center">
    <div class="container">
        <hr/>
		<?php $footer = file_get_contents('https://cdn.joomla.org/template/renderer.php?section=footer&language=en-GB');

		// Replace the placeholders and return the result
		echo strtr(
			$footer,
			['%reportroute%' => '#',
			 '%loginroute%'  => '/',
			 '%logintext%'   => '',
			 '%currentyear%' => date('Y'),]
		); ?>
    </div>
</footer>

</body>
</html>
