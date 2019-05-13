<?php

namespace Aero\Database\ext;

use Aero\Supports\Lerma;
use Error;

final class mysqli implements \Aero\Database\InterfaceDriver
{
	private $connect, $result;
	
	public function __construct ( Lerma $lerma, array $params )
	{
		$this -> lerma = $lerma;
		
		[ 'host' => $host, 'user' => $user, 'password' => $pass, 'dbname' => $dbname, 'port' => $port, 'charset' => $charset ] = $params;
		
		$this -> connect = new \mysqli( $host, $user, $pass, $dbname, $port );
		
		if ( $this -> connect -> connect_error ) 
		{
			throw new Error( "Error connect ({$this -> connect -> connect_errno}) {$this -> connect -> connect_error}" );
		}
		
		$this -> connect -> set_charset( $charset );
	}
	
	public function isError( $obj = null )
	{
		$obj = $obj ?? $this -> connect;
		
		if ( $obj -> errno )
		{
			throw new Error( $obj -> error );
		}
	}
	
	public function query( string $sql )
	{
		return $this -> lerma -> query = $this -> connect -> query( $sql );
	}
	
	public function prepare( string $sql )
	{
		return $this -> lerma -> statement = $this -> connect -> prepare( $sql );
	}
	
	public function execute()
	{
		return $this -> lerma -> statement -> execute();
	}
	
	public function binding( array ...$binding )
	{
		$count = count ( $binding[0] );
		
		$for = ( array ) implode ( '', array_map ( function ( $arg )
		{
			if ( !in_array ( $type = gettype ( $arg ), [ 'integer', 'double', 'string' ] ) )
			{
				throw new Error( 'Invalid type ' . $type );
			}
			
			return $type[0];
		}, 
		$binding[0] ) );

		for ( $i = 0; $i < $count; $for[] = &${ 'bind_' . $i++ } ){}
		
		( new \ReflectionMethod( 'mysqli_stmt', 'bind_param' ) ) -> invokeArgs( $this -> lerma -> statement, $for );

		foreach ( $binding AS $items )
		{
			$items = $this -> lerma -> executeHolders( $items );
			
			extract ( $items, EXTR_PREFIX_ALL, 'bind' );
			
			$this -> lerma -> statement -> execute();
		}
		
		
	}
	
	public function bindResult( $result )
	{
		return $this -> lerma -> statement -> bind_result( ...$result );
	}
	
	public function close()
	{
		( $this -> lerma -> statement ?? $this -> lerma -> query ) -> close();
		
		$this -> lerma -> statement = $this -> lerma -> query = $this -> result = null;
		
		return $this;
	}
	
	/*
		- Определение типа запроса в базу данных
	*/
	protected function result()
	{
		if ( !is_null ( $this -> lerma -> statement ) )
		{
			return $this -> result ?? $this -> result = $this -> lerma -> statement -> get_result();
		}
		
		return $this -> lerma -> query;
	}
	
	public function fetch( int $int )
	{
		switch ( $int )
		{
			case Lerma :: FETCH_NUM:
				return $this -> result() -> fetch_array( MYSQLI_NUM );
			break;
			case Lerma :: FETCH_ASSOC:
				return $this -> result() -> fetch_array( MYSQLI_ASSOC );
			break;
			case Lerma :: FETCH_OBJ:
				return $this -> result() -> fetch_object();
			break;
			case Lerma :: FETCH_BIND:
				return $this -> lerma -> statement -> fetch();
			break;
			case Lerma :: FETCH_FIELD:
				return ( array ) $this -> result() -> fetch_field();
			break;
			default:
				return null;
		}
	}
	
	public function fetchAll( int $int )
	{
		switch ( $int )
		{
			case Lerma :: FETCH_NUM:
				return $this -> result() -> fetch_all( MYSQLI_NUM );
			break;
			case Lerma :: FETCH_ASSOC:
				return $this -> result() -> fetch_all( MYSQLI_ASSOC );
			break;
			case Lerma :: FETCH_FIELD:
				return $this -> result() -> fetch_fields();
			break;
			default:
				return null;
		}
	}
	
	public function countColumn(): int
	{
		return $this -> connect -> field_count;
	}
	
	public function rowCount(): int
	{
		return $this -> result() -> num_rows;
	}
	
	public function InsertID(): int
	{
		return $this -> connect -> insert_id;
	}
	
	public function rollBack( ...$rollback ): bool
	{
		return $this -> connect -> rollback( ...$rollback );
	}
	
	public function beginTransaction( ...$rollback ): bool
	{
		return $this -> connect -> begin_transaction( ...$rollback );
	}
	
	public function commit( ...$commit ): bool
	{
		return $this -> connect -> commit( ...$commit );
	}
}
