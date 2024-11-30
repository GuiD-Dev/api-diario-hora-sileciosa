<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once(__DIR__ . '/../lib/DBOpenHelper.php');

$app->post('/users-auth', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody()->getContents(), true);
    $name       = $params["name"];
    $password   = $params["password"];
    
    $db = new DBOpenHelper();
    $where = "name = '" . addslashes($name) . "'";
    
    if ($params["missionary"]) {
        $where = $where . " and missionary = true";
    }
    
    $users = $db->queryWhere("users", $where);
    $result = [];
        
    if ($users) {
        $user = $users[0];

        if (password_verify($password, $user['password'])) {            
            $result['apiKey'] = generateAuthKey($user["id"], $user['password']);
            
            unset($user['password']);

            $result['user'] = $user;
        }
    }
    
    $response->getBody()->write(json_encode($result));
    return $response;
});


$app->post('/users', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody()->getContents(), true);
    $auth_key = $request->getHeader('Api-Key');
    $id_user  = $params["loggedUserId"];

    if (validateAuthKey($id_user, $auth_key[0])) {
        $db = new DBOpenHelper();
        $password = $params["user"]["password"];

        if (!empty($password)) {
            $params["user"]["password"] = password_hash($password, PASSWORD_DEFAULT);
        }

        $result = $db->execInsert("users", $params["user"]);
        $response->getBody()->write(json_encode($result));
    } else {
        $response->getBody()->write(json_encode("Chave inválida."));
    }

    return $response;
});


$app->get('/users/{user_id}', function (Request $request, Response $response, $args) {

    $users = [];
    $key = $request->getHeader('Api-Key');
    if (validateAuthKey($args["user_id"], $key[0])) {
        $db = new DBOpenHelper();
        $users = $db->queryAll("users", "order by name asc");
        
        if (is_array($users)) {
            $users = array_map(function ($user) {
                unset($user['password']);
                return $user;
            }, $users);
        }
    }

    $response->getBody()->write(json_encode($users));
    return $response;
});


$app->put('/users', function (Request $request, Response $response, $args) {
    $params = json_decode($request->getBody()->getContents(), true);
    $auth_key = $request->getHeader('Api-Key');
    $id_user  = $params["loggedUserId"];

    // OBTER O ID do USUARIO LOGADO
    if (validateAuthKey($id_user, $auth_key[0])) {
        $db = new DBOpenHelper();
        $password = $params["user"]["password"];

        if (!empty($password)) {
            $params["user"]["password"] = password_hash($password, PASSWORD_DEFAULT);
        }

        $result = $db->execUpdate("users", $params["user"]);
        $response->getBody()->write(json_encode($result));
    } else {
        var_dump($params["key"]);
        $response->getBody()->write(json_encode($result));
    }

    return $response;
});


$app->delete('/users/{user_id}/{id}', function (Request $request, Response $response, $args) {
    $user_id  = $args["user_id"];
    $auth_key = $request->getHeader('Api-Key');

    if (validateAuthKey($user_id, $auth_key[0])) {
        $db = new DBOpenHelper();
        $status = $db->execDelete("users", "id = " . $args["id"]);
    } else {
        $status = "Falha ao validar chave de autenticação.";
    }

    $response->getBody()->write(json_encode($status));
    return $response;
});


function generateAuthKey($id_user, $password_hash) {
    return password_hash($id_user . $password_hash, PASSWORD_DEFAULT);
}

function validateAuthKey($id_user, $auth_key) {

    $db = new DBOpenHelper();
    $where = "id = " . addslashes($id_user);

    $users = $db->queryWhere("users", $where);
    if (!is_array($users)) {
        return false;
    }
    
    $user = $users[0];

    return password_verify($id_user . $user['password'], $auth_key);
}