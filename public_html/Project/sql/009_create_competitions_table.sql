CREATE TABLE IF NOT EXISTS Competitions(
    id int auto_increment not null,
    username varchar(20) not null unique,
    duration TIME NOT NULL,
    expires TIME DEFAULT ADDTIME(duration, null),
    current_reward int,
    starting_reward int,
    join_fee int,
    current_participants int,
    min_participants int,
    paid_out boolean,
    min_score int,
    first_place_per varchar(20),
    second_place_per varchar(20),
    third_place_per varchar(20),
    cost_to_create int,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified timestamp default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
} 