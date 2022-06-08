<?php

namespace Kodeine\Metable\Tests\Models;

use Kodeine\Metable\Metable;
use Illuminate\Events\Dispatcher;
use Kodeine\Metable\HasMetaEvents;
use Illuminate\Database\Eloquent\Model;
use Kodeine\Metable\Tests\Events\MetaSavingTestEvent;
use Kodeine\Metable\Tests\Events\MetaUpdatingTestEvent;
use Kodeine\Metable\Tests\Events\MetaDeletingTestEvent;
use Kodeine\Metable\Tests\Events\MetaCreatingTestEvent;

class EventTest extends Model
{
	use Metable, HasMetaEvents;
	
	public $listenersChanges = [];
	public $observersChanges = [];
	public $classListenersChanges = [];
	public $listenerShouldReturnFalse = [];
	public $observersShouldReturnFalse = [];
	public $classListenersShouldReturnFalse = [];
	
	protected $dispatchesEvents = [
		'metaCreating' => MetaCreatingTestEvent::class,
		'metaSaving' => MetaSavingTestEvent::class,
		'metaUpdating' => MetaUpdatingTestEvent::class,
		'metaDeleting' => MetaDeletingTestEvent::class,
	];
	
	public static function boot() {
		static::setEventDispatcher( new Dispatcher() );
		parent::boot();
		
		$listener = function (EventTest $model, $meta, $eventName) {
			if ( ! isset( $model->listenersChanges[$eventName] ) ) {
				$model->listenersChanges[$eventName] = [];
			}
			$model->listenersChanges[$eventName][] = $meta;
			if ( $model->listenerShouldReturnFalse[$eventName] ?? false ) {
				return false;
			}
		};
		
		static::metaCreating( function (EventTest $model, $meta) use ($listener) {
			return $listener( $model, $meta, 'metaCreating' );
		} );
		
		static::metaSaving( function (EventTest $model, $meta) use ($listener) {
			return $listener( $model, $meta, 'metaSaving' );
		} );
		
		static::metaUpdating( function (EventTest $model, $meta) use ($listener) {
			return $listener( $model, $meta, 'metaUpdating' );
		} );
		
		static::metaDeleting( function (EventTest $model, $meta) use ($listener) {
			return $listener( $model, $meta, 'metaDeleting' );
		} );
	}
}