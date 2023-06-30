<?php

namespace Kodeine\Metable\Tests\Traits;

use Kodeine\Metable\Tests\Casts\UserCastedObject;

trait HasUserCasts
{
	public $casted = [
		'state' => UserCastedObject::class,
	];

	public static function bootHasUserCasts()
	{
		self::creating( function ( $model ) {
			$model->setDefaultCastedProperties();
		} );
	}

	public function initializeHasUserCasts()
	{
		$this->setDefaultCastedProperties();
	}

	private function setDefaultCastedProperties() 
	{
		foreach ( $this->casted as $key => $class ) {
			if ($this->{$key} === null) {
				continue;
			}

			$this->{$key} = new $class( $key );
		}
	}
}