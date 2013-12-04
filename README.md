concrete5 - Localizer package
=============================

A concrete5 package to allow localization of special items in concrete5.

Installation
------------

1. cd into the packages directory 
2. clone repository using `git clone https://github.com/mlocati/concrete5-localizer.git localizer`

Or install using composer (http://getcomposer.org ) 

1. cd to your concrete5 root directory
2. run `curl -sS https://getcomposer.org/installer | php`
3. add this to your `composer.json` file

````
{
    "require": {
        "mlocati/localizer": "@dev"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mlocati/concrete5-localizer"
        }
    ]
}
````

4. run `php composer.phar install`
