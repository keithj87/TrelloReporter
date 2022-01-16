Trello Reporter
====

This is a simple PHP script that reports the status of trello Cards, based on a board and tag.
<br><br>

#### Setting Environment Variables
This project uses environment variables for Trello's API. Using env variables allows for better security and configuration setup/changes, without requiring a deployment. The following environment variables need to be added to your ```.bashrc``` file:
* **TRELLO_API_KEY**
* **TRELLO_API_KEY**
<br><br>

#### Script Options
* ```-b```
    - Description: This is the Trello Board ID 
    - Required: YES
* ```-t```
    - Description: This is the tag to filter on for the Trello Board cards. If filtering by more than one tag is desired, you can provide a list with the tags comma separated( i.e. "Tag1,Tag2" )
    - Required: YES
* ```--overview```
    - Description: When this flag is set, an overview for cards is given based on the list set on the Trello Board
    - Required: NO
* ```--detailed```
    - Description: When this flag is set, an detailed overview for cards is given based on the list set on the Trello Board 
    - Required: No
* ```--csv```
    - Description: When this flag is set, a CSV output for cards on the Trello Board is provided. The columns will be: Lane, Labels, Card Title.
    - Required: No
<br><br>

#### Usage
* Regular - **php trelloReporter.php -b ```{board_id}``` -t ```{tag}```**
* Overview - **php trelloReporter.php -b ```{board_id}``` -t ```{tag}``` --overview**
* Detailed - **php trelloReporter.php -b ```{board_id}``` -t ```{tag}``` --detailed**
* CSV - **php trelloReporter.php -b ```{board_id}``` -t ```{tag}``` --csv**