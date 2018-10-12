INSERT IGNORE INTO code_pairs (`from`, `to`)
SELECT c1.code, c2.code
FROM codes c1
INNER JOIN codes c2 ON c1.code <> c2.code;


# example: calculate progress for codes starting with Z
SELECT
    c.`code`,
    c1.cnt as Done,
    c2.cnt as Total,
    (100 * c1.cnt / c2.cnt) AS Progress
FROM codes c
    INNER JOIN (SELECT c1.`from` AS code, COUNT(*) AS cnt FROM code_pairs c1 WHERE `total_time` IS NOT NULL  GROUP BY c1.`from`) c1 USING(`code`)
    INNER JOIN (SELECT c2.`from` AS code, COUNT(*) AS cnt FROM code_pairs c2  GROUP BY c2.`from`) c2 USING(`code`)
WHERE
    c.`code` LIKE 'z%';

# overall progress
SELECT
    (SELECT COUNT(*) AS cnt FROM code_pairs WHERE `total_time` IS NOT NULL) as Done,
    (SELECT COUNT(*) AS cnt FROM code_pairs) as Total,
    (100 * (SELECT COUNT(*) AS cnt FROM code_pairs WHERE `total_time` IS NOT NULL) / (SELECT COUNT(*) AS cnt FROM code_pairs)) AS Progress
