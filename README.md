AuToDo
======

Setup instructions
------------------

1. Setup your Apache, PHP5, and MySQL server.
2. Ensure you have installed and enabled the PHP curl, mysql, mcrypt and mbstring extensions
3. Enable mod_rewrite in your apache httpd.conf
	- Uncomment the LoadModule line for mod_rewrite. If this line is not present, run 'sudo a2enmod rewrite'
	- Find the line that says 'DocumentRoot /path/to/doc/root/', and underneath you will find a <Directory> definition
	- In the <Directory> definition set the 'Options' and 'AllowOverride' variables to 'All'
4. Point webserver to laravel/public/ directory
	- Edit your 'hosts' file add `127.0.0.1    autodo`
	- Edit your virtual hosts file (httpd.conf will do fine if you don't have a dedicated vhosts file) and add the following lines

```
<VirtualHost *:80>
    DocumentRoot "/path/to/webserver/autodo/public"
    ServerName autodo
</VirtualHost>
```

5. Install [composer](http://www.getcomposer.org)
6. Go to the laravel directory and run 'composer install'
7. Go to http://autodo/

Future of AuToDo
----------------

* Documentation
* Additional input style support (XML)

Application Ideas
-----------------

* Google calendar plugin

API Specification and Requests
------------------------------

All api requests begin with the base url `http://www.autodoapi.com/api`. All requests listed below assume that this base url is already included in the request url.

Requests to the API can be made using either JSON input, or XML input, and data can be returned in either JSON or XML format. JSON is the default format expect for data input and output.

You can explicitly state the input format by setting the `CONTENT-TYPE` header to `application/json` for JSON input or `application/xml` for XML input.

You can explicitly state the output format by setting the `ACCEPT` header to `application/json` for JSON output or `application/xml` for XML output.

If the `CONTENT-TYPE` header is set, but the `ACCEPT` header is not, the AuToDo API assumes that the output format should be the same as the input format.

### API Scheduling Calls

| Method            | Request Type  | URL                       | Description                                                                               |
| ----------------- | ------------- | ------------------------- | ----------------------------------------------------------------------------------------- |
| Generate Schedule | POST          | /schedule                 | Include a data object as described [here](#schedule_data_structure).                      |
| Generate Schedule | GET           | /user/{user_id}/schedule  | Returns schedule generated from saved user information for user with specified `user_id`. |

### Users

The `user_id` parameter is a numeric value, unique to each user.

| Method    | Request Type  | URL               | Description                                                                                   |
| --------- | ------------- | ----------------- | --------------------------------------------------------------------------------------------- |
| Create    | POST          | /user             | Include a data object with the `name` and `email` parameters. Returns created user.           |
| Get       | GET           | /user/{user_id}   | Returns user with specified `user_id`.                                                        |
| Update    | PUT           | /user/{user_id}   | Include a data object with the `name` and/or `email` parameters. Returns the updated user.    |
| Delete    | DELETE        | /user/{user_id}   | Deletes the specified user.                                                                   |

### Tasks

The `task_id` parameter is a numeric value, unique to each task. The `user_id` parameter is a numeric value, unique to each user. Where both `task_id` and `user_id` are required, if the task with identifier `task_id` does not have a matching `user_id`, it will not be returned.

| Method    | Request Type  | URL                               | Description                                                                                   |
| --------- | ------------- | --------------------------------- | --------------------------------------------------------------------------------------------- |
| Create    | POST          | /user/{user_id}/task              | Include a data object as described [here](#task_structure). Returns created task.             |
| List      | GET           | /user/{user_id}/task              | Returns all tasks associated with the specified user.                                         |
| Get       | GET           | /user/{user_id}/task/{task_id}    | Returns task with specified `task_id` and `user_id`.                                          |
| Update    | PUT           | /user/{user_id}/task/{task_id}    | Include a data object as described [here](#task_structure). Returns the updated task.         |
| Delete    | DELETE        | /user/{user_id}/task/{task_id}    | Deletes the specified task.                                                                   |

### Fixed Events

The `event_id` parameter is a numeric value, unique to each task. The `user_id` parameter is a numeric value, unique to each user. Where both `event_id` and `user_id` are required, if the task with identifier `event_id` does not have a matching `user_id`, it will not be returned.

| Method    | Request Type  | URL                                   | Description                                                                                   |
| --------- | ------------- | ------------------------------------- | --------------------------------------------------------------------------------------------- |
| Create    | POST          | /user/{user_id}/fixedevent            | Include a data object as described [here](#event_structure). Returns created event.           |
| List      | GET           | /user/{user_id}/fixedevent            | Returns all events associated with the specified user.                                        |
| Get       | GET           | /user/{user_id}/fixedevent/{event_id} | Returns event with specified `event_id` and `user_id`.                                        |
| Update    | PUT           | /user/{user_id}/fixedevent/{event_id} | Include a data object as described [here](#event_structure). Returns the updated event.       |
| Delete    | DELETE        | /user/{user_id}/fixedevent/{event_id} | Deletes the specified event.                                                                  |

### Preferences

The `user_id` parameter is a numeric value, unique to each user.

| Method    | Request Type  | URL                       | Description                                                                                   |
| --------- | ------------- | ------------------------- | --------------------------------------------------------------------------------------------- |
| Create    | POST          | /preferences              | Include a data object as described [here](#prefs_structure). Returns created preferences.     |
| Get       | GET           | /preferences/{user_id}    | Returns preferences associated with specified user.                                           |
| Update    | PUT           | /preferences/{user_id}    | Include a data object as described [here](#prefs_structure). Returns the updated preferences. |
| Delete    | DELETE        | /preferences/{user_id}    | Deletes the specified event.                                                                  |

Data Object Structures
----------------------

### User

| Attribute Name    | Formatting            | Can Be Modified   | Required On Input | Description                                                   |
| ----------------- | --------------------- | ------------------| ----------------- | ------------------------------------------------------------- |
| id                | Integer               | No                | No                | User's unique identifier.                                     |
| name              | String                | Yes               | On Create Only    | User's real name.                                             |
| email             | Valid Email Address   | Yes               | On Create Only    | User's contact email address.                                 |
| created_at        | Timestamp             | No                | No                | Date and time when the user was created in the database.      |
| updated_at        | Timestamp             | No                | No                | Date and time when the user was last updated in the database. |

### <a id='task_structure'></a>Task

| Attribute Name    | Formatting            | Can Be Modified   | Required On Input     | Description                                                               |
| ----------------- | --------------------- | ------------------| --------------------- | ------------------------------------------------------------------------- |
| id                | Integer               | No                | No                    | Task's unique identifier.                                                 |
| user_id           | Integer               | No                | On Create Or Update   | User's unique identifier.                                                 |
| name              | String                | Yes               | Yes                   | A name for the task.                                                      |
| priority          | Integer, [0-3]        | Yes               | Yes                   | The priority of the task. Higher priority indicates higher importance.    |
| due               | Datetime              | Yes               | Yes                   | The due date and time for the task.                                       |
| duration          | Integer               | Yes               | Yes                   | Expected duration of the task, in minutes.                                |
| complete          | Integer, [0-1]        | Yes               | No                    | Whether the task is complete.                                             |
| created_at        | Timestamp             | No                | No                    | Date and time when the task was created in the database.                  |
| updated_at        | Timestamp             | No                | No                    | Date and time when the task was last updated in the database.             |

### <a id='event_structure'></a>Fixed Event

| Attribute Name    | Formatting                | Can Be Modified   | Required On Input     | Description                                                               |
| ----------------- | ------------------------- | ------------------| --------------------- | ------------------------------------------------------------------------- |
| id                | Integer                   | No                | No                    | Event's unique identifier.                                                |
| user_id           | Integer                   | No                | On Create Or Update   | User's unique identifier.                                                |
| name              | String                    | Yes               | Yes                   | A name for the event.                                                      |
| start_time        | Integer, [0-1440]         | Yes               | Yes                   | The start time, in minutes, that the event begins on a specific day.      |
| end_time          | Integer, [0-1440]         | Yes               | Yes                   | The end time, in minutes, that the event begins on a specific day.        |
| start_date        | Datetime                  | Yes               | Yes                   | A calendar date to begin the recurring events on.                         |
| end_date          | Datetime                  | Yes               | Yes                   | A calendar date to end the recurring events on.                           |
| recurrences       | String, Valid JSON Array  | Yes               | No                    | Days of the week that this event occurs. Sunday is 0, Saturday is 6. Empty JSON array denotes one time event. |
| created_at        | Timestamp                 | No                | No                    | Date and time when the event was created in the database.                 |
| updated_at        | Timestamp                 | No                | No                    | Date and time when the event was last updated in the database.            |

### <a id='prefs_structure'></a>Preferences

| Attribute Name    | Formatting            | Can Be Modified   | Required On Input     | Description                                                               |
| ----------------- | --------------------- | ------------------| --------------------- | ------------------------------------------------------------------------- |
| id                | Integer               | No                | No                    | Preference set's unique identifier.                                       |
| user_id           | Integer               | No                | On Create Or Update   | User's unique identifier.                                                 |
| break             | Integer               | Yes               | No                    | How long to break in between tasks, in minutes. Default is 15.            |
| show_fixed_events | Boolean               | Yes               | No                    | Whether or not to include fixed events in generated schedules.            |
| created_at        | Timestamp             | No                | No                    | Date and time when the task was created in the database.                  |
| updated_at        | Timestamp             | No                | No                    | Date and time when the task was last updated in the database.             |

### <a id='schedule_data_structure'></a>Schedule Input Data Structure

The `schedule_start` field is a date time value indicating when to begin the scheduling.

#### Sample JSON Input

```
{
    "tasks" : [
        {
            "name" : "name1",
            "priority" : 1,
            "due" : "2013-12-04 12:00:00",
            "duration" : 40,
            "complete" : 0
        },
        ...
    ],
    "fixed" : [
        {
            "name" : "Sleep",
            "start_time" : 0,
            "end_time" : 420,
            "start_date" : "2012-09-01 00:00:00",
            "end_date" : "2013-09-01 00:00:00",
            "recurrences" : "[0,1,2,3,4,5,6]"
        },
        ...
    ],
    "preferences" : {
        "break" : 15,
        "show_fixed_events" : true 
    },
    "schedule_start" : "2013-07-05 10:00:00"
}
```

#### Sample XML Input

The name of the root node, in this case `document`, does not matter.

```
<?xml version="1.0" encoding="UTF-8" ?>
<document>
    <tasks>
        <user_id>1</user_id>
        <name>name1</name>
        <due>2013-12-04 12:00:00</due>
        <duration>40</duration>
        <priority>1</priority>
    </tasks>
    <tasks>
        <user_id>1</user_id>
        <name>name2</name>
        <due>2013-12-04 12:00:00</due>
        <duration>60</duration>
        <priority>0</priority>
    </tasks>
    <fixedevents>
        <user_id>1</user_id>
        <name>Sleep</name>
        <start_time>0</start_time>
        <end_time>420</end_time>
        <start_date>2012-09-01 00:00:00</start_date>
        <end_date>2013-09-01 00:00:00</end_date>
        <recurrences>[0,1,2,3,4,5,6]</recurrences>
    </fixedevents>
    <fixedevents>
        <user_id>1</user_id>
        <name>Class</name>
        <start_time>690</start_time>
        <end_time>810</end_time>
        <start_date>2013-05-01 00:00:00</start_date>
        <end_date>2013-09-01 00:00:00</end_date>
        <recurrences>[1,3,5]</recurrences>
    </fixedevents>
    <preferences>
        <break>15</break>
        <show_fixed_events>1</show_fixed_events>
    </preferences>
    <schedule_start>2013-05-01 00:00:00</schedule_start>
</document>
```