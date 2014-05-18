gpio-web
========


Easy to understand, flexible and powerful API to manage your GPIO from anywhere.


Features
========
  * It doesn't require sudo (only at installation time)
  * Very easy to use
  * Flexible and powerful
  * Uses json for easy to write clients
  * A nice demo is included
  

Installation
============

Run the following commands <b>as root</b>
```  bash
# Recommended to install in /var/www
$ cd /var/www
$ apt-get install git
$ git clone https://github.com/twinone/gpio-web
$ cd gpio-web/install
$ ./install.sh
```

Screenshots
===========

![Screenshot 1](https://raw.github.com/twinone/gpio-web/master/screenshots/screenshot1.png "Screenshot 1")
![Screenshot 2](https://raw.github.com/twinone/gpio-web/master/screenshots/screenshot2.png "Screenshot 2")


Usage
=====

If you just want to use your GPIO from the web, browse to:
```
http://rasbperry_ip_address_here/gpio-web/demo
```


Building an API Client
======================

See the [API doc](https://github.com/twinone/gpio-web/blob/master/APIDOC.md) for more info
