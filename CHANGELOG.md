# 1.0

## 1.0.9
* Fix issue with multiple versions based on the same changes. Since the
  versioning-bundle bases it's changes on the entity in the entity-table, when
  the same change (from ie an import) is applied, a new version would always be
  created. This fix will compare the changeset with the changeset of the latest
  version. If they differ, the version will be created, if the are the same, the
  new version will be skipped.

## 1.0.8
* Fix erroneous results whenquerying "pending" versions.

## 1.0.7
* an embeddedVersionableInterface object could miss his parent is some cases;
  this defends for it

## 1.0.6
* Unsets dateActiveFrom when creating a new version. This is to prevent the
  'old' activation date to copy over to the new version.

## 1.0.5
* Fixes quiet mode of cronjob task to be actually quiet (i.e. do not display
  activation messages)

## 1.0.4
* Fixes compatibility with `zicht/itertools`

## 1.0.3
* Fixes implicit version operations to be "marked as handled" for ACTIVATE and
  NEW (see also 1.0.2)

## 1.0.2
* mark 'explicit' version operations as 'handled', so they don't get triggered
  implicitly; i.o.w. remove the versions that are supposed to be created
  explicitly from the versioning manager, after the add is delegated to the unit
  of work

## 1.0.1
* Fixes several bugs with cloning objects and handling multiple objects in one
  unit-of-work.

## 1.0.0
* First stable release

