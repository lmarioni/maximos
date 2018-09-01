angular.module("Maximus",[])
.controller("MaximusController",function($scope,$http){
  //  $scope.maximos = '[{ "name":"fron squat", "max": "180" },{ "name":"clean", "max": "90" },]';

  $http.get("./api/public/maximos")
  .then(function(response){
    $scope.maximos = response.data;
    console.log($scope.maximos);
  },function(err){
    console.log(err);
  });

  $scope.addMax = function(){
    $http.post("./api/public/maximo/add",{
      name: $scope.newMax.name,
      kg: $scope.newMax.kg
    })
    .then(function(response){
      console.log(response);
      $scope.maximos.push($scope.newMax);
      $scope.newMax = [];
    },function(err){
      console.log(err);
    });
  }

  $scope.deleteMax = function(max){
    idMax = max.id;
    console.log(idMax);
    $http.delete("./api/public/maximo/delete/"+idMax)
    .then(function(response){
      //aca lo borro
      $scope.maximos = $scope.maximos.filter(function(element){
        return element.id !== max.id;
      });
      console.log(response);
    },function(err){
      console.log(err);
    });
  }

});



/*
example of list
maximos
[
{ "name":"fron squat", "max": "180" },
{ "name":"clean", "max": "90" },
]
*/
