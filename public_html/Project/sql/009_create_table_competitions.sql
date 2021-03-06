CREATE TABLE IF NOT EXISTS Competitions(
    id int AUTO_INCREMENT PRIMARY KEY,
    name varchar(20) not null unique,
    duration int DEFAULT 3,
    expires TIMESTAMP,
    current_reward int DEFAULT 1,
    starting_reward int DEFAULT 1,
    join_fee int DEFAULT 0,
    current_participants int DEFAULT 0,
    min_participants int DEFAULT 3,
    paid_out boolean DEFAULT 0,
    min_score int DEFAULT 1,
    first_place_per varchar(20),
    second_place_per varchar(20),
    third_place_per varchar(20),
    cost_to_create int DEFAULT 2,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified  TIMESTAMP DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
)