#!/bin/sh
service cron start
/usr/sbin/apache2ctl -D FOREGROUND
