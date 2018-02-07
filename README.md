# ZichtVersioningBundle #

The ZichtVersioningBundle hooks into Doctrine to save versions in a separate entity
called `EntityVersion`, keeping track of changes and being able to revert to and
plan future versions.

## Implementation notes ##

### Marking versionable behaviour ###
To make an entity versionable, the entity should implement the interface 
`VersionableInterface`. For all OneToMany relations, the relation must be marked
`cascade={...,"persist"}` and the contained entity must implement the
`EmbeddedVersionableInterface`. 

The version data that is currently in the entity's table is called the `active`
version. By setting another version active, the data from the version table
is loaded into the entity and persisted to the database. This makes the 
versioning transparent; i.e., other components do not need to know that the
entity is versioned, and can simply query the database directly, since the 
`active` version is always in the database table.

There is a command to introspect these versions, which can be useful for
debugging. 

### cloning objects ###
If you want to be able to clone objects, you *need to reset the object's id*:

```
public function __clone()
{
    $this->id = null;
}
```

Otherwise, the versioning will mix up the record id references. 

## Sonata integration ##
Because the versioned embedded entities do not exist in the database, sonata's
method of referring to objects using their id (in the `childId` parameter in the
routes) does not work. For this, the `EmbeddedVersionableAdminTrait` is supplied
to override the `id()`, `update()` and `generateObjectUrl()` methods, so the
*index* rather than the id is used to refer to child objects. These values
are loaded from the parent object in stead of from the 

You should use this trait in *all* admins that are managing entities that
implement the `EmbeddedVersionableInterface`, e.g. as such:

```
class ContentItemDetailAdmin extends Admin
{
    use EmbeddedVersionableAdminTrait;
}
```

# Maintainer
* Boudewijn Schoon <boudewijn@zicht.nl>
* Philip Bergman <philip@zicht.nl>

