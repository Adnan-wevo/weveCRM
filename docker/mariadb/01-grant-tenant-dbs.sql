-- Grant the application user permission to create/drop tenant databases.
-- MariaDB runs scripts in /docker-entrypoint-initdb.d/ on first initialisation.
-- The tenant DB manager creates databases prefixed with "tenant" (e.g. tenantacme).
GRANT ALL PRIVILEGES ON `tenant%`.* TO 'wevetel'@'%';
FLUSH PRIVILEGES;
