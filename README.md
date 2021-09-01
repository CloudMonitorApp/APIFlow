Develop RESTful API endpoints fast with rich features such as keyword querying, pagination, etc.

### Prepare controllers
Similar to special attributes in Eloquent, such as $fillables, APIFlow allows for custom attributes defined as arrays. This way you can control how your data is exposed through your API.

Currently 2 attributes are optionally supported:

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

Will be cased to an array of `[1,2]`.

### Available features

Currently the features with APIFlow are:

* Keyword searchin (read above for defining columns): `query=`
* Exlcude IDs, if you want to prevent to query something that already is displayed in ie. a list: `exclude=`
* Set limit of results to output, with an upperlimit at 25: `limit=`
* Load related data to the model: `with=`
* Run custom scopes on the model: `scopename=`

### Example

```
/api/users?query=John&exclude=4&limit=10&with=department&departments=1,3
```
