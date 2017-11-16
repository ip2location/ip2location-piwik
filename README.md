# Piwik IP2Location Plugin
[![Latest Stable Version](https://img.shields.io/packagist/v/ip2location/ip2location-piwik.svg)](https://packagist.org/packages/ip2location/ip2location-piwik)
[![Total Downloads](https://img.shields.io/packagist/dt/ip2location/ip2location-piwik.svg?style=flat-square)](https://packagist.org/packages/ip2location/ip2location-piwik)

## Description

This IP2Location plugin enables more accurate location lookup in your Piwik visitor log.

You need a IP2Location BIN database to make this plugin works. Database is available for free at

http://lite.ip2location.com or http://www.ip2location.com for a commercial database.



## Installation / Update

See http://piwik.org/faq/plugins/#faq_21

## FAQ

__How to I configure the plugin?__

Login as administrator, then go to System > IP2Location.



__Where to download IP2Location database?__

You can download IP2Location database for free at http://lite.ip2location.com or commercial version from http://www.ip2location.com



__Can I use IP2Location Web service?__

Yes, please purchase credits from http://www.ip2location.com/web-service and insert your API key in the settings page.



## Change Log

__3.1.6__

* Added instructions for automated IP2Location database update.



__3.1.5__

* Added screenshot and FAQ.

  ​

__3.1.4__

* Bugs and typos fixed.

  ​

__3.1.2__

* Updated version number.

  ​

__3.1.1__

* Removed testing data.

  ​


__3.1.0__

* Added IP2Location settings menu.

* Added support for IP2Location Web service.

  ​

__3.0.0__

- Removed compatibilities with Piwik 2.x. Version [2.3.0](https://github.com/ip2location/ip2location-piwik/releases/tag/2.3.0) is the last version supporting Piwik 2.x.

- Prevented plugin from overwrite existing location provider.

- Appeared as a separated location provider under admin area.

- Supported visitor log and live view directly in admin area.

- Database file no longer stored within plugin folder to prevent deletion/modification during updates.

- Database file is stored in `/path/to/piwik/misc/` starting this version.

  ​

__2.3.2__

* Fixed error when BIN file is not readable. Added backward compatible.

  ​

__2.3.0__
* Updated to IP2Location PHP 8.0.2 library.

  ​

__2.2.0__
* Added custom report to view additional information such as Time Zone, ZIP code, usage type.

  ​

__2.1.0__
* Updated to IP2Location 7.0.0 library

  ​

__2.0.0__
* First release for Piwik 2.0

  ​



## License

GPL v3 / fair use



## Support

Please contact us at support@ip2location.com
