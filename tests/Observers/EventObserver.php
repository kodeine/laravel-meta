<?php

namespace Kodeine\Metable\Tests\Observers;

use Kodeine\Metable\Tests\Models\EventTest;

class EventObserver
{
	public function metaCreating(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaCreated(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaSaving(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaSaved(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaUpdating(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaUpdated(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaDeleting(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function metaDeleted(EventTest $model, $meta) {
		return $this->genericObserver( $model, $meta, __FUNCTION__ );
	}
	
	public function createdWithMetas(EventTest $model) {
		return $this->genericObserver( $model, null, __FUNCTION__ );
	}
	
	public function updatedWithMetas(EventTest $model) {
		return $this->genericObserver( $model, null, __FUNCTION__ );
	}
	
	public function savedWithMetas(EventTest $model) {
		return $this->genericObserver( $model, null, __FUNCTION__ );
	}
	
	protected function genericObserver(EventTest $model, $meta, $eventName) {
		if ( ! isset( $model->observersChanges[$eventName] ) ) {
			$model->observersChanges[$eventName] = [];
		}
		$model->observersChanges[$eventName][] = $meta;
		if ( $model->observersShouldReturnFalse[$eventName] ?? false ) {
			return false;
		}
	}
}