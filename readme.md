# PHP-ZTS-ZMQ-Async-Server
&copy; Paul Novack https://github.com/PaulNovack

[![License](https://img.shields.io/badge/License-BSD_3--Clause-blue.svg)](https://opensource.org/licenses/BSD-3-Clause)

Listens on a ZMQ port and processes requests asynchronously

###Must be running a ZTS build of php.  See ub20-zts-build.sh for a file that will compile php-zts on a fresh ubuntu 20 server

Currently has an example for sending SQL commands asynchronously.

ToDo:  Add an example for using this as a remove log collector.

Pluggable architecture allows adding of workers by simply adding a Worker Class
and a Worker Client class to send items to queue.

NOTE!!:  The application will create folders data, logs,queueData above the project directory.  This is by intent to keep IDE from indexing the files will create a lot of files and slow IDE if you are using an IDE.

### Create new Workers 
* Set the number of threads in Constructor
* Set the delay between threads being spawned
* Set content of runThread in a closure

### Built in Methods for all Workers

* CLASSNAMEQueued - Return array of all items in queue with payload
* CLASSNAMEProcessing - Returns array of all items being processed in a thread

### Shared by all classes will iterate all Workers

* PersistQueue - Writes all queue Items to disk
* Shutdown - Writes all queue items to disk and shutsdown server

#### Tip for issues with parallel

if you get "Uncaught parallel\Runtime\Error\Bootstrap: bootstrapping failed with parallelBootstrap.php "
try to comment out the including of classes in that file to see the PHP compile error.
or comment out the line   

\parallel\bootstrap('parallelBootstrap.php');

### Useful commands when coding if something hangs somehow

#### get process listening on port
sudo lsof -i -P -n | grep LISTEN | grep 5555 | cut -c 11-16

#### kill by process id
sudo kill -9 process_id

#### One liner find process and kill it evaluate grep of listen cut chars 11-16
sudo kill -9 `sudo lsof -i -P -n | grep LISTEN | grep 5555 | cut -c 11-16`

#### File count in directory
ls | wc -l