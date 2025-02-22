## Link Shortener
A simple project to redirect users by short link specified in URL.

![GitHub release](https://img.shields.io/github/release/misiektedi/short-links?color=blue)

 - Minimum `PHP 8.0`
 - Tested with **MySQL** and **MariaDB** databases


## **This project requires `Config.php` file with database connection credentials.**
Structure of **`Config.php`** file:

    <?php
    
    return [
	   'database_driver'  =>  '',
	   'database_host'  =>  '',
	   'database_name'  =>  '',
	   'database_user'  =>  '',
	   'database_password'  =>  '',
	];

Database must have two tables named:

 1. `links`
 3. `links_history`
