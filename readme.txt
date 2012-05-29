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

Additional information can be found in original article http://heximal.ru/blog/en/coding/itunesconnect-scraper/

CHANGELOG
=========
ver. 1.0 (26-Apr-2012)
initial

ver. 1.1 (29-May-2012)
Changes in sales.php and itc_scraper.php due to some modifications made by Apple in itunesconnect.apple.com ajax machanisms which 
has broken sales reports scraping