# DataTransmitter

get LotData XML from FXL, filter, validate, transform to array and send it via REST to WMS. 

## Setup

### Setup docker configuration
> Make sure you use unique ports so everybody can run all microservices at the same time without
  port conflicts.


  
### Init docker environment

* `$ cd docker`
* `$ make setup`


    
###Start docker environment

* `$ cd docker`
* `$ make start`


###Connect S3-Bucket
* Host: variable (AWS_ENDPOINT in Makefile)
* Port: 45701

       
## processing
* `JIRA_ENABLE_CREATE_TICKET = 1` 
* `JIRA_PROJECT = (EDI  .etc)` 
* `$ cd docker`
* `$ make transmit`

> this command executes index.php file

