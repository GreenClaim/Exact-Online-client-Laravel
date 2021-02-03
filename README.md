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

### Each page
Exact Online returns a maximum of 60 results. With `eachaPage()` you can iterate over all the pages and execute a
callback to deal with the results per page. Similarly to `chunk()` for Laravel queries except that there is no count
parameter you always get a maximum of 60 results.

Keep in mind that for potentially large queries Exact Online requires you to make a subselection of the field you want
returned. This is possible with `select()`.
````
use Yource\ExactOnlineClient\Resources\GeneralJournalEntry;

GeneralJournalEntry::select([
    'EntryID',
    'EntryNumber',
    'JournalCode',
    'Currency',
    'FinancialPeriod',
    'FinancialYear',
    'StatusDescription',
    'JournalCode',
    'Created',
    'Modified',
])
->eachPage(function ($entries) {
    foreach ($entries as $entry) {
        // Handle the resuls. Eg. store them in the database.
    }
});
````

### Webhooks
Exact Online provides the possibility to work with webhooks (https://support.exactonline.com/community/s/knowledge-base#All-All-DNO-Content-webhooksc). You will have to create a POST endpoint where Exact Online can do a POST request to that will handle syncing the data. Exact Online will sent a POST request for both created as updated resources. See the Exact Online documentation for more info on the request that will be sent: https://support.exactonline.com/community/s/knowledge-base#All-All-DNO-Content-webhookstut.

You can verify the request by using the `Yource\ExactOnlineClient\Http\Middlewares\ExactOnlineWebhookAuthentication` middleware provided by this package.

Next you will need to subscribe to a "topic". This can be done like creating any other resource using the `WebhookSubscriptions` resources provide:
````
$webhookSubscription = new \Yource\ExactOnlineClient\Resources\WebhookSubscriptions([
        'CallbackURL' => 'https://gca.yource.com/webhooks/exactOnline/generalJournalEntries',
        'Topic'       => 'GeneralJournalEntries',
    ]);

$webhookSubscription->create();
````
Note: Exact online will do a verification request without any body to the callback URL defined. So make sure the endpoint exists. The `ExactOnlineWebhookAuthentication` middleware will provide Exact Online with the required 200 respons for the verification request.
