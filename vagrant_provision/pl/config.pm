package CONFIG;
use File::Spec::Functions qw(rootdir);

# ** ---------------------------- CONFIGURATION ----------------------------- **

# Paths (as arrays of directories)
our @VAGRANT_HOMEPATH = (rootdir(), "home", "vagrant");
our @REPO_PATH = (@VAGRANT_HOMEPATH, "github_phplw");
our @CONFIG_PATH = (@REPO_PATH, "vagrant_provision", "config");
our @LMTOOLS_PATH = (rootdir(), "opt", "lmtools");
our @HTML_PATH = (rootdir(), "var", "www", "html");
our @LOGROTATE_PATH = (rootdir(), "etc", "logrotate.d");
our @APACHE_PATH = (rootdir(), "etc", "apache2");
our @PHP_INI_DEV_PATH = (rootdir(), "usr", "lib", "php", "7.4");
our @PHP_INI_APACHE_PATH = (rootdir(), "etc", "php", "7.4", "apache2");
our @PHP_INI_CLI_PATH = (rootdir(), "etc", "php", "7.4", "cli");
our @CACHE_PATH = (rootdir(), "var", "cache", "phplw");

# Relevant files
our @CODE_FILES = qw(*.php *.html *.js *.css *.template mathematica);
our $CONFIG_FILE = "config.php";
our $SQL_FILE = "phplicensewatcher.sql";
our $LOGROTATE_CONF_FILE = "phplw.conf";
our $APACHE_CONF_FILE = "phplw.conf";
our $PHP_INI_DEV_FILE = "php.ini-development";
our $PHP_INI_FILE = "php.ini";
our $UPDATE_CODE = "update_code.pl";
our $LICENSE_UTIL = "license_util.php";
our $LICENSE_CACHE = "license_cache.php";
our $COMPOSER_PACKAGES = "vendor";

# Packages needed for phplw.
our @REQUIRED_PACKAGES = ("apache2", "php", "php-mysql", "mysql-server", "mysql-client", "lsb", "zip", "unzip");

# Non super user account.  Some package systems run better when not as root.
our $VAGRANT_USER = "vagrant";
our $VAGRANT_UID = getpwnam $VAGRANT_USER;
our $VAGRANT_GID = getgrnam $VAGRANT_USER;

# Cache files owner
our $CACHE_OWNER = "www-data";
our $CACHE_OWNER_UID = getpwnam $CACHE_OWNER;
our $CACHE_OWNER_GID = getgrnam $CACHE_OWNER;
our $CACHE_PERMISSIONS = 0700;

# List of Flex LM binaries and ownership
our @LMTOOLS_FILES = ("lmutil", "monitorlm");
our $LMTOOLS_OWNER = "www-data";
our $LMTOOLS_OWNER_UID = getpwnam $LMTOOLS_OWNER;
our $LMTOOLS_OWNER_GID = getgrnam $LMTOOLS_OWNER;
our $LMTOOLS_PERMISSIONS = 0770;

# DB config
our @DB_HOSTS = ("localhost", "_gateway");
our @DB_CONFIG_PATH = (rootdir(), "etc", "mysql", "mysql.conf.d");
our $DB_CONFIG_FILE = "mysqld.cnf";
our $DB_NAME = "vagrant";
our $DB_USER = "vagrant";
our $DB_PASS = "vagrant";

# ** -------------------------- END CONFIGURATION --------------------------- **

1
