MATCH(n) DETACH DELETE n;

LOAD CSV WITH HEADERS FROM "https://raw.githubusercontent.com/i1t2b3/train-stations-matrix/master/csv/codes.csv" AS row
CREATE (n:Station)
SET 
  n = row;

CREATE INDEX ON :Station(stationID);

LOAD CSV WITH HEADERS FROM "https://raw.githubusercontent.com/i1t2b3/train-stations-matrix/master/csv/code_pairs2.csv" AS row
MATCH (f:Station), (to:Station)
WHERE f.code = row.from AND to.code = row.to
CREATE (f)-[details:LEADS]->(to)
SET details.time = toInteger(row.time),
    details.train = row.train


MATCH (a:Station {code: "AAP"})-[r:LEADS]->(b:Station {code: "SOO"}) RETURN a, r, b

MATCH 
  p = shortestPath(a:Station {code: "ABW"})-[l:LEADS*]->(b:Station {code: "PLU"})
RETURN 
  reduce(time=0, r in relationships(p) |  time+r.time) AS totalTime
ORDER BY totalTime ASC
LIMIT 1


MATCH (start:Station {code: "AAP"}), (end:Station {code: "PLU"})
CALL algo.kShortestPaths.stream(start, end, 2, 'time' ,{})
YIELD index, nodeIds, path, costs
RETURN [node in algo.getNodesById(nodeIds) | node.code] AS places,
       costs,
       reduce(acc = 0.0, time in costs | acc + time) AS totalTime


MATCH p=(a:Station {code: "ABW"})-[*]->(b:Station {code: "PLU"})
RETURN p AS shortestPath, 
reduce(time=0, r in relationships(p) |  time+r.time) AS totalTime
                ORDER BY totalTime ASC
                LIMIT 1


Neo.TransientError.General.OutOfMemoryError: There is not enough memory to perform the current task. Please try increasing 'dbms.memory.heap.max_size' in the neo4j configuration (normally in 'conf/neo4j.conf' or, if you you are using Neo4j Desktop, found through the user interface) or if you are running an embedded installation increase the heap by using '-Xmx' command line flag, and then restart the database.