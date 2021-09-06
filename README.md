# Overview
BYou is Binghamton University's Identity & Access Management (IAM) Application Engine.  This application is 'alpha' stage of development and should not be considered for a production deployment.

# Developer Installation Instructions

1. Clone the repo locally: `git clone https://github.com/BinghamtonUniversity/BYou.git`
2. Install Composer Dependencies: `composer install`
3. Copy the `.env.enample` file to `.env`
4. Setup MySQL Databases:
```bash
$ mysql
> CREATE DATABASE byou;
> CREATE USER 'byou'@'127.0.0.1' IDENTIFIED BY 'byou';
> GRANT ALL PRIVILEGES ON byou. * TO 'byou'@'127.0.0.1';
> exit;
```
4. Modify the `.env` file as follows:
```
DB_DATABASE=byou
DB_USERNAME=byou
DB_PASSWORD=byou
```
5. Generate App Key: `php artisan key:generate`
6. Run Migrations & Seed Database: `php artisan migrate:refresh --seed`
7. Serve the application `php artisan serve`

# License
BYou is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).
