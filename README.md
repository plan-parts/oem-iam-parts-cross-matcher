# OEM / IAM Parts Cross-Matcher for Laravel

**Boost your auto parts sales by finding analogs automatically.**

A Laravel package that connects your supplier price lists with the [plan.parts Aftermarket API](https://plan.parts/docs/aftermarket-oem-oe-iam) to find OEM and IAM cross-reference parts in seconds. When a customer brings an article number — even an OEM number from a vehicle manufacturer — the system searches for all known analogs across brands and shows which ones you actually have in stock.

---

## How It Works

```
Customer: "I need Bosch F026407123"
         ↓
1. Search plan.parts API → finds analogs: MANN W712/95, MAHLE OC 229, VAG 03L115562 ...
         ↓
2. Cross-reference check → gets all OEM numbers linked to these analogs
         ↓
3. Match against your uploaded price lists
         ↓
Result: "You have MANN W712/95 at $9.99 and MAHLE OC 229 at $11.25 in stock"
```

**The result:** instead of saying "we don't have Bosch", you offer the customer two alternatives from your warehouse and make the sale.

---

## Real Example

A customer asks for **Bosch F026407123** (oil filter). They may only have the OEM number **03L115562** from their Volkswagen manual. Either way, the API resolves it:

| Analog Article | Brand  | Type | Your Price |
|----------------|--------|------|-----------|
| W712/95        | MANN   | IAM  | $9.99     |
| OC 229         | MAHLE  | IAM  | $11.25    |
| WL7374         | WIX    | IAM  | $8.75     |

> This example is based on the real cross-reference network documented on [plan.parts blog](https://plan.parts/blog/aftermarket-crosses-iam-vs-oem).

---

## Requirements

- PHP **8.2+**
- Laravel **11** or **12**
- A plan.parts API token — [register here](https://plan.parts/register) (free tier available)

---

## Installation

```bash
composer require plan-parts/oem-iam-parts-cross-matcher
```

Laravel auto-discovers the service provider. Run migrations:

```bash
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag=plan-parts-config
```

---

## Configuration

Add your API token to `.env`:

```dotenv
PLAN_PARTS_TOKEN=your_token_here
```

Get your token by registering at [plan.parts](https://plan.parts/register).

Full config reference (`config/plan-parts.php`):

```php
return [
    'token'        => env('PLAN_PARTS_TOKEN', ''),
    'base_url'     => env('PLAN_PARTS_BASE_URL', 'https://plan.parts/api'),
    'timeout'      => 15,

    // Web UI route: accessible at /cross-matcher
    'route_prefix' => 'cross-matcher',
    'middleware'   => ['web'],

    'upload' => [
        'disk'        => 'local',
        'max_size_mb' => 20,
    ],

    'search' => [
        'max_analog_depth'  => 1,   // how deep to follow analog chains
        'skip_brand_lookup' => false,
    ],
];
```

---

## Web Interface

After installation, navigate to `/cross-matcher` in your browser. The UI lets you:

- **Search** by article number with optional brand filter
- **Upload** supplier price lists as CSV files
- **View** all matching offers sorted by price

![Search interface showing analog results matched against price lists]

---

## Price List CSV Format

Upload your supplier price lists in CSV format:

```csv
article,brand,price,currency,description,availability
F026407123,BOSCH,12.50,USD,Oil Filter BOSCH,in_stock
W712/95,MANN,9.99,USD,Oil Filter MANN,in_stock
OC 229,MAHLE,11.25,USD,Oil Filter MAHLE,in_stock
03L115562,VAG,24.00,USD,Oil Filter OEM VW/Audi,in_stock
```

Required columns: `article`, `brand`, `price`
Optional columns: `currency`, `description`, `availability`

A ready-to-use template is in [`storage/price-list-template.csv`](storage/price-list-template.csv).

### Import via Artisan

```bash
php artisan plan-parts:import /path/to/supplier.csv --name="Supplier A" --supplier="Supplier A Ltd"
```

---

## Programmatic Usage

### Using the Facade

```php
use PlanParts\CrossMatcher\Facades\PlanParts;

// Find analogs by article + brand
$analogs = PlanParts::analogs('F026407123', 'BOSCH');

// Without brand — searches all manufacturers
$analogs = PlanParts::analogs('F026407123');

// Get OEM cross-references for a specific article ID
$crosses = PlanParts::crosses('12345678');

// Search to discover brand for an unknown article
$results = PlanParts::search('03L115562');
```

### Using the Service

```php
use PlanParts\CrossMatcher\Services\AnalogsSearchService;

class PartsController extends Controller
{
    public function search(Request $request, AnalogsSearchService $service)
    {
        $result = $service->search(
            article: $request->input('article'),
            brand:   $request->input('brand'),
        );

        // $result['analogs']  — array of analog articles from plan.parts API
        // $result['crosses']  — OEM cross-reference numbers
        // $result['offers']   — Collection of PriceListItem models from your price lists
        // $result['error']    — string|null

        return view('parts.results', $result);
    }
}
```

### Importing Price Lists Programmatically

```php
use PlanParts\CrossMatcher\Services\PriceListImporter;

$importer  = app(PriceListImporter::class);
$priceList = $importer->import(
    filePath: storage_path('app/imports/supplier.csv'),
    name:     'Supplier A — October 2024',
    supplier: 'Supplier A Ltd',
);

echo "Imported {$priceList->items_count} items";
```

---

## API Endpoints Used

| Endpoint | Cost | Purpose |
|----------|------|---------|
| `GET /v1/aftermarket/analogs` | 2 tokens | Find OEM/IAM analogs by article + brand |
| `GET /v1/aftermarket/articles/{id}/crosses` | 2 tokens | Get OEM cross-reference numbers |
| `GET /v1/aftermarket/search` | 2 tokens | Resolve brand from unknown article number |
| `GET /v1/aftermarket/search/suggest` | 1 token | Autocomplete suggestions |

Full API documentation: [plan.parts/docs/aftermarket-oem-oe-iam](https://plan.parts/docs/aftermarket-oem-oe-iam)

---

## Why IAM vs OEM Numbers Matter

The API distinguishes between two types of article numbers:

- **IAM numbers** — issued by aftermarket brands (Bosch, MANN, Febi, etc.). Example: `F026407123` from Bosch.
- **OEM numbers** — issued by vehicle manufacturers (VW, BMW, Toyota, etc.). Example: `03L115562` from Volkswagen.

This package handles both. When you search `03L115562`, the API identifies it as an OEM number and finds all IAM analogs linked to it.

Read more: [plan.parts/blog/aftermarket-crosses-iam-vs-oem](https://plan.parts/blog/aftermarket-crosses-iam-vs-oem)

---

## Support

- Questions or issues? [Register at plan.parts](https://plan.parts/register) and open a support ticket.
- API documentation: [plan.parts/docs/aftermarket-oem-oe-iam](https://plan.parts/docs/aftermarket-oem-oe-iam)
- Website: [plan.parts](https://plan.parts)

---

## License

This package is open-source and may be used for any lawful purpose — personal, commercial, or otherwise.
See the [LICENSE](LICENSE) file for details.
