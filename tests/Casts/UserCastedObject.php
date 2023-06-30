<?php

namespace Kodeine\Metable\Tests\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class UserCastedObject implements Castable, CastsAttributes
{
	public $description;
	
	public function get($model, $key, $value, $attributes) {
		if ( is_null( $value ) ) return null;
		return json_decode( $value, true );
	}
	
	public function set($model, $key, $value, $attributes) {
		return [$key => json_encode( $value )];
	}
	
	public static function castUsing(array $arguments) {
		return new self();
	}
}