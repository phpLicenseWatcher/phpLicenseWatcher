# Vagrant
## Summary
Vagrant is software used to more easily manage VirtualBox, while VirtualBox will run the actual virtual machine.  This repository provides a `vagrantfile` that defines a Vagrant box to help with code development.  The Vagrant box is a virtual machine that acts as a development and test server.  It provides a LAMP stack with Ubuntu 18.04, Apache2, MySQL, and PHP.

The Vagrant Box was originally developed and tested with Vagrant 2.2.3, VirtualBox 5.2.26, and macOS 10.12.6.  It should work with Vagrant 2.2.0 or later, VirtualBox 5.2.x or later, and any operating systems compatible with Vagrant and Virtualbox.

_Please do **not** try to use the vagrant box as a production system.  The vagrant box is provided as a development aid.  It is meant to be only accessible to its host computer, and it is not designed with the proper security needed for a production system._

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
    * You do _not_ need the Virtualbox Extension Pack to run this Vagrant box.
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
* You do _not_ need to install PHP on your host.
* To test any code changes, the Vagrant VM needs to be updated with this command: `vagrant up --provision-with update`
    * Some composer libraries are dormant, and checking for composer library updates can take extra time when all you want is to update your working code.  Therefore, checking for composer library updates is separate: `vagrant up --provision-with composer-update`
* You can view the VM server webpage at `http://localhost:50080`
* A MySQL database viewer can connect to the VM server at `localhost`, port `53306`.

### Tips and Tricks
* You can use secure copy ('scp') to upload a file to the VM without it being tracked by git:`scp -P 2222 my_file vagrant@localhost:`  Password is `vagrant`.
* The VM can use the host's Internet connection.
    * Virtualbox typically assigns the VM an IP of `10.0.2.15`.  This is not visible from the host due to Virtualbox's NAT firewall.  Instead, certain ports are forwarded to the host at `localhost`.
    * Virtualbox's NAT firewall is meant to block connections to the VM from the Internet.
    * Your host can be seen from within the VM at `10.0.2.2`.

### Troubleshooting
* Many problems can be solved by issuing a "full-update". `vagrant up --provision-with full-update`
* If a "full update" doesn't fix the problem, you can rebuild the VM.  Note that this will destroy any personal modifications you may have made (e.g. custom config file).
    1. Destroy the VM. `vagrant destroy`
    2. Update the locally cached Ubuntu image.  This may reduce time needed to provision a new VM. `vagrant box update`
    3. Build a new VM. `vagrant up`
* The vagrant box gets occasional patches in this github repository, which can make your current VM obsolete.  Sometimes, a "full-update" will bring your VM current, but that is not a guarantee.  When a "full-update" doesn't work, please rebuild the VM as described above.

### Common Commands
Command | Purpose
--- | ---
`vagrant up` | Build and/or start the VM.  The VM will reserve 2GB of RAM while active.  If you want to rebuild the VM, you need to first delete it with `vagrant destroy`.
`vagrant halt` | Gracefully shutdown the VM, which will return the reserved 2GB of RAM.  Use `vagrant up` to restart the VM.
`vagrant destroy` | Delete the VM.  It will need to be rebuilt with `vagrant up` to be used again.
`vagrant box update` | Updates the locally cached Ubuntu image.  May reduce the time to provision a brand new VM should you destroy an existing VM.
`vagrant up --provision-with update` | Update the VM with your latest code.  You'll also have to refresh your web browser.
`vagrant up --provision-with full-update` | Remove all code and packages from guest.  Reinstall development code from working branch.  Reinstall composer packages.  Reinstall provision configuration file.  Do this only if you need a complete reset.
`vagrant up --provision-with composer-update` | Checks composer for&mdash;and installs&mdash;all updates to packages.
`vagrant ssh` | Opens a secure shell connection to the VM.

### Forwarded Ports
Service | Guest Port | Host Port
--- | --- | ---
SSH | 22 | 2222
HTTP | 80 | 50080
MySQL Server | 3306 | 53306

### MySQL Database Info
**Never use these values in a production system.**
Property | Value
--- | ---
Database Name | `vagrant`
Database User | `vagrant`
Database Password | `vagrant`
