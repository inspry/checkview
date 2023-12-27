# CheckView

## Branch description 

> **production:-** It corresponds to live website and will be exact copy of live filesystem

> **master:-** It will contain stable changes which are tested and ready to go live

> **staging:-** It corresponds to staging website and might contain unstable changes as they are under development

## Development process

### Setting up repository/project on local-

- clone the repository on local machine
- Its ready to use now.

Now you can access the project as a WP plugin via browser and it should work.

### Initiating new task-

- whenever, the developer needs to implement new task, he should create a new branch from “master” on his/her local setup.
- then developer can do and commit the changes in the newly created branch 
- non composer work should be done in app folder only and only changes of app folder should be commited

For composer work, for example , extension installation via composer

- run composer commands on local and only commit composer.json, composer.lock and auth.json

### Deploy changes to staging website -

- Merge the local branch on which custom work is done with the local staging branch 
- then local staging branch needs to be pushed to remote staging branch
- once this is done, login to staging server via SSH and navigate to root directory of WordPress
- execute command git pull origin staging this will ask a password and the password is same as of SSH password 
- if you have pushed composer related changes then run command composer install

### Deploy changes to live website -

- Merge the local branch on which custom work is done with the local master branch and push the master branch to remote master branch
- then merge local master branch with local production branch and push the production branch to remote production branch
- once this is done, login to live server via SSH and navigate to root directory of WordPress
- execute command git pull origin production this will ask a password and the password is same as of SSH password 
- if you have pushed composer related changes then run command composer install