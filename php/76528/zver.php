<?php

use Aero\Supports\Lerma;

require 'autoload.php';

if ( filter_input ( INPUT_SERVER, 'REQUEST_METHOD' ) == 'POST' )
{
	# Ограничение на глобальную установку
	$add_points = false;
	
	# Максимум точек
	$max_points = 4;
	
	$input = array_diff ( filter_input_array ( INPUT_POST, [ 
		'user' => FILTER_DEFAULT, 
		'x' => [ 
			'filter' => FILTER_CALLBACK, 
			'options' => function ( $v )
			{
				if ( in_array ( $v, range ( 1, 73 ) ) )
				{
					return (int)$v;
				}
				
				return null;
			}
		], 
		'y' => [ 
			'filter' => FILTER_CALLBACK, 
			'options' => function ( $v )
			{
				if ( in_array ( $v, range ( 1, 54 ) ) )
				{
					return (int)$v;
				}
				
				return null;
			}
		],
		'color' => [ 
			'filter' => FILTER_CALLBACK, 
			'options' => function ( $v )
			{
				if ( in_array ( $v, range ( 1, 17 ) ) )
				{
					return (int)$v;
				}
				
				return null;
			}
		]
	] ), [ null ] );
	
	if ( !( $input != [] ?? false ) || ( $users = Lerma :: prepare( 'SELECT id FROM users WHERE name = ?', [ $input['user'] ] ) ) -> rowCount() == 0 )
	{
		echo json_encode ( [ 'err' => [ 'message' => 'validation failed' ] ] );
	}
	else
	{
		$lrm = Lerma :: query( [ 'SELECT content FROM kub WHERE user = %d', $id = $users -> fetch( Lerma :: FETCH_COLUMN ) ] );
		
		if ( isset ( $input['x'], $input['y'], $input['color'] ) )
		{
			$map = "{$input['x']}_{$input['y']}_{$input['color']}";
			
			if ( $lrm -> rowCount() )
			{
				$j = json_decode ( $lrm -> fetch( Lerma :: FETCH_COLUMN ), true );
				
				if ( in_array ( $map, $j ) )
				{
					Lerma :: query( [ 
						"UPDATE kub SET content = '%s' WHERE user = %d",  
						json_encode ( array_values ( array_diff ( $j, [ $map ] ) ) ), 
						$id 
					] );
					
					echo json_encode ( [ 'points' => [ 'user' => $input['user'], 'unset' => [ $map ] ] ] );
				}
				else
				{
					if ( count ( $j ) < $max_points )
					{
						foreach ( $j AS $k => $p )
						{
							[ $x, $y, $color ] = explode ( '_', $p );
							
							if ( $input['x'] == $x && $input['y'] == $y )
							{
								unset ( $j[$k] );
								
								$add_points = true;
								
								break;
							}
							elseif ( !$add_points && in_array ( "{$x}_{$y}", [ ( $input['x'] - 1 ) . '_' . $input['y'], ( $input['x'] + 1 ) . '_' . $input['y'], $input['x'] . '_' . ( $input['y'] - 1 ), $input['x'] . '_' . ( $input['y'] + 1 ) ] ) )
							{
								$add_points = true;
							}
						}
					}
					
					
					
					if ( $add_points || $j == [] )
					{
						$j[] = $map;
						
						Lerma :: query( [ "UPDATE kub SET content = '%s' WHERE user = %d", json_encode ( array_values ( $j ) ), $id ] );
						
						echo json_encode ( [ 'points' => [ 'user' => $input['user'], 'add' => [ $map ] ] ] );
					}
					else
					{
						echo json_encode ( [ 'points' => [ 'user' => $input['user'], 'err' => [ $map ] ] ] );
					}
				}
			}
			else
			{
				Lerma :: query( [ "INSERT INTO kub ( user, content ) VALUES ( %d, '%s' )", $id, json_encode ( [ $map ] ) ] );
				
				echo json_encode ( [ 'points' => [ 'user' => $input['user'], 'add' => [ $map ] ] ] );
			}
		}
		else
		{
			if ( $lrm -> rowCount() )
			{
				echo json_encode ( [ 'points' => [ 'user' => $input['user'], 'add' => json_decode ( $lrm -> fetch( Lerma :: FETCH_COLUMN ), true ) ] ] );
			}
			else
			{
				echo json_encode ( [ 'points' => [] ] );
			}
		}
	}
}
# END