# Adapt DB


## What is Adapt DB?

Adapt DB is a web-application that supports civil protection workers in finding vulnerable people in case of an emergency evacuation. It aims to complement the municipal emergency plans of small communities in disaster risk areas and was conceptualised by a consortium of aid organisations as part of the EU co-financed project ADAPT - Awareness of Disaster Relief for Vulnerable Groups. For more information about the project, visit http://adapt.samaritan-international.eu/.

Adapt DB was created as part of the EU co-financed project ADAPT.
More information is available on the project website http://adapt.samaritan-international.eu/

Person data can be entered manually or imported into the application from a CSV-file. Adapt DB allows for multiple data sources. This means that one person can be in the database with multiple person-entries, each of which contains data from one data-source. To aggregate all the information about a person, the application supports grouping the multiple person-entries into a "potential identity" (PI). The system automatically creates PIs when specific columns of multiple people match. A data administrator can check these potential identities and add or remove person-entries from a potential identity manually.  

Other features:

- Emergencies contains an geo-area and a street-list. Rescue Workers can find vulnerable people for each active emergency based on the geo-area or based on the street list.
- Access Restrictions: Rescue Workers are only allowed to access data of people within the geo-area of an active emergency
- Information about changed data of person-entities are saved
- When data couldn't be imported from a CSV-file the application saves a list. This helps to ensure that errors that occurred in an import will be recognized.
- Data can be accesses via an API. Access to the API can be granted for every user account when an API-key for this user is created. 


## Data protection features and considerations

To comply with data protection standards, Adapt DB allows for various user roles, which can be given to users as needed:
Sysadmins - Can create users, can define data sources and the levels/terminology of vulnerability the system uses
Dataadmins - Have access to the saved data and can maintain it. They can also define emergency situations, to allow rescue workers limited and temporary access to necessary information.
Rescueworkers - Can use the emergency workflow of the application to access data about people affected by a defined emergency.

Besides selecting appropriate user roles, it is recommended to enable TLS on any instance of Adapt DB processing real personal data. Additional data at rest encryption should also be used on the webserver hosting Adapt DB.

You should always consult a legal professional on applicable data protection law before collecting personal data, in Adapt DB or otherwise.


## How to setup Adapt DB?

Check SETUP_MANUAL.md for information on how to setup the application on your server.

## Where can I find more information about the API?

For the available API routes check the route ```[Adapt-DB-Base-URL]/api/doc```. You can find JSON-Schema files and examples in the ```/api```-folder.  


## How to backup data of an Adapt DB instance?

All data Adapt DB used are stored in one database. To backup all data you can simply create an database dump for this database.

The dump contains everything: the person-data, addresses, emergencies and users with their roles.
