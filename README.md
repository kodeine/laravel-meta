# Fluent Meta Data for Eloquent Models
[![Laravel](https://img.shields.io/badge/Laravel-~8.0-green.svg?style=flat-square)](http://laravel.com)
[![Source](http://img.shields.io/badge/source-kodeine/laravel--meta-blue.svg?style=flat-square)](https://github.com/kodeine/laravel-meta/)
[![Build Status](http://img.shields.io/travis/kodeine/laravel--meta/master.svg?style=flat-square)](https://travis-ci.org/kodeine/laravel-meta)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

Metable Trait adds the ability to access meta data as if it is a property on your model.
Metable is Fluent, just like using an eloquent model attribute you can set or unset metas. Follow along the documentation to find out more.

## Installation

#### Composer

Add this to your composer.json file, in the require object:

```javascript
"kodeine/laravel-meta": "master"
```

After that, run composer install to install the package.

#### Migration Table Schema
```php
/**
* Run the migrations.
*
* @return void
*/
public function up()
{
    Schema::create('posts_meta', function (Blueprint $table) {
        $table->increments('id');

        $table->integer('post_id')->unsigned()->index();
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
Default meta table name would be, `model_meta`.
In case you need to define your own meta table name, you can specify in model:

```php
class Post extends Eloquent
{
    protected $metaTable = 'posts_meta'; //optional.
}
```

#### Default Model Attribute values

Additionally, you can set default values by setting an array called `$defaultMetaValues` on the model. Setting default has two side-effects:

  1. If a meta attribute does not exist, the default value will be returned instead of `null`.

  2. if you attempt to set a meta attribute to the default value, the row in the meta table will be removed, which will cause the default value to be returned, as per rule 1.

This is be the desired and expected functionality for most projects, but be aware that you may need to reimplement default functionality with your own custom accessors and mutators if this functionality does not fit your needs.

This functionality is most suited for meta entries that note exceptions to rules. For example: employees sick out of office (default value: in office), nodes taken down for maintance (default value: node up), etc. This means the table doesn't need to store data on every entry which is in the expected state, only those rows in the exceptional state, and allows the rows to have a default state upon creation without needing to add code to write it.

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
Metable checks if attribute belongs to model, if not it will
access meta model to append or set a new meta.

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
```

#### Retrieving Meta

To retrieve a meta value on a piece of content, use the `getMeta` method:

> **Fluent way**, You can access meta data as if it is a property on your model.
Just like you do on your regular eloquent models.

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

You may also retrieve more than one meta at a time and get an illuminate collection:

```php
// using comma or pipe
$post = $post->getMeta('content|views');
// or an array
$post = $post->getMeta(['content', 'views']);
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

When you need to retrive multiple results from your model, you can eager load `metas`

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
