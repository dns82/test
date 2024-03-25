sequenceDiagram
       actor Client
       rect rgba(0, 0, 0, .2)
       note right of Client: Client create or update personal data 
       Client ->> Customer Facade: POST /<br>PUT /{id}
       activate Customer Facade
       Customer Facade ->>+ Personal: Save user data
       Personal -->>- Customer Facade: Personal data
       
