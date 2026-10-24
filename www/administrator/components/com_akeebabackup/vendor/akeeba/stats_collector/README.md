# Akeeba Anonymous Usage Statistic Collection

This library is used to collect Joomla, WordPress, PHP, database, and software version numbers from installations of Akeeba software.

## Information for end users

### What we collect and store, and where

We only collect the versions of your CMS (Joomla, or WordPress), PHP, database server (MySQL or MariaDB), and our software, along with a randomly generated mostly-unique site identifier. This information is sent to [our statistics collection server](https://abrandnewsite.com). The site identifier is generated randomly, and is only used to deduplicate the collected information, i.e. not count your site two or more times. We cannot link the site identifier to a specific site or person.

We **NEVER** communicate any personally identifiable information (indicatively: your Download ID, email address, full name etc) to the usage statistics collection server. 

The IP addresses of the requests made to our statistics collection server are stored for 12 to 24 months for server security and legal compliance reasons only.

### How this information is used

The information collected is aggregated into trend graphs which allow us to determine when it is meaningful and fair to drop support for older Joomla, WordPress, PHP, and database versions. 

Without this information, we would be dropping support of old versions of Joomla, WordPress, PHP, and database servers after 3 to 6 months since the date they become End of Life, regardless of how many users were still using them on their sites.

### Compliance with GDPR, CCPA, and other privacy-related legislation

We do NOT collect any personally identifiable information. The site identifier is randomly generated, and cannot be linked to a specific site, or natural person. As a result, **the collected information is truly anonymous**, satisfying the requirements of privacy-related legislation for information collection without explicit consent.

On top of making absolutely sure that we protect your privacy, we also offer you the option to opt-out of the anonymous usage statistics collection in our software.

## Information for developers

### Usage

Define the repository in `composer.json`:

```json
"repositories": [
		{
			"type": "github",
			"url": "git@github.com:akeeba/stats_collector.git"
		},
	]
```

Require the library in `composer.json`:

```json
"require": {
    "akeeba/stats_collector": "dev-main"
}
```

In your code, initialise the `StatsCollector` object with the software information and call its `conditionalSendStatistics()` method.

For example, this is how to do it for Akeeba Backup for Joomla:

```php
(new \Akeeba\UsageStats\Collector\StatsCollector(
    \Akeeba\UsageStats\Collector\Constants\SoftwareType::AB_JOOMLA_CORE,
    AKEEBABACKUP_VERSION,
    AKEEBABACKUP_PRO
))->conditionalSendStatistics();
```

The above example works for _both_ Akeeba Backup Core and Professional. The software type mutates automatically to the correct Core / Pro identifier based on the rules included prescribed in `\Akeeba\UsageStats\Collector\Constants\SoftwareType::changeCoreOrPro`.

### Adding new platforms / CMS

To support a new software platform / CMS you need to modify the following:

- Create the new platform / CMS in the Usage Stats site.
- Update `\Akeeba\UsageStats\Collector\Constants\CmsType` with the new platform / CMS ID.
- Update `\Akeeba\UsageStats\Collector\Constants\SoftwareType::getCMSType` with the new platform / CMS ID for the corresponding software.
- Create a new adapter in the `Akeeba\UsageStats\Collector\CmsInfo\Adapter` namespace to get the CMS information.
- Create a new adapter in the `Akeeba\UsageStats\Collector\CommonVariables\Adapter` namespace to get/set the common variables in persistent storage.
- Create a new adapter in the `Akeeba\UsageStats\Collector\DatabaseInfo\Adapter` namespace to collect the database information.
- Create a new adapter in the `Akeeba\UsageStats\Collector\Sender\Adapter` namespace to send an HTTP GET query.
- Create a new adapter in the `Akeeba\UsageStats\Collector\SiteUrl\Adapter` namespace to get the site's URL.

### Adding a new database type

To support a new database type you need to modify the following

- Create the new database type in the Usage Stats site.
- Update `\Akeeba\UsageStats\Collector\Constants\DatabaseType` with the new Database Type ID.
- Update the adapters in `Akeeba\UsageStats\Collector\DatabaseInfo\Adapter` to account for the new database type.

### Adding a new software type, or split a software type to Core and Pro

- Create the new software type in the Usage Stats site.
- Update `\Akeeba\UsageStats\Collector\Constants\SoftwareType` with the new Software Type ID.
- Update `\Akeeba\UsageStats\Collector\Constants\SoftwareType::getCMSType` to return the correct platform for the new software type (not needed for Standalone software).
- Update `\Akeeba\UsageStats\Collector\Constants\SoftwareType::changeCoreOrPro` if there is a Core and Pro version for the software

If the software is based on AWF you need to create a few more adapters:

- Create a new adapter in the `Akeeba\UsageStats\Collector\CommonVariables\Adapter` namespace to get/set the common variables in persistent storage.
- Create a new adapter in the `Akeeba\UsageStats\Collector\DatabaseInfo\Adapter` namespace to collect the database information.
- Create a new adapter in the `Akeeba\UsageStats\Collector\Sender\Adapter` namespace to send an HTTP GET query. 
- Create a new adapter in the `Akeeba\UsageStats\Collector\SiteUrl\Adapter` namespace to get the site's URL.
