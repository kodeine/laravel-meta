<?php

namespace Kodeine\Metable\Tests\Casts;

use Exception;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class StateCaster implements CastsAttributes
{
	private $baseStateClass;
	
	public function __construct(string $baseStateClass) {
		$this->baseStateClass = $baseStateClass;
	}
	
	public function get($model, $key, $value, $attributes) {
		if ( is_null( $value ) ) return null;
		
		if ( ! is_subclass_of( $value, $this->baseStateClass ) ) {
			return null;
		}
		
		$stateClassName = $value::config()['default'];
		
		return new $stateClassName( $model );
	}
	
	/**
	 * @throws Exception
	 */
	public function set($model, $key, $value, $attributes) {
		if ( is_null( $value ) ) return null;
		
		if ( ! is_subclass_of( $value, $this->baseStateClass ) ) {
			throw new Exception( 'Invalid state class.' );
		}
		
		$value = new $value( $model );
		
		if ( $value instanceof $this->baseStateClass ) {
			$value->setField( $key );
		}
		
		return $value->getMorphClass();
	}
}