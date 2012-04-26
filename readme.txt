PHP iTunesConnect scraper
-------------------------

PHP ITC scraper is the tool for populating data from http://iTunesConnect.apple.com portal regarding apple developer
applications such as app state and sales reports.

How to use?
All you have to do is just run itc_scraper.php script.
But before create database objects with db_itc.sql (this is MySQL database dump file)
and make some changes in config.php
Probably it makes sense to make itc_scraper.php run by cron.

After succesfull scraping there must appear directory with iTunesConnect meta data which can be vizualized with index.php script
by accessing it via any popular web browser. 


CHANGELOG
=========
ver. 1.0 (26-Apr-2012)
initial