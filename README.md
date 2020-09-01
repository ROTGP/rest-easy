# RestEasy
A non-invasive PHP trait for [Laravel](https://laravel.com/) [Controllers](https://laravel.com/docs/7.x/controllers), to ensure a simpler, more secure REST API.


## Installation

Use the package manager [Composer](https://getcomposer.org/) to install RestEasy.

```bash
composer require rotgp/rest-easy
```

## Usage
```php
use Illuminate\Routing\Controller;

class FooController extends Controller
{
    use RestEasyTrait;
}
```

Now, associate your [route](https://laravel.com/docs/7.x/routing) with your [controller](https://laravel.com/docs/7.x/controllers) as you always would. The standard Laravel [resource](https://laravel.com/docs/7.x/controllers#resource-controllers) will automatically map to the standard methods (`index`, `show`, `update`, `store`, `destroy`) in the `RestEasyTrait`. 


```php
Route::resource('foos', 'FooController');
```

Of course, you may define [partial](https://laravel.com/docs/7.x/controllers#restful-partial-resource-routes) resources (or single), implementing only the methods you choose.

```php
Route::resource('foos', 'FooController')->only([
    'index', 'show'
]);
```





## App structure
The model associated with the controller is automatically inferred. A `FooController` will look for the `Foo` model in several locations of the app namespace. First in the top-level, then in `namespace/Model` or `namespace/Models`. 

Alternatively, the controller may implement the `modelNamespace` method, which returns either the namespace where the model is defined, or the fully qualified namespace of the model itself. The former is a good solution for base controller classes wanting to define a custom location for models. 

The associated model is a standard vanilla eloquent model, requiring no special traits.


```php
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    use RestEasyTrait;
    
    // define a custom namespace where all controllers
    // extending this class should find their model
    protected function modelNamespace()
    {
        return 'Org\Foo\Bar\Custom\Location\Models';
    }
}
```

## Validation
Laravel [Validation](https://laravel.com/docs/7.x/validation) is powerful, but it can be painful and tedious. This trait aims to make validation automatic, simple, and flexible. Validation is automatically performed with `update` and `store` 
 (`PUT` and `POST`) requests. 

Simply define your rules in your model using the `validationRules` method. This method also includes an [optional](https://laravel.com/docs/7.x/helpers#method-optional) `$authUser` parameter, allowing conditional validation based on the auth user making the request. 


```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    protected function validationRules($authUser)
    {
        return [
            'name' => 'required|unique',
            'title' => 'max:100',
            'bar_id' => 'integer|exists'
        ];
    }
}
```

The above example will validate the name, title, and bar_id of the Foo object. All rules are standard Laravel [rules](https://laravel.com/docs/7.x/validation#available-validation-rules). 

Note that, normally, using the [unique](https://laravel.com/docs/7.x/validation#rule-unique) and [exists](https://laravel.com/docs/7.x/validation#rule-exists) rules requires special attention according to whether the model is being created or updated, and requires the table name (or model) to be appended, as well as the id of the model being updated. This is all taken care of automatically with RestEasy. However, if you wish to define these rules manually, RestEasy won't interfere.

RestEast also offers easy custom validation rules, as well as model-based rules which take multiple fields (of the model) into account. Simply define a method using the 'validate' + RuleName ([studly](https://laravel.com/docs/7.x/helpers#method-studly-case) case) convention, and refer to it using the rule_name ([camel](https://laravel.com/docs/7.x/helpers#method-fluent-str-camel) case) convention. 

For example, the rule below named `not_reserved` will look for the `validateNotReserved` method. The rule is considered to fail if anything other than null (ie, an error message) is returned. If an error message is returned, that is what will be returned in the request response.

```php
use Illuminate\Database\Eloquent\Model;

class Foo extends Model
{
    protected function validationRules($authUser)
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
        if (strlen($value[$params[0]]) + strlen($value[$params[1]]) > $params[2])
          return 'Combined length too long';
    }
}
```
Other points to mention for custom validation rules:

- the `$field` parameter refers to the field being validated, which may also be 'model' in the case of model rules
- the `$value` parameter refers to the value being validated, which in the case of model rules. This `$value` is [optional](https://laravel.com/docs/7.x/helpers#method-optional), so if you're expecting an array or an object, then it's safe to access `$value->doesNotExist` or `$value['does_not_exist']` without fear or errors
- when updating a model, any fields missing from the payload will be filled with the model's existing data. In this sense, RestEasy offers [PATCH](https://en.wikipedia.org/wiki/Patch_verb)-like functionality when making [PUT](https://en.wikipedia.org/wiki/Put) requests
- similarly, when updating a model, any custom validation methods may make reference to `$this`, which refers to the model itself
- the `$params` parameter refers to the parameters (an array) to be passed from the rule definitions (ie: `'foo:bar,baz'`). If no params are passed, then this array will be empty

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)