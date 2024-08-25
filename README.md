
# Keos eventos

## Introduction

Keos Eventos is an Ticket Sales and Event Management System


## Minimum Requirements

The application uses the following dependencies:

- PHP 7.2
- Shymphony 4.4


## Features

- Beautiful Landing Page
- Personal Experience
- Subscribers
- Event add for physical & virtual.
- zoom, teams , google meeting sdk add 
- chatbot train text & multiple attach file.
- chat with chatbot for about induvidually event.
- Quiz run into the meeting running. 
- Many More .....

## Getting Started

### Installation

To install the required dependencies, run the following command:

### Starting the Application

To start the application, use the following command:

Step 1 - Clone the Git Repository:

```
git clone https://github.com/Keos-LLC/keosEventsCX.git

```
Step 2 - Import the Database:

```
Import the provided database file into MySQL server.

```

Step 3 - After Import The Database you need to run this command in SQL

```
SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));

```

Step 4 - Install Dependencies:

```
composer install

```

### Starting Configuration in env

Step 5 - You need to some following setup :

```

MAINTENANCE_MODE=0
APP_ENV=prod
APP_DEBUG=false

IS_WEBSITE_CONFIGURED=1
WEBSITE_NAME=Keos

WEBSITE_ROOT_URL=keos.co
DATE_FORMAT="eee dd MMM y, h:mm a z"
DATE_FORMAT_SIMPLE="d/m/Y, g:i A T"
DATE_FORMAT_DATE_ONLY="D j M Y"
DATE_TIMEZONE=America/New_York
DEFAULT_LOCALE=en

APP_LOCALES=en|fr|es|ar|de|pt|it|br|

###> symfony/framework-bundle ###
APP_SECRET=9002a243dfd2fa2acf5ab6ffdc572b63
###< symfony/framework-bundle ###

```

Step 6 - Database Connection (Configure your .env file with the following details) :

```
###> doctrine/doctrine-bundle ###
# Format described at http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url
# For an SQLite database, use: "sqlite:///%kernel.project_dir%/var/data.db"
# Configure your db driver and server_version in config/packages/doctrine.yaml

DATABASE_URL=mysql://{user_name}:{password}@{host_address}/{database_name}

###< doctrine/doctrine-bundle ###

```

Step 7 - Mail Server Setup (Configure your .env file for SMTP mailing) :

```
###> symfony/swiftmailer-bundle ###
# Delivery is disabled by default via "null://localhost"
NO_REPLY_EMAIL=admin@eventos.keosapp.com
MAILER_URL=smtp://7b5edb12bf63621a4ebd4541cb13bb3e:02af2ff382b71bdf715deb225336bb0f@in-v3.mailjet.com:587/?encryption=tls
MAILER_DSN=gmail+smtps://acoronel40teamsourcing.com.ec:zybamiinoettched@default

###< symfony/swiftmailer-bundle ###

```

Step 8 - If want to connect recaptcha

```
###> excelwebzone/recaptcha-bundle ###
# To use Google Recaptcha, you must register a site on Recaptcha's admin panel:
# https://www.google.com/recaptcha/admin
EWZ_RECAPTCHA_SITE_KEY=
EWZ_RECAPTCHA_SECRET=
###< excelwebzone/recaptcha-bundle ###

```

Step 9 - CORS Bundle :

```
###> nelmio/cors-bundle ###
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
###< nelmio/cors-bundle ###

```

Step 10 -  Node server connect - Socket Server - Required :

```
NODE_SERVER="https://socket.keosapp.com/"

```

Step 11 -  Class Quiz API - Required :

```
QUIZ_API="https://classquiz.keosapp.com/play"

```

Step 12 -  ZOOM_AUTH_END_POINT - Required :

```
ZOOM_AUTH_END_POINT="https://zoom.keosapp.com"

```

Step 13 -  Chatbot Setting - required :

```
MAIN_DOMAIN="https://eventos.keoscx.com/"
CHATBOT_BASEURL="https://chatbot-train.keoscx.com/api/v1"
CHATBOT_APIHOST="https://keosgpt.keoscx.com"
CHATBOT_FLOWISE="https://cdn.jsdelivr.net/npm/flowise-embed/dist/web.js"

```

Step 14 - Others & Additional Setup Setup As like php-encryption , Google Maps , MailChimp ,google/apiclient , :

```
###> defuse/php-encryption ###
PAYUM_CYPHER_KEY=def000004e528ca52c35f95d09c4b1cd1c05890e731986c6db6e8dc8b703d1a27a57da13d2770a2974651a4842241b407b83ff29e71dae4491bbff3b1088778f1fc067d4
###< ###

###> Google Maps ###
# Leave api key empty to disable google maps project wide
GOOGLE_MAPS_API_KEY=
###< ###

###> MailChimp ###
NEWSLETTER_ENABLED=no
MAILCHIMP_API_KEY=
MAILCHIMP_LIST_ID=
###< ###


###> google/apiclient ###
GOOGLE_API_KEY=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_AUTH_CONFIG=%kernel.project_dir%/path/to/file.json
###< google/apiclient ###

```

## How To Create Google Credentials , Team Credentials &  Zoom Credentials 

```
Link : 

```

## License

- This project is licensed under the MIT License.


