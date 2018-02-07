# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added|Changed|Deprecated|Removed|Fixed|Security
Nothing so far

## 1.1.1 - 2018-02-07
### Fixed
- Check the validity of history items and do not allow invalid items to be used.

## 1.1.0 - 2017-12-04
### Added
- `FileNormalizer` and test.

## 1.0.9
### Fixed
- Fix issue with multiple versions based on the same changes. Since the
  versioning-bundle bases it's changes on the entity in the entity-table, when
  the same change (from ie an import) is applied, a new version would always be
  created. This fix will compare the changeset with the changeset of the latest
  version. If they differ, the version will be created, if the are the same, the
  new version will be skipped.

## 1.0.8
### Fixed
- Fix erroneous results when querying "pending" versions.

## 1.0.7
### Fixed
- an embeddedVersionableInterface object could miss his parent is some cases;
  this defends for it

## 1.0.6
### Fixed
- Unsets dateActiveFrom when creating a new version. This is to prevent the
  'old' activation date to copy over to the new version.

## 1.0.5
### Fixed
- Fixes quiet mode of cronjob task to be actually quiet (i.e. do not display
  activation messages)

## 1.0.4
### Fixed
- Fixes compatibility with `zicht/itertools`

## 1.0.3
### Fixed
- Fixes implicit version operations to be "marked as handled" for ACTIVATE and
  NEW (see also 1.0.2)

## 1.0.2
### Fixed
- mark 'explicit' version operations as 'handled', so they don't get triggered
  implicitly; i.o.w. remove the versions that are supposed to be created
  explicitly from the versioning manager, after the add is delegated to the unit
  of work

## 1.0.1
### Fixed
- Fixes several bugs with cloning objects and handling multiple objects in one
  unit-of-work.

## 1.0.0
### Added
- First stable release

