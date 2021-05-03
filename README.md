## License

   This program is licensed under the terms of GNU Public License. You can read it at http://www.gnu.org/copyleft/gpl.html#SEC1 or you can find a file LICENSE in this directory with a plain text copy of it.

## What this program does

* Shows the health of a license server or a group of them
* Check which licenses are being used and who is currently using them
* Get a listing of licenses, their expiration days and number of days to expiration
* E-mail alert of licenses that will expire within certain time period ie. within next 10 days.
* Monitors server utilization
* Provides usage charts

## Warning

   There is no warranty on this package. I wrote this scripts to help myself
   keep tabs on my FlexLM servers. I am not a FlexLM developer and base my
   scripts on using the publicly available commands such as lmstat, lmdiag so
   this may not work for you. I do most of my testing using FlexLM server
   version 8.0d under RedHat Linux.

   Please do not run these scripts on a publicly available Internet server
   because phplicensewatcher has not been audited to make sure it is secure.
   It likely isn't. You have been warned.

## Limitations

   Currently only FlexLM servers are supported but in the future a wider array of license servers may be supported.

## Requirements

* 64-bit PHP enabled web server
* MySQL
* FlexLM lmstat/lmutil/lmdiag binaries for the OS you are running the web server on.

## Install process
1. Retrieve required packages for your OS/distribution:
   * Apache2
   * PHP 7.3 or higher
   * MySQL-server, MySQL-client, PHP MySQL Extension
   * You need the Linux Standard Base (LSB) to run Linux-precompiled FlexLM binaries.

   For example, using Ubuntu 20.04:
   ```
   sudo apt install apache2 php mysql-server mysql-client php-mysql lsb
   ```
2. Clone repostiory locally using git
   ```
   git clone https://github.com/mcglow2-RPI/phpLicenseWatcher.git /var/www/html/
   ```
3. Create the database
   ```
   mysqladmin create licenses
   mysql -f licenses < phplicensewatcher.sql
   ```
4. Copy "config/sample-config.php" to "./config.php" and edit it for the proper values for your setup.  Brief instructions are provided within the file as code comments.

5. Setup cron to run scheduled tasks
   ```
   0 6 * * 1 php /var/www/html/license_alert.php >> /dev/null
   0,10,20,30,40,50 * * * * php /var/www/html/license_util.php >> /dev/null
   15 0 * * 1  php /var/www/html/license_cache.php >> /dev/null
   ```
6. You should use your webservers built in capabilities to password protect your site.
7. Navigate to page `check_installation.php` to check for possible installation issues.

### Crontab details

There are there scripts that need to be executed ie. license_util.php and license_cache.php.

* License_util.php is used to get current license usage. It should be run periodically throughout the day ie. every 10 minutes.
* License_cache.php stores the total number of available licenses on particular day. This script is necessary because you may have temporary keys that may expire on a particular day and you want to capture that. It should be run once a day preferably soon after the midnight after which license server should invalidate all the expired keys.
* license_alert.php check for expiring licenses and emails admins.  I run once a week.



## Example Screenshots
![Alt text](https://github.com/rpi-dotcio/phpLicenseWatcher/raw/assets/screenshot1.png?raw=true "List of license servers")
![Alt text](https://github.com/rpi-dotcio/phpLicenseWatcher/raw/assets/screenshot2.png?raw=true "List of features and licenses in use")
![Alt text](https://github.com/rpi-dotcio/phpLicenseWatcher/raw/assets/screenshot3.png?raw=true "License usage statistics")
![Alt text](https://github.com/rpi-dotcio/phpLicenseWatcher/raw/assets/screenshot4.png?raw=true "License usage statistics")
