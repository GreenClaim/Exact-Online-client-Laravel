# Exact Online client for Laravel
A Laravel library to consume the Exact Online API.

## Resources
The architecture of the resources is inspired by Laravel's Eloquent models.

### Install
````
composer require greenclaim/exact-online-client-laravel "dev-master"
````

### Publish
````
php artisan vendor:publish --provider="Yource\ExactOnlineClient\ExactOnlineClientServiceProvider"
````

### Finding a single resource by its GUID
````
use Yource\ExactOnlineClient\Resources\BankEntry;

$bankEntry = BankEntry::find('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
````