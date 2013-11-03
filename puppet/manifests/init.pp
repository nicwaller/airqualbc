# == Class: air
#
# Full description of class air here.
#
# === Parameters
#
# Document parameters here.
#
# [*sample_parameter*]
#   Explanation of what this parameter affects and what it defaults to.
#   e.g. "Specify one or more upstream ntp servers as an array."
#
# === Variables
#
# Here you should define a list of variables that this module would require.
#
# [*sample_variable*]
#   Explanation of how this variable affects the funtion of this class and if
#   it has a default. e.g. "The parameter enc_ntp_servers must be set by the
#   External Node Classifier as a comma separated list of hostnames." (Note,
#   global variables should be avoided in favor of class parameters as
#   of Puppet 2.6.)
#
# === Examples
#
#  class { air:
#    servers => [ 'pool.ntp.org', 'ntp.local.company.com' ],
#  }
#
# === Authors
#
# Author Name <author@domain.com>
#
# === Copyright
#
# Copyright 2013 Your name here, unless otherwise noted.
#
class air {
	class { 'apache':
		default_vhost => false,
	}
	include apache::mod::php

	file { '/var/www/air':
		ensure => directory,
	} -> apache::vhost { $::fqdn:
		default_vhost => true,
		port => '80',
		docroot => '/var/www/air/',
	}

	class { '::mysql::server':
		root_password => 'air',
	}
	class { '::mysql::client': }
	mysql::db { 'air':
		user     => 'air',
		password => 'air',
		host     => 'localhost',
		grant    => ['ALL'],
	}

	php::ini { '/etc/php.ini':
		display_errors => 'Off',
	}
	include php::cli
	php::module { ['mysql', 'pdo', 'mbstring', 'xml']: }
}
