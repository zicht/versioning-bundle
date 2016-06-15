# ZichtVersioningBundle #

The ZichtVersioningBundle hooks into Doctrine to save versions in a separate entity
called `EntityVersion`, keeping track of changes and being able to revert to and
plan future versions.

## Implementation notes ##

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

