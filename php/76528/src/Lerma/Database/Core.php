<?php

namespace Aero\Database;

use Aero;
use Aero\Supports\Lerma;
use Aero\Database\InterfaceDriver;
use Throwable;
use Error;

class Core extends ComponentFetch
{
	# Загрузка ядра, последующее берем готовые данные
	protected static $instance;
	
	protected $config;	# Object configurations
	
	protected
		$drivers = [],	# Объект подключенного драйвера
		$matches;		# Placeholders
	
	public $query, $statement;
	/*
		- Запуск ядра
	*/
	protected static function instance(): Lerma
	{
		try
		{
			return static :: $instance ?: ( static :: $instance = ( new static ) -> IDrivers( 'conf.php' ) );
		}
		catch ( Throwable $t )
		{
			( new static ) -> exceptionIDriver( $t );
		}
	}
	
	/*
		- Моем посуду
	*/
	protected function dead(): Lerma
	{
		if ( $this -> statement !== null || $this -> query !== null )
		{
			$this -> drivers[$this -> config -> default] -> close();
		}

		return $this;
	}

	/*
		- Загрузка драйвера манипулирования БД
	*/
	protected function IDrivers( string $config ): Lerma
	{
		$driver = 'Aero\\Database\\ext\\' . ( $this -> config = json_decode ( include $config ) ) -> default;
		
		$this -> drivers[$this -> config -> default] = new $driver( $this, (array)$this -> config -> connect -> {$this -> config -> default} );
		
		if ( !is_a ( $this -> drivers[$this -> config -> default], InterfaceDriver::class ) )
		{
			throw new Error( 'Загруженный драйвер `' . $this -> config -> default . '` не соответсвует требованиям интерфейсу InterfaceDriver' );
		}
		
		return $this;
	}
	
	/*
		- Посылаем данные в астрал
	*/
	protected function execute( array $executes )
	{
		if ( is_null ( $this -> statement ) )
		{
			throw new Error( 'This is not statement' );
		}
		
		$executes = ( is_array ( $executes[0] ) ? $executes : [ $executes ] );
		
		$this -> drivers[$this -> config -> default] -> binding( ...$executes );
		
		$this -> config -> fast ?: $this -> drivers[$this -> config -> default] -> isError( $this -> statement );
		
		return $this -> drivers[$this -> config -> default] -> countColumn() ?: $this;
	}
	
	/*
		- Поиск ':', замена placeholders на '?'
	*/
	protected function replaceHolders( $sql ): string
	{
		if ( strpos ( $sql, ':' ) !== false )
		{
			preg_match_all ( '/(\?|:[a-zA-Z]{1,})/', $sql, $matches );
			
			$sql = strtr ( $sql, array_fill_keys ( $this -> matches = $matches[1], '?' ) );
		}
		else
		{
			$this -> matches = [];
		}
		
		return $sql;
	}
	
	/*
		- Реформирование данных в массиве по найденным placeholders
	*/
	public function executeHolders( array $execute ): array
	{
		$new = [];
		
		foreach ( $this -> matches as $plaseholder )
		{
			if ( $plaseholder === '?' )
			{
				$new[] = array_shift ( $execute );
			}
			else
			{
				if ( isset ( $new[$plaseholder] ) )
				{
					$new[] = $new[$plaseholder];
				}
				else
				{			
					$new[$plaseholder] = $execute[$plaseholder] ?: null;
					
					unset ( $execute[$plaseholder] );
				}
			}
		}
		
		return $new ?: $execute;
	}
	
	public function rowCount(): int
	{
		return $this -> drivers[$this -> config -> default] -> rowCount();
	}
}
