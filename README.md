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

### Creating a financial entry resource with lines 
When create a new financial entry make sure you keep the entry in perfect balance. This means the sum of all the
 lines needs to be exactly 0. 
````
use Yource\ExactOnlineClient\Resources\GeneralJournalEntry;
use Yource\ExactOnlineClient\Resources\GeneralJournalEntryLine;

$generalJournalEntryLine1 = new GeneralJournalEntryLine([
    'GLAccount' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'AmountFC'  => '1000',
]);
$generalJournalEntryLine2 = new GeneralJournalEntryLine([
    'GLAccount' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'AmountFC'  => '-1000',
]);

$generalJournalEntry = new GeneralJournalEntry([
    'Currency'                 => 'EUR',
    'FinancialYear'            => '2020',
    'FinancialPeriod'          => '11',
    'JournalCode'              => '95',
    'GeneralJournalEntryLines' => collect([$generalJournalEntryLine1, $generalJournalEntryLine2]),
]);
$generalJournalEntry->create();
````