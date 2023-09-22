<?php
namespace Tualo\Office\GraphQL\Routes;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;

use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\Basic\Route As R;
use Tualo\Office\Basic\IRoute;


class Route implements IRoute{
    public static function register(){

        R::add('/graphql/definition',function($matches){
            $list_tables_sql = 'select table_name from ds where existsreal=1 and title<>"" and table_name="test" ';

            try {
                $db = TualoApplication::get('session')->db;
                $list_tables=$db->direct($list_tables_sql);

                foreach($list_tables as $list_table){
                    $userType = new ObjectType([
                        'name' => 'User',
                        'fields' => [
                            'id' => Type::nonNull(Type::id()),
                            'firstName' => Type::string(),
                            'lastName' => Type::string()
                        ]
                    ]);
                }
                
            }catch(\Exception $e){
                TualoApplication::result('msg', $e->getMessage());
            }
        });
            
        R::add('/graphql/ep',function($matches){
            

            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'echo' => [
                        'type' => Type::string(),
                        'args' => [
                            'message' => Type::nonNull(Type::string()),
                        ],
                        'resolve' => fn ($rootValue, array $args): string => $rootValue['prefix'] . $args['message'],
                    ],
                ],
            ]);

            $schema = new Schema([
                'query' => $queryType
            ]);
            

            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            $query = $input['query'];
            $variableValues = isset($input['variables']) ? $input['variables'] : null;

            try {
                $rootValue = ['prefix' => 'You said: '];
                $result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues);
                $output = $result->toArray();
            } catch (\Exception $e) {
                $output = [
                    'errors' => [
                        [
                            'message' => $e->getMessage()
                        ]
                    ]
                ];
            }
            header('Content-Type: application/json');
            echo json_encode($output, JSON_THROW_ON_ERROR);
            exit();

        },['get','post'],true);

    }
}