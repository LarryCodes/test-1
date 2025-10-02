# Endpoints

- GET /users
  - Get user by email
  - *Query Parameters*:
    - email (string, required): The email of the user to retrieve.

- POST /users/store
  - Store user information
  - *Body Parameters*:
    - email (string, required): The email of the user.
    - name (string): The name of the user.
    - country (string): The country of the user.

- GET /users/country
  - Get users by country
  - *Query Parameters*:
    - email (string, required): The email of the user whose country is to be retrieved.
