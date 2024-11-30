<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once(__DIR__ . '/../lib/DBOpenHelper.php');

$app->post('/weeks', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody()->getContents(), true);

    $key = $request->getHeader('Api-Key');
    if (validateAuthKey2($args["user_id"], $key[0])) {
        $db = new DBOpenHelper();
        $id = $db->execInsert("weeks", $params);

        if ($id > 0) {
            foreach ($params["verses"] as $key => $value) {
                $value["weeks_id"] = $id;
                $result = $db->execInsert("week_days", $value);
            }
        }
    }

    $response->getBody()->write(json_encode($result));
    return $response;
});

$app->get('/weeks/{user_id}', function (Request $request, Response $response, $args) {
    $key = $request->getHeader('Api-Key');
    if (validateAuthKey2($args["user_id"], $key[0])) {
        $db = new DBOpenHelper();
        $weeks = $db->queryAll("weeks", "order by number desc");

        if ($weeks) {
            foreach ($weeks as $key => $values) {
                $days = $db->queryWhere("week_days", "weeks_id = " . $values["id"]);
                $weeks["$key"]["verses"] = $days;
            }
        }
    }

    $response->getBody()->write(json_encode($weeks));
    
    return $response;
});

$app->put('/weeks', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody()->getContents(), true);
    $db = new DBOpenHelper();
    $result = $db->execUpdate("weeks", $params);

    if ($result)
        foreach ($params["verses"] as $key => $value)
            $result = $db->execUpdate("week_days", $value);

    $response->getBody()->write(json_encode(print_r($params)));
    return $response;
});

$app->delete('/weeks/{user_id}/{id}', function (Request $request, Response $response, $args) {

    $key = $request->getHeader('Api-Key');
    if (validateAuthKey2($args["user_id"], $key[0])) {
        $db = new DBOpenHelper();
        $status = $db->execDelete("week_days", "weeks_id = " . $args["id"]);

        if ($status)
            $status = $db->execDelete("weeks", "id = " . $args["id"]);
    }

    $response->getBody()->write(json_encode($status));
    return $response;
});


function validateAuthKey2($id_user, $auth_key) {

    $db = new DBOpenHelper();
    $where = "id = " . addslashes($id_user);

    $users = $db->queryWhere("users", $where);
    if (!is_array($users)) {
        return false;
    }
    
    $user = $users[0];

    return password_verify($id_user . $user['password'], $auth_key);
}