# Fluent Meta Data for Eloquent Models

[![Laravel](https://img.shields.io/badge/Laravel-~8.0-green.svg?style=flat-square)](http://laravel.com)
[![Source](http://img.shields.io/badge/source-kodeine/laravel--meta-blue.svg?style=flat-square)](https://github.com/kodeine/laravel-meta/)
[![Build Status](http://img.shields.io/travis/kodeine/laravel--meta/master.svg?style=flat-square)](https://travis-ci.org/kodeine/laravel-meta)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

Metable Trait adds the ability to access meta data as if it is a property on your model.
Metable is Fluent, just like using an eloquent model attribute you can set or unset metas. Follow along the documentation to find out more.

## Changelog

visit [CHANGELOG.md](CHANGELOG.md)

## Installation

#### Composer

Laravel can be installed on laravel `8.x` or higher.

Run:

```
composer require kodeine/laravel-meta
```

For laravel 7.x or below visit [this link](https://github.com/kodeine/laravel-meta/tree/master).

#### Upgrade guide

Change this line in `composer.json`:

```
"kodeine/laravel-meta": "master"
```

to:

```
"kodeine/laravel-meta": "^2.0"
```

after that, run `composer update` to upgrade the package.

##### Upgrade notice

Laravel meta 2 has some backward incompatible changes that listed below:

1. Laravel 7 or lower not supported.
2. Removed the following methods: `__get`, `__set`, `__isset`. If you have defined any of these methods, then you probably have something like this in your model:

   ```php
   class User extends Model{
       use Metable{
           __get as __metaGet
       }
   ```

   You need to remove `as` operator of the methods.
3. Removed legacy getter. in older version if you had a method called `getSomething()` then you could access return value of this method using `$model->something`. this is no longer the case, and you have to call `$model->getSomething()`.
4. Added new method `setAttribute` that overrides parent method.
5. Renamed `getMetaDefaultValue` method to `getDefaultMetaValue`.
6. Second parameter of `getMeta` method is now default value when meta is null.
7. Removed `whereMeta` method in favor of `scopeWhereMeta`. example: `User::whereMeta($key,$value)->get();`
8. Removed `getModelKey` method.

#### Migration Table Schema

Each model needs its own meta table.

This is an example migration. you need change parts of it.

In this example we assume you have a model named `Post`.

Meta table name should be your model's table name + `_meta` which in this case, model's table name is pluralized form of the model name. so the table name becomes `posts_meta`.

If you don't want to follow this naming convention and use something else for table name, make sure you add this name to your model's body:

```php
protected $metaTable = 'custom_meta_table';
```

the foreign key name should be your model's name + `_id` = `post_id`

If you used something else for foreign key, make sure you add this to your model's body:

```php
protected $metaKeyName = 'custom_foreign_key';
```

```php
/**
* Run the migrations.
*
* @return void
*/
public function up()
{
    Schema::create('posts_meta', function (Blueprint $table) {
        $table->bigIncrements('id');

        $table->bigInteger('post_id')->unsigned();
        $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');

        $table->string('type')->default('null');

        $table->string('key')->index();
        $table->text('value')->nullable();

        $table->timestamps();
    });
}

/**
* Reverse the migrations.
*
* @return void
*/
public function down()
{
    Schema::drop('posts_meta');
}
```

## Configuration

#### Model Setup

Next, add the `Metable` trait to each of your metable model definition:

```php
use Kodeine\Metable\Metable;

class Post extends Eloquent
{
    use Metable;
}
```

Metable Trait will automatically set the meta table based on your model name.
Default meta table name would be, `models_meta` where `models` is pluralized form of the model name.
In case you need to define your own meta table name, you can specify in model:

```php
class Post extends Eloquent
{
    protected $metaTable = 'posts_meta'; //optional.
}
```

#### Default Model Attribute values

Additionally, you can set default values by setting an array called `$defaultMetaValues` on the model. Setting default has two side effects:

1. If a meta attribute does not exist, the default value will be returned instead of `null`.
2. if you attempt to set a meta attribute to the default value, the row in the meta table will be removed, which will cause the default value to be returned, as per rule 1.

This is being the desired and expected functionality for most projects, but be aware that you may need to reimplement default functionality with your own custom accessors and mutators if this functionality does not fit your needs.

This functionality is most suited for meta entries that note exceptions to rules. For example: employees sick out of office (default value: in office), nodes taken down for maintenance (default value: node up), etc. This means the table doesn't need to store data on every entry which is in the expected state, only those rows in the exceptional state, and allows the rows to have a default state upon creation without needing to add code to write it.

```
   public $defaultMetaValues = [
      'is_user_home_sick' => false,
   ];
```

#### Gotcha

When you extend a model and still want to use the same meta table you must override `getMetaKeyName` function.

```
class Post extends Eloquent
{

}

class Slideshow extends Post
{
    protected function getMetaKeyName()
    {
        return 'post_id' // The parent foreign key
    }   
}
```

## Working With Meta

#### Setting Content Meta

To set a meta value on an existing piece of content or create a new data:

> **Fluent way**, You can **set meta flawlessly** as you do on your regular eloquent models.
> Metable checks if attribute belongs to model, if not it will
> access meta model to append or set a new meta.

```php
$post = Post::find(1);
$post->name = 'hello world'; // model attribute
$post->content = 'some content goes here'; // meta data attribute
$post->save(); // save attributes to respective tables
```

Or

```php
$post = Post::find(1);
$post->name = 'hello world'; // model attribute
$post->setMeta('content', 'Some content here');
$post->save();
```

Or `set multiple metas` at once:

```php
...
$post->setMeta([
    'content' => 'Some content here',
    'views' => 1,
]);
$post->save();
```

Or `set multiple metas and columns` at once:

```php
...
$post->setAttributes([
    'name' => 'hello world'; // model attribute
    'content' => 'Some content here',
    'views' => 1,
]);
$post->save();
```

> **Note:** If a piece of content already has a meta the existing value will be updated.

You can also save metas with `saveMeta` without saving the model itself:

```php
$post->content = 'some content goes here'; // meta data attribute
$post->saveMeta(); // will save metas to database but won't save the model itself
```

#### Unsetting Content Meta

Similarly, you may unset meta from an existing piece of content:

> **Fluent way** to unset.

```php
$post = Post::find(1);
$post->name // model attribute
unset($post->content) // delete meta on save
$post->save();
```

Or

```php
$post->unsetMeta('content');
$post->save();
```

Or `unset multiple metas` at once:

```php
$post->unsetMeta('content,views');
// or
$post->unsetMeta('content|views');
// or
$post->unsetMeta('content', 'views');
// or array
$post->unsetMeta(['content', 'views']);

$post->save();
```

> **Note:** The system will not throw an error if the content does not have the requested meta.

#### Checking for Metas

To see if a piece of content has a meta:

> **Fluent way**, Metable is clever enough to understand $post->content is an attribute of meta.

```php
if (isset($post->content)) {

}
// or
if ($post->hasMeta('content')){

}
```

You may also check if model has multiple metas:

```php
$post->hasMeta(['content','views']); // returns true only if all the metas exist
// or
$post->hasMeta('content|views');
// or
$post->hasMeta('content,views');
```

#### Retrieving Meta

To retrieve a meta value on a piece of content, use the `getMeta` method:

> **Fluent way**, You can access meta data as if it is a property on your model.
> Just like you do on your regular eloquent models.

```php
$post = Post::find(1);
dump($post->name);
dump($post->content); // will access meta.
```

Or

```php
$post = $post->getMeta('content');
```

Or specify a default value, if not set:

```php
$post = $post->getMeta('content', 'Something');
```

> **Note:** default values set in defaultMetaValues property take precedence over default value passed to this method.

You may also retrieve more than one meta at a time and get an illuminate collection:

```php
// using comma or pipe
$post = $post->getMeta('content|views');
// or an array
$post = $post->getMeta(['content', 'views']);
// specify default values
$post->getMeta(['content', 'views'],['content'=>'something','views'=>0]);
// or specify one default value for all missing metas
$post->getMeta(['content', 'views'],'none');// result if the metas are missing: ['content'=>'none','views'=>'none']
// without specifying default value result will be null
$post->getMeta(['content', 'views']);// result if the metas are missing: ['content'=>null,'views'=>null]
```

#### Disable Fluent Access

If you don't want to access metas in fluent way, you can disable it by adding following property to your model:

```php
protected $disableFluentMeta = true;
```

By setting that property, this package will no longer handle metas in the following ways:

```php
$post->content='something';// will not set meta. original laravel action will be taken
$post->content;// will not retrieve meta
unset($post->content);// will not unset meta
isset($post->content);// will not check if meta exists
```

#### Retrieving All Metas

To fetch all metas associated with a piece of content, use the `getMeta` without any params

```php
$metas = $post->getMeta();
```

#### Retrieving an Array of All Metas

To fetch all metas associated with a piece of content and return them as an array, use the `toArray` method:

```php
$metas = $post->getMeta()->toArray();
```

#### Meta Table Join

When you need to filter your model based on the meta data , you can use `meta` scope in Eloquent Query Builder.

```php

$post = Post::meta()
    ->where(function($query){
          $query->where('posts_meta.key', '=', 'revision')
                ->where('posts_meta.value', '=', 'draft');
    })

```

#### Eager Loading

When you need to retrieve multiple results from your model, you can eager load `metas`

```php
$post = Post::with(['metas'])->get();
```

#### Prevent metas attribute from being populated

When you convert a model to an array (or json) and you don't need all meta fields, you can create a model's property to prevent metas from being added to the resulting array.
You can also use it on eloquent relations.

```php
/* Post model */
public $hideMeta = true; // Do not add metas to array
```

## Events

Laravel meta dispatches several events, allowing you to hook into the following events: `metaCreating`, `metaCreated`, `metaSaving`, `metaSaved`, `metaUpdating`, `metaUpdated`, `metaDeleting` and `metaDeleted`. Listeners should expect two parameters, first an instance of the model and second, name of the meta that event occurred for it. To enable events you need to add `HasMetaEvents` trait to your model:

```php
use Kodeine\Metable\Metable;
use Kodeine\Metable\HasMetaEvents;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Metable,HasMetaEvents;
}
```

After that, you can listen for events the [same way](https://laravel.com/docs/master/eloquent#events) you do for models.

> If you `return false;` in listener of any event ending with `ing`, that operation will be aborted.

### Additional events

There are some additional events that extend existing laravel events. These events don't need `HasMetaEvents` trait and like default laravel events, the event listeners should expect one parameter.
Event names: `createdWithMetas`, `updatedWithMetas`, `savedWithMetas`. These events fire exactly like default laravel events except that they are only fired after all metas saved to database.
For example, you may need to access metas inside a queue job after model created. But because metas have not been saved to database yet (metas will be saved to database in `saved` event and this event has not been fired yet), job can't access them. by using `createdWithMetas` event instead of `created` event, the problem will be solved.

There are 3 ways to listen for events:

#### 1. By Defining `$dispatchesEvents` Property

```php
use App\Events\UserMetaSaved;
use Kodeine\Metable\Metable;
use Kodeine\Metable\HasMetaEvents;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Metable,HasMetaEvents;

    protected $dispatchesEvents = [
        'metaSaved' => UserMetaSaved::class,
    ];
}
```

#### 2. [Using Closures](https://laravel.com/docs/master/eloquent#events-using-closures)

```php
use Kodeine\Metable\Metable;
use Kodeine\Metable\HasMetaEvents;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Metable,HasMetaEvents;

    protected static function booted()
    {
        static::metaCreated(function ($user, $meta) {
            //
        });
    }
}
```

#### 3. [Observers](https://laravel.com/docs/master/eloquent#observers)

```php
class UserObserver
{
    public function metaCreated(User $user,$meta)
    {
        //
    }
}
```
