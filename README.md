License

   This program is licensed under the terms of GNU Public License. You can
   read it at

   http://www.gnu.org/copyleft/gpl.html#SEC1

   or you can find a file LICENSE in this directory with a plain text copy of
   it.

Thanks

   This package would be impossible if it weren't for these software project

     * 

       PHP and PHP's PEAR classes - a marvelous scripting language
       (http://www.php.net/)

     * 

       PHP-Diagram class - simple diagramming class
       (http://www.tuxxland.de/php/cdiagram)

What this program does:

     * 

       Shows the health of a license server or a group of them

     * 

       Check which licenses are being used and who is currently using them

     * 

       Get a listing of licenses, their expiration days and number of days to
       expiration

     * 

       E-mail alert of licenses that will expire within certain time period
       ie. within next 10 days.

     * 

       Monitors server utilization

Warning:

   There is no warranty on this package. I wrote this scripts to help myself
   keep tabs on my FlexLM servers. I am not a FlexLM developer and base my
   scripts on using the publicly available commands such as lmstat, lmdiag so
   this may not work for you. I do most of my testing using FlexLM server
   version 8.0d under RedHat Linux.

   Please do not run these scripts on a publicly available Internet server
   because phplicensewatcher has not been audited to make sure it is secure.
   It likely isn't. You have been warned.

Limitations:

   Currently only FlexLM servers are supported but in the future a wider
   array of license servers may be supported.

   Requirements:

     * 

       PHP enabled web server (most Linux distributions come with Apache and
       PHP installed)

     * 

       FlexLM lmstat/lmutil/lmdiag binaries for the OS you are running the
       web server on. Latest FlexLM can be obtained at
       http://www.globetrotter.com/flexlm/lmgrd.shtml#unixdownload

          You don't have to run this on the same machine that you are running
          the web server on. My license server runs on Solaris and I use a
          Linux box with Linux lmstat binary to query it remotely.

Basic Install process

   I assume you already unpacked this archive to a directory ie.
   phplicensewatcher. All you have to do now is modify the values in
   config.php. Config.php contains comments on and then point your browser to

   http://your.host.com/phplicensewatcher/

   or other location where you installed it. This will enable only basic
   options such as displaying the list of features, current usage. If you
   need options such as email alerts you have to follow the extended install
   process

Extended Install process

   I consider all of the extended install options to be for Administrators
   only. To access them please go to

   http://your.host.com/phplicensewatcher/admin.php

  License Alerts e-mail

   This is probably the most important part of managing licenses :-). Being
   notified when licenses are due to expire. E-mails come as HTML mail since
   I wanted to use tables and colors.

    Installation

   The way I use it is to run license_alert.php report every night at 2 a.m.
   This report will query all specified license servers and figure out which
   licenses are due to expire. There are two ways to run license_alert.php
   script. You can use PHP interpreter directly if you have it installed in
   on your machine ie. RedHat distributes php interpreter as php-*.rpm
   packages. You can then have one of the following entries in your crontab

 0 2 * * * php /var/www/html/phplicensewatcher/license_alert.php >> /dev/null

   The other way to invoke it is to use wget which is a web retrieval tool.
   You can also use curl if you prefer that

 0 2 * * * wget -O - http://your.apachehost.com/phplicensewatcher/license_alert.php >> /dev/null

  License utilization

   In order to evaluate license utilization at your site you need to start
   collecting data usage statistics. PHPLicensewatcher does this by querying
   the license server periodically and storing the data in a SQL database.
   For example I have a cron job that queries the FlexLM server every 15
   minutes.

   In the config.php you would specify which keys you are interested in
   monitoring and that is what will show up on the Utilization page.

   Limitation: Generate graph has a number of different limitations which I
   will document at some later date. One of the biggest ones is that the
   X-scale is dependent on values being consecutive ie. it will start
   plotting values from time 0 no matter when you started recording values so
   once you install it it will appear that you started logging since
   midnight. You will have "realistic" usage only after that day. Sorry.

    Installation

   DB setup

   First you have to decide where you are going to store the database files.
   I use mysql. To create my own database as mySQL administrator I entered

 mysqladmin create licenses

   Create a user or use an existing user and then as such execute something
   along these lines

 mysql -f licenses < phplicensewatcher.sql

   This will create appropriate SQL. You should also create a database user
   ie. Username=nobody password=nopasswd. To do that type

 mysql mysql
 grant select,insert on licenses.* to nobody@"localhost" identified by 'nopasswd';

   Then edit config.php to suit your site.

   Crontab setup

   Last but not least is to set up data collection crontabs. There are two
   scripts that need to be executed ie. license_util.php and
   license_cache.php.

     * 

       License_util.php is used to get current license usage. It should be
       run periodically throughout the day ie. every 15 minutes.

     * 

       License_cache.php stores the total number of available licenses on
       particular day. This script is necessary because you may have
       temporary keys that may expire on a particular day and you want to
       capture that. It should be run once a day preferably soon after the
       midnight after which license server should invalidate all the expired
       keys.

   My crontab looks like this

 0,15,30,45 * * * * wget -O - http://your.apachehost.com/phplicensewatcher/license_util.php >> /dev/null
 15 0 * * *  wget -O - http://your.apachehost.com/phplicensewatcher/license_cache.php >> /dev/null

  License denials / per user usage

   An important metric in evaluating your licenses is license denials ie. how
   many people were denied access to a certain feature because we were out of
   licenses. To do that we have to analyze FlexLM logs. FlexLM logs are
   enabled during FlexLM start up. For example we start our FlexLM servers
   with following options

 su nobody -c '/tools/lmgrd -l /usr/tmp/27000-at-licenserv -c /tools/license/license.dat'

   This will create /usr/tmp/27000-at-licenserv which will contain
   information such as when the license has been checked out, when it was
   checked in plus any time a license has been denied ie.

 16:01:20 (daemond) DENIED: "viewer" jack@server  (Licensed number of users already reached (-4,342))

   We want to capture this information since a high number of denials would
   indicate that we should consider getting additional licenses since a lot
   of people are being denied access.

    Installation

   If you haven't set up databases for license utilization please follow DB
   setup instructions under License utilization. Phplicensewatcher.sql
   creates the table structure necessary for Denials as well.

   Crontab setup

   FlexLM logs can be very large so it may take a long time to analyze them.
   I run the analysis scripts around 2 a.m. every morning. To run them put
   something like this in your crontab

 0 2 * * *  wget -O - http://your.apachehost.com/phplicensewatcher/parselog.php >> /dev/null

   You are done now.

Questions, Comments, Accolades, Patches

   Please address any questions, comments or patches to

   Vladimir Vuksan <vuksan-php@veus.hr >
