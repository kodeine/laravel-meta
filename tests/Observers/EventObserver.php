<?php

namespace Kodeine\Metable\Tests\Observers;

use Kodeine\Metable\Tests\Models\Event;

class EventObserver
{
	public function metaSaving(Event $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	protected function genericObserver(Event $model, $meta, $eventName) {
		if ( ! isset( $model->observersChanges[$eventName] ) ) {
			$model->observersChanges[$eventName] = [];
		}
		$model->observersChanges[$eventName][] = $meta;
		if ( $model->observersShouldReturnFalse ) {
			return false;
		}
	}
}