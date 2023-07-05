<?php

namespace Kodeine\Metable;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Collection $metas
 */
trait Metable
{
	
	protected $__metaData = null;
	protected $__wasCreatedEventFired = false;
	protected $__wasUpdatedEventFired = false;
	protected $__wasSavedEventFired = false;
	
	/**
	 * whereMeta scope for easier join
	 * -------------------------
	 */
	public function scopeWhereMeta($query, $key, $value, $alias = null) {
		$alias = (empty( $alias )) ? $this->getMetaTable() : $alias;
		return $query->join( $this->getMetaTable() . ' AS ' . $alias, $this->getQualifiedKeyName(), '=', $alias . '.' . $this->getMetaKeyName() )->where( $alias . '.key', '=', $key )->where( $alias . '.value', '=', $value )->select( $this->getTable() . '.*' );
	}
	
	/**
	 * Meta scope for easier join
	 * -------------------------
	 */
	public function scopeMeta($query, $alias = null) {
		$alias = (empty( $alias )) ? $this->getMetaTable() : $alias;
		return $query->join( $this->getMetaTable() . ' AS ' . $alias, $this->getQualifiedKeyName(), '=', $alias . '.' . $this->getMetaKeyName() )->select( $this->getTable() . '.*' );
	}
	
	/**
	 * Set Meta Data functions
	 * -------------------------.
	 */
	public function setMeta($key, $value = null) {
		$setMeta = 'setMeta' . ucfirst( gettype( $key ) );
		
		return $this->$setMeta( $key, $value );
	}
	
	protected function setMetaString($key, $value) {
		$key = strtolower( $key );
		
		// If there is a default value, remove the meta row instead - future returns of
		// this value will be handled via the default logic in the accessor
		if (
			property_exists( $this, 'defaultMetaValues' ) &&
			array_key_exists( $key, $this->defaultMetaValues ) &&
			$this->defaultMetaValues[$key] == $value
		) {
			$this->unsetMeta( $key );
			
			return $this;
		}
		
		if ( $this->getMetaData()->has( $key ) ) {
			
			// Make sure deletion marker is not set
			$this->getMetaData()[$key]->markForDeletion( false );
			
			$this->getMetaData()[$key]->value = $value;
			
			return $this->getMetaData()[$key];
		}
		
		return $this->getMetaData()[$key] = $this->getModelStub( [
			'key' => $key,
			'value' => $value,
		] );
	}
	
	protected function setMetaArray(): Collection {
		list( $metas ) = func_get_args();
		
		$collection = new Collection();
		
		foreach ($metas as $key => $value) {
			$collection[] = $this->setMetaString( $key, $value );
		}
		
		return $collection;
	}
	
	/**
	 * check if meta exists
	 *
	 * @param string|array $key
	 * @return bool
	 */
	public function hasMeta($key): bool {
		if ( is_string( $key ) && preg_match( '/[,|]/is', $key ) ) {
			$key = preg_split( '/ ?[,|] ?/', $key );
		}
		$hasMeta = 'hasMeta' . ucfirst( gettype( $key ) );
		
		return $this->$hasMeta( $key );
	}
	
	protected function hasMetaString($key): bool {
		$key = strtolower( $key );
		if ( $this->getMetaData()->has( $key ) ) {
			return ! $this->getMetaData()[$key]->isMarkedForDeletion();
		}
		return false;
	}
	
	protected function hasMetaArray($keys): bool {
		foreach ($keys as $key) {
			if ( ! $this->hasMeta( $key ) ) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Determine if the meta or any of the given metas have been modified.
	 *
	 * @param array|string|null $metas
	 * @return bool
	 */
	public function isMetaDirty(...$metas): bool {
		if ( empty( $metas ) ) {
			foreach ($this->getMetaData() as $meta) {
				if ( $meta->isDirty() ) {
					return true;
				}
			}
			return false;
		}
		if ( is_array( $metas[0] ) ) {
			$metas = $metas[0];
		}
		elseif ( is_string( $metas[0] ) && preg_match( '/[,|]/is', $metas[0] ) ) {
			$metas = preg_split( '/ ?[,|] ?/', $metas[0] );
		}
		
		foreach ($metas as $meta) {
			if ( $this->getMetaData()->has( $meta ) ) {
				if ( $this->getMetaData()[$meta]->isDirty() ) {
					return true;
				}
				if ( $this->getMetaData()[$meta]->isMarkedForDeletion() ) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Unset Meta Data functions
	 * -------------------------.
	 */
	public function unsetMeta($key) {
		$unsetMeta = 'unsetMeta' . ucfirst( gettype( $key ) );
		
		return $this->$unsetMeta( $key );
	}
	
	protected function unsetMetaString($key) {
		$key = strtolower( $key );
		if ( $this->getMetaData()->has( $key ) ) {
			$this->getMetaData()[$key]->markForDeletion();
		}
	}
	
	protected function unsetMetaArray() {
		list( $keys ) = func_get_args();
		
		foreach ($keys as $key) {
			$key = strtolower( $key );
			$this->unsetMetaString( $key );
		}
	}
	
	/**
	 * Get Meta Data functions
	 * -------------------------.
	 */
	
	public function getMeta($key = null, $default = null) {
		if ( is_string( $key ) && preg_match( '/[,|]/is', $key ) ) {
			$key = preg_split( '/ ?[,|] ?/', $key );
		}
		
		$getMeta = 'getMeta' . ucfirst( strtolower( gettype( $key ) ) );
		
		// Default value is used if getMeta is null
		return $this->$getMeta( $key, $default );
	}
	
	/**
	 * Check if meta has default value
	 * @param $key
	 * @return bool
	 */
	public function hasDefaultMetaValue($key): bool {
		if ( property_exists( $this, 'defaultMetaValues' ) ) {
			return array_key_exists( $key, $this->defaultMetaValues );
		}
		return false;
	}
	
	// Returns either the default value or null if default isn't set
	public function getDefaultMetaValue($key) {
		if ( property_exists( $this, 'defaultMetaValues' ) && array_key_exists( $key, $this->defaultMetaValues ) ) {
			return $this->defaultMetaValues[$key];
		}
		else {
			return null;
		}
	}
	
	protected function getMetaString($key, $default = null) {
		$key = strtolower( $key );
		$meta = $this->getMetaData()->get( $key );
		
		if ( is_null( $meta ) || $meta->isMarkedForDeletion() ) {
			// Default values set in defaultMetaValues property take precedence over default value passed to this method
			return $this->getDefaultMetaValue( $key ) ?? $default;
		}
		
		return $meta->value;
	}
	
	protected function getMetaArray($keys, $default = null): BaseCollection {
		$collection = new BaseCollection();
		
		foreach ($keys as $key) {
			$key = strtolower( $key );
			if ( $this->hasMeta( $key ) ) {
				$meta = $this->getMetaData()[$key];
				if ( ! $meta->isMarkedForDeletion() ) {
					$collection->put( $key, $meta->value );
					continue;
				}
			}
			// Key does not exist, so it's value will be the default value
			// Default values set in defaultMetaValues property take precedence over default value passed to this method
			$defaultValue = $this->getDefaultMetaValue( $key );
			if ( is_null( $defaultValue ) ) {
				if ( is_array( $default ) ) {
					$defaultValue = $default[$key] ?? null;
				}
				else {
					$defaultValue = $default;
				}
			}
			
			$collection->put( $key, $defaultValue );
		}
		
		return $collection;
	}
	
	protected function getMetaNull(): BaseCollection {
		/** @noinspection PhpUnusedLocalVariableInspection */
		list( $keys, $raw ) = func_get_args();
		
		$collection = new BaseCollection();
		
		foreach ($this->getMetaData() as $meta) {
			if ( ! $meta->isMarkedForDeletion() ) {
				$collection->put( $meta->key, $raw ? $meta : $meta->value );
			}
		}
		
		return $collection;
	}
	
	/**
	 * Relationship for meta tables
	 */
	public function metas(): HasMany {
		$classname = $this->getMetaClass();
		$model = new $classname;
		$model->setTable( $this->getMetaTable() );
		
		return new HasMany( $model->newQuery(), $this, $this->getMetaKeyName(), $this->getKeyName() );
	}
	
	protected function getModelStub() {
		// get new meta model instance
		$classname = $this->getMetaClass();
		$model = new $classname;
		$model->setTable( $this->getMetaTable() );
		
		// model fill with attributes.
		if ( func_num_args() > 0 ) {
			array_filter( func_get_args(), [$model, 'fill'] );
		}
		
		return $model;
	}
	
	public function saveMeta() {
		foreach ($this->getMetaData() as $meta) {
			$meta->setTable( $this->getMetaTable() );
			
			if ( $meta->isMarkedForDeletion() ) {
				if ( $meta->exists ) {
					if ( $this->fireMetaEvent( 'deleting', $meta->key ) === false ) {
						continue;
					}
				}
				$meta->delete();
				unset( $this->getMetaData()[$meta->key] );
				$this->fireMetaEvent( 'deleted', $meta->key, false );
				continue;
			}
			
			if ( $meta->isDirty() ) {
				if ( $this->fireMetaEvent( 'saving', $meta->key ) === false ) {
					continue;
				}
				if ( $meta->exists ) {
					if ( $this->fireMetaEvent( 'updating', $meta->key ) === false ) {
						continue;
					}
					$nextEvent = 'updated';
				}
				else {
					if ( $this->fireMetaEvent( 'creating', $meta->key ) === false ) {
						continue;
					}
					$nextEvent = 'created';
				}
				// set meta and model relation id's into meta table.
				$meta->setAttribute( $this->getMetaKeyName(), $this->getKey() );
				if ( $meta->save() ) {
					$this->fireMetaEvent( $nextEvent, $meta->key, false );
					$this->fireMetaEvent( 'saved', $meta->key, false );
				}
			}
		}
		
		if ( $this->__wasCreatedEventFired ) {
			$this->__wasCreatedEventFired = false;
			$this->fireModelEvent( 'createdWithMetas', false );
		}
		
		if ( $this->__wasUpdatedEventFired ) {
			$this->__wasUpdatedEventFired = false;
			$this->fireModelEvent( 'updatedWithMetas', false );
		}
		
		if ( $this->__wasSavedEventFired ) {
			$this->__wasSavedEventFired = false;
			$this->fireModelEvent( 'savedWithMetas', false );
		}
	}
	
	protected function fireMetaEvent($event, $metaName, bool $halt = true) {
		if ( method_exists( $this, '__fireMetaEvent' ) ) {
			return $this->__fireMetaEvent( 'meta' . Str::ucfirst( $event ), $metaName, $halt );
		}
		return true;
	}
	
	public function getMetaData() {
		if ( is_null( $this->__metaData ) ) {
			
			if ( $this->exists && ! is_null( $this->metas ) ) {
				$this->__metaData = $this->metas->keyBy( 'key' );
			}
			else {
				$this->__metaData = new Collection();
			}
		}
		return $this->__metaData;
	}
	
	/**
	 * Return the foreign key name for the meta table.
	 *
	 * @return string
	 */
	public function getMetaKeyName(): string {
		return property_exists( $this, 'metaKeyName' ) ? $this->metaKeyName : $this->getForeignKey();
	}
	
	/**
	 * Return the table name.
	 *
	 * @return string
	 */
	public function getMetaTable(): string {
		return property_exists( $this, 'metaTable' ) ? $this->metaTable : $this->getTable() . '_meta';
	}
	
	/**
	 * Return the model class name.
	 *
	 * @return string
	 */
	protected function getMetaClass(): string {
		return property_exists( $this, 'metaClass' ) ? $this->metaClass : MetaData::class;
	}
	
	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function toArray(): array {
		return (property_exists( $this, 'hideMeta' ) && $this->hideMeta) ?
			parent::toArray() :
			array_merge( parent::toArray(), [
				'meta_data' => $this->getMeta()->toArray(),
			] );
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
	public function getAttribute($key) {
		// parent call first.
		if ( ($attr = parent::getAttribute( $key )) !== null ) {
			return $attr;
		}
		
		// Don't get meta data if fluent access is disabled.
		if ( property_exists( $this, 'disableFluentMeta' ) && $this->disableFluentMeta ) {
			return $attr;
		}
		
		// It is possible that attribute exists, or it has a cast, but it's null, so we check for that
		if ( array_key_exists( $key, $this->attributes ) ||
			array_key_exists( $key, $this->casts ) ||
			$this->hasGetMutator( $key ) ||
			$this->hasAttributeMutator( $key ) ||
			$this->isClassCastable( $key ) ) {
			return $attr;
		}
		
		// If key is a relation name, then return parent value.
		// The reason for this is that it's possible that the relation does not exist and parent call returns null for that.
		if ( $this->isRelation( $key ) && $this->relationLoaded( $key ) ) {
			return $attr;
		}
		
		// there was no attribute on the model
		// retrieve the data from meta relationship
		$meta = $this->getMeta( $key );
		
		// Check for meta accessor
		$accessor = Str::camel( 'get_' . $key . '_meta' );
		
		if ( method_exists( $this, $accessor ) ) {
			return $this->{$accessor}( $meta );
		}
		return $meta;
	}
	
	/**
	 * @inheritDoc
	 */
	public function setAttribute($key, $value) {
		// Don't set meta data if fluent access is disabled.
		if ( property_exists( $this, 'disableFluentMeta' ) && $this->disableFluentMeta ) {
			return parent::setAttribute( $key, $value );
		}
		
		// First we will check for the presence of a mutator
		// or if key is a model attribute or has a column named to the key
		if ( $this->hasSetMutator( $key ) ||
			$this->hasAttributeSetMutator( $key ) ||
			$this->isEnumCastable( $key ) ||
			$this->isClassCastable( $key ) ||
			(! is_null( $value ) && $this->isJsonCastable( $key )) ||
			str_contains( $key, '->' ) ||
			$this->hasColumn( $key ) ||
			array_key_exists( $key, parent::getAttributes() )
		) {
			return parent::setAttribute( $key, $value );
		}
		
		// If there is a default value, remove the meta row instead - future returns of
		// this value will be handled via the default logic in the accessor
		if (
			property_exists( $this, 'defaultMetaValues' ) &&
			array_key_exists( $key, $this->defaultMetaValues ) &&
			$this->defaultMetaValues[$key] == $value
		) {
			$this->unsetMeta( $key );
			
			return $this;
		}
		
		// if the key has a mutator execute it
		$mutator = Str::camel( 'set_' . $key . '_meta' );
		
		if ( method_exists( $this, $mutator ) ) {
			return $this->{$mutator}( $value );
		}
		
		// key doesn't belong to model, lets create a new meta relationship
		return $this->setMetaString( $key, $value );
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
	public function hasColumn($column): bool {
		static $columns;
		$class = get_class( $this );
		if ( ! isset( $columns[$class] ) ) {
			$columns[$class] = $this->getConnection()->getSchemaBuilder()->getColumnListing( $this->getTable() );
			if ( empty( $columns[$class] ) ) {
				$columns[$class] = [];
			}
			$columns[$class] = array_map(
				'strtolower',
				$columns[$class]
			);
		}
		return in_array( strtolower( $column ), $columns[$class] );
	}
	
	public static function bootMetable() {
		static::saved( function ($model) {
			$model->__wasSavedEventFired = true;
			$model->saveMeta();
		} );
		
		static::created( function ($model) {
			$model->__wasCreatedEventFired = true;
		} );
		
		static::updated( function ($model) {
			$model->__wasUpdatedEventFired = true;
		} );
	}
	
	protected function initializeMetable() {
		$this->observables = array_merge( $this->observables, [
			'createdWithMetas',
			'updatedWithMetas',
			'savedWithMetas',
		] );
		$this->observables = array_unique( $this->observables );
	}
	
	public static function createdWithMetas($callback) {
		static::registerModelEvent( 'createdWithMetas', $callback );
	}
	
	public static function updatedWithMetas($callback) {
		static::registerModelEvent( 'updatedWithMetas', $callback );
	}
	
	public static function savedWithMetas($callback) {
		static::registerModelEvent( 'savedWithMetas', $callback );
	}
	
	public function __unset($key) {
		// unset attributes and relations
		parent::__unset( $key );
		
		// Don't unset meta data if fluent access is disabled.
		if ( property_exists( $this, 'disableFluentMeta' ) && $this->disableFluentMeta ) {
			return;
		}
		
		// delete meta, only if pivot-prefix is not detected in order to avoid unnecessary (N+1) queries
		// since Eloquent tries to "unset" pivot-prefixed attributes in m2m queries on pivot tables.
		// N.B. Regular unset of pivot-prefixed keys is thus compromised.
		if ( strpos( $key, 'pivot_' ) !== 0 ) {
			$this->unsetMeta( $key );
		}
	}
}
