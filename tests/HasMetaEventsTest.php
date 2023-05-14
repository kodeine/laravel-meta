<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedFieldInspection */

namespace Kodeine\Metable\Tests;

use PHPUnit\Framework\TestCase;
use Kodeine\Metable\Tests\Models\EventTest;
use Kodeine\Metable\Tests\Events\MetaSavedTestEvent;
use Kodeine\Metable\Tests\Events\MetaSavingTestEvent;
use Kodeine\Metable\Tests\Events\MetaCreatedTestEvent;
use Kodeine\Metable\Tests\Events\MetaUpdatedTestEvent;
use Kodeine\Metable\Tests\Events\MetaDeletedTestEvent;
use Kodeine\Metable\Tests\Events\MetaUpdatingTestEvent;
use Illuminate\Database\Capsule\Manager as Capsule;
use Kodeine\Metable\Tests\Observers\EventObserver;
use Kodeine\Metable\Tests\Events\MetaDeletingTestEvent;
use Kodeine\Metable\Tests\Events\MetaCreatingTestEvent;
use Kodeine\Metable\Tests\Events\SavedWithMetasTestEvent;
use Kodeine\Metable\Tests\Events\CreatedWithMetasTestEvent;
use Kodeine\Metable\Tests\Events\UpdatedWithMetasTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaSavedTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaSavingTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaCreatedTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaUpdatedTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaDeletedTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaUpdatingTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaDeletingTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleMetaCreatingTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleSavedWithMetasTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleCreatedWithMetasTestEvent;
use Kodeine\Metable\Tests\Listeners\HandleUpdatedWithMetasTestEvent;

class HasMetaEventsTest extends TestCase
{
	public static function setUpBeforeClass(): void {
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
		Capsule::schema()->create( 'event_tests', function ($table) {
			$table->id();
			$table->string( 'name' )->default( 'john' );
			$table->timestamps();
		} );
		Capsule::schema()->create( 'event_tests_meta', function ($table) {
			$table->id();
			$table->integer( 'event_test_id' )->unsigned();
			$table->foreign( 'event_test_id' )->references( 'id' )->on( 'event_tests' )->onDelete( 'cascade' );
			$table->string( 'type' )->default( 'null' );
			$table->string( 'key' )->index();
			$table->text( 'value' )->nullable();
			
			$table->timestamps();
		} );
		
		// create new model so that it's boot method registers the event dispatcher
		/** @noinspection PhpUnusedLocalVariableInspection */
		$event = new EventTest;
		EventTest::observe( EventObserver::class );
		
		$listen = [
			MetaCreatingTestEvent::class => [
				HandleMetaCreatingTestEvent::class,
			],
			MetaCreatedTestEvent::class => [
				HandleMetaCreatedTestEvent::class,
			],
			MetaSavingTestEvent::class => [
				HandleMetaSavingTestEvent::class,
			],
			MetaSavedTestEvent::class => [
				HandleMetaSavedTestEvent::class,
			],
			MetaUpdatingTestEvent::class => [
				HandleMetaUpdatingTestEvent::class,
			],
			MetaUpdatedTestEvent::class => [
				HandleMetaUpdatedTestEvent::class,
			],
			MetaDeletingTestEvent::class => [
				HandleMetaDeletingTestEvent::class,
			],
			MetaDeletedTestEvent::class => [
				HandleMetaDeletedTestEvent::class,
			],
			CreatedWithMetasTestEvent::class => [
				HandleCreatedWithMetasTestEvent::class,
			],
			UpdatedWithMetasTestEvent::class => [
				HandleUpdatedWithMetasTestEvent::class,
			],
			SavedWithMetasTestEvent::class => [
				HandleSavedWithMetasTestEvent::class,
			],
		];
		foreach ($listen as $event => $listeners) {
			foreach ($listeners as $listener) {
				EventTest::getEventDispatcher()->listen( $event, $listener );
			}
		}
	}
	
	public function testMetaCreatingEvent() {
		$eventName = 'metaCreating';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		$event->listenerShouldReturnFalse[$eventName] = true;
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		$this->assertNull( $metaData->first(), "Meta should not be saved" );
		
		$event->listenerShouldReturnFalse[$eventName] = false;
		$event->observersShouldReturnFalse[$eventName] = true;
		$event->foo = 'baz';
		$event->save();
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->observersShouldReturnFalse[$eventName] = false;
		$event->classListenersShouldReturnFalse[$eventName] = true;
		$event->save();
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->classListenersShouldReturnFalse[$eventName] = false;
		$event->save();
		
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->delete();
	}
	
	public function testMetaCreatedEvent() {
		$eventName = 'metaCreated';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		$event->listenerShouldReturnFalse['metaCreating'] = true;
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertNotContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		$this->assertNull( $metaData->first(), "Meta should not be saved" );
		
		$event->delete();
	}
	
	public function testMetaSavingEvent() {
		$eventName = 'metaSaving';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		$event->listenerShouldReturnFalse[$eventName] = true;
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		$this->assertNull( $metaData->first(), "Meta should not be saved" );
		
		$event->listenerShouldReturnFalse[$eventName] = false;
		$event->observersShouldReturnFalse[$eventName] = true;
		$event->foo = 'baz';
		$event->save();
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->observersShouldReturnFalse[$eventName] = false;
		$event->classListenersShouldReturnFalse[$eventName] = true;
		$event->save();
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->classListenersShouldReturnFalse[$eventName] = false;
		$event->save();
		
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->delete();
	}
	
	public function testMetaSavedEvent() {
		$eventName = 'metaSaved';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		$event->listenerShouldReturnFalse['metaSaving'] = true;
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertNotContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		$this->assertNull( $metaData->first(), "Meta should not be saved" );
		
		$event->delete();
	}
	
	public function testMetaUpdatingEvent() {
		$eventName = 'metaUpdating';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		
		$this->assertNotContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired when meta didn't exist before" );
		$this->assertNotContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should not be fired when meta didn't exist before" );
		$this->assertNotContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should not be fired when meta didn't exist before" );
		
		$event->foo = 'foobar';
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		$event->saveMeta();
		$event->bar = 'foobar';
		$event->listenerShouldReturnFalse[$eventName] = true;
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		$this->assertEquals( 'bar', is_null( $meta = $metaData->first() ) ? null : $meta->value, "Meta should not be changed" );
		
		$event->listenerShouldReturnFalse[$eventName] = false;
		$event->observersShouldReturnFalse[$eventName] = true;
		$event->save();
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->observersShouldReturnFalse[$eventName] = false;
		$event->classListenersShouldReturnFalse[$eventName] = true;
		$event->save();
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->classListenersShouldReturnFalse[$eventName] = false;
		$event->save();
		
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->delete();
	}
	
	public function testMetaUpdatedEvent() {
		$eventName = 'metaUpdated';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		$event->foo = 'foobar';
		$event->saveMeta();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		$event->saveMeta();
		$event->bar = 'foobar';
		$event->listenerShouldReturnFalse['metaUpdating'] = true;
		
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertNotContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertTrue( $event->isMetaDirty( 'bar' ), "Meta should be dirty" );
		$this->assertEquals( 'bar', is_null( $meta = $metaData->first() ) ? null : $meta->value, "Meta should not be updated" );
		
		$event->delete();
	}
	
	public function testMetaDeletingEvent() {
		$eventName = 'metaDeleting';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		
		$event->unsetMeta( 'foo' );
		$event->save();
		
		$this->assertNotContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertNotContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertNotContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertFalse( $event->hasMeta( 'foo' ), "Meta should not exist" );
		
		$event->foo = 'bar';
		$event->saveMeta();
		$event->unsetMeta( 'foo' );
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->hasMeta( 'foo' ), "Meta should not exist" );
		
		$event->bar = 'bar';
		$event->save();
		$event->listenerShouldReturnFalse[$eventName] = true;
		$event->unsetMeta( 'bar' );
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertTrue( $event->getMetaData()->has( 'bar' ), "Meta should exist" );
		$this->assertFalse( $event->hasMeta( 'bar' ), "Meta should not exist because it is marked for deletion" );
		$this->assertNotNull( $metaData->first(), "Meta should not be removed" );
		
		$event->listenerShouldReturnFalse[$eventName] = false;
		$event->observersShouldReturnFalse[$eventName] = true;
		$event->save();
		
		$this->assertTrue( $event->getMetaData()->has( 'bar' ), "Meta should exist" );
		$this->assertFalse( $event->hasMeta( 'bar' ), "Meta should not exist because it is marked for deletion" );
		$this->assertNotNull( $metaData->first(), "Meta should not be removed" );
		
		$event->observersShouldReturnFalse[$eventName] = false;
		$event->classListenersShouldReturnFalse[$eventName] = true;
		$event->save();
		
		$this->assertTrue( $event->getMetaData()->has( 'bar' ), "Meta should exist" );
		$this->assertFalse( $event->hasMeta( 'bar' ), "Meta should not exist because it is marked for deletion" );
		$this->assertNotNull( $metaData->first(), "Meta should not be removed" );
		
		$event->classListenersShouldReturnFalse[$eventName] = false;
		$event->save();
		
		$this->assertFalse( $event->getMetaData()->has( 'bar' ), "Meta should not exist" );
		$this->assertNull( $metaData->first(), "Meta should be removed" );
		
		$event->delete();
	}
	
	public function testMetaDeletedEvent() {
		$eventName = 'metaDeleted';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->unsetMeta( 'foo' );
		$event->save();
		
		$this->assertNotContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertNotContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertNotContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertFalse( $event->hasMeta( 'foo' ), "Meta should not exist" );
		
		$event->foo = 'bar';
		$event->saveMeta();
		$event->unsetMeta( 'foo' );
		$event->save();
		
		$this->assertContains( 'foo', $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired" );
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertContains( 'foo', $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertContains( 'foo', $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		$this->assertFalse( $event->isMetaDirty( 'bar' ), "Meta should not be dirty" );
		
		$event->bar = 'bar';
		$event->save();
		$event->listenerShouldReturnFalse['metaDeleting'] = true;
		$event->unsetMeta( 'bar' );
		$event->save();
		
		$metaData = Capsule::table( $event->getMetaTable() )->where( $event->getMetaKeyName(), $event->getKey() )->where( 'key', 'bar' );
		
		$this->assertNotContains( 'bar', $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertTrue( $event->getMetaData()->has( 'bar' ), "Meta should exist" );
		$this->assertEquals( 'bar', is_null( $meta = $metaData->first() ) ? null : $meta->value, "Meta should not be removed" );
		
		$event->listenerShouldReturnFalse['metaDeleting'] = false;
		$event->delete();
	}
	
	public function testCreatedWithMetasEvent() {
		$eventName = 'createdWithMetas';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		EventTest::creating( function () {
			static $fired = false;//make sure event listener fired only once
			if ( ! $fired ) {
				$fired = true;
				return false;
			}
			return true;
		} );
		
		$event->foo = 'bar';
		$event->save();//the creating event in above should prevent model saving process
		
		$this->assertEmpty( $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertEmpty( $event->observersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertEmpty( $event->classListenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertFalse( $event->exists, "model should not be saved" );
		
		$event->save();//the creating event in above should have no affect
		
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		
		$event->bar = 'bar';
		
		$event->save();//model already created. so everything should be the same
		
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		
		$event->delete();
	}
	
	public function testUpdatedWithMetasEvent() {
		$eventName = 'updatedWithMetas';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		$event->foo = 'bar';
		$event->save();
		
		$this->assertEmpty( $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertEmpty( $event->observersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertEmpty( $event->classListenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		
		$event->name = 'foo';
		$event->save();
		
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		
		EventTest::updating( function () {
			static $fired = false;//make sure event listener fired only once
			if ( ! $fired ) {
				$fired = true;
				return false;
			}
			return true;
		} );
		
		$event->name = 'bar';
		
		$event->save();//the updating event in above should prevent model updating process
		
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		
		$event->delete();
	}
	
	public function testSavedWithMetasEvent() {
		$eventName = 'savedWithMetas';
		$event = new EventTest;
		
		$this->assertContains( $eventName, $event->getObservableEvents(), "$eventName event should be observable" );
		
		EventTest::saving( function () {
			static $fired = false;//make sure event listener fired only once
			if ( ! $fired ) {
				$fired = true;
				return false;
			}
			return true;
		} );
		
		$event->foo = 'bar';
		$event->save();//the saving event in above should prevent model saving process
		
		$this->assertEmpty( $event->listenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertEmpty( $event->observersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertEmpty( $event->classListenersChanges[$eventName] ?? [], "$eventName event should not be fired" );
		$this->assertFalse( $event->exists, "model should not be saved" );
		
		$event->save();//the saving event in above should have no affect
		
		$this->assertCount( 1, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired only once" );
		$this->assertCount( 1, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer only once" );
		$this->assertCount( 1, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener only once" );
		
		$event->name = 'foo';
		
		$event->save();
		
		$this->assertCount( 2, $event->listenersChanges[$eventName] ?? [], "$eventName event should be fired twice" );
		$this->assertCount( 2, $event->observersChanges[$eventName] ?? [], "$eventName event should be fired by observer twice" );
		$this->assertCount( 2, $event->classListenersChanges[$eventName] ?? [], "$eventName event should be fired by class listener twice" );
		
		$event->delete();
	}
}