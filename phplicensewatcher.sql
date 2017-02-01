DROP TABLE flexlm_events;
DROP TABLE license_usage;
DROP TABLE licenses_available;

CREATE TABLE flexlm_events (
    flmevent_date                            DATE NOT NULL,
    flmevent_time                            TIME NOT NULL,
    flmevent_type                VARCHAR(20) NOT NULL,
    flmevent_feature                         VARCHAR(40) NOT NULL,
    flmevent_user                            VARCHAR(80) NOT NULL,
    flmevent_reason                          TEXT NOT NULL,
    PRIMARY KEY (flmevent_date, flmevent_time, flmevent_feature, flmevent_user)
);    
    
CREATE TABLE license_usage (
    flmusage_server          varchar(80) NOT NULL,
    flmusage_product         varchar(80) NOT NULL,
    flmusage_date            date NOT NULL,
    flmusage_time            time NOT NULL,
    flmusage_users           int  NOT NULL,
    PRIMARY KEY (flmusage_product, flmusage_server, flmusage_date, flmusage_time)
);

CREATE TABLE licenses_available (
   flmavailable_date                 date NOT NULL,
   flmavailable_server              varchar(80) NOT NULL,
   flmavailable_product 	  varchar(80) NOT NULL,
   flmavailable_num_licenses	  int NOT NULL,
   PRIMARY KEY (flmavailable_date, flmavailable_server, flmavailable_product, flmavailable_num_licenses)
);


