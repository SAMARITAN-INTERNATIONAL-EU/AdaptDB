#Adapt DB - Commands


##Commands for initial database setup

Three commands are available to setup the database initially:

 * **phing setupDatabase**: to begin with empty tables.

 * **phing setupDatabaseWithFixtures**: to begin with basic entities already created like country or medical requirements.

 * **phing setupDatabaseWithTestdata**: to begin with fixtures and and test-data. These tables are filled with test-data.

To make this work a database needs to exist. The database parameters can be modified in the parameters.yml file in the folder "app/config".


##Commands to cleanup and update the database of Adapt DB

These commands are used to execute some internal tasks in the Adapt DB database. It is recommended that these commands are executed at least once a day. You can setup each command (in the list below) as a cronjob, or you can use the combined command `phing executePeriodicTasks` instead. It executes these commands with just one call. 

* **php bin/console adaptDB:cleanUpDatabase**: It cleans up the database
* **php bin/console adaptDB:detectAndDeletePotentialIdentities**: Updates the potential Identities 
* **php bin/console adaptDB:detectInconsistentData**: It searches for inconsistent data persons of the Potential Identities
* **php bin/console adaptDB:setIsActiveBasedAbsenceToUntilDate**: It searches for personAddresses where the AbsenceTo-date has been reached. The command removes the absence-dates and sets the address to active.


##Commands for sending out notification emails to persons

 **php bin/console adaptDB:sendEmailsForDataChanges**: The command looks for entries in the table "data_change_history". It send out notification emails to be users affected by recent data changes. **Note:** The most recent data changes are ignored to prevent multiple emails shortly after another. The threshold-time can be defined in parameters.yml - It is called: "send\_emails\_for\_data\_changes\_not\_modified\_minutes\_threshold"
