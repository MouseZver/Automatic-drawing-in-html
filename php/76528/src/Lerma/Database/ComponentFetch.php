<?php

namespace Aero\Database;

use Aero\Supports\Lerma;
use Error;

class ComponentFetch
{
	private $_fetch = [
		Lerma :: FETCH_NUM => [ 'fetch_num', 'all' => 'fetchall_num' ],
		Lerma :: FETCH_ASSOC => [ 'fetch_assoc', 'all' => 'fetchall_assoc' ],
		Lerma :: FETCH_FIELD => [ 'fetch_field', 'all' => 'fetchall_field' ],
		Lerma :: FETCH_OBJ => [ 'fetch_obj', 'all' => 'fetchall_obj' ],
		Lerma :: FETCH_BIND => [ 'fetch_bind' ],
		Lerma :: FETCH_BIND | Lerma :: FETCH_COLUMN => [ 'fetch_bind' ],
		Lerma :: FETCH_COLUMN => [ 'fetch_column', 'all' => 'fetchall_obj' ],
		Lerma :: FETCH_KEY_PAIR => [ 'fetch_key_pair', 'all' => 'fetchall_key_pair' ],
		Lerma :: FETCH_KEY_PAIR | Lerma :: FETCH_NAMED => [ 'all' => 'fetchall_key_pair' ],
		Lerma :: FETCH_FUNC => [ 'fetch_func', 'all' => 'fetchall_obj' ],
		Lerma :: FETCH_CLASS => [ 'fetch_class', 'all' => 'fetchall_obj' ],
		Lerma :: FETCH_CLASSTYPE => [ 'fetch_class', 'all' => 'fetchall_obj' ],
		Lerma :: FETCH_UNIQUE => [ 'all' => 'fetchall_unique' ],
		Lerma :: FETCH_CLASSTYPE | Lerma :: FETCH_UNIQUE => [ 'all' => 'fetchall_unique' ],
		Lerma :: FETCH_GROUP => [ 'all' => 'fetchall_group' ],
		Lerma :: FETCH_GROUP | Lerma :: FETCH_COLUMN => [ 'all' => 'fetchall_group_column' ],
	];
	
	/*
		- Контроль доступа к стилям
		- Стиль возвращаемого результата с одной строки
		- fetch_style - Идентификатор выбираемого стиля. Default Lerma :: FETCH_NUM
		- fetch_argument - атрибут для совершения действий над данными
	*/
	public function fetch( int $fetch_style = Lerma :: FETCH_NUM, $fetch_argument = null )
	{
		if ( $this -> _fetch[$fetch_style][0] ?? null )
		{
			return $this -> {$this -> _fetch[$fetch_style][0]}( $fetch_style, $fetch_argument );
		}
		
		throw new Error( 'unrecognized key name in fetch_style argument' );
	}

	/*
		- Контроль доступа к стилям
		- Стиль возвращаемого результата со всех строк
		- fetch_style - Идентификатор выбираемого стиля. Default Lerma :: FETCH_NUM
		- fetch_argument - атрибут для совершения действий над данными
	*/
	public function fetchAll( int $fetch_style = Lerma :: FETCH_NUM, $fetch_argument = null ): array
	{
		if ( $this -> _fetch[$fetch_style]['all'] ?? null )
		{
			return $this -> {$this -> _fetch[$fetch_style]['all']}( $fetch_style, $fetch_argument );
		}
		
		throw new Error( 'unrecognized key name in fetch_style argument' );
	}
	
	/*
		-
	*/
	protected function fetchall_num( int $fetch_style, $fetch_argument )
	{
		return $this -> drivers[$this -> config -> default] -> fetchAll( Lerma :: FETCH_NUM );
	}
	
	/*
		-
	*/
	protected function fetchall_assoc( int $fetch_style, $fetch_argument )
	{
		return $this -> drivers[$this -> config -> default] -> fetchAll( Lerma :: FETCH_ASSOC );
	}
	
	/*
		-
	*/
	protected function fetchall_field( int $fetch_style, $fetch_argument )
	{
		$info = $this -> drivers[$this -> config -> default] -> fetchAll( Lerma :: FETCH_FIELD );
		
		if ( is_null ( $fetch_argument ) )
		{
			return $info;
		}
		
		return array_column ( $info, $fetch_argument );
	}
	
	/*
		-
	*/
	protected function fetchall_obj( int $fetch_style, $fetch_argument )
	{
		$all = [];
		
		while ( !is_null ( $res = $this -> fetch( $fetch_style, $fetch_argument ) ) ) 
		{ 
			$all[] = $res; 
		}

		return $all;
	}
	
	/*
		-
	*/
	protected function fetchall_key_pair( int $fetch_style, $fetch_argument )
	{
		if ( $this -> countColumn() !== 2 )
		{
			throw new Error( 'Требуется выбрать только две колонки' );
		}
		
		$all = [];
		
		while ( $num = $this -> drivers[$this -> config -> default] -> fetch( Lerma :: FETCH_NUM ) )
		{
			if ( $fetch_style === ( Lerma :: FETCH_KEY_PAIR | Lerma :: FETCH_NAMED ) && isset ( $all[$num[0]] ) )
			{
				if ( is_array ( $all[$num[0]] ) )
				{
					$all[$num[0]][] = $num[1];
				}
				else
				{
					$all[$num[0]] = [ $all[$num[0]], $num[1] ];
				}
			}
			else
			{
				$all[$num[0]] = $num[1];
			}
		}
		
		return $all;
	}
	
	/*
		-
	*/
	protected function fetchall_unique( int $fetch_style, string $fetch_argument )
	{
		if ( $this -> countColumn() < 2 )
		{
			throw new Error( 'Допустимое кол - во выбраных колонок не менее двух' );
		}
		
		$all = [];
		
		foreach ( $this -> drivers[$this -> config -> default] -> fetchAll( Lerma :: FETCH_ASSOC ) AS $items )
		{
			if ( ( Lerma :: FETCH_CLASSTYPE | Lerma :: FETCH_UNIQUE ) === $fetch_style )
			{
				$class = array_shift ( $items );
				
				$RefClass = ( $c = new \ReflectionClass( $class ) ) -> newInstanceWithoutConstructor();
				
				foreach ( $items AS $name => $item )
				{
					$RefClass -> $name = $item;
				}
				
				$RefClass -> __construct();
				
				$all[( $fetch_argument ? $c -> getShortName() : $class )] = $RefClass;
			}
			else
			{
				$all[array_shift ( $items )] = $items;
			}
		}
		
		return $all;
	}
	
	/*
		-
	*/
	protected function fetchall_group( int $fetch_style, $fetch_argument )
	{
		if ( $this -> countColumn() < 2 )
		{
			throw new Error( 'Допустимое кол - во выбраных колонок не менее двух' );
		}
		
		$all = [];
		
		foreach ( $this -> drivers[$this -> config -> default] -> fetchAll( Lerma :: FETCH_ASSOC ) AS $s )
		{
			$all[array_shift ( $s )][] = $s;
		}
		
		return $all;
	}
	
	/*
		-
	*/
	protected function fetchall_group_column( int $fetch_style, $fetch_argument )
	{
		if ( $this -> drivers[$this -> config -> default] -> countColumn() != 2 )
		{
			throw new Error( 'Требуется выбрать только две колонки' );
		}
		
		$all = [];
		
		foreach ( $this -> drivers[$this -> config -> default] -> fetchAll( Lerma :: FETCH_NUM ) AS $s )
		{
			$all[array_shift ( $s )][] = $s[0];
		}
		
		return $all;
	}
	
	/*
		-
	*/
	protected function fetch_num( int $fetch_style, $fetch_argument )
	{
		return $this -> drivers[$this -> config -> default] -> fetch( Lerma :: FETCH_NUM );
	}

	/*
		-
	*/
	protected function fetch_assoc( int $fetch_style, $fetch_argument )
	{
		return $this -> drivers[$this -> config -> default] -> fetch( Lerma :: FETCH_ASSOC );
	}
	
	/*
		-
	*/
	protected function fetch_field( int $fetch_style, string $fetch_argument )
	{
		if ( array_key_exists ( 0, ( $info = $this -> drivers[$this -> config -> default] -> fetch( Lerma :: FETCH_FIELD ) ) ) )
		{
			return null;
		}
		
		return $info[$fetch_argument] ?? $info;
	}
	
	/*
		-
	*/
	protected function fetch_obj( int $fetch_style, $fetch_argument )
	{
		return $this -> drivers[$this -> config -> default] -> fetch( Lerma :: FETCH_OBJ );
	}
	
	/*
		-
	*/
	protected function fetch_bind( int $fetch_style, $fetch_argument )
	{
		if ( !$this -> bind() -> fetch( Lerma :: FETCH_BIND ) ) #&&&&&&&&&&&&&&& bind()
		{
			$this -> config -> fast ?? self::instance() -> drivers[$this -> config -> default] -> isError( $this -> statement );

			return $this -> bind_result = false;
		}

		if ( $fetch_style == ( Lerma :: FETCH_BIND | Lerma :: FETCH_COLUMN ) )
		{
			if ( $this -> countColumn() == 1 )
			{
				throw new Error( 'Требуется выбрать только одну колонку' );
			}

			return $this -> bind_result[0];
		}

		return $this -> bind_result;
	}

	/*
		-
	*/
	protected function fetch_column( int $fetch_style, $fetch_argument )
	{
		return $this -> drivers[$this -> config -> default] -> fetch( Lerma :: FETCH_NUM )[0];
	}

	/*
		-
	*/
	protected function fetch_key_pair( int $fetch_style, $fetch_argument ) # column1 => column2
	{
		if ( is_null ( $items = $this -> drivers[$this -> config -> default] -> fetch( Lerma :: FETCH_NUM ) ) )
		{
			return null;
		}
		
		return [ $items[0] => $items[1] ];
	}

	/*
		-
	*/
	protected function fetch_func( int $fetch_style, callable $fetch_argument )
	{
		if ( ( $items = $this -> drivers[$this -> config -> default] -> fetch( Lerma :: FETCH_NUM ) ) === null )
		{
			return null;
		}
		
		return $fetch_argument( ...$items );
	}

	/*
		-
	*/
	protected function fetch_class( int $fetch_style, string $fetch_argument )
	{
		/* if ( !is_string ( $fetch_argument ) && Lerma :: FETCH_CLASS === $fetch_style )
		{
			throw new Error( 'Invalid argument2 is not type string' );
		}
		elseif ( Lerma :: FETCH_CLASSTYPE === $fetch_style && $this -> driver -> countColumn() < 2 )
		{
			throw new Error( 'Допустимое кол - во выбраных колонок: не менее двух' );
		}

		if ( ( $items = $this -> driver -> fetch( Lerma :: FETCH_ASSOC ) ) === null )
		{
			return null;
		}

		$RefClass = ( new \ReflectionClass( ( Lerma :: FETCH_CLASSTYPE === $fetch_style ?
			array_shift ( $items ) : $fetch_argument ) ) ) -> newInstanceWithoutConstructor();

		foreach ( $items AS $name => $item )
		{
			$RefClass -> $name = $item;
		}

		$RefClass -> __construct(); #&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&

		return $RefClass; */
		throw new Error( 'Test...' );
	}
}