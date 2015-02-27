# Simple PHP Ping Checking Service

Pings a configured list of IPv4 addresses and alerts a list of notification email addresses if a ping fails.

## Setup

Very simple to setup using the `config.yml` which includes configurations for SMTP auth to send alert emails.

Application logs messages inside the `log/` folder. Logs rotate on a daily basis and may need a cleanup script added.

## Running

Should run on most linux servers with PHP installed (may need additional php-* packages - please submit an issue if you find required packages).

To run simply enter:

`php index.php`

To stop:

`ctrl + c`

To run in the background:

`nohup php index.php &`

## Notes

Pull requests are very welcome, however it may take some time for us to test and accept them.

This library was put together very quickly so may contain unexpected functionality (bugs), please raise issues if you find any!
