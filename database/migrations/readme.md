Migrations are only intended for existing installs of phpLicenseWatcher.
Schema changes in these migrations have already been implemented in the main
database SQL file (`phplicensewatcher.sql` and `phplicensewatcher.maria.sql`)

- `migration-01.sql`
   - Expand phpLicenseWatcher to work with additional license managers.
   - Change column `servers.lmgrd-version` to `servers.version`
   - Add new column `servers.license_manager varchar(25) not null`
