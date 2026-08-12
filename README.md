# `hatchyu/laravel-steward`

Elegant CQRS primitives, request query parsing, and native JSON:API stewardship for Laravel.

## What the package provides

- Action base classes for create/update style writes.
- Query base classes for model reads.
- Query parameter parsing for plain REST and JSON:API requests.
- Query criteria objects for search, filter, and sort definitions.
- Request validation rules for list endpoints, including JSON:API `include` and `fields[...]`.
- A thin JSON:API resource base on top of Laravel's native `Illuminate\Http\Resources\JsonApi\JsonApiResource`.
- Query-side eager-loading helpers derived from the same JSON:API resource metadata.

The package intentionally uses Laravel's built-in JSON:API resource support instead of `league/fractal`.

> [!IMPORTANT]
> **Security & Authorization Boundaries**  
> `laravel-steward` is a **CQRS data orchestration and query engine**, not an authorization or multi-tenancy boundary.
> - **Authorization**: Always perform Policy checks (`$this->authorize(...)` / `Gate::authorize()`) in your Controllers or Form Requests before passing models to Steward Actions.
> - **Tenant Scoping**: Ensure tenant-scoped global scopes or explicit query scoping (e.g., `$user->team->customers()`) are applied prior to executing Steward Queries or Actions.
> Steward assumes the model or query passed into its pipeline has already been authorized and scoped by your application.

## Installation

### Via Direct Git Repository URL

Add the repository definition to your target Laravel project's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:rajeshmk/laravel-steward.git"
    }
]
```

Then require a tagged release:

```bash
composer require hatchyu/laravel-steward:^0.1
```

### Via Local Path (For Local Development)

If developing locally alongside your application:

```json
"repositories": [
    {
        "type": "path",
        "url": "../common-packages/laravel-steward"
    }
]
```

Then run:

```bash
composer require hatchyu/laravel-steward:@dev
```

The package registers:

- `Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract`
- `Hatchyu\Steward\Queries\Contracts\QueryParamsProcessorContract`

through [StewardServiceProvider.php](src/StewardServiceProvider.php).

## Query parsing

`QueryParamsParserContract` auto-detects plain query strings vs JSON:API query format.

Plain format example:

```http
GET /customers?search=alice&filter[is_active]=1&sort=-created_at&page=2&size=25
```

Plain format also accepts `filter` as a JSON object string (unencoded / URL-encoded):

```http
# Unencoded (Readable JSON string)
GET /customers?filter={"is_active":1,"gender":"female"}

# URL-encoded (Standard HTTP client payload)
GET /customers?filter=%7B%22is_active%22%3A1%2C%22gender%22%3A%22female%22%7D
```

JSON:API format example:

```http
GET /customers?filter[search]=alice&sort=-created_at&page[number]=2&page[size]=25
```

JSON:API format also accepts `filter` as a JSON object string:

```http
# Unencoded (Readable JSON string)
GET /customers?filter={"search":"alice","is_active":1}&sort=-created_at&page[number]=2&page[size]=25

# URL-encoded (Standard HTTP client payload)
GET /customers?filter=%7B%22search%22%3A%22alice%22%2C%22is_active%22%3A1%7D&sort=-created_at&page[number]=2&page[size]=25
```

Notes:

- Bracket-style query params (`filter[field]=...`) are usually easier to read and operate.
- JSON-string filters are supported for clients that already serialize query payloads that way.

### Query Parameter Conventions

`laravel-steward` uses standard, fixed query parameter conventions across its parsers:

| Parameter | Type | Format / Example | Description |
| :--- | :--- | :--- | :--- |
| `search` | `string` | `?search=john` | Free-text search string. In JSON:API format, `?filter[search]=john` is also supported. |
| `filter` | `array\|string` | Single value: `?filter[status]=active`<br>Comma-separated: `?filter[id]=1,2,3,4,5`<br>Array list: `?filter[id][]=1&filter[id][]=2`<br>JSON string: `?filter={"status":"active"}` | Filter criteria. Supports single scalars, array lists, comma-separated strings (`1,2,3`), or JSON object strings. |
| `sort` | `string\|array` | `?sort=-created_at,name` | Comma-separated string or array. Prefix `-` denotes descending order (`desc`). |
| `include` | `string` | `?include=addresses,orders.items` | Comma-separated list of relationship inclusion paths. |
| `fields` | `array` | `?fields[customers]=name,email` | Object keyed by JSON:API resource type specifying sparse fieldsets. |
| `page[number]` | `integer` | `?page[number]=2` | Page number for offset pagination (1-indexed, minimum `1`). |
| `page[size]` | `integer` | `?page[size]=25` | Number of records per page (minimum `1`, maximum `100`). |
| `page[cursor]` | `string` | `?page[cursor]=eyJpZCI6MTB9` | Cursor token string for cursor-based pagination. |
| `size` | `integer` | `?size=25` | Top-level fallback parameter for page size. |

Convert a request into normalized query params:

```php
use Hatchyu\Steward\Requests\ListQueryRequest;

final class ListCustomersRequest extends ListQueryRequest
{
}

$params = ListCustomersRequest::from($request)->toQueryParams();
```

The normalized result is `Hatchyu\Steward\Queries\Params\QueryParams`.

It includes:

- `search`
- `filters`
- `sort`
- `includes`
- `fields`
- `page`
- `size`

For safety, list endpoints reject `include` and sparse `fields[...]` parameters unless the query or request declares them explicitly. When using `JsonApiResource`, its resource metadata supplies those allowlists automatically. Non-JSON:API queries can override `GetModelQuery::allowedIncludes()`.

## Query definitions

Use `QueryDefinition` to declare which fields can be searched, filtered, and sorted.

Simple strings work for common cases:

```php
use Hatchyu\Steward\Queries\Params\QueryDefinition;

new QueryDefinition(
    searchable: ['name', 'email'],
    filterable: ['is_active', 'gender'],
    sortable: ['name', 'created_at'],
);
```

For mapped names or custom behavior, pass criteria objects:

```php
use Hatchyu\Steward\Queries\Criteria\Filters\SimpleFilter;
use Hatchyu\Steward\Queries\Criteria\Searches\EndsWithSearch;
use Hatchyu\Steward\Queries\Criteria\Searches\PartialSearch;
use Hatchyu\Steward\Queries\Criteria\Searches\SimpleSearch;
use Hatchyu\Steward\Queries\Criteria\Searches\StartsWithSearch;
use Hatchyu\Steward\Queries\Criteria\Sorts\SimpleSort;
use Hatchyu\Steward\Queries\Params\QueryDefinition;

new QueryDefinition(
    searchable: [new PartialSearch('term', 'users.email')],
    filterable: [new SimpleFilter('status', 'users.is_active')],
    sortable: [new SimpleSort('latest', 'users.created_at')],
);
```

Additional search strategies are also available for finer control:

```php
new QueryDefinition(
    searchable: [
        new SimpleSearch('email_exact', 'email'),
        new StartsWithSearch('name_prefix', 'name'),
        new EndsWithSearch('domain_suffix', 'email'),
    ],
);
```

`PartialSearch`, `StartsWithSearch`, and `EndsWithSearch` escape SQL `LIKE` wildcard characters (`%` and `_`) before querying.

## Model queries

Use `GetModelQuery` or `FindModelQuery` for read flows.

Example:

```php
use App\Domain\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Hatchyu\Steward\Queries\Contracts\QueryParamsProcessorContract;
use Hatchyu\Steward\Queries\GetModelQuery;
use Hatchyu\Steward\Queries\Params\QueryDefinition;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Override;

final class ListCustomersQuery extends GetModelQuery
{
    public function __construct(QueryParamsProcessorContract $processor)
    {
        parent::__construct(new Customer(), $processor);
    }

    public function execute(QueryParams $params): LengthAwarePaginator
    {
        $query = $this->query();

        $this->applyQueryParams($query, $params);

        return $this->paginateByQueryParams($query, $params);
    }

    #[Override]
    protected function definition(): QueryDefinition
    {
        return new QueryDefinition(
            searchable: ['name', 'email'],
            filterable: ['is_active'],
            sortable: ['created_at'],
        );
    }

    #[Override]
    protected function jsonApiResource(): ?string
    {
        return CustomerResource::class;
    }
}
```

When `jsonApiResource()` is configured, `applyQueryParams()` will:

- validate requested `include` paths against the resource metadata
- validate sparse fieldsets against the same resource metadata
- apply `with(...)` eager-loading for those includes
- apply conservative top-level `select(...)` projection from sparse fieldsets

Projection rules:

- the model primary key is always selected
- only the primary resource type is projected at query level
- included resource fieldsets can also project eager-loaded relation queries when the relation is supported
- if a field does not map cleanly to database columns, omit it from `jsonApiFieldColumnMap()`

Relation-aware eager-load projection rules:

- the related model primary key is always selected
- relation matching keys required by Eloquent are preserved automatically
- nested includes preserve the keys needed for their next eager-load hop
- unsupported or risky relation types can fall back to default eager loading

Current conservative behavior:

- top-level projection is always supported
- eager-load projection is intended for common direct relations such as `hasOne`, `hasMany`, `belongsTo`, and morph variants
- `belongsToMany` is intentionally left conservative and falls back to the normal eager-load query

- `CreateModelAction`
- `UpdateModelAction` (supports both `execute($id, $data)` and `executeModel($model, $data)`)
- `DeleteModelAction` (supports both `execute($id)` and `executeModel($model)`)
- base abstractions for custom actions

These are designed to pair with `AbstractData` request DTOs. When you already have an instantiated model (for example, from Route Model Binding or policy authorization), call `executeModel()` to avoid an unnecessary database lookup.

## Model Finders (`FindModelQuery`)

`FindModelQuery` provides point-lookups by primary key:

- `byId(int|string $id)` / `find(int|string $id)`
- `byIdOrFail(int|string $id)` / `findOrFail(int|string $id)`
- `byIds(array $ids)` / `findMany(array $ids)`
- `byIdsOrFail(array $ids)` / `findManyOrFail(array $ids)`: ensures *all* requested primary keys exist in the database, throwing `ModelIdsNotFoundException` with the list of missing IDs if any key cannot be found.

## Cursor Pagination

`GetModelQuery` supports both standard offset pagination (`paginate()`) and constant-time cursor pagination (`cursorPaginate()`):

```php
$params = ListCustomersRequest::from($request)->toQueryParams();

// Cursor pagination using page[cursor] or ?cursor=...
return $query->cursorPaginateByQueryParams($queryBuilder, $params);
```

When using `cursorPaginateByQueryParams()`, Steward appends the model primary key as a tie-breaker when the requested sort does not already include it. This keeps cursor traversal stable when a client sorts on non-unique values.

## Data objects

`AbstractData` lets you map validated input into typed DTO-style objects.

It normalizes booleans, backed/unit enums, and concrete `int`, `float`, and `string` constructor properties. Validate request data before constructing DTOs; ambiguous union types intentionally retain PHP's native type resolution.

Use it for action payloads and request body transformation. The test suite includes examples of case conversion and nullable handling under `tests/Unit/`.

## JSON:API resources

Extend `Hatchyu\Steward\Resources\JsonApiResource` for response documents.

Example:

```php
use Illuminate\Http\Request;
use Hatchyu\Steward\Resources\JsonApiResource;

final class CustomerResource extends JsonApiResource
{
    public static function jsonApiResourceType(): string
    {
        return 'customers';
    }

    public static function jsonApiAllowedFields(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function jsonApiFieldColumnMap(): array
    {
        return [
            'name' => ['name'],
            'email' => ['email'],
            'phone' => ['phone'],
        ];
    }

    protected static function jsonApiIncludeResources(): array
    {
        return [
            'addresses' => CustomerAddressResource::class,
        ];
    }

    protected function jsonApiAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
```

Return a single resource:

```php
return new CustomerResource($customer);
```

Return a collection:

```php
return CustomerResource::collection($customers);
```

Laravel will emit JSON:API documents with:

- `data`
- `relationships`
- `included`
- `links`
- `meta`
- `Content-Type: application/vnd.api+json`

## JSON:API request validation

`ListQueryRequest` can validate:

- `filter` as either an array or a JSON object string
- `sort`
- `page`
- `include`
- `fields[...]`

The preferred approach is to point the request at the primary JSON:API resource:

```php
use App\Http\Resources\CustomerResource;
use Hatchyu\Steward\Requests\ListQueryRequest;
use Override;

final class ListCustomersRequest extends ListQueryRequest
{
    #[Override]
    protected function primaryJsonApiResource(): ?string
    {
        return CustomerResource::class;
    }
}
```

From that resource, the request derives:

- allowed `include` paths
- allowed sparse fieldsets for the primary and included resources

The matching query can derive eager-loading from the same resource by overriding `jsonApiResource()`.

That same resource metadata also powers conservative sparse-field projection for:

- the primary query
- eager-loaded included relations

If needed, you can still override `allowedIncludes()` and `allowedFields()` directly.

## Design notes

- The package favors Laravel-native primitives over third-party transport layers.
- Query parsing, query execution, request validation, and response formatting stay separate.
- JSON:API capability is declared near the resource, then reused by request validation.

## Test Examples in this Repository

See the working integration and test suite in:

- [ListQueryRequestValidationTest.php](tests/Unit/ListQueryRequestValidationTest.php)
- [GetModelQueryJsonApiIncludeTest.php](tests/Unit/GetModelQueryJsonApiIncludeTest.php)
- [FindModelQueryTest.php](tests/Unit/FindModelQueryTest.php)
- [WriteActionsTest.php](tests/Unit/WriteActionsTest.php)
