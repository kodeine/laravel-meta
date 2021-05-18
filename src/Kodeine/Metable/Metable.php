<?php

namespace Kodeine\Metable;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait Metable
{
    
    // Static property registration sigleton for save observation and slow large set hotfix
    public static $_isObserverRegistered;
    public static $_columnNames;
    
    /**
     * whereMeta scope for easier join
     * -------------------------
     */
    public function scopeWhereMeta($query, $key, $value, $alias = null)
    {
        $alias = (empty($alias)) ? $this->getMetaTable() : $alias;
        return $query->join($this->getMetaTable() . ' AS ' . $alias, $this->getQualifiedKeyName(), '=', $alias . '.' . $this->getMetaKeyName())->where('key', '=', $key)->where('value', '=', $value)->select($this->getTable() . '.*');
    }

    /**
     * Meta scope for easier join
     * -------------------------
     */
    public function scopeMeta($query, $alias = null)
    {
        $alias = (empty($alias)) ? $this->getMetaTable() : $alias;
        return $query->join($this->getMetaTable() . ' AS ' . $alias, $this->getQualifiedKeyName(), '=', $alias . '.' . $this->getMetaKeyName())->select($this->getTable() . '.*');
    }

    /**
     * Set Meta Data functions
     * -------------------------.
     */
    public function setMeta($key, $value = null)
    {
        $setMeta = 'setMeta'.ucfirst(gettype($key));

        return $this->$setMeta($key, $value);
    }

    protected function setMetaString($key, $value)
    {
        $key = strtolower($key);
        if ($this->metaData->has($key)) {

            // Make sure deletion marker is not set
            $this->metaData[$key]->markForDeletion(false);

            $this->metaData[$key]->value = $value;

            return $this->metaData[$key];
        }

        return $this->metaData[$key] = $this->getModelStub([
            'key'   => $key,
            'value' => $value,
        ]);
    }

    protected function setMetaArray()
    {
        list($metas) = func_get_args();

        foreach ($metas as $key => $value) {
            $this->setMetaString($key, $value);
        }

        return $this->metaData->sortByDesc('id')
            ->take(count($metas));
    }

    /**
     * Unset Meta Data functions
     * -------------------------.
     */
    public function unsetMeta($key)
    {
        $unsetMeta = 'unsetMeta'.ucfirst(gettype($key));

        return $this->$unsetMeta($key);
    }

    protected function unsetMetaString($key)
    {
        $key = strtolower($key);
        if ($this->metaData->has($key)) {
            $this->metaData[$key]->markForDeletion();
        }
    }

    protected function unsetMetaArray()
    {
        list($keys) = func_get_args();

        foreach ($keys as $key) {
            $key = strtolower($key);
            $this->unsetMetaString($key);
        }
    }

    /**
     * Get Meta Data functions
     * -------------------------.
     */

    public function getMeta($key = null, $raw = false)
    {
        if (is_string($key) && preg_match('/[,|]/is', $key, $m)) {
            $key = preg_split('/ ?[,|] ?/', $key);
        }

        $getMeta = 'getMeta'.ucfirst(strtolower(gettype($key)));

        // Default value is used if getMeta is null
        return $this->$getMeta($key, $raw) ?? $this->getMetaDefaultValue($key);
    }

    // Returns either the default value or null if default isn't set
    private function getMetaDefaultValue($key) {
          if(isset($this->defaultMetaValues) && array_key_exists($key, $this->defaultMetaValues)) {
            return $this->defaultMetaValues[$key];
        } else {
            return null;
        }
    }

    protected function getMetaString($key, $raw = false)
    {
        $meta = $this->metaData->get($key, null);

        if (is_null($meta) || $meta->isMarkedForDeletion()) {
            return;
        }

        return ($raw) ? $meta : $meta->value;
    }

    protected function getMetaArray($keys, $raw = false)
    {
        $collection = new BaseCollection();

        foreach ($this->metaData as $meta) {
            if (!$meta->isMarkedForDeletion() && in_array($meta->key, $keys)) {
                $collection->put($meta->key, $raw ? $meta : $meta->value);
            }
        }

        return $collection;
    }

    protected function getMetaNull()
    {
        list($keys, $raw) = func_get_args();

        $collection = new BaseCollection();

        foreach ($this->metaData as $meta) {
            if (!$meta->isMarkedForDeletion()) {
                $collection->put($meta->key, $raw ? $meta : $meta->value);
            }
        }

        return $collection;
    }

    /**
     * Relationship for meta tables
     */
    public function metas()
    {
        $classname = $this->getMetaClass();
        $model = new $classname;
        $model->setTable($this->getMetaTable());

        return new HasMany($model->newQuery(), $this, $this->getMetaKeyName(), $this->getKeyName());
    }

    /**
     * Query Meta Table functions
     * -------------------------.
     */
    public function whereMeta($key, $value)
    {
        return $this->getModelStub()
            ->whereKey(strtolower($key))
            ->whereValue($value)
            ->get();
    }

    /**
     * Trait specific functions
     * -------------------------.
     */
    protected function setObserver()
    {
        if(!isset(self::$_isObserverRegistered)) {
            $this->saved(function ($model) {
                $model->saveMeta();
            });
            self::$_isObserverRegistered = true;
        }
    }

    protected function getModelStub()
    {
        // get new meta model instance
        $classname = $this->getMetaClass();
        $model = new $classname;
        $model->setTable($this->metaTable);

        // model fill with attributes.
        if (func_num_args() > 0) {
            array_filter(func_get_args(), [$model, 'fill']);
        }

        return $model;
    }

    protected function saveMeta()
    {
        foreach ($this->metaData as $meta) {
            $meta->setTable($this->metaTable);

            if ($meta->isMarkedForDeletion()) {
                $meta->delete();
                continue;
            }

            if ($meta->isDirty()) {
                // set meta and model relation id's into meta table.
                $meta->setAttribute($this->metaKeyName, $this->modelKey);
                $meta->save();
            }
        }
    }

    protected function getMetaData()
    {
        if (!isset($this->metaLoaded)) {
            $this->setObserver();

            if ($this->exists) {
                $objects = $this->metas
                    ->where($this->metaKeyName, $this->modelKey);

                if (!is_null($objects)) {
                    $this->metaLoaded = true;

                    return $this->metaData = $objects->keyBy('key');
                }
            }
            $this->metaLoaded = true;

            return $this->metaData = new Collection();
        }
    }

    /**
     * Return the key for the model.
     *
     * @return string
     */
    protected function getModelKey()
    {
        return $this->getKey();
    }

    /**
     * Return the foreign key name for the meta table.
     *
     * @return string
     */
    protected function getMetaKeyName()
    {
        return isset($this->metaKeyName) ? $this->metaKeyName : $this->getForeignKey();
    }

    /**
     * Return the table name.
     *
     * @return string
     */
    protected function getMetaTable()
    {
        return isset($this->metaTable) ? $this->metaTable : $this->getTable().'_meta';
    }

    /**
     * Return the model class name.
     *
     * @return string
     */
    protected function getMetaClass()
    {
        return isset($this->metaClass) ? $this->metaClass : MetaData::class;
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->hideMeta ?
            parent::toArray() :
            array_merge(parent::toArray(), [
                'meta_data' => $this->getMeta()->toArray(),
            ]);
    }

    /**
     * Model Override functions
     * -------------------------.
     */

    /**
     * Get an attribute from the model.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        // parent call first.
        if (($attr = parent::getAttribute($key)) !== null) {
            return $attr;
        }

        // there was no attribute on the model
        // retrieve the data from meta relationship
        return $this->getMeta($key);
    }

    /**
     * Set attributes for the model
     *
     * @param array $attributes
     *
     * @return void
     */
    public function setAttributes(array $attributes) {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /**
     * Determine if model table has a given column.
     * 
     * @param  [string]  $column
     * 
     * @return boolean
     */
    public function hasColumn($column) {
        if(empty(self::$_columnNames)) self::$_columnNames = array_map('strtolower',\Schema::connection($this->getConnectionName())->getColumnListing($this->getTable()));
        return in_array(strtolower($column), self::$_columnNames);
    }

    public function __unset($key)
    {
        // unset attributes and relations
        parent::__unset($key);

        // delete meta, only if pivot-prefix is not detected in order to avoid unnecessary (N+1) queries
        // since Eloquent tries to "unset" pivot-prefixed attributes in m2m queries on pivot tables.
        // N.B. Regular unset of pivot-prefixed keys is thus compromised.
        if (strpos($key, 'pivot_') !== 0) {
            $this->unsetMeta($key);
        }
    }

    public function __get($attr)
    {
        // Check for meta accessor
        $accessor = Str::camel('get_'.$attr.'_meta');

        if (method_exists($this, $accessor)) {
            return $this->{$accessor}();
        }

        // Check for legacy getter
        $getter = 'get'.ucfirst($attr);

        // leave model relation methods for parent::
        $isRelationship = method_exists($this, $attr);

        if (method_exists($this, $getter) && !$isRelationship) {
            return $this->{$getter}();
        }

        return parent::__get($attr);
    }

    public function __set($key, $value)
    {
        // ignore the trait properties being set.
        if (Str::startsWith($key, 'meta') || $key == 'query') {
            $this->$key = $value;

            return;
        }

        // if key is a model attribute, set as is
        if (array_key_exists($key, parent::getAttributes())) {
            parent::setAttribute($key, $value);

            return;
        }

        // If there is a default value, remove the meta row instead - future returns of
        // this value will be handled via the default logic in the accessor
        if(
            isset($this->defaultMetaValues) &&
            array_key_exists($key, $this->defaultMetaValues) &&
            $this->defaultMetaValues[$key] == $value
        ) {
          $this->unsetMeta($key);

          return;
        }

        // if the key has a mutator execute it
        $mutator = Str::camel('set_'.$key.'_meta');

        if (method_exists($this, $mutator)) {
            $this->{$mutator}($value);

            return;
        }

        // if key belongs to meta data, append its value.
        if ($this->metaData->has($key)) {
            /*if ( is_null($value) ) {
                $this->metaData[$key]->markForDeletion();
                return;
            }*/
            $this->metaData[$key]->value = $value;

            return;
        }

        // if model table has the column named to the key
        if ($this->hasColumn($key)) {
            parent::setAttribute($key, $value);

            return;
        }

        // key doesn't belong to model, lets create a new meta relationship
        //if ( ! is_null($value) ) {
        $this->setMetaString($key, $value);
        //}
    }

    public function __isset($key)
    {
        // trait properties.
        if (Str::startsWith($key, 'meta') || $key == 'query') {
            return isset($this->{$key});
        }

        // check parent first.
        if (parent::__isset($key) === true) {
            return true;
        }


        // Keys with default values always "exist" from the perspective
        // of the end calling function, even if the DB row doesn't exist
        if(isset($this->defaultMetaValues) && array_key_exists($key, $this->defaultMetaValues)) {
          return true;
        }

        // lets check meta data.
        return isset($this->getMetaData()[$key]);
    }
}
