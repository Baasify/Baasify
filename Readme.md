## Baasify [![Build Status](https://travis-ci.org/Baasify/Baasify.svg?branch=master)](https://travis-ci.org/Baasify/Baasify)

Baasify is an open source backend-as-a-service in its early stage. Built on [Lumen](http://lumen.laravel.com/).
 
#### Current Functions
 
* Content Management.
* File Management.
* Users Management.
* Push Notifications.

#### Planned Functions and Features

* Database Management.
* Analytics.
* SDKs for both iOS and Android.
* Web interface.

## How to build Baasify

1. Rename .env.example to .env and configure your database settings.
2. Set your app key.
3. Go to your Baasify directory and type:
 
```bash
composer update
php artisan migrate
php artisan db:seed
```

## How to use Baasify

Baasify API is fully documented at [Baasify API Documentation](http://baasify.org/docs).
 
Still need help? You are welcome to join [#baasify on freenode](http://webchat.freenode.net/?channels=baasify) 
and ask for help there.

## Helping Baasify

### I found a bug

If you found a bug, then search existing open issues first. If it's not there, file an issue and include the steps required
to reproduce the problem.

### I have a new suggestion

First, we are grateful for your caring about Baasify. 
If no one else [suggested](https://github.com/Baasify/Baasify/labels/enhancement) it before here, 
feel free to file it as an issue and we will take it from there.

## Credits

Baasify is built on [Lumen](http://lumen.laravel.com/) and
use [Notificato](https://github.com/mac-cain13/notificato) to take care of Apple Push Notification Service.

## License

Baasify is an open source software licensed under the [MIT license](http://opensource.org/licenses/MIT)
