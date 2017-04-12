# Adapt DB - Setup Manual

This manual describes how to setup an instance of Adapt DB.

## Requirements
### Required Software
A few things need to be done before Adapt DB can run on your system (or server):
 
 1. Webserver Configuration
 2. PHP Configuration
 3. Setup a MySQL Database
 4. Install Composer
 5. Install Phing

See more in [Required Software Setup](#RequiredSoftwareSetup).

### Other Requirements
There are some other things required, additionally to the software that needs to be installed on the system Adapt DB is running on.

For the location search when creating a new emergency a **GeoNames Account** is required. You can create an account here: [http://www.geonames.org/login](http://www.geonames.org/login)

Please ensure that you activate this account. You can do this on this page: [http://www.geonames.org/manageaccount](http://www.geonames.org/manageaccount). Otherwiese the location search will not work.

<a name="RequiredSoftwareSetup"></a>
## Required Software Setup

### 1. Webserver Configuration
For basic webserver configuration please check the web for tutorials.

You need to configure your webserver so that the folder *web* is set to be the so-called *document root* directory.

### 2. PHP Configuration
The project is based on the PHP framework Symfony 3.

The basic PHP requirements for Symfony 3 are:

 - PHP needs to be a minimum version of PHP 5.5.9
 - JSON needs to be enabled	
 - ctype needs to be enabled
 - php.ini needs to have the date.timezone setting

You can find more detailled informations on [http://symfony.com/doc/current/reference/requirements.html](http://symfony.com/doc/current/reference/requirements.html).

### 3. Setup a MySQL database
You need a MySQL database for Adapt DB. You are free to choose the name for the database. For example you could call it *adapt*.

You will need to configure the database name and the credentials to log in to the database in step 6.

### 4. Install Composer
Composer is required to download all the required dependencies. Please check this page how to install and setup composer on your system: [https://getcomposer.org/doc/00-intro.md](https://getcomposer.org/doc/00-intro.md)

### 5. Install Phing
Adapt DB provides phing commands for basic tasks. Therefore it is required that phing is installed. Please check this page for more information: [https://www.phing.info/trac/wiki/Users/Installation](https://www.phing.info/trac/wiki/Users/Installation)

## Adapt DB Setup

### 6. Install dependencies
Install dependencies by running `composer install` in the project root directory. The system will ask you for the parameters in this step. If you skipped some of these parameters you can set or change them later in the parameters.yml in the *app/config* directory.

For descriptions of the parameters check *app/config/parameters.dist.yml*.

### 7. Setup database with a basic data
For the initial database setup run `phing setupDatabaseWithFixtures` in the project root directory. You'll need to confirm this action by typing "Y". Then the system will setup the basic database for you. 

**Important:** The command will create a user account for you. Please note the credentials - you'll need them later to log into the system.

### 8. First login and creating new user accounts 
Open your Adapt DB installation by entering the url in the browser (the url was configured in the webserver configuration)

Log into the system using the credentials of step 7.

After siging in you can create additional user accounts with these steps:

- Click on *Master Data* in the main navigation bar
- Click *Users and Permissions*
- Click *New User*
- Fill the form
- Click *Create*

### 9. Configure Cronjobs
A cronjob is a scheduled task that runs on a regular basis. Adapt DB contains a couple of commands which need to be run frequently, preferred as cronjobs. You can find many tutorials on the web about configuration of cronjobs on Linux-/UNIX-like systems - check this page for example: [https://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/](https://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/)

These two commands should be defined as cronjobs to run frequently:

 - `phing executePeriodicTasks`
 - `phing sendEmailsForDataChanges`

It is recommended that these tasks are running at least once a day. Check README_COMMANDS.md for more information about the commands available.

## Troubleshooting

Usually, in the webserver configuration there is a log directory defined. If you see error messages, it is likely that you can find a clue about the reason in these log files.

#### Wrong permissions
A common problem for various errors is, that the application might not have write permissions for the *app/cache* directory.

#### Errors after an update
When errors occur after Adapt DB has been updated, it is likely that cached data is the problem. To clear the cache type execute `php bin/console cache:clear --env=prod` in the project root directory.