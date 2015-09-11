# Fluent Meta Data for Eloquent Models
[![Laravel](https://img.shields.io/badge/Laravel-~5.1-orange.svg?style=flat-square)](http://laravel.com)
[![Source](http://img.shields.io/badge/source-kodeine/laravel--meta-blue.svg?style=flat-square)](https://github.com/kodeine/laravel-meta/)
[![Build Status](http://img.shields.io/travis/kodeine/laravel--meta/master.svg?style=flat-square)](https://travis-ci.org/kodeine/laravel-meta)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

Metable Trait adds the ability to access meta data as if it is a property on your model.
Metable is Fluent, just like using an eloquent model attribute you can set or unset metas. Follow along the documentation to find out more.

## Installation

#### Composer

Add this to your composer.json file, in the require object:

```javascript
"kodeine/laravel-meta": "dev-master"
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
