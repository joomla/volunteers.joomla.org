# volunteers.joomla.org
This is the repository of the website [volunteers.joomla.org](https://volunteers.joomla.org). This websites runs on a default Joomla! installation extended with a custom component called `com_volunteers`. Your ideas, feature requests, bug reports and code contributions are welcome! 
 
# Bugs, ideas & feature requests
Please use the [Issue tracker](https://github.com/joomla/volunteers.joomla.org/issues) to report bugs, ideas and feature requests.

---

# Contributing code
Code contributions are much welcome! Browse the [Issue tracker](https://github.com/joomla/volunteers.joomla.org/issues) for issues that need code and/or come up with your own ideas & code. Please open a [Pull Request](https://github.com/joomla/volunteers.joomla.org/pulls) to contribute your own code.

## Local Development
You can fork & clone the repository for local development. To get started you need to:
 - Clone the repository
 - Setup the [sample database](https://github.com/joomla/volunteers.joomla.org/tree/master/db/installation/)
 - Rename `configuration.php.dist` into `configuration.php`, add the database credentials and paths to the `logs` and `tmp` folder. All marked with `[XXXXXXXXXX]`.
 - Rename `htaccess.txt` into `.htaccess`

## Database changes
Please update the sample database if any code changes are needed for your code contributions and add a .sql file with the changes in the [updates folder](https://github.com/joomla/volunteers.joomla.org/tree/master/db/updates/) and use the issue number as filename. 

---

# Volunteers Portal Structure
The Volunteers Portal and its custom component `com_volunteers` is using the following setup.

_Note: please read `Departments` as `Leadership` until the new organizational structure and methodology of the Joomla!-project is in place._

## Departments
`com_volunteers/departments`

An Department has an _Departmental Coordination Team Leader_, _Assistant Departmental Coordination Team Leader_ and _Departmental Coordinators_. A department has several teams within their department. Within the department Reports can be created. Volunteers are assigned to an department as Member.

## Teams
`com_volunteers/teams`

A Team has a _Team Leader_, _Assistant Team Leader_, _Members_ and _Contributors_. A team is assigned to one department. A team can also contain one or more subteams. Within the team Reports and Roles can be created. Volunteers are assigned to a team as Member.

## Volunteers
`com_volunteers/volunteers`

A Volunteer (_Joomler_) is a user with an account on the website. A volunteer has their own profile with fields that can be edited by the volunteer.

## Members
`com_volunteers/members`

A Member is the relation between an Department or Team and a Volunteer. It also contains its Position and (optional) Role within the Department or Team. Each member has a Start Date. Once someone steps down an End Date will be set and the volunteer will move to the Honor Roll. Permissions connected with their Position will become inactive. 

## Reports
`com_volunteers/reports`

A Report is an article reporting about the progress of an Department or Team and written by a Volunteer.

## Positions
`com_volunteers/positions`

A Position is the relation between a Volunteer and its permissions. Departments and Teams have their own set of positions & permissions:

### Departments

* **Departmental Coordination Team Leader**
The _Departmental Coordination Team Leader_ is the leader of an department.

* **Assistant Departmental Coordination Team Leader**
The _Assistant Departmental Coordination Team Leader_ is the assistant of an Departmental Coordination Team Leader.

* **Departmental Coordinator**
The _Departmental Coordinator_ is a general member of an department.

#### Permissions

The _Departmental Coordination Team Leader_, _Assistant Departmental Coordination Team Leader_ and _Departmental Coordinator_ can:

* create 
  * teams within the department
  * reports for the department and teams within the department
  * members for the department and teams within the department
  * roles for the teams within the department
* edit
  * department
  * teams within the department
  * reports within the department and its teams
  * members within the department and its teams
  * roles for the teams within the department

### Teams

* **Team Leader**
The _Team Leader_ is the leader of a (sub)team.

* **Assistant Team Leader** 
The _Assistant Team Leader_ is the assistant of the leader of a (sub)team.

* **Member** 
The _Member_ is a general member of a team.

* **Contributor** 
The _Contributor_ is a contributing, but not a member of a team.

#### Permissions

The _Team Leader_ and _Assistant Team Leader_ can:

* create 
  * subteams for the team
  * reports for the team and subteams
  * members for the team and subteams
  * roles for the team and subteams
* edit
  * team
  * subteams
  * reports within the team and subteams
  * members within the team and subteams
  * roles within the team and subteams
  
The _Member_ can:

* create 
  * reports for the team involved
* edit
  * their own reports
  
The _Contributor_ can:

* _none_

## Roles
`com_volunteers/roles`

A Role is used (optionally) for teams. For each role a description can be provided and if the Role is open (Team is looking for Volunteers for the Role).

---

## Test User Accounts
The sample database has several accounts available for testing.

#### Admin

**Admin User**
User ID: 1
Name: `Admin Joomler`
Email: `admin@volunteers.joomla.org`
Password: `test`

#### Departmental Coordination Team Leader

**Frontend Department**
User ID: 2
Name: `Frontend Leader`
Email: `frontend.leader@volunteers.joomla.org`
Password: `test`

**Backend Department**
User ID: 5
Name: `Backend Leader`
Email: `backend.leader@volunteers.joomla.org`
Password: `test`

#### Assistant Departmental Coordination Team Leader

**Frontend Department**
User ID: 3
Name: `Frontend Assistant`
Email: `frontend.assistant@volunteers.joomla.org`
Password: `test`

**Backend Department**
User ID: 6
Name: `Backend Assistant`
Email: `backend.assistant@volunteers.joomla.org`
Password: `test`

#### Departmental Coordinator

**Frontend Department**
User ID: 4
Name: `Frontend Coordinator`
Email: `frontend.coordinator@volunteers.joomla.org`
Password: `test`

**Backend Department**
User ID: 7
Name: `Backend Coordinator`
Email: `backend.coordinator@volunteers.joomla.org`
Password: `test`

#### Team Leader

**Extensions Team**
User ID: 8
Name: `Extensions Leader`
Email: `extensions.leader@volunteers.joomla.org`
Password: `test`

**Sample Data Team**
User ID: 12
Name: `Sample Leader`
Email: `sample.leader@volunteers.joomla.org`
Password: `test`

#### Assistant Team Leader

**Extensions Team**
User ID: 9
Name: `Extensions Assistant`
Email: `extensions.assistant@volunteers.joomla.org`
Password: `test`

**Sample Data Team**
User ID: 13
Name: `Sample Assistant`
Email: `sample.assistant@volunteers.joomla.org`
Password: `test`

#### Member

**Extensions Team**
User ID: 10
Name: `Extensions Member`
Email: `extensions.member@volunteers.joomla.org`
Password: `test`

**Sample Data Team**
User ID: 14
Name: `Sample Member`
Email: `sample.member@volunteers.joomla.org`
Password: `test`

#### Contributor

**Extensions Team**
User ID: 11
Name: `Extensions Contributor`
Email: `extensions.contributor@volunteers.joomla.org`
Password: `test`

**Sample Data Team**
User ID: 15
Name: `Sample Contributor`
Email: `sample.contributor@volunteers.joomla.org`
Password: `test`