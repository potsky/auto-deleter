# auto-deleter

> PHP script to weekly delete files on a shared server

## Requirements

- PHP >= 5.6.4
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension

## Installation

1. Copy the `.env.example` file at root to `.env`
2. Change the `.env` according to your settings
3. Set this crontab on your server `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

## Slack

Create a webhook at this address <https://my.slack.com/services/new/incoming-webhook>