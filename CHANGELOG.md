#<= 1.0.8
   ... a working version-bundle ...
   
#1.0.9
## Bugfixes
- Fix issue with multiple versions based on the same changes.<br>
<br>
Since the versioning-bundle bases it's changes on the entity in the entity-table, when the same change (from ie an import) is applied, a new version would always be created. This fix will compare the changeset with the changeset of the latest version. If the differ, the version will be created, if the are the same, the new version will be skipped.