#NOTE

This isn't an installable extension because there is only one site we are using it and we only patching the existing version.

I have opened it for getting code reviews and to allow people to contribute


# Extension to manage the volunteers portal
## Main features
* 3 levels: Departments, Groups, Subgroups
* Allows self management

## Organisation structure
### Department 
A department consists of a number of groups.

### Group
Is build for a certain purpose and has team members, a team leader, an assistant team leader and can have a 2nd assistant team leader. A group can have subgroups.

### Subgroup
Subgroups are just a tool to assign responsibilities to a part of the group, they can have a subgroup team leader and assistant leaders. Subgroups can be created and removed as needed. You need to be part of the group to join a subgroup.

## Access control
There are two ways for access control built-in

1. Access control over Joomla ACL
This is only used to set up administrators. If you are administrator you can do anything at any level. Administrators are the only ones who can create departments, groups and can change not to a department connected groups.

2. Access control because of your position in the organisation
This allows the self management and should make sure that people follow the processes we as a community have agreed to.

### Department: team leader
* Has to use the backend
* Can set up department assistant team leaders (limited to two)
* Can edit department information
* Can edit group information
* Can add group members on group level

### Department: assistant team leader
* Can edit department information
* Can edit group information
* Can add group members on group level

### Group: team leader
* Can set up group assistant team leaders (limited to two)
* Can edit group information
* Can add group members
* Can edit group information
* Can add reports
* Can add subgroups and administrate subgroups

### Group: assistant team leaders
* Can edit group information
* Can add group members
* Can edit group information
* Can add reports
* Can add subgroups and administrate subgroups

### Subgroup: team leader
* Can set up subgroup assistant team leaders (limited to two)
* Can edit subgroup information
* Can add subgroup members
* Can edit subgroup information
* Can add reports

### Subgroup: assistant team leaders
* Can edit subgroup information
* Can add subgroup members
* Can edit subgroup information
* Can add reports

### Group member
* Can add reports
* Can set up an leave date to be not longer listed as group member
