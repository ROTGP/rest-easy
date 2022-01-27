Introduction
=================
[RestEasy](https://github.com/ROTGP/rest-easy) is a PHP [trait](https://www.php.net/manual/en/language.oop5.traits.php) for [Laravel](https://laravel.com/) [Controllers](https://laravel.com/docs/7.x/controllers), to ensure simpler, more secure REST APIs. The aim is to **heavily** reduce [boilerplate](https://stackoverflow.com/a/3992211/1985175) code, whilst enabling fine-grained control over validation, permissions, querying, and [much more](#features-at-a-glance).

It implements and encourages [object-oriented design](https://en.wikipedia.org/wiki/Object-oriented_design), and has zero-dependencies (apart from [Laravel](https://laravel.com/) itself).

It is designed to be non-invasive — simply remove the trait from your controller to return to standard Laravel functionality. There's no need to extend, implement or subclass any code.



Why?
===

I got tired of rewriting the same boilerplate code every time I started a new Laravel project, or even a new model/controller. It became too much code to maintain, for seemingly repetitive functionality. 

Permissions were also a concern — Laravel provides great mechanisms but it requires you to implement them explicitly, which again results in a lot of code to manage.




Table of contents
=================

   * [Introduction](#introduction)
   * [Why?](#why)
   * [Table of contents](#table-of-contents)
   * [Features-at-a-glance](#features-at-a-glance)
   * [Installation](#installation)
   * [Model discovery](#model-discovery)
   * [Validation](#validation)
   * [Permissions](#permissions)
   * [Querying](#querying)
   * [Tests](#tests)
   * [Contributing](#contributing)
   * [License](#license)


Features at a glance
====================

* Validation
  * All `PUT` and `POST` requests are automatically validated
  * `unique` & `exists` rules are completed according to request type (`PUT` or `POST`) (with id, model, table, etc)
  * Easy custom rules
  * Model rules (validate the model as a whole, not just individual fields)
  * Define `immutableFields` – fields which can't be modified for `PUT` requests. These fields will not be validated when updating
  * Payload pruning and merging - only (normalised) fillable fields will be validated.
  *  Only aforementioned fillable fields will be passed to the model for update/create.
  * PATCH-like functionality – when updating a model, fields missing from the payload will be filled with existing model data, so as to satisfy validation requirements
  * 
* Permissions
  * Simple and implicit model-based validation rules based on eloquent events and the `authUser`. You don't need to explicity ask for permission — if a permission is violated then the request is aborted. Additionally, if the violation happens during an `POST`, `PUT`, or `DELETE` request then it will be automatically wrapped in a transaction
  * Define permissions for read / write / create / delete / attach / detach
  * Graceful automatic error response
  * Object-oriented definitions — define rules in base classes
  * Enable/disable permissions per-controller, and opt in/out of explicit permissions (if explicit (the default), then the permission method must be defined in the model, and the absence of said method will be interpreted as denial
  * Easy error responses. Optionally define your error class containing your codes. If you return an integer as a permission response, then the response will automatically return a detailed message
  * Easy permission exception logging, with detailed information of the violation
* Querying
  * Define `safeRelationships` – model relationships which can be queried using the `with` `GET` variable (works with all request types except `DELETE`)
  * Define safeScopes – scopes which can be queried using GET variables for `index/list` requests
  * Auto-pagination (really). Just define `page` and `page_size` `GET` variables
  * Custom `selects`
  * Define implicit scopes using the implicitScope method. This will be implied implicitly to all requests. 
  * GroupBys
  * Aggregations
  * OrderBys (including by multiple fields)
* Syncing
  * Attach and detach models easily
  * Apply permissions for said syncing  
* Response
  * Easily translate error responses 
* Error handling



Installation
============

Use the package manager [Composer](https://getcomposer.org/) to install RestEasy.

```bash
composer require rotgp/rest-easy
```

## Usage

Just drop the trait into your controller.

```php
use Illuminate\Routing\Controller;

class FooController extends Controller
{
    use RestEasyTrait;
}
```

Now, associate your [route](https://laravel.com/docs/7.x/routing) with your [controller](https://laravel.com/docs/7.x/controllers) as you normally would. The standard Laravel [resource](https://laravel.com/docs/7.x/controllers#resource-controllers) will automatically map to the standard methods (`index`, `show`, `update`, `store`, `destroy`) in the `RestEasyTrait`. 


```php
Route::resource('foos', 'FooController');
```

Of course, you may define [partial](https://laravel.com/docs/7.x/controllers#restful-partial-resource-routes) resources (or single), implementing only the methods you choose.

```php
Route::resource('foos', 'FooController')->only([
    'index', 'show'
]);
```

<br>

## Model discovery
The controller using [RestEasy](https://github.com/ROTGP/rest-easy) works with the associated model, to ascertain validation rules, permissions, and other optional functionality. The model is a standard vanilla [eloquent model](https://laravel.com/docs/7.x/eloquent), requiring no special traits. A `FooController` will look for the `Foo` model in several locations of the app namespace. First in the top-level, then in `namespace\Model` or `namespace\Models`. 

Alternatively, the controller may implement the `modelNamespace` method, which returns either the namespace where the model is defined, or the fully qualified namespace of the model itself. The former is a good solution for base controller classes wanting to define a custom location for models. 


```php
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    use RestEasyTrait;
    
    protected function modelNamespace()
    {
        return 'Org\Foo\Bar\Custom\Location\Models';
    }
}
```

## Validation
Laravel [Validation](https://laravel.com/docs/7.x/validation) is powerful, but it can be painful and tedious. This trait aims to make validation automatic, simple, and flexible. Validation is automatically performed on all `update` and `store` 
 (`PUT` and `POST`) requests. 

Simply define your [rules](https://laravel.com/docs/7.x/validation#available-validation-rules) in your model using the `validationRules` method. This method also includes an [optional](https://laravel.com/docs/7.x/helpers#method-optional) `$authUser` parameter, allowing conditional validation based on the auth user making the request. 


```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    public function validationRules($authUser)
    {
        return [
            'name' => 'required|unique',
            'title' => 'max:100',
            'bar_id' => 'integer|exists'
        ];
    }
}
```


#### Unique and exists validations
Note that, normally, using the [unique](https://laravel.com/docs/7.x/validation#rule-unique) and [exists](https://laravel.com/docs/7.x/validation#rule-exists) rules requires special attention according to whether the model is being created or updated, and requires the table name (or model) to be appended, as well as (in the case of updates) the id of the model being updated. This is all taken care of automatically with RestEasy — just define `unique` or `exists` with no further parameters. However, if you wish to define these rule definitions manually, RestEasy won't interfere.


#### Custom validation rules
RestEast also offers easy custom validation rules, as well as model-based rules which take multiple fields (of the model) into account. Simply define a method using the 'validate' + RuleName ([studly](https://laravel.com/docs/7.x/helpers#method-studly-case) case) convention, and refer to it using the rule_name ([camel](https://laravel.com/docs/7.x/helpers#method-fluent-str-camel) case) convention. 

For example, the rule below named `not_reserved` will look for the `validateNotReserved` method. The rule is considered to fail if anything other than null (ie, an error message) is returned. If an error message is returned, that is what will be returned in the request response.

When using model rules, simply specify the field as 'model'. The corresponding validation method will receive the full payload as it's `$value`, allowing for more complex validations to be performed.

```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    public function validationRules($authUser)
    {
        return [
            'name' => 'required|unique|not_reserved:foo,bar',
            'title' => 'max:100',
            'description' => 'max:500',
            'bar_id' => 'integer|exists',
            'model' => 'combined_length:name,title,550'
        ];
    }

    public function validateNotReserved($field, $value, $params) {
        if (in_array($value, $params))
          return $field . ' may not contain: ' . implode(',', $params);
    }

    public function validateCombinedLength($field, $value, $params) {
        $val1 = $value[$params[0]];
        $val2 = $value[$params[1]];
        $maxLen = $params[2];
        $combinedLen = strlen($val1) + strlen($val2];
        if ($combinedLen > $maxLen)
          return 'Combined length too long';
    }
}
```


#### Other points to mention for custom validation rules

- the `$field` parameter refers to the field being validated, which may also be 'model' in the case of model rules
- the `$value` parameter refers to the value being validated, which in the case of model rules will be the full payload. This `$value` is [optional](https://laravel.com/docs/7.x/helpers#method-optional), so if you're expecting an array or an object, then it's safe to access `$value->doesNotExist` or `$value['does_not_exist']` without fear or errors
- when updating a model, any fields missing from the payload will be filled with the model's existing data. In this sense, RestEasy offers [PATCH](https://en.wikipedia.org/wiki/Patch_verb)-like functionality when making [PUT](https://en.wikipedia.org/wiki/Put) requests
- similarly, when updating a model, any custom validation methods may make reference to `$this`, which refers to the model being updated
- the `$params` parameter refers to the parameters (an array) to be passed from the rule definitions. For example, the `validateFoo` method for the `'foo:bar,baz'` rule definition will receive the `$params` of `['bar', 'baz']`. If no params are passed, then this array will be empty.

<br>


## Permissions
There are many packages available to handle laravel
permissions, but I find most/all of them to be verbose,
unintuitive and inflexible. With `rest-easy`, it's as
simple as defining `can{verb}` methods on the model, and
returning a boolean. The `$authUser` (authenticated user
making the current request, if any) will be passed to
the method. All permissions are open by default, but
it's easy to create object-oriented base classes to
define your rules, and customize them as required. 

Note that these permissions depend on Eloquent events,
and as such, if multiple models are accessed through a
join (using Laravel's `with` helper), then permission
will be required for even deeply nested models. If one
of the model's pernmission fails, then the entire
request will be aborted.

Of course, as these permissions depend on Eloquent events,
if your request doesn't interact with the database (or
Eloquent models), then you'll need to implement your own
logic.

```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    public function canCreate($authUser)
    {
        return true;
    }

    public function canRead($authUser)
    {
        return true;
    }

    public function canUpdate($authUser)
    {
        return true;
    }

    public function canAttach($modelToAttach, $authUser)
    {
        return true;
    }

    public function canDetach($modelToDetach, $authUser)
    {
        return true;
    }
}
```


#### Context-specific logic
With the exception of `canCreate`, remember that the
method being called is on the instance of the model
being accessed, so code based rules are easy to
implement.

```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    public function canRead($authUser)
    {
        return $authUser->id === $this->user->id && !$this->user->blocked;
    }
}
```


## Querying
Once again, Laravel offers ways to query models, but it
tends to be verbose. `Rest-Easy` makes it a breeze.

### Safe relationships
Define a method `safeRelationships` on any model to
define which related models may be queried. Return an
array of relationships names which correspond to methods which
define Laravel's native relationships.

```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    public function safeRelationships($authUser)
    {
        return ['bar', 'bazzes'];
    }

    public function bar()
    {
        return $this->hasOne(Bar::class);
    }

    public function bazzes()
    {
        return $this->hasMany(Baz::class);
    }
}
```

Once defined, the resource may be queried using the
'with' GET variable. Note that this also works with
other verbs (update, post, patch). For example when
updating a resource, you may request related entities in
the same request.

`GET https://myapi.com/foos?with=bar,bazzes`

### Safe scopes
Laravel already provides a great API to make scoped
queries, but it can be verbose and inflexible.
`Rest-Easy` aims to make it concise and flexible. Simple
define a `safeScopes` methods on your model, and return
a list of scopes which correspond to standard Laravel
`scope` methods on said model. 

```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    public function safeScopes($authUser)
    {
        return ['born_after'];
    }

    public function scopeBornAfter($query, $params)
    {
        return $query->where('date_of_birth', '>', Carbon::parse($params));
    }
}
```

Once defined, the resource may be queried using any
number of scopes. For example, with the above example,
the following query may be performed to find `foos` born
after the October 15th 1980:

`GET https://myapi.com/foos?born_after=1980-10-15`


### Auto-pagination
Really. It's another task that should be easy but
requires a surprising amount of code. If the client
desires paginated results, then simply provide `page`
and `page_size` `GET` variables. That's all there is to it.

`GET https://myapi.com/foos?page=5&page_size=20`

And expect a JSON response like:

```php
[
    'page' : 5,
    'page_size' : 20,
    'total_results': 134,
    'total_pages': 7,
    'data' => [
       /// items
    ]
]
```



### Implicit scopes
Make implicit scopes on any model by defining a
`scopeImplicit` method. This scope will be applied
implicitly to all requests for the given model, and is
handy for scoping context-based queries, such as the
user making the request.


```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    public function scopeImplicit($query)
    {
        $authUserId = optional(auth()->user())->id;
        if ($authUserId === null) 
            return;
        $query->whereHas('bars', function ($query1) use ($authUserId) {
            $query1->where('bar_user.user_id', $authUserId); 
        });
    }

    public function bars()
    {
        return $this->belongsToMany(Bar::class);
    }
}
```

`GET https://myapi.com/foos?with=bars`

This will return `foos` with their associated `bars`, but
only where `bars` and `users` exist on a pivot table,
and `bar_user.user_id` matches the id of the user making
the (authenticated) request.

### Custom selects
Similar in concept to `Graphql`, simply specify the
columns using a `select` parameter, and only those
columns will be returned. Helpful for complex models
with many columns. 

`GET https://myapi.com/foos/1?select=name,age`

### Order by

Ordering records in Laravel is usually straight-forward,
but it gets complicated for multiple fields.
`Rest-Easy`, of course, makes this easy.

Standard

`GET https://myapi.com/songs?order_by=album_id`

Ascending (default)

`GET https://myapi.com/songs?order_by=album_id,asc`

Descending

`GET https://myapi.com/songs?order_by=album_id,desc`

Multiple fields, descending

`GET https://myapi.com/songs?order_by=album_id,length_seconds,desc`


### Aggregation queries
The client can make complex aggregation queries using
any of the following verbs, and specifying the column(s)
of interest. These requests are heavily sanitized and
are not vulnerable to SQL injection attacks. However,
please note that permissions won't be checked as
individual Eloquent models are never accessed individually.

`count`, `sum`, `avg`, `min`, `max`

For example: 

`GET https://myapi.com/songs?avg=length_seconds`

`GET https://myapi.com/songs?avg=length_seconds&group_by=album_id`

`GET https://myapi.com/songs?songs?max=length_seconds`

`GET https://myapi.com/songs?max=length_seconds&group_by=album_id`

`GET https://myapi.com/songs?songs?count=id`

`GET https://myapi.com/songs?count=id&group_by=album_id`

`GET https://myapi.com/songs?sum=length_seconds`

`GET https://myapi.com/songs?sum=length_seconds&group_by=album_id`

`GET
https://myapi.com/songs?sum=length_seconds&group_by=album_id&order_by=length_seconds_sum,desc`

`GET https://myapi.com/songs?songs?sum=length_seconds&group_by=album_id&order_by=length_seconds_sum,desc&longer_than=200`

`GET
https://myapi.com/songs?sum=id&min=length_seconds&max=length_seconds&avg=length_seconds&count=album_id`


Tests
============
Run `vendor/bin/phpunit` to run the tests.

<br>

Contributing
============
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests appropriately.

<br>

License
=======
[MIT](https://choosealicense.com/licenses/mit/)