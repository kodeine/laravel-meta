<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace Kodeine\Metable\Tests;

use PHPUnit\Framework\TestCase;
use Kodeine\Metable\Tests\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class MetableTest extends TestCase
{
	protected function setUp(): void {
		$capsule = new Capsule;
		$capsule->addConnection( [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'charset' => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix' => '',
			'foreign_key_constraints' => true,
		] );
		$capsule->setAsGlobal();
		$capsule->bootEloquent();
		Capsule::schema()->enableForeignKeyConstraints();
		Capsule::schema()->create( 'users', function ($table) {
			$table->id();
			$table->string( 'name' )->default( 'john' );
			$table->string( 'email' )->default( 'john@doe.com' );
			$table->string( 'password' )->nullable();
			$table->integer( 'user_id' )->unsigned()->nullable();
			$table->foreign( 'user_id' )->references( 'id' )->on( 'users' );
			$table->timestamps();
		} );
		Capsule::schema()->create( 'users_meta', function ($table) {
			$table->id();
			$table->integer( 'user_id' )->unsigned();
			$table->foreign( 'user_id' )->references( 'id' )->on( 'users' )->onDelete( 'cascade' );
			$table->string( 'type' )->default( 'null' );
			$table->string( 'key' )->index();
			$table->text( 'value' )->nullable();
			
			$table->timestamps();
		} );
		/*Capsule::schema()->table( 'users_meta', function ($table) {
			$table->foreign( 'user_id' )->references( 'id' )->on( 'users' )->onDelete( 'cascade' );
		} );*/
	}
	
	public function testFluentMeta() {
		$user = new User;
		
		$this->assertNull( $user->foo, 'Meta should be null by default' );
		
		$user->foo = 'bar';
		
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertTrue( isset( $user->foo ), 'Fluent meta should be set before save.' );
		$this->assertEquals( 'bar', $user->foo, 'Fluent setter not working before save.' );
		$this->assertEquals( 0, Capsule::table( 'users_meta' )->count(), 'Fluent setter should not save to database before save.' );
		
		$this->assertNull( $user->dummy, 'Dummy relation should be null by default' );
		$this->dummy = 'dummy';
		
		$user->save();
		
		$this->assertNull( $user->dummy, 'Dummy relation should be null after setting meta named dummy' );
		
		$metaData = Capsule::table( 'users_meta' )->where( $user->getMetaKeyName(), $user->getKey() )->where( 'key', 'foo' );
		
		$this->assertTrue( isset( $user->foo ), 'Fluent meta should be set.' );
		$this->assertEquals( 'bar', $user->foo, 'Fluent setter not working.' );
		$this->assertEquals( 'bar', is_null( $meta = $metaData->first() ) ? null : $meta->value, 'Fluent setter did not save meta to database.' );
		
		$user->foo = 'baz';
		
		$this->assertEquals( 'baz', $user->foo, 'Fluent setter did not update existing meta before save.' );
		$this->assertEquals( 'bar', is_null( $meta = $metaData->first() ) ? null : $meta->value, 'Fluent setter should not update meta in database before save.' );
		
		$user->save();
		
		$this->assertEquals( 'baz', $user->foo, 'Fluent setter did not update existing meta.' );
		$this->assertEquals( 'baz', is_null( $meta = $metaData->first() ) ? null : $meta->value, 'Fluent setter did not update meta in database.' );
		$this->assertEquals( 1, $metaData->count(), 'Fluent setter created multiple rows for one meta data.' );
		
		unset( $user->foo );
		
		$this->assertNull( $user->foo, 'Unsetter did not work before save.' );
		$this->assertEquals( 'baz', is_null( $meta = $metaData->first() ) ? null : $meta->value, 'Fluent unsetter should not remove meta from database before save.' );
		$this->assertEquals( 1, $metaData->count(), 'Fluent unsetter should not remove meta from database before save.' );
		$this->assertFalse( isset( $user->foo ), 'Fluent meta should not be set before save.' );
		
		$user->save();
		
		$this->assertNull( $user->foo, 'Unsetter did not work.' );
		$this->assertNull( $metaData->first(), 'Fluent unsetter did not remove meta from database.' );
		$this->assertEquals( 0, $metaData->count(), 'Fluent unsetter did not remove meta from database.' );
		$this->assertFalse( isset( $user->foo ), 'Fluent meta should not be set.' );
		
		$user->foo = 'bar';
		$user->save();
		$user->delete();
		
		$this->assertEquals( 0, $metaData->count(), 'Meta should be deleted from database after deleting user.' );
	}
	
	public function testScopes() {
		$user1 = new User;
		$user1->foo = 'bar';
		$user1->save();
		
		$user2 = new User;
		$user2->foo = 'baz';
		$user2->save();
		
		$scope = User::meta()->where( 'users_meta.key', 'foo' )->where( 'users_meta.value', 'baz' );
		$user = $scope->first();
		$this->assertEquals( $user2->getKey(), $user->getKey(), 'Meta scope found wrong user' );
		$this->assertEquals( $user->foo, $user2->foo, 'Meta scope found wrong user' );
		$this->assertNotNull( $user->metas, 'Metas relation should not be null' );
		
		$scope = User::whereMeta( 'foo', 'baz' );
		$user = $scope->first();
		$this->assertEquals( $user2->getKey(), $user->getKey(), 'WhereMeta scope found wrong user' );
		$this->assertEquals( $user->foo, $user2->foo, 'WhereMeta scope found wrong user' );
		
		$user1->delete();
		$user2->delete();
	}
	
	public function testDefaultMetaValues() {
		$user = new User;
		
		$this->assertTrue( isset( $user->default_meta_key ), 'Default meta key should be set' );
		$this->assertTrue( $user->hasDefaultMetaValue( 'default_meta_key' ), 'Default meta key should be set' );
		$this->assertFalse( $user->hasDefaultMetaValue( 'foo' ), 'Non Default meta key should not be set' );
		$this->assertEquals( 'default_meta_value', $user->default_meta_key, 'Default meta value should be set' );
		$user->default_meta_key = 'foo';
		$this->assertEquals( 'foo', $user->default_meta_key, 'Default meta value should be changed' );
		
		$user->save();
		$metaData = Capsule::table( 'users_meta' )->where( $user->getMetaKeyName(), $user->getKey() )->where( 'key', 'default_meta_key' );
		
		$this->assertEquals( 'foo', is_null( $meta = $metaData->first() ) ? null : $meta->value, 'Default value should be changed in database.' );
		
		$user->default_meta_key = 'default_meta_value';
		$user->save();
		
		$this->assertNull( $metaData->first(), 'Default value should be removed from database.' );
		
		$user->delete();
	}
	
	public function testAccessorAndMutator() {
		$user = new User;
		
		$this->assertTrue( isset( $user->accessor ), 'Meta accessor key should be set' );
		$this->assertEquals( 'accessed_', $user->accessor, 'Meta accessor value should be set' );
		
		$user->accessor = 'foo';
		$this->assertEquals( 'accessed_foo', $user->accessor, 'Meta accessor value should be changed' );
		
		$this->assertFalse( isset( $user->mutator ), 'Meta mutator key should not be set' );
		
		$user->mutator = 'foo';
		$this->assertEquals( 'mutated_foo', $user->mutator, 'Meta mutator value should be changed' );
	}
	
	public function testMetaMethods() {
		$user = new User;
		
		$user->setMeta( 'foo', 'bar' );
		$this->assertEquals( 'bar', $user->getMeta( 'foo' ), 'Meta method getMeta did not return correct value' );
		
		$user->setMeta( [
			'foo' => 'baz',
			'bas' => 'bar',
		] );
		
		$user->save();
		
		// re retrieve user to make sure meta is saved
		$user = User::find( $user->getKey() );
		
		$this->assertEquals( 'baz', $user->getMeta( 'foo' ), 'Meta method getMeta did not return correct value' );
		$this->assertEquals( 'bar', $user->getMeta( 'bas' ), 'Meta method getMeta did not return correct value' );
		$this->assertSame( ['foo' => 'baz', 'bas' => 'bar'], $user->getMeta()->toArray(), 'Meta method getMeta did not return correct value' );
		$this->assertSame( ['foo' => 'baz', 'bas' => 'bar'], $user->getMeta( ['foo', 'bas'] )->toArray(), 'Meta method getMeta did not return correct value' );
		$this->assertSame( ['foo' => 'baz', 'bas' => 'bar'], $user->getMeta( 'foo|bas' )->toArray(), 'Meta method getMeta did not return correct value' );
		$this->assertSame( ['foo' => 'baz'], $user->getMeta( ['foo'] )->toArray(), 'Meta method getMeta did not return correct value' );
		
		$this->assertTrue( $user->hasMeta( 'foo' ), 'Meta method hasMeta did not return correct value' );
		$this->assertFalse( $user->hasMeta( 'bar' ), 'Meta method hasMeta did not return correct value' );
		$this->assertTrue( $user->hasMeta( ['foo', 'bas'] ), 'Meta method hasMeta did not return correct value' );
		$this->assertTrue( $user->hasMeta( ['foo|bas'] ), 'Meta method hasMeta did not return correct value' );
		$this->assertFalse( $user->hasMeta( ['foo', 'bar'] ), 'Meta method hasMeta did not return correct value' );
		
		$user->unsetMeta( 'foo' );
		$this->assertFalse( $user->hasMeta( 'foo' ), 'Meta method hasMeta did not return correct value' );
		$this->assertNull( $user->getMeta( 'foo' ), 'Meta method getMeta did not return correct value' );
		
		$user->setMeta( 'foo', 'bar' );
		$user->setMeta( 'bar', 'baz' );
		$user->unsetMeta( ['foo', 'bas'] );
		
		$this->assertFalse( $user->hasMeta( 'foo' ), 'Meta method hasMeta did not return correct value' );
		$this->assertFalse( $user->hasMeta( 'bas' ), 'Meta method hasMeta did not return correct value' );
		$this->assertFalse( $user->hasMeta( ['foo', 'bas'] ), 'Meta method hasMeta did not return correct value' );
		$this->assertFalse( $user->hasMeta( ['foo|bas'] ), 'Meta method hasMeta did not return correct value' );
		$this->assertFalse( $user->hasMeta( ['foo', 'bar'] ), 'Meta method hasMeta did not return correct value' );
		$this->assertTrue( $user->hasMeta( ['bar'] ), 'Meta method hasMeta did not return correct value' );
		
		$user->delete();
	}
	
	public function testDefaultParameterInGetMeta() {
		$user = new User;
		
		$this->assertEquals( 'default_value', $user->getMeta( 'foo', 'default_value' ), 'Default parameter should be returned when meta is null' );
		$this->assertSame( ['foo' => 'foo_value', 'bar' => 'bar_value'], $user->getMeta( ['foo', 'bar'], ['foo' => 'foo_value', 'bar' => 'bar_value'] )->toArray(), 'Default parameter should be returned when meta is null' );
		$this->assertSame( ['foo' => 'default_value', 'bar' => 'default_value'], $user->getMeta( ['foo', 'bar'], 'default_value' )->toArray(), 'Default parameter should be returned when meta is null' );
		
		$this->assertEquals( 'default_meta_value', $user->getMeta( 'default_meta_key', 'bar' ), 'Default value set in defaultMetaValues property should be returned when meta is null' );
		$this->assertSame( ['default_meta_key' => 'default_meta_value', 'foo' => 'bar'], $user->getMeta( ['default_meta_key', 'foo'], ['default_meta_key' => 'bar', 'foo' => 'bar'] )->toArray(), 'Default value set in defaultMetaValues property should be returned when meta is null' );
		$this->assertSame( ['default_meta_key' => 'default_meta_value', 'foo' => 'bar'], $user->getMeta( ['default_meta_key', 'foo'], 'bar' )->toArray(), 'Default value set in defaultMetaValues property should be returned when meta is null' );
	}
	
	public function testHasColumn() {
		$user = new User;
		$this->assertTrue( $user->hasColumn( 'name' ), 'User does not have "name" column' );
		$this->assertFalse( $user->hasColumn( 'foo' ), 'User should not have "foo" column' );
	}
	
	public function testHideMeta() {
		$user = new User;
		$user->setMeta( 'foo', 'bar' );
		$user->hideMeta = false;
		
		$this->assertArrayHasKey( 'meta_data', $user->toArray(), 'Metas should be included in array' );
		$this->assertArrayHasKey( 'foo', $user->toArray()['meta_data'], 'Meta should be included in array' );
		
		$user->hideMeta = true;
		$this->assertArrayNotHasKey( 'meta_data', $user->toArray(), 'Metas should not be included in array' );
		
	}
}