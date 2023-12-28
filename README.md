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

# CheckView API Documentation

## Introduction
This documentation provides details about the endpoints and functionalities exposed by the CheckView API class. The API is designed to handle CRUD operations related to CheckView forms and their test results.

## Base URL
The base URL for the API is `https://your-wordpress-site.com/wp-json/checkview/v1`.

## Authentication
The API requires authentication using a valid JWT token. Include the JWT token in the request headers with the key `_checkview_token`.

## Endpoints

### 1. Retrieve Available Forms List

**Endpoint:** `/forms/formslist`

**Method:** `GET`

**Parameters:**
- `_checkview_token` (required): JWT token for authentication.

**Usage:**
Retrieve a list of available forms along with associated information such as form ID, name, pages, and addons.

### 2. Retrieve Form Test Results

**Endpoint:** `/forms/formstestresults`

**Method:** `GET`

**Parameters:**
- `_checkview_token` (required): JWT token for authentication.
- `frm_id` (required): Form ID.
- `pg_id` (required): Page ID.
- `type` (required): Type of form.
- `send_to` (required): Email address to send test results.

**Usage:**
Retrieve test results for a specific form identified by the form ID, page ID, form type, and email address to send the results.

### 3. Register Form Test

**Endpoint:** `/forms/registerformtest`

**Method:** `DELETE`

**Parameters:**
- `_checkview_token` (required): JWT token for authentication.
- `id` (required): ID of the form test to be deleted.

**Usage:**
Deletes a registered form test identified by its ID.

## Permissions
All API endpoints require a valid JWT token for authentication. Ensure that the `_checkview_token` parameter is included in the request headers. Invalid or missing tokens will result in a `400` error response.

## Error Handling
Errors are returned in JSON format with the following structure:
```json
{
  "status": "error",
  "code": 400,
  "message": "Error message details"
}
```

## Examples
### Retrieve Available Forms List
**Request:**
```http
GET /wp-json/checkview/v1/forms/formslist
Headers:
  _checkview_token: Your_JWT_Token
```
**Response:**
```json
{
  "status": 200,
  "response": "Successfully retrieved the forms list.",
  "body_response": {
    "GravityForms": {
      "1": {
        "ID": 1,
        "Name": "Gravity Form 1",
        "addons": ["addon1", "addon2"],
        "pages": [{"ID": 1, "url": "https://example.com/page1"}]
      },
      "2": {
        "ID": 2,
        "Name": "Gravity Form 2",
        "addons": ["addon3", "addon4"],
        "pages": [{"ID": 2, "url": "https://example.com/page2"}]
      }
    },
    "FluentForms": {
      "3": {"ID": 3, "Name": "Fluent Form 1", "pages": [{"ID": 3, "url": "https://example.com/page3"}]}
    },
    // ... other form types
  }
}
```

### Retrieve Form Test Results
**Request:**
```http
GET /wp-json/checkview/v1/forms/formstestresults?_checkview_token=Your_JWT_Token&frm_id=1&pg_id=1&type=gravityforms&send_to=test@example.com
```
**Response:**
```json
{
  "status": 200,
  "response": "Successfully retrieved the test results.",
  "body_response": [
    {"field_id": "input_1_field1", "field_value": "Value1"},
    {"field_id": "input_1_field2", "field_value": "Value2"},
    // ... other fields
  ]
}
```

### Register Form Test
**Request:**
```http
DELETE /wp-json/checkview/v1/forms/registerformtest?_checkview_token=Your_JWT_Token&id=1
```
**Response:**
```json
{
  "status": 200,
  "response": "success",
  "body_response": "Check Form Test Successfully Added"
}
```

## Notes
- The API responses and examples provided are for illustrative purposes. Make sure to replace placeholder values such as `Your_JWT_Token`, form IDs, page IDs, etc., with actual values.
- This documentation assumes the implementation of the JWT authentication mechanism and the functions `validate_jwt_token` and `must_ssl_url`, which are referenced in the provided code. Ensure that these functions are defined and functional in your implementation.
- Adjust the URLs, namespaces, and authentication mechanisms based on your WordPress setup and customizations.