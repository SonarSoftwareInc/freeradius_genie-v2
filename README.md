# FreeRADIUS Installer
An installer to setup and configure FreeRADIUS for use with Sonar. **This is currently unfinished and not ready for production!**

## Getting started

This installer is designed to be run on [Ubuntu 16.04 64bit](http://www.ubuntu.com/download/server). Download and install Ubuntu on the server you wish to run FreeRADIUS on. If you want to host it online, I recommend [Digital Ocean](https://m.do.co/c/84841b1bca8e).

Once Ubuntu is installed, SSH in and run the following commands to prepare installation:

1. `sudo apt-get update`
2. `sudo apt-get upgrade`
3. `sudo apt-get install php-cli php-mbstring php-mysql unzip`

Once these commands are complete, you can download the installer by executing `wget https://github.com/SonarSoftware/freeradius_installer/archive/master.zip` and then `unzip master.zip`.

Now execute the installer by running `php installer.php`

## Completing preliminary installation

Once the installer has finished, all the necessary software to run your FreeRADIUS server will be installed. You will need to configure your SQL database before proceeding any further. To do this, run `/usr/bin/mysql_secure_installation` and answer the questions using the following:

1. **Enter current password for root (enter for none):** - Press enter
2. **Set root password? [Y/n]** - Press 'y'
3. **New password:** - Enter a strong password and *write it down* - **we will need this password shortly!**
4. **Remove anonymous users? [Y/n]** - Press 'y'
5. **Disallow root login remotely? [Y/n]** - Press 'y'
6. **Remove test database and access to it? [Y/n]** - Press 'y'
7. **Reload privilege tables now? [Y/n]** - Press 'y'

Once this is done, we have a very basic server setup - FreeRADIUS and the MySQL database are installed we're ready to move onto the initial configuration.

## Configuration

In order to allow the Sonar `genie` tool to setup everything else for you, you need to enter the MySQL root password you setup a minute ago in a **.env** file. Type `cp .env.example .env` and then `nano .env`. You'll see a line that says `MYSQL_PASSWORD=changeme`. Use 
the backspace key to delete `changeme` and replace it with the MySQL root password you setup. Press `CTRL+X` to exit, and save your changes.

Once that's done, we're ready to start using genie!

## Genie

Genie is a command line tool we built to help automate the setup and configuration of your FreeRADIUS server. We're going to step through each initial setup item to get our initial configuration out of the way. Type `php genie` and you'll see something like this:

![Image of Genie](https://github.com/SonarSoftware/freeradius_installer/blob/master/images/genie.png)

This is the tool you'll use to do **all** of your configuration - no need to jump into configuration files or the MySQL database!

Let's start by getting the database setup. Highlight the **Initial Configuration** option, press the space bar to select it, and then press enter. You'll see an option titled **Setup initial database structure** - press the space bar to select it, press enter, and your database will be configured. If you
receive an error message about credentials, double check the root password you placed into your `.env` file in the **Configuration** section.

Once that's completed, we need to setup the FreeRADIUS configuration files. Select **Perform initial FreeRADIUS configuration** by using the space bar to select it, and then pressing enter.