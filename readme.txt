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

ver. 1.2 (27-Jul-2012)
Apple has made certain modification in iTunesConnect web-interface so the parser has become invalid. One regular expression is fixed in 
itc_scraper.php

ver. 1.3 (04-Aug-2012)
Apple has make modifications in 'AppleID or password specified incorrect' which led to even locking itunes account due to series of
failed sign in attempts.
Sales report agregation routine was modified to avoid multiple running after each login processing. Now it runs only once in the end 
of all logins processed
Intorodicing new config parameter: $scrape_sales_at
It doesn't make sense to run sales reports scraping on each script launch if you're launching it more than once a day.
index.php and itc.css was modified. 'Sort by' control was added. 'Sales last updated' was added