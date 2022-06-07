<?php

namespace Kodeine\Metable\Tests\Observers;

use Kodeine\Metable\Tests\Models\EventTest;

class EventObserver
{
	public function metaSaving(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaUpdating(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaDeleting(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	protected function genericObserver(EventTest $model, $meta, $eventName) {
		if ( ! isset( $model->observersChanges[$eventName] ) ) {
			$model->observersChanges[$eventName] = [];
		}
		$model->observersChanges[$eventName][] = $meta;
		if ( $model->observersShouldReturnFalse ) {
			return false;
		}
	}
}