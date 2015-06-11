# sql-server
TCP server is Created, that takes sql like statements, and returns rows from a fixed  csv file that match the query.


### Quick summary ###

This library created a TCP server (in PHP), that takes sql like statements, finds rows from a fixed csv file that match the query, and prints the results

### Installation ###

```
sudo apt-get install apache2

sudo apt-get install php5

sudo apt-get install libapache2-mod-php5

sudo /etc/init.d/apache2 restart
```


### Usage ###

```
php server.php                   //To start the server
telnet localhost 6543            //Run this in Separate Terminal, To run SQL Queries
```
