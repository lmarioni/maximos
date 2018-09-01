<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

// $app->get('/[{name}]', function (Request $request, Response $response, array $args) {
//     // Sample log message
//     $this->logger->info("Slim-Skeleton '/' route");
//
//     // Render index view
//     return $this->renderer->render($response, 'index.phtml', $args);
// });

$app->get('/maximos', function (Request $request, Response $response, array $args) {
$c = new Sql('maximos');
$maximos = $c->fetch();
echo json_encode($maximos);
});

$app->post('/maximo/add', function (Request $request, Response $response, array $args) {
$name = $request->getParam('name');
$kg = $request->getParam('kg');
//guardo
$c = new Registry('maximos');
$c->name = $name;
$c->kg = $kg;
$c->date = date('Y-m-d');
$save = $c->save();

if($save>0){
  echo json_encode(array('error'=>'0','text'=>'success'));
}else {
  echo json_encode(array('error'=>'1','text'=>'error'));
}
});

$app->delete('/maximo/delete/{id}', function (Request $request, Response $response, array $args) {
  $id = $request->getAttribute('id');
  $sql = "DELETE FROM maximos WHERE id=$id;";
  $sqlresp=Sql::DoSql($sql);
if($sqlresp>0){
  return json_encode(array('error'=>'0','text'=>'success'));
}else {
  return json_encode(array('error'=>'1','text'=>'error'));
}
});
