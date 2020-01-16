# IP2Location Matomo (Piwik)
[![Latest Stable Version](https://img.shields.io/packagist/v/ip2location/ip2location-piwik.svg)](https://packagist.org/packages/ip2location/ip2location-piwik)
[![Total Downloads](https://img.shields.io/packagist/dt/ip2location/ip2location-piwik.svg?style=flat-square)](https://packagist.org/packages/ip2location/ip2location-piwik)

## Description

This IP2Location plugin enables more accurate location lookup in your Matomo (Piwik) visitor log.

You need a IP2Location BIN database to make this plugin works. Database is available for free at

https://lite.ip2location.com or https://www.ip2location.com for a commercial database.



## Installation / Update

1. Login into your Matomo administrator page.
2. Go to System > Plugins.
3. Click on the **Install New Plugins** button at the bottom of the page.
4. Search for **IP2Location** from the plugin page.
5. Install and activate the plugin.
6. Upload a IP2Location BIN database to **misc** folder. 
7. Navigate to System > IP2Location page to make sure the BIN database is detected by the plugin.
8. Go to System > Geolocation.
9. Select **IP2Location** as provider and save.




## Configure settings

You can visit IP2Location Settings by selecting the menu on the left pane (under **System**). This plugin support geolocation lookup using IP2Location BIN file and web service.

**To use BIN file, please download it from the below links**
* [IP2Location LITE Database (Free)](https://lite.ip2location.com)
* [IP2Location Database (Commercial)](https://www.ip2location.com)

**To use web service, please sign up for the API key at**
* [IP2Location Web Service](https://www.ip2location.com/web-service/ip2location). Trial key available for testing.

## How to import the IP2Location BIN file for usage
You should copy the BIN file into **/var/www/html/misc** folder (for default installation). If you customize the installation path, it should be the **misc** folder inside your root folder.

If you are using Piwik docker image, then you can use below command to copy the BIN into piwik container.
```
sudo docker cp {your_local_bin_file_location} {your_piwik_container_name}:/var/www/html/misc
```

## FAQ

__How to I configure the plugin?__

Login as administrator, then go to System > IP2Location.



__Where to download IP2Location database?__

You can download IP2Location database for free at https://lite.ip2location.com or commercial version from https://www.ip2location.com



__Can I use IP2Location Web service?__

Yes, please purchase credits from https://www.ip2location.com/web-service and insert your API key in the settings page.



## Change Log

**3.1.18**

* Added better handler when IP2Location BIN database file is not loaded.



**3.1.17**

* Updated install instructions.



__3.1.14__

- Fixed class name error.



__3.1.13__

- Fixed array assignment issues.



__3.1.12__

- Fixed syntax error in PHP 7.1.



__3.1.11__

- Fixed class error.



__3.1.10__

- Minor changes and fixes.



__3.1.9__

- Update README.md



__3.1.8__

- Updated version due to licensing error.



__3.1.7__

- Updated version due to licensing error.



__3.1.6__

* Added instructions for automated IP2Location database update.



__3.1.5__

* Added screenshot and FAQ.

  

__3.1.4__

* Bugs and typos fixed.

  

__3.1.2__

* Updated version number.

  

__3.1.1__

* Removed testing data.

  


__3.1.0__

* Added IP2Location settings menu.

* Added support for IP2Location Web service.

  

__3.0.0__

- Removed compatibilities with Piwik 2.x. Version [2.3.0](https://github.com/ip2location/ip2location-piwik/releases/tag/2.3.0) is the last version supporting Piwik 2.x.

- Prevented plugin from overwrite existing location provider.

- Appeared as a separated location provider under admin area.

- Supported visitor log and live view directly in admin area.

- Database file no longer stored within plugin folder to prevent deletion/modification during updates.

- Database file is stored in `/path/to/piwik/misc/` starting this version.

  

__2.3.2__

* Fixed error when BIN file is not readable. Added backward compatible.

  

__2.3.0__
* Updated to IP2Location PHP 8.0.2 library.

  

__2.2.0__
* Added custom report to view additional information such as Time Zone, ZIP code, usage type.

  

__2.1.0__
* Updated to IP2Location 7.0.0 library

  

__2.0.0__
* First release for Piwik 2.0

  

IPv4 BIN vs IPv6 BIN
====================

Use the IPv4 BIN file if you just need to query IPv4 addresses.

Use the IPv6 BIN file if you need to query BOTH IPv4 and IPv6 addresses.


## License

GPL v3 / fair use



## Support
Website: https://www.ip2location.com
Email: support@ip2location.com
