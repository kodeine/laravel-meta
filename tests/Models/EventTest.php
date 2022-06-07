<?php

namespace Kodeine\Metable\Tests\Models;

use Kodeine\Metable\Metable;
use Illuminate\Events\Dispatcher;
use Kodeine\Metable\HasMetaEvents;
use Illuminate\Database\Eloquent\Model;
use Kodeine\Metable\Tests\Events\MetaSavingTestEvent;
use Kodeine\Metable\Tests\Events\MetaUpdatingTestEvent;

class EventTest extends Model
{
	use Metable, HasMetaEvents;
	
	public $listenersChanges = [];
	public $observersChanges = [];
	public $classListenersChanges = [];
	public $listenerShouldReturnFalse = false;
	public $observersShouldReturnFalse = false;
	public $classListenersShouldReturnFalse = false;
	
	protected $dispatchesEvents = [
		'metaSaving' => MetaSavingTestEvent::class,
		'metaUpdating' => MetaUpdatingTestEvent::class,
	];
	
	public static function boot() {
		static::setEventDispatcher( new Dispatcher() );
		parent::boot();
		
		$listener = function (EventTest $model, $meta, $eventName) {
			if ( ! isset( $model->listenersChanges[$eventName] ) ) {
				$model->listenersChanges[$eventName] = [];
			}
			$model->listenersChanges[$eventName][] = $meta;
			if ( $model->listenerShouldReturnFalse ) {
				return false;
			}
		};
		
		static::metaSaving( function (EventTest $model, $meta) use ($listener) {
			return $listener( $model, $meta, 'metaSaving' );
		} );
		
		static::metaUpdating( function (EventTest $model, $meta) use ($listener) {
			return $listener( $model, $meta, 'metaUpdating' );
		} );
	}
}