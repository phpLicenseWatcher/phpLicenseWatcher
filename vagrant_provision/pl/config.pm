package CONFIG;
use File::Spec::Functions qw(rootdir);

# ** ---------------------------- CONFIGURATION ----------------------------- **

# Paths (as arrays of directories)
our @VAGRANT_HOMEPATH = (rootdir(), "home", "vagrant");
our @REPO_PATH = (@VAGRANT_HOMEPATH, "github_phplw");
our @CONFIG_PATH = ("vagrant_provision", "config");  # Relative to @REPO_PATH
our @LMTOOLS_PATH = (rootdir(), "opt", "lmtools");
our @HTML_PATH = (rootdir(), "var", "www", "html");
our @LOGROTATE_PATH = (rootdir(), "etc", "logrotate.d");
our @APACHE_PATH = (rootdir(), "etc", "apache2");
our @CACHE_PATH = (rootdir(), "var", "cache", "phplw");

# Relevant files
our $CONFIG_FILE = "config.php";
our $SQL_FILE = "phplicensewatcher.sql";
our $LOGROTATE_CONF_FILE = "phplw.conf";
our $APACHE_CONF_FILE = "phplw.conf";
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
