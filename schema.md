
1. USER table
id tinyint
first name string 
last name string
email string
password string (hash)
division string
sections string
status (ACTIVE ,INACTIVE, ARCHIRVIED, SUSPENDED)
role (ENUM: USER, STAFF, ADMIN)
position string2

2. Ticket Table
ID
USER.ID
issue = string
ticket no 
problem_category.id
data submitted datenow()
status (ENUM, OPEN, ESCALATED, CANCEL, CLOSE)
urgency (ENUM LOW, NORMAL, HIGH)
upload_intralab nullable
upload_limsportal nullable
description



problem_category
id
type
categories