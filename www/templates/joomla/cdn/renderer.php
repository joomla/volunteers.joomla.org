<?php
// Get the section from the request
$section = isset($_REQUEST['section']) ? $_REQUEST['section'] : 'display';
$section = filter_var($section, FILTER_SANITIZE_STRING);

$sectionDir = __DIR__ . "/layouts/$section";

if (!is_dir($sectionDir))
{
	echo 'Invalid request';

	exit(1);
}

// Get the language from the request
$language = isset($_REQUEST['language']) ? $_REQUEST['language'] : 'en-GB';
$language = filter_var($language, FILTER_SANITIZE_STRING);

// Take the language and uppercase the second section
$langParts = explode('-', $language);
$langParts[1] = strtoupper($langParts[1]);
$language = implode('-', $langParts);

// Build the filename to lookup
$includeFile = "$sectionDir/$language.$section.html";

// If the locale aware version of the file doesn't exist, fallback to English
if (!file_exists($includeFile))
{
	$includeFile = "$sectionDir/en-GB.$section.html";
}

include $includeFile;
