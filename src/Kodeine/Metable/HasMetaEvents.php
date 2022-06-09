<?php

namespace Kodeine\Metable;

trait HasMetaEvents
{
	/**
	 * @param $event
	 * @param $metaName
	 * @param bool $halt
	 * @return mixed
	 */
	protected function __fireMetaEvent($event, $metaName, bool $halt = true) {
		if ( ! isset( static::$dispatcher ) ) {
			return true;
		}
		// First, we will get the proper method to call on the event dispatcher, and then we
		// will attempt to fire a custom, object based event for the given event. If that
		// returns a result we can return that result, or we'll call the string events.
		$method = $halt ? 'until' : 'dispatch';
		$result = $this->filterModelEventResults(
			$this->fireCustomMetaEvent( $event, $method, $metaName )
		);
		
		if ( $result === false ) {
			return false;
		}
		
		return ! empty( $result ) ? $result : static::$dispatcher->{$method}(
			"eloquent.{$event}: " . static::class, [
				$this,
				$metaName,
			]
		);
	}
	
	/**
	 * @param $event
	 * @param $method
	 * @param $params
	 * @return mixed|null
	 */
	protected function fireCustomMetaEvent($event, $method, $params) {
		if ( ! isset( $this->dispatchesEvents[$event] ) ) {
			return;
		}
		return static::$dispatcher->$method( new $this->dispatchesEvents[$event]( $this, $params ) );
	}
	
	protected function initializeHasMetaEvents() {
		$this->observables = array_merge( $this->observables, [
			'metaCreating',
			'metaCreated',
			'metaSaving',
			'metaSaved',
			'metaUpdating',
			'metaUpdated',
			'metaDeleting',
			'metaDeleted',
		] );
		$this->observables = array_unique( $this->observables );
	}
	
	public static function metaCreating($callback) {
		static::registerModelEvent( 'metaCreating', $callback );
	}
	
	public static function metaCreated($callback) {
		static::registerModelEvent( 'metaCreated', $callback );
	}
	
	public static function metaSaving($callback) {
		static::registerModelEvent( 'metaSaving', $callback );
	}
	
	public static function metaSaved($callback) {
		static::registerModelEvent( 'metaSaved', $callback );
	}
	
	public static function metaUpdating($callback) {
		static::registerModelEvent( 'metaUpdating', $callback );
	}
	
	public static function metaUpdated($callback) {
		static::registerModelEvent( 'metaUpdated', $callback );
	}
	
	public static function metaDeleting($callback) {
		static::registerModelEvent( 'metaDeleting', $callback );
	}
	
	public static function metaDeleted($callback) {
		static::registerModelEvent( 'metaDeleted', $callback );
	}
}