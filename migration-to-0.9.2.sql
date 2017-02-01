CREATE TABLE flexlm_events (
    date                            DATE NOT NULL,
    time                            TIME NOT NULL,
    type                VARCHAR(20) NOT NULL,
    feature                         VARCHAR(40) NOT NULL,
    user                            VARCHAR(80) NOT NULL,
    reason                          TEXT NOT NULL,
    PRIMARY KEY (date, time, feature, user)
);

INSERT INTO flexlm_events SELECT * FROM flexlm_denials;
