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

Now, associate your [route](https://laravel.com/docs/7.x/routing) with your [controller](https://laravel.com/docs/7.x/controllers). The standard Laravel [resource](https://laravel.com/docs/7.x/controllers#resource-controllers) will automatically map to the standard methods (`index`, `show`, `update`, `store`, `destroy`) in the `RestEasyTrait`. 


```php
Route::resource('foos', 'FooController');
```

Of course, you may define [partial](https://laravel.com/docs/7.x/controllers#restful-partial-resource-routes) resources (or single), implementing only the methods you choose.

```php
Route::resource('foos', 'FooController')->only([
    'index', 'show'
]);
```




## Overview

#### Structure
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

#### Validation
Validation in Laravel is powerful, but it can be painful and tedious. This trait aims to make validation automatic, simple, and flexible.

- validation is automatically performed with `PUT` and `POST` requests


## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)