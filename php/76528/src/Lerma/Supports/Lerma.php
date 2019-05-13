<?php

/*
	@ Author: MouseZver
	@ Email: mouse-zver@xaker.ru
	@ url-source: http://github.com/MouseZver/Lerma
	@ php-version 7.2
*/

namespace Aero\Supports;

use Aero\Database\Core;
use Throwable;
use Error;

final class Lerma extends Core
{
	const
		FETCH_NUM		= 1,
		FETCH_ASSOC		= 2,
		FETCH_OBJ		= 4,
		FETCH_BIND		= 663,
		FETCH_COLUMN	= 265,
		FETCH_KEY_PAIR	= 307,
		FETCH_NAMED		= 173,
		FETCH_UNIQUE	= 333,
		FETCH_GROUP		= 428,
		FETCH_FUNC		= 586,
		FETCH_CLASS		= 977,
		FETCH_CLASSTYPE	= 473,
		FETCH_FIELD		= 343;
	
	/*
		- tool
		- Определение подготовленного запроса на форматирование строки
	*/
	public static function prepare( ...$args ): Lerma
	{
		try
		{
			if ( empty ( $args[1] ) )
			{
				throw new Error( 'Данные пусты. Используйте функцию query' );
			}
			
			$sql = static :: instance() -> dead() -> replaceHolders( is_string ( $args[0] ) ? $args[0] : sprintf ( ...$args[0] ) );
			
			if ( strpbrk ( $sql, '?:' ) === false )
			{
				throw new Error( 'Метки параметров запроса отсутствуют. Используйте функцию query' );
			}
			
			static :: instance() -> drivers[static :: instance() -> config -> default] -> prepare( $sql );
			
			static :: instance() -> config -> fast ?: static :: instance() -> drivers[static :: instance() -> config -> default] -> isError();
			
			static :: instance() -> execute( $args[1] );
		}
		catch ( Throwable $t )
		{
			static :: instance() -> drivers[static :: instance() -> config -> default] -> rollBack();

			static :: instance() -> exceptionIDriver( $t );
		}
		
		return static :: instance();
	}
	
	/*
		- tool
		- Определение запроса на форматирование строки
	*/
	public static function query( $sql ): Lerma
	{
		static :: instance() -> dead() -> drivers[static :: instance() -> config -> default] -> query( is_string ( $sql ) ? $sql : sprintf ( ...$sql ) );
		
		static :: instance() -> config -> fast ?: static :: instance() -> drivers[static :: instance() -> config -> default] -> isError();

		return static :: instance();
	}
	
	/*
		- tool
		- Смена драйвера
	*/
	/* public static function change( string $name )
	{
		if ( $name === 'driver' || is_null ( $this -> config -> $name ) )
		{
			throw new Error( 'Отсутствуют параметры драйвера ' . $name );
		}
		
		if ( is_null ( self :: $instance ) )
		{
			throw new Error( 'Данная функция не должна стартовать раньше чем запуск ядра, либо смените имя первичного драйвера в conf.' );
		}
		
		if ( !array_key_exists ( self :: $instance -> dead() -> config -> driver = $name, $instance -> drivers ) )
		{
			return self :: $instance -> IDrivers( self :: $instance -> config );
		}

		return self :: $instance;
	} */
	
	protected function exceptionIDriver( Throwable $t )
	{
		exit ( '<pre>IDriver: ' . $t -> getMessage() . '<br>File: ' . $t -> getFile() . ' line: ' . $t -> getLine() . PHP_EOL . $t -> getTraceAsString() . '</pre>' );
	}
}
