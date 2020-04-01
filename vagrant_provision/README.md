# Vagrant

## Summary
Vagrant is software used to more easily manage VirtualBox, while VirtualBox will run the actual virtual machine.  This repository provides a `vagrantfile` that defines a Vagrant box to help with code development.  The Vagrant VM will act as a development and test server.  It provides a LAMP stack with Ubuntu 18.04, Apache2, MySQL, and PHP.

The Vagrant Box was originally developed and tested with Vagrant 2.2.3, VirtualBox 5.2.26, and macOS 10.12.6.  It should work with Vagrant 2.2.0 or later, VirtualBox 5.2.x or later, and any operating systems compatible with Vagrant and Virtualbox.

## Jargon
* *Guest*, *Box*, *VM*:  The virtual machine development and test server.
* *Host*: refers to your computer that is running the virtual machine.

## Downloads
Download and install for your operating system:

* **Vagrant** By HashiCorp: https://www.vagrantup.com/
* **VirtualBox** By Oracle: https://www.virtualbox.org/

## Using Vagrant
### Setup
1. Install Vagrant and VirtualBox.
    * Make sure to install the vbguest plugin.  `vagrant plugin install vagrant-vbguest`
2. Clone this repository to your host, either with `git` command line or the Github Application.
3. Go to the root folder of the cloned repository.
4. On the command line: `vagrant up`
    * There will be a long series of build messages printed to the console.
    * Build messages will be logged in the repository at `.vagrant/provision.log` (not tracked by git).
    * Depending on the speed of your Internet connection and the speed of your host computer, it can take 30 minutes or more to build the VM.
    * Once the VM is built, it doesn't take nearly as long to start up the VM another time.

### Using Vagrant For Development
You may develop code for this repository on your host.  Make sure the VM is running to test the server in your web browser.

* Clone the repository, and develop and commit code on your host.
* Code is in HTML, CSS, and PHP.
* You do *not* need to install PHP on your host.
* To test any code changes, the Vagrant VM needs to be updated with this command: `vagrant up --provision-with refresh`
    * Some composer libraries are dormant, and checking for composer library updates can take extra time when all you want is to refresh your own code.  Therefore, checking for composer library updates is separate: `vagrant up --provision-with composer-update`
* You can view the VM server webpage at `http://localhost:50080`
* A MySQL database viewer can connect to the VM server at `localhost`, port `53306`.

### Common Commands
Command | Purpose
--- | ---
`vagrant up` | Build and/or start the VM.  Note that the VM will reserve 2GB of RAM while active.
`vagrant halt` | Gracefully shutdown the VM, which will return the reserved 2GB of RAM.
`vagrant destroy` | Delete the VM.  It will need to be rebuilt with `vagrant up` to be used again.
`vagrant up --provision-with update` | Update the VM with your latest code.  You'll also have to refresh your web browser.
`vagrant up --provision-with full-update` | Remove all code and packages from guest.  Reinstall development code from working branch.  Reinstall composer packages.  Reinstall provision configuration file.  Just in case you need a complete reset.
`vagrant up --provision-with composer-update` | Checks composer for and installs all updates to packages.
`vagrant ssh` | Opens a secure shell connection to the VM.  You probably won't need this, but it is available if you want to internally review the server.

### Forwarded Ports
Service | Guest Port | Host Port
--- | --- | ---
SSH | 22 | 2222
HTTP | 80 | 50080
MySQL Server | 3306 | 53306

### MySQL Database Info
**Never use these values in a production system.**
--- | ---
Database Name | `phplw_dev`
Database User | `dev_dbuser`
Database Password | `dev_dbpassword`
