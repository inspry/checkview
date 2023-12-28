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


```markdown
# CheckView API Class

Handles Froms API functions for the CheckView plugin.

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Endpoints](#endpoints)
- [Methods](#methods)
- [Permission Check](#permission-check)

## Overview

This class defines the code necessary for handling CheckView Form API CRUD operations. It includes methods for registering REST API routes, retrieving available forms, registering form tests, and managing test results.

## Requirements

- PHP 5.6 or later
- WordPress
- CheckView plugin

## Installation

Include this class in your plugin or theme:

```php
require_once 'path/to/class-checkview-api.php';
```

## Usage

Initialize the class:

```php
$checkview_api = new CheckView_Api( 'your_plugin_name', 'your_plugin_version' );
```

Register REST API routes:

```php
$checkview_api->checkview_register_rest_route();
```
## Base URL
The base URL for the API is `https://your-wordpress-site.com/wp-json/checkview/v1`.

## Authentication
The API requires authentication using a valid JWT token. Include the JWT token in the request headers with the key `_checkview_token`.

## Endpoints

### 1. Retrieve Available Forms List

- **Endpoint:** `/forms/formslist` (GET)
- **Description:** Retrieve the list of available forms.
- **Parameters:**
  - `_checkview_token` (required): Valid JWT token for authentication.
- **Usage:**
  ```bash
  GET /wp-json/checkview/v1/forms/formslist?_checkview_token=your_jwt_token
  ```
- **Returns:**
  - `200 OK` on success with JSON body containing the forms list.
  - `400 Bad Request` on failure with error details.
- **Response:**
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

### 2. Register Form Test

- **Endpoint:** `/forms/registerformtest` (PUT)
- **Description:** Register a form test for validation.
- **Parameters:**
  - `_checkview_token` (required): Valid JWT token for authentication.
  - `frm_id` (required): Form ID.
  - `pg_id` (required): Page ID.
  - `type` (required): Test type.
  - `send_to` (required): Email or destination for test results.
- **Usage:**
  ```bash
  PUT /wp-json/checkview/v1/forms/registerformtest?_checkview_token=your_jwt_token&frm_id=123&pg_id=456&type=test&send_to=email@example.com
  ```
- **Returns:**
  - `200 OK` on success with JSON body containing success message.
  - `400 Bad Request` on failure with error details.
- **Response:**
  ```json
  {
    "status": 200,
    "response": "success",
    "body_response": "Check Form Test Successfully Added"
  }
  ```

### 3. Retrieve Test Results for a Form

- **Endpoint:** `/forms/formstestresults` (GET)
- **Description:** Retrieve test results for a specific form.
- **Parameters:**
  - `uid` (required): User ID.
  - `_checkview_token` (required): Valid JWT token for authentication.
- **Usage:**
  ```bash
  GET /wp-json/checkview/v1/forms/formstestresults?uid=123&_checkview_token=your_jwt_token
  ```
- **Returns:**
  - `200 OK` on success with JSON body containing the test results.
  - `400 Bad Request` on failure with error details.
- **Response:**
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

### 4. Delete Test Results for a Form

- **Endpoint:** `/forms/deleteformstest` (DELETE)
- **Description:** Delete test results for a specific form.
- **Parameters:**
  - `uid` (required): User ID.
  - `_checkview_token` (required): Valid JWT token for authentication.
- **Usage:**
  ```bash
  DELETE /wp-json/checkview/v1/forms/deleteformstest?uid=123&_checkview_token=your_jwt_token
  ```
- **Returns:**
  - `200 OK` on success with JSON body containing success message.
  - `400 Bad Request` on failure with error details.

## Methods

- `checkview_get_available_forms_list()` - Retrieve available forms list.
- `checkview_get_available_forms_test_results()` - Retrieve test results for a form.
- `checkview_register_form_test()` - Register a form test.
- `checkview_delete_forms_test_results()` - Delete test results for a form.

## Permission Check

The class includes a permission check for validating JWT tokens before API calls. The `get_items_permissions_check()` method performs the check.

```php
// Example Usage
$valid_token = $checkview_api->get_items_permissions_check( $request );
if ( is_wp_error( $valid_token ) ) {
    // Handle invalid token.
} else {
    // Proceed with the API call.
}
```
## Notes
- The API responses and examples provided are for illustrative purposes. Make sure to replace placeholder values such as `Your_JWT_Token`, form IDs, page IDs, etc., with actual values.
- This documentation assumes the implementation of the JWT authentication mechanism and the functions `validate_jwt_token` and `must_ssl_url`, which are referenced in the provided code. Ensure that these functions are defined and functional in your implementation.
- Adjust the URLs, namespaces, and authentication mechanisms based on your WordPress setup and customizations.

For more information, visit [CheckView Documentation](https://checkview.io).
