Develop RESTful API endpoints fast with rich features such as keyword querying, pagination, etc.

### Install

```shell
composer require cloudmonitor/apiflow
```

### Prepare controllers
Similar to special attributes in Eloquent, such as $fillables, APIFlow allows for custom attributes defined as arrays. This way you can control how your data is exposed through your API.

#### Methods

Currently APIFlow is intended for RESTful resource listings, that be `index` or collections. It can either be done by specifically referering to a API resource and an Eloquent model:

```php
class UserController extends APIController
{
  public function index()
  {
    return parent::api(\App\Http\Resources\User::class, \App\Models\User::class);
  }
}
```

Or, if as above, the names are following the standard convention, that is App\Http\Controllers\\**User**Controller, \App\Http\Resources\User and \App\Models\User:

```php
class UserController extends APIController
{
  public function index()
  {
    return parent::api();
  }
}
```

Keep in mind the last example is sort of syntactic sugar and won't work in most cases.

#### Attributes

Currently 3 attributes are supported:

```php
/**
 * Search query database columns.
 * 
 * @var array
 */
protected $queryColumns = ['name', 'email', 'auid'];

/**
 * Model scopes exposed to the API.
 * 
 * @var array
 */
protected $scopes = ['departments'];

/**
 * Related models API can include.
 * 
 * @var array
 */
protected $withRelations = ['department'];
```

First attribute (`queryColumns`) tells APIFlow which columns in the database it can do a full text search. Every column will be a partial search, meaning text can be surrounded to the search string (`%string%`). The string will also be lower cased to avoid case sensivity.

Second attribute (`scopes`) allows APIFlow to perform queries towards your model scopes (`public function scopeDepartment()`). Scopes should only require 2 parameters, the default `Builder` and the query string parameter. The second parameter will always be cased to the given format. Such as:

```php
GET /api/users?departments=1,2
```

Where the scope is:

```php
public function scopeDepartments(Builder $query, array $departments): void
```

Will be casted to an array of `[1,2]`.

### Available features

Currently the features with APIFlow are:

* Keyword searchin (read above for defining columns): `query=`
* Exlcude IDs, if you want to prevent to query something that already is displayed in ie. a list: `exclude=`
* Set limit of results to output, with an upperlimit at 25: `limit=`
* Load related data to the model. Separated with comma: `with=`
* Run custom scopes on the model: `scopename=`

### Example

```shell
/api/users?query=John&exclude=4&limit=10&with=department,team&departments=1,3
```
