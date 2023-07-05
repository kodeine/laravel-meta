<?php

namespace Kodeine\Metable\Tests\Traits;

use Kodeine\Metable\Tests\Casts\UserState\State;
use Kodeine\Metable\Tests\Casts\UserCastedObject;

trait HasUserStates
{
	public $casted = [
		'state' => State::class,
	];
	
	public static function bootHasUserCasts() {
		self::creating( function ($model) {
			$model->setDefaultCastedProperties();
		} );
	}
	
	public function initializeHasUserCasts() {
		$this->setDefaultCastedProperties();
	}
	
	private function getStateConfigs() {
		$casts = $this->getCasts();
		
		$states = [];
		
		foreach ($casts as $prop => $state) {
			if ( ! is_subclass_of( $state, UserCastedObject::class ) ) {
				continue;
			}
			
			$states[$prop] = $state::config();
		}
		
		return $states;
	}
	
	private function setDefaultCastedProperties() {
		foreach ($this->getStateConfigs() as $prop => $config) {
			if ( $this->{$prop} === null ) {
				continue;
			}
			
			if ( ! isset( $config['default'] ) ) {
				continue;
			}
			
			$this->{$prop} = $config['default'];
		}
	}
}