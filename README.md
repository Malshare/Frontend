# Malshare Frontend

## General Information
The main site data is stored under `public_html`.

All core operational functions are contained within `server_includes.php`.  Setup of users is handled by `server_registration.php`



## Setting up dev environment

The following will stand up a working test instance of MalShare which can be accessed at http://localhost/:

```
cd docker
docker-compose up

```

This env has several limitations, unless API keys are set:
 - Sample files are not included
 - Recaptcha doesn't work
 - Mailgun doesn't work