# Release Notes

## v2.2.1

### Fixed

* Fixed a bug that was caused by a null value or a null cast. [PR #104](https://github.com/kodeine/laravel-meta/pull/104)

## v2.2.0

### Added

* Added `createdWithMetas`, `updatedWithMetas`, `savedWithMetas` events.

## v2.1.1

### Added

* Added Laravel 10.x Compatibility.

## v2.1.0

### Added

* Added [events](README.md#events) for metas.
* Added `isMetaDirty` method.

## v2.0.1

### Changes

* Improved `getMetaArray` method by removing linear search from `getMetaArray`

## v2.0.0

### Added

* Added `setAttribute` method.
* Added `hasMeta` method.
* Added `hasDefaultMetaValue` method.
* Added ability to disable fluent meta access by setting `$disableFluentMeta` to `true`.

### Changed

* Removed laravel 7 and bellow support.
* Removed `__get` method.
* Removed `__set` method.
* Removed `__isset` method.
* Removed legacy getter.
* Removed `whereMeta` method in favor of `scopeWhereMeta`.
* Renamed `getMetaDefaultValue` method to `getDefaultMetaValue`.
* You can now set meta names starting with `meta`.
* Changed `saveMeta` method's visibility to public.
* Changed `getMetaData` method's visibility to public.
* Fluent setter will now check for any cast or mutator.
* Passing an array to `getMeta()` will now return a Collection with all the requested metas, even if they don't exist. non-existent metas value would be based on second parameter or `null` if nothing is provided.

### Fixed

* Fixed `getMeta` method's second parameter.
* Fixed duplicate queries executed when retrieving the models stored as meta.
* Fixed fluent getter treating relations as meta when the result is null.
