<?php

namespace Kodeine\Metable\Tests\Models;

use Kodeine\Metable\Metable;
use Illuminate\Events\Dispatcher;
use Kodeine\Metable\HasMetaEvents;
use Illuminate\Database\Eloquent\Model;
use Kodeine\Metable\Tests\Events\MetaSaving;
use Kodeine\Metable\Tests\Events\MetaUpdating;

class Event extends Model
{
	use Metable, HasMetaEvents;
	
	public $listenersChanges = [];
	public $observersChanges = [];
	public $classListenersChanges = [];
	public $listenerShouldReturnFalse = false;
	public $observersShouldReturnFalse = false;
	public $classListenersShouldReturnFalse = false;
	
	protected $dispatchesEvents = [
		'metaSaving' => MetaSaving::class,
		'metaUpdating' => MetaUpdating::class,
	];
	
	public static function boot() {
		static::setEventDispatcher( new Dispatcher() );
		parent::boot();
		
		$listener = function (Event $model, $meta, $eventName) {
			if ( ! isset( $model->listenersChanges[$eventName] ) ) {
				$model->listenersChanges[$eventName] = [];
			}
			$model->listenersChanges[$eventName][] = $meta;
			if ( $model->listenerShouldReturnFalse ) {
				return false;
			}
		};
		
		static::metaSaving( function (Event $model, $meta) use ($listener) {
			return $listener( $model, $meta, 'metaSaving' );
		} );
		
		static::metaUpdating( function (Event $model, $meta) use ($listener) {
			return $listener( $model, $meta, 'metaUpdating' );
		} );
	}
}