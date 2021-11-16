CREATE TABLE IF NOT EXISTS  `Scores`(
    `id`            int auto_increment PRIMARY KEY,
    `user_id`       int,
    `score`         int,
    `modified`      timestamp default current_timestamp,
    FOREIGN KEY (`user_id`) REFERENCES Users(`id`),
    check (`score` > 0)
)